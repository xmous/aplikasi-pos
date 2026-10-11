<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockController extends Controller
{
    private const MOVEMENT_TYPES = [
        'purchase' => 'Pembelian',
        'sale' => 'Penjualan',
        'adjustment' => 'Penyesuaian',
        'stock_opname' => 'Stok Opname',
        'purchase_return' => 'Retur Pembelian',
        'sale_return' => 'Retur Penjualan',
    ];

    public function movements(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'warehouse_id' => (string) $request->query('warehouse_id', ''),
            'type' => (string) $request->query('type', 'all'),
            'direction' => (string) $request->query('direction', 'all'),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'per_page' => (int) $request->query('per_page', 30),
        ];

        if ($filters['type'] !== 'all' && ! array_key_exists($filters['type'], self::MOVEMENT_TYPES)) {
            $filters['type'] = 'all';
        }

        if (! in_array($filters['direction'], ['all', 'in', 'out'], true)) {
            $filters['direction'] = 'all';
        }

        if (! in_array($filters['per_page'], [20, 30, 50], true)) {
            $filters['per_page'] = 30;
        }

        $query = StockMovement::query()
            ->with(['product.unit', 'warehouse', 'batch', 'user']);

        if ($filters['q'] !== '') {
            $terms = preg_split('/\s+/', $filters['q']) ?: [];

            foreach (array_filter($terms) as $term) {
                $like = '%'.$term.'%';

                $query->where(function (Builder $builder) use ($like): void {
                    $builder
                        ->where('notes', 'like', $like)
                        ->orWhereHas('product', function (Builder $product) use ($like): void {
                            $product
                                ->where('name', 'like', $like)
                                ->orWhere('sku', 'like', $like)
                                ->orWhere('barcode', 'like', $like);
                        })
                        ->orWhereHas('warehouse', function (Builder $warehouse) use ($like): void {
                            $warehouse
                                ->where('name', 'like', $like)
                                ->orWhere('code', 'like', $like);
                        })
                        ->orWhereHas('batch', fn (Builder $batch) => $batch->where('batch_number', 'like', $like))
                        ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $like));
                });
            }
        }

        $query
            ->when(
                $filters['warehouse_id'] !== '',
                fn (Builder $builder) => $builder->where('warehouse_id', (int) $filters['warehouse_id'])
            )
            ->when(
                $filters['type'] !== 'all',
                fn (Builder $builder) => $builder->where('type', $filters['type'])
            )
            ->when(
                $filters['direction'] !== 'all',
                fn (Builder $builder) => $builder->where('direction', $filters['direction'])
            )
            ->when(
                $filters['date_from'] !== '',
                fn (Builder $builder) => $builder->whereDate('created_at', '>=', $filters['date_from'])
            )
            ->when(
                $filters['date_to'] !== '',
                fn (Builder $builder) => $builder->whereDate('created_at', '<=', $filters['date_to'])
            );

        $todayQuery = StockMovement::query()->whereDate('created_at', today());

        $stats = [
            'today_count' => (clone $todayQuery)->count(),
            'today_in' => (float) (clone $todayQuery)->where('direction', 'in')->sum('quantity'),
            'today_out' => (float) (clone $todayQuery)->where('direction', 'out')->sum('quantity'),
            'adjustments' => StockMovement::query()
                ->where('type', 'adjustment')
                ->whereDate('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        return view('stocks.movements', [
            'movements' => $query
                ->latest('created_at')
                ->paginate($filters['per_page'])
                ->withQueryString(),
            'warehouses' => Warehouse::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'movementTypes' => self::MOVEMENT_TYPES,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function batches(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'warehouse_id' => (string) $request->query('warehouse_id', ''),
            'status' => (string) $request->query('status', 'all'),
            'per_page' => (int) $request->query('per_page', 30),
        ];

        if (! in_array($filters['status'], ['all', 'expired', 'expiring', 'active', 'no_expiry'], true)) {
            $filters['status'] = 'all';
        }

        if (! in_array($filters['per_page'], [20, 30, 50], true)) {
            $filters['per_page'] = 30;
        }

        $query = ProductBatch::query()
            ->with(['product.unit', 'warehouse'])
            ->where('quantity', '>', 0);

        if ($filters['q'] !== '') {
            $terms = preg_split('/\s+/', $filters['q']) ?: [];

            foreach (array_filter($terms) as $term) {
                $like = '%'.$term.'%';

                $query->where(function (Builder $builder) use ($like): void {
                    $builder
                        ->where('batch_number', 'like', $like)
                        ->orWhereHas('product', function (Builder $product) use ($like): void {
                            $product
                                ->where('name', 'like', $like)
                                ->orWhere('sku', 'like', $like);
                        })
                        ->orWhereHas('warehouse', function (Builder $warehouse) use ($like): void {
                            $warehouse
                                ->where('name', 'like', $like)
                                ->orWhere('code', 'like', $like);
                        });
                });
            }
        }

        $query
            ->when(
                $filters['warehouse_id'] !== '',
                fn (Builder $builder) => $builder->where('warehouse_id', (int) $filters['warehouse_id'])
            )
            ->when(
                $filters['status'] === 'expired',
                fn (Builder $builder) => $builder
                    ->whereNotNull('expired_at')
                    ->whereDate('expired_at', '<', today())
            )
            ->when(
                $filters['status'] === 'expiring',
                fn (Builder $builder) => $builder
                    ->whereBetween('expired_at', [today(), today()->addDays(30)])
            )
            ->when(
                $filters['status'] === 'active',
                fn (Builder $builder) => $builder
                    ->whereNotNull('expired_at')
                    ->whereDate('expired_at', '>', today()->addDays(30))
            )
            ->when(
                $filters['status'] === 'no_expiry',
                fn (Builder $builder) => $builder->whereNull('expired_at')
            );

        $positiveBatches = ProductBatch::query()->where('quantity', '>', 0);

        $stats = [
            'total' => (clone $positiveBatches)->count(),
            'expired' => (clone $positiveBatches)
                ->whereNotNull('expired_at')
                ->whereDate('expired_at', '<', today())
                ->count(),
            'expiring' => (clone $positiveBatches)
                ->whereBetween('expired_at', [today(), today()->addDays(30)])
                ->count(),
            'sellable_quantity' => (float) (clone $positiveBatches)
                ->where(function (Builder $builder): void {
                    $builder
                        ->whereNull('expired_at')
                        ->orWhereDate('expired_at', '>=', today());
                })
                ->sum('quantity'),
        ];

        return view('stocks.batches', [
            'batches' => $query
                ->orderByRaw('CASE WHEN expired_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('expired_at')
                ->orderBy('id')
                ->paginate($filters['per_page'])
                ->withQueryString(),
            'warehouses' => Warehouse::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function adjustment(): View
    {
        return view('stocks.adjustment', [
            'products' => Product::query()
                ->with(['unit', 'stocks'])
                ->where('is_active', true)
                ->where('track_stock', true)
                ->orderBy('name')
                ->get(),
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeAdjustment(Request $request, StockService $service): RedirectResponse
    {
        $data = $request->validate(
            [
                'warehouse_id' => ['required', 'exists:warehouses,id'],
                'product_id' => ['required', 'exists:products,id'],
                'direction' => ['required', 'in:in,out'],
                'quantity' => ['required', 'numeric', 'gt:0'],
                'batch_number' => ['nullable', 'string', 'max:100'],
                'expired_at' => ['nullable', 'date'],
                'reason' => ['required', 'string', 'min:5', 'max:1000'],
            ],
            [
                'quantity.gt' => 'Jumlah penyesuaian harus lebih besar dari nol.',
                'reason.min' => 'Alasan penyesuaian minimal 5 karakter.',
            ]
        );

        $product = Product::query()
            ->whereKey($data['product_id'])
            ->where('is_active', true)
            ->where('track_stock', true)
            ->firstOrFail();

        $warehouse = Warehouse::query()
            ->whereKey($data['warehouse_id'])
            ->where('is_active', true)
            ->firstOrFail();

        if ($product->has_expiry && $data['direction'] === 'in') {
            if (empty($data['batch_number'])) {
                throw ValidationException::withMessages([
                    'batch_number' => 'Nomor batch wajib diisi untuk barang berkedaluwarsa.',
                ]);
            }

            if (empty($data['expired_at'])) {
                throw ValidationException::withMessages([
                    'expired_at' => 'Tanggal kedaluwarsa wajib diisi untuk barang ini.',
                ]);
            }
        }

        if ($data['direction'] === 'in') {
            $service->increase(
                $warehouse->id,
                $product,
                (float) $data['quantity'],
                'adjustment',
                null,
                $data['reason'],
                auth()->id(),
                $data['batch_number'] ?? null,
                $data['expired_at'] ?? null
            );
        } else {
            $service->decreaseFefo(
                $warehouse->id,
                $product,
                (float) $data['quantity'],
                'adjustment',
                null,
                $data['reason'],
                auth()->id(),
                true
            );
        }

        AuditService::log('stock.adjusted', $product, [
            ...$data,
            'warehouse_name' => $warehouse->name,
        ]);

        return redirect()
            ->route('stocks.movements', [
                'q' => $product->sku,
                'warehouse_id' => $warehouse->id,
            ])
            ->with('success', 'Penyesuaian stok berhasil diposting.');
    }
}
