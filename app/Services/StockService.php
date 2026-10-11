<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function increase(
        int $warehouseId,
        Product $product,
        float $quantity,
        string $type,
        ?Model $reference = null,
        ?string $notes = null,
        ?int $userId = null,
        ?string $batchNumber = null,
        mixed $expiredAt = null,
        ?int $purchaseItemId = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah stok harus lebih dari nol.']);
        }

        return DB::transaction(function () use (
            $warehouseId, $product, $quantity, $type, $reference, $notes,
            $userId, $batchNumber, $expiredAt, $purchaseItemId
        ) {
            $stock = $this->lockedStock($warehouseId, $product->id);
            $before = (float) $stock->quantity;
            $after = $before + $quantity;

            $normalizedBatch = trim((string) ($batchNumber ?: 'GENERAL'));
            $batch = ProductBatch::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $product->id)
                ->where('batch_number', $normalizedBatch)
                ->when($expiredAt, fn ($q) => $q->whereDate('expired_at', $expiredAt))
                ->when(! $expiredAt, fn ($q) => $q->whereNull('expired_at'))
                ->lockForUpdate()
                ->first();

            if (! $batch) {
                $batch = ProductBatch::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $product->id,
                    'purchase_item_id' => $purchaseItemId,
                    'batch_number' => $normalizedBatch,
                    'expired_at' => $expiredAt,
                    'quantity' => 0,
                ]);
                $batch = ProductBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            }

            $batch->update([
                'quantity' => (float) $batch->quantity + $quantity,
                'purchase_item_id' => $batch->purchase_item_id ?: $purchaseItemId,
            ]);
            $stock->update(['quantity' => $after]);

            return $this->movement(
                $warehouseId, $product, $batch, $quantity, 'in', $type,
                $before, $after, $reference, $notes, $userId
            );
        }, 3);
    }

    /**
     * Mengurangi stok dengan metode FEFO: batch yang paling dekat kedaluwarsa dipakai lebih dahulu.
     * Batch yang sudah kedaluwarsa tidak dianggap stok layak jual.
     */
    public function decreaseFefo(
        int $warehouseId,
        Product $product,
        float $quantity,
        string $type,
        ?Model $reference = null,
        ?string $notes = null,
        ?int $userId = null,
        bool $allowExpired = false,
    ): array {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah stok harus lebih dari nol.']);
        }

        return DB::transaction(function () use (
            $warehouseId, $product, $quantity, $type, $reference, $notes, $userId, $allowExpired
        ) {
            $stock = $this->lockedStock($warehouseId, $product->id);
            $aggregateBefore = (float) $stock->quantity;

            if ($aggregateBefore < $quantity) {
                throw ValidationException::withMessages([
                    'stock' => "Stok {$product->name} tidak mencukupi. Tersedia {$aggregateBefore}.",
                ]);
            }

            $this->synchronizeGeneralBatch($warehouseId, $product->id, $aggregateBefore);

            $batchQuery = ProductBatch::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $product->id)
                ->where('quantity', '>', 0);

            if (! $allowExpired) {
                $batchQuery->where(function ($query) {
                    $query->whereNull('expired_at')->orWhereDate('expired_at', '>=', today());
                });
            }

            $batches = $batchQuery
                ->orderByRaw('CASE WHEN expired_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('expired_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $sellable = (float) $batches->sum('quantity');
            if ($sellable < $quantity) {
                throw ValidationException::withMessages([
                    'stock' => "Stok layak jual {$product->name} hanya {$sellable}. Periksa batch kedaluwarsa.",
                ]);
            }

            $remaining = $quantity;
            $runningBefore = $aggregateBefore;
            $movements = [];

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $taken = min($remaining, (float) $batch->quantity);
                $batch->update(['quantity' => (float) $batch->quantity - $taken]);

                $runningAfter = $runningBefore - $taken;
                $movements[] = $this->movement(
                    $warehouseId, $product, $batch, $taken, 'out', $type,
                    $runningBefore, $runningAfter, $reference, $notes, $userId
                );

                $runningBefore = $runningAfter;
                $remaining -= $taken;
            }

            $stock->update(['quantity' => $aggregateBefore - $quantity]);

            return $movements;
        }, 3);
    }

    public function sellableQuantity(int $warehouseId, int $productId): float
    {
        return (float) ProductBatch::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->where(fn ($q) => $q->whereNull('expired_at')->orWhereDate('expired_at', '>=', today()))
            ->sum('quantity');
    }

    private function lockedStock(int $warehouseId, int $productId): Stock
    {
        $stock = Stock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            $stock = Stock::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'quantity' => 0,
            ]);

            $stock = Stock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
        }

        return $stock;
    }

    private function synchronizeGeneralBatch(int $warehouseId, int $productId, float $aggregate): void
    {
        $batchTotal = (float) ProductBatch::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->sum('quantity');

        $difference = round($aggregate - $batchTotal, 3);
        if ($difference <= 0) {
            return;
        }

        $batch = ProductBatch::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('batch_number', 'GENERAL')
            ->whereNull('expired_at')
            ->lockForUpdate()
            ->first();

        if (! $batch) {
            ProductBatch::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'batch_number' => 'GENERAL',
                'quantity' => $difference,
            ]);
            return;
        }

        $batch->increment('quantity', $difference);
    }

    private function movement(
        int $warehouseId,
        Product $product,
        ProductBatch $batch,
        float $quantity,
        string $direction,
        string $type,
        float $before,
        float $after,
        ?Model $reference,
        ?string $notes,
        ?int $userId,
    ): StockMovement {
        return StockMovement::create([
            'warehouse_id' => $warehouseId,
            'product_id' => $product->id,
            'product_batch_id' => $batch->id,
            'created_by' => $userId,
            'type' => $type,
            'direction' => $direction,
            'quantity' => $quantity,
            'stock_before' => $before,
            'stock_after' => $after,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'notes' => $notes,
        ]);
    }
}
