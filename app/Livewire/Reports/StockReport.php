<?php

namespace App\Livewire\Reports;

use App\Models\ProductBatch;
use App\Models\ProductCategory;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class StockReport extends Component
{
    use WithPagination;

    public string $search = '';
    public string $warehouseId = '';
    public string $categoryId = '';
    public string $stockStatus = 'all';
    public bool $expiredOnly = false;
    public int $perPage = 20;

    public function mount(): void
    {
        Gate::authorize('reports.stocks');
    }

    public function updated(string $property): void
    {
        if ($property === 'perPage' && ! in_array($this->perPage, [15, 20, 50], true)) {
            $this->perPage = 20;
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->warehouseId = '';
        $this->categoryId = '';
        $this->stockStatus = 'all';
        $this->expiredOnly = false;
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('reports.stocks');

        $this->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'warehouseId' => ['nullable', 'integer', 'exists:warehouses,id'],
            'categoryId' => ['nullable', 'integer', 'exists:product_categories,id'],
            'stockStatus' => ['required', 'in:all,healthy,low,out'],
            'expiredOnly' => ['boolean'],
            'perPage' => ['integer', 'in:15,20,50'],
        ]);

        $query = $this->query();
        $summaryRows = (clone $query)
            ->with('product:id,average_cost,minimum_stock')
            ->addSelect($this->batchSelects())
            ->get();

        $inventoryValue = (float) $summaryRows->sum(
            fn (Stock $stock): float => (float) $stock->quantity
                * (float) $stock->product->average_cost
        );
        $expiredValue = (float) $summaryRows->sum(
            fn (Stock $stock): float => (float) $stock->expired_quantity
                * (float) $stock->product->average_cost
        );

        return view('livewire.reports.stock-report', [
            'stocks' => $query
                ->with([
                    'product.category',
                    'product.unit',
                    'product.conversionUnits.unit',
                    'warehouse',
                ])
                ->addSelect($this->batchSelects())
                ->orderBy('quantity')
                ->paginate($this->perPage),
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'categories' => ProductCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'summary' => [
                'rows' => $summaryRows->count(),
                'quantity' => (float) $summaryRows->sum('quantity'),
                'inventory_value' => $inventoryValue,
                'expired_value' => $expiredValue,
                'sellable_value' => max(0, $inventoryValue - $expiredValue),
                'low_count' => $summaryRows->filter(
                    fn (Stock $stock): bool => (float) $stock->quantity > 0
                        && (float) $stock->quantity <= (float) $stock->product->minimum_stock
                )->count(),
                'out_count' => $summaryRows->filter(
                    fn (Stock $stock): bool => (float) $stock->quantity <= 0
                )->count(),
                'expired_rows' => $summaryRows->filter(
                    fn (Stock $stock): bool => (float) $stock->expired_quantity > 0
                )->count(),
            ],
        ]);
    }

    private function query(): Builder
    {
        return Stock::query()
            ->when(
                $this->warehouseId !== '',
                fn (Builder $query) => $query->where('warehouse_id', $this->warehouseId)
            )
            ->when(
                $this->categoryId !== '',
                fn (Builder $query) => $query->whereHas(
                    'product',
                    fn (Builder $product) => $product->where(
                        'product_category_id',
                        $this->categoryId
                    )
                )
            )
            ->when($this->search !== '', function (Builder $query): void {
                $terms = preg_split('/\s+/', trim($this->search)) ?: [];

                foreach (array_filter($terms) as $term) {
                    $like = '%'.$term.'%';
                    $query->whereHas('product', function (Builder $product) use ($like): void {
                        $product->where(function (Builder $search) use ($like): void {
                            $search
                                ->where('name', 'like', $like)
                                ->orWhere('sku', 'like', $like)
                                ->orWhere('barcode', 'like', $like)
                                ->orWhere('rack_location', 'like', $like);
                        });
                    });
                }
            })
            ->when(
                $this->stockStatus === 'out',
                fn (Builder $query) => $query->where('quantity', '<=', 0)
            )
            ->when(
                $this->stockStatus === 'low',
                fn (Builder $query) => $query
                    ->where('quantity', '>', 0)
                    ->whereRaw(
                        'stocks.quantity <= (select minimum_stock from products where products.id = stocks.product_id)'
                    )
            )
            ->when(
                $this->stockStatus === 'healthy',
                fn (Builder $query) => $query->whereRaw(
                    'stocks.quantity > (select minimum_stock from products where products.id = stocks.product_id)'
                )
            )
            ->when($this->expiredOnly, function (Builder $query): void {
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

    private function batchSelects(): array
    {
        return [
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
                ->whereBetween('product_batches.expired_at', [
                    today(),
                    today()->addDays(30),
                ]),
        ];
    }
}
