<?php

namespace App\Exports;

use App\Models\ProductBatch;
use App\Models\Stock;
use App\Support\ExcelCell;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StocksExport implements FromCollection, WithHeadings
{
    public function __construct(private array $filters = []) {}

    public function collection(): Collection
    {
        return $this->query()
            ->with([
                'product.category',
                'product.unit',
                'product.conversionUnits.unit',
                'warehouse',
            ])
            ->addSelect([
                'stocks.*',
                'expired_quantity' => ProductBatch::query()
                    ->selectRaw('COALESCE(SUM(product_batches.quantity),0)')
                    ->whereColumn('product_batches.product_id', 'stocks.product_id')
                    ->whereColumn('product_batches.warehouse_id', 'stocks.warehouse_id')
                    ->where('product_batches.quantity', '>', 0)
                    ->whereNotNull('product_batches.expired_at')
                    ->whereDate('product_batches.expired_at', '<', today()),
                'expiring_quantity' => ProductBatch::query()
                    ->selectRaw('COALESCE(SUM(product_batches.quantity),0)')
                    ->whereColumn('product_batches.product_id', 'stocks.product_id')
                    ->whereColumn('product_batches.warehouse_id', 'stocks.warehouse_id')
                    ->where('product_batches.quantity', '>', 0)
                    ->whereBetween('product_batches.expired_at', [today(), today()->addDays(30)]),
            ])
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->get()
            ->map(fn (Stock $stock) => ExcelCell::row([
                $stock->product->sku,
                $stock->product->name,
                $stock->product->category?->name,
                $stock->warehouse->name,
                (float) $stock->quantity,
                $stock->product->unit?->symbol,
                $stock->product->conversionUnits->where('is_active', true)
                    ->map(fn ($conversion) => number_format(
                        (float) $stock->quantity / (float) $conversion->conversion_factor,
                        3,
                        '.',
                        ''
                    ).' '.$conversion->unit->symbol)
                    ->join('; '),
                (float) $stock->product->minimum_stock,
                (float) ($stock->expired_quantity ?? 0),
                (float) ($stock->expiring_quantity ?? 0),
                (float) $stock->quantity * (float) $stock->product->average_cost,
                (float) ($stock->expired_quantity ?? 0) * (float) $stock->product->average_cost,
                $this->status($stock),
            ]));
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Barang',
            'Kategori',
            'Gudang',
            'Stok Dasar',
            'Satuan Dasar',
            'Ekuivalen',
            'Stok Minimum',
            'Qty Kedaluwarsa',
            'Qty ≤ 30 Hari',
            'Nilai Modal',
            'Nilai Kedaluwarsa',
            'Status Stok',
        ];
    }

    private function query(): Builder
    {
        return Stock::query()
            ->when($this->filters['warehouse_id'] ?? null, fn (Builder $query, int|string $value) => $query->where('warehouse_id', $value))
            ->when($this->filters['category_id'] ?? null, fn (Builder $query, int|string $value) => $query->whereHas('product', fn (Builder $product) => $product->where('product_category_id', $value)))
            ->when($this->filters['search'] ?? null, function (Builder $query, string $value): void {
                $like = '%'.$value.'%';
                $query->whereHas('product', fn (Builder $product) => $product->where(fn (Builder $search) => $search->where('name', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('barcode', 'like', $like)));
            })
            ->when(($this->filters['stock_status'] ?? 'all') === 'out', fn (Builder $query) => $query->where('quantity', '<=', 0))
            ->when(($this->filters['stock_status'] ?? 'all') === 'low', fn (Builder $query) => $query->where('quantity', '>', 0)->whereRaw('stocks.quantity <= (select minimum_stock from products where products.id = stocks.product_id)'))
            ->when(($this->filters['stock_status'] ?? 'all') === 'healthy', fn (Builder $query) => $query->whereRaw('stocks.quantity > (select minimum_stock from products where products.id = stocks.product_id)'))
            ->when($this->filters['expired_only'] ?? false, function (Builder $query): void {
                $query->whereExists(function ($sub): void {
                    $sub->selectRaw('1')
                        ->from('product_batches')
                        ->whereColumn('product_batches.product_id', 'stocks.product_id')
                        ->whereColumn('product_batches.warehouse_id', 'stocks.warehouse_id')
                        ->where('product_batches.quantity', '>', 0)
                        ->whereNotNull('product_batches.expired_at')
                        ->whereDate('product_batches.expired_at', '<', today());
                });
            });
    }

    private function status(Stock $stock): string
    {
        if ((float) $stock->quantity <= 0) {
            return 'Habis';
        }

        if ((float) $stock->quantity <= (float) $stock->product->minimum_stock) {
            return 'Menipis';
        }

        return 'Aman';
    }
}
