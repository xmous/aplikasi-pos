<?php

namespace App\Livewire\Reports;

use App\Models\CashierShift;
use App\Models\Expense;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class OperationsReport extends Component
{
    use WithPagination;

    public string $tab = 'purchases';
    public string $from;
    public string $to;
    public string $search = '';
    public int $perPage = 20;

    private const TABS = [
        'purchases',
        'movements',
        'expiry',
        'shifts',
        'expenses',
        'sale_returns',
        'purchase_returns',
        'opnames',
    ];

    public function mount(): void
    {
        Gate::authorize('reports.operations');
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function updated(string $property): void
    {
        if ($property === 'perPage' && ! in_array($this->perPage, [15, 20, 50], true)) {
            $this->perPage = 20;
        }

        $this->resetPage();
    }

    public function selectTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->tab = $tab;
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('reports.operations');

        $this->validate([
            'tab' => ['required', Rule::in(self::TABS)],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:100'],
            'perPage' => ['integer', 'in:15,20,50'],
        ]);

        return view('livewire.reports.operations-report', [
            'rows' => $this->rows(),
            'summary' => $this->summary(),
            'tabs' => [
                'purchases' => ['label' => 'Pembelian', 'icon' => 'fa-cart-shopping'],
                'movements' => ['label' => 'Mutasi', 'icon' => 'fa-arrow-right-arrow-left'],
                'expiry' => ['label' => 'Kedaluwarsa', 'icon' => 'fa-calendar-xmark'],
                'shifts' => ['label' => 'Shift', 'icon' => 'fa-cash-register'],
                'expenses' => ['label' => 'Pengeluaran', 'icon' => 'fa-money-bill-transfer'],
                'sale_returns' => ['label' => 'Retur Jual', 'icon' => 'fa-arrow-rotate-left'],
                'purchase_returns' => ['label' => 'Retur Beli', 'icon' => 'fa-rotate-left'],
                'opnames' => ['label' => 'Opname', 'icon' => 'fa-clipboard-check'],
            ],
        ]);
    }

    private function rows()
    {
        $term = '%'.$this->search.'%';

        return match ($this->tab) {
            'purchases' => Purchase::query()
                ->with(['supplier', 'warehouse'])
                ->withSum('returns', 'total')
                ->whereBetween('purchase_date', [$this->from, $this->to])
                ->when($this->search !== '', function (Builder $query) use ($term): void {
                    $query->where(function (Builder $search) use ($term): void {
                        $search
                            ->where('number', 'like', $term)
                            ->orWhere('supplier_invoice', 'like', $term)
                            ->orWhereHas('supplier', fn (Builder $supplier) => $supplier->where('name', 'like', $term))
                            ->orWhereHas('warehouse', fn (Builder $warehouse) => $warehouse->where('name', 'like', $term));
                    });
                })
                ->latest('purchase_date')
                ->paginate($this->perPage),

            'movements' => StockMovement::query()
                ->with(['product.unit', 'warehouse', 'batch', 'user'])
                ->whereBetween('created_at', [
                    $this->from.' 00:00:00',
                    $this->to.' 23:59:59',
                ])
                ->when($this->search !== '', function (Builder $query) use ($term): void {
                    $query->where(function (Builder $search) use ($term): void {
                        $search
                            ->where('notes', 'like', $term)
                            ->orWhereHas('product', function (Builder $product) use ($term): void {
                                $product->where('name', 'like', $term)->orWhere('sku', 'like', $term);
                            })
                            ->orWhereHas('warehouse', fn (Builder $warehouse) => $warehouse->where('name', 'like', $term))
                            ->orWhereHas('batch', fn (Builder $batch) => $batch->where('batch_number', 'like', $term));
                    });
                })
                ->latest()
                ->paginate($this->perPage),

            'expiry' => ProductBatch::query()
                ->with(['product.unit', 'warehouse'])
                ->where('quantity', '>', 0)
                ->whereNotNull('expired_at')
                ->whereBetween('expired_at', [$this->from, $this->to])
                ->when($this->search !== '', function (Builder $query) use ($term): void {
                    $query->where(function (Builder $search) use ($term): void {
                        $search
                            ->where('batch_number', 'like', $term)
                            ->orWhereHas('product', function (Builder $product) use ($term): void {
                                $product->where('name', 'like', $term)->orWhere('sku', 'like', $term);
                            })
                            ->orWhereHas('warehouse', fn (Builder $warehouse) => $warehouse->where('name', 'like', $term));
                    });
                })
                ->orderBy('expired_at')
                ->paginate($this->perPage),

            'shifts' => CashierShift::query()
                ->with('user')
                ->whereBetween('opened_at', [
                    $this->from.' 00:00:00',
                    $this->to.' 23:59:59',
                ])
                ->when($this->search !== '', fn (Builder $query) => $query->whereHas(
                    'user',
                    fn (Builder $user) => $user->where('name', 'like', $term)
                ))
                ->latest('opened_at')
                ->paginate($this->perPage),

            'expenses' => Expense::query()
                ->with(['creator', 'cashierShift'])
                ->whereBetween('expense_date', [$this->from, $this->to])
                ->when($this->search !== '', function (Builder $query) use ($term): void {
                    $query->where(function (Builder $search) use ($term): void {
                        $search
                            ->where('category', 'like', $term)
                            ->orWhere('notes', 'like', $term)
                            ->orWhereHas('creator', fn (Builder $user) => $user->where('name', 'like', $term));
                    });
                })
                ->latest('expense_date')
                ->latest('id')
                ->paginate($this->perPage),

            'sale_returns' => SaleReturn::query()
                ->with(['sale.customer', 'creator'])
                ->withCount('items')
                ->whereBetween('returned_at', [
                    $this->from.' 00:00:00',
                    $this->to.' 23:59:59',
                ])
                ->when($this->search !== '', function (Builder $query) use ($term): void {
                    $query->where(function (Builder $search) use ($term): void {
                        $search
                            ->where('number', 'like', $term)
                            ->orWhereHas('sale', function (Builder $sale) use ($term): void {
                                $sale
                                    ->where('number', 'like', $term)
                                    ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', $term));
                            });
                    });
                })
                ->latest('returned_at')
                ->paginate($this->perPage),

            'purchase_returns' => PurchaseReturn::query()
                ->with(['purchase.supplier', 'creator'])
                ->withCount('items')
                ->whereBetween('returned_at', [
                    $this->from.' 00:00:00',
                    $this->to.' 23:59:59',
                ])
                ->when($this->search !== '', function (Builder $query) use ($term): void {
                    $query->where(function (Builder $search) use ($term): void {
                        $search
                            ->where('number', 'like', $term)
                            ->orWhereHas('purchase', function (Builder $purchase) use ($term): void {
                                $purchase
                                    ->where('number', 'like', $term)
                                    ->orWhereHas('supplier', fn (Builder $supplier) => $supplier->where('name', 'like', $term));
                            });
                    });
                })
                ->latest('returned_at')
                ->paginate($this->perPage),

            'opnames' => StockOpname::query()
                ->with(['warehouse', 'creator', 'approver'])
                ->withCount('items')
                ->withCount([
                    'items as difference_items_count' => fn (Builder $items) => $items->where('difference', '!=', 0),
                ])
                ->withSum('items as difference_sum', 'difference')
                ->whereBetween('opname_date', [$this->from, $this->to])
                ->when($this->search !== '', function (Builder $query) use ($term): void {
                    $query->where(function (Builder $search) use ($term): void {
                        $search
                            ->where('number', 'like', $term)
                            ->orWhere('notes', 'like', $term)
                            ->orWhereHas('warehouse', fn (Builder $warehouse) => $warehouse->where('name', 'like', $term));
                    });
                })
                ->latest('opname_date')
                ->paginate($this->perPage),
        };
    }

    private function summary(): array
    {
        return match ($this->tab) {
            'purchases' => $this->purchaseSummary(),
            'movements' => $this->movementSummary(),
            'expiry' => $this->expirySummary(),
            'shifts' => $this->shiftSummary(),
            'expenses' => $this->expenseSummary(),
            'sale_returns' => $this->saleReturnSummary(),
            'purchase_returns' => $this->purchaseReturnSummary(),
            'opnames' => $this->opnameSummary(),
        };
    }

    private function purchaseSummary(): array
    {
        $rows = Purchase::query()
            ->withSum('returns', 'total')
            ->whereBetween('purchase_date', [$this->from, $this->to])
            ->get(['id', 'total', 'paid_amount']);

        return [
            ['label' => 'Transaksi', 'value' => number_format($rows->count()), 'icon' => 'fa-file-invoice'],
            ['label' => 'Pembelian Bersih', 'value' => $this->money((float) $rows->sum(fn (Purchase $row) => $row->net_total)), 'icon' => 'fa-cart-shopping'],
            ['label' => 'Sudah Dibayar', 'value' => $this->money((float) $rows->sum('paid_amount')), 'icon' => 'fa-wallet'],
            ['label' => 'Sisa Utang', 'value' => $this->money((float) $rows->sum(fn (Purchase $row) => $row->balance)), 'icon' => 'fa-clock'],
        ];
    }

    private function movementSummary(): array
    {
        $query = StockMovement::query()->whereBetween('created_at', [
            $this->from.' 00:00:00',
            $this->to.' 23:59:59',
        ]);

        return [
            ['label' => 'Jumlah Mutasi', 'value' => number_format((clone $query)->count()), 'icon' => 'fa-list'],
            ['label' => 'Stok Masuk', 'value' => '+'.\App\Support\Number::quantity((clone $query)->where('direction', 'in')->sum('quantity')), 'icon' => 'fa-arrow-down'],
            ['label' => 'Stok Keluar', 'value' => '-'.\App\Support\Number::quantity((clone $query)->where('direction', 'out')->sum('quantity')), 'icon' => 'fa-arrow-up'],
            ['label' => 'Penyesuaian', 'value' => number_format((clone $query)->whereIn('type', ['adjustment', 'stock_opname'])->count()), 'icon' => 'fa-sliders'],
        ];
    }

    private function expirySummary(): array
    {
        $query = ProductBatch::query()
            ->where('quantity', '>', 0)
            ->whereNotNull('expired_at')
            ->whereBetween('expired_at', [$this->from, $this->to]);

        $value = (float) (clone $query)
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->selectRaw('COALESCE(SUM(product_batches.quantity * products.average_cost),0) value')
            ->value('value');

        return [
            ['label' => 'Batch', 'value' => number_format((clone $query)->count()), 'icon' => 'fa-layer-group'],
            ['label' => 'Jumlah Stok', 'value' => \App\Support\Number::quantity((clone $query)->sum('quantity')), 'icon' => 'fa-boxes-stacked'],
            ['label' => 'Sudah Lewat', 'value' => number_format((clone $query)->whereDate('expired_at', '<', today())->count()), 'icon' => 'fa-circle-xmark'],
            ['label' => 'Nilai Modal', 'value' => $this->money($value), 'icon' => 'fa-coins'],
        ];
    }

    private function shiftSummary(): array
    {
        $query = CashierShift::query()->whereBetween('opened_at', [
            $this->from.' 00:00:00',
            $this->to.' 23:59:59',
        ]);

        return [
            ['label' => 'Jumlah Shift', 'value' => number_format((clone $query)->count()), 'icon' => 'fa-cash-register'],
            ['label' => 'Masih Terbuka', 'value' => number_format((clone $query)->where('status', 'open')->count()), 'icon' => 'fa-lock-open'],
            ['label' => 'Kas Sistem', 'value' => $this->money((float) (clone $query)->sum('expected_cash')), 'icon' => 'fa-wallet'],
            ['label' => 'Total Selisih', 'value' => $this->money((float) (clone $query)->whereNotNull('cash_difference')->sum('cash_difference')), 'icon' => 'fa-scale-balanced'],
        ];
    }

    private function expenseSummary(): array
    {
        $query = Expense::query()->whereBetween('expense_date', [$this->from, $this->to]);

        return [
            ['label' => 'Transaksi', 'value' => number_format((clone $query)->count()), 'icon' => 'fa-receipt'],
            ['label' => 'Total Biaya', 'value' => $this->money((float) (clone $query)->sum('amount')), 'icon' => 'fa-coins'],
            ['label' => 'Tunai', 'value' => $this->money((float) (clone $query)->where('payment_method', 'cash')->sum('amount')), 'icon' => 'fa-money-bill-wave'],
            ['label' => 'Non-Tunai', 'value' => $this->money((float) (clone $query)->where('payment_method', '!=', 'cash')->sum('amount')), 'icon' => 'fa-credit-card'],
        ];
    }

    private function saleReturnSummary(): array
    {
        $query = SaleReturn::query()->whereBetween('returned_at', [
            $this->from.' 00:00:00',
            $this->to.' 23:59:59',
        ]);
        $value = (float) (clone $query)->sum('total');
        $refund = (float) (clone $query)->sum('refund_amount');

        return [
            ['label' => 'Transaksi', 'value' => number_format((clone $query)->count()), 'icon' => 'fa-file-arrow-left'],
            ['label' => 'Nilai Retur', 'value' => $this->money($value), 'icon' => 'fa-box-open'],
            ['label' => 'Pengurang Piutang', 'value' => $this->money(max(0, $value - $refund)), 'icon' => 'fa-file-circle-minus'],
            ['label' => 'Refund Aktual', 'value' => $this->money($refund), 'icon' => 'fa-money-bill-transfer'],
        ];
    }

    private function purchaseReturnSummary(): array
    {
        $query = PurchaseReturn::query()->whereBetween('returned_at', [
            $this->from.' 00:00:00',
            $this->to.' 23:59:59',
        ]);
        $ids = (clone $query)->pluck('id');

        return [
            ['label' => 'Transaksi', 'value' => number_format($ids->count()), 'icon' => 'fa-file-arrow-left'],
            ['label' => 'Nilai Retur', 'value' => $this->money((float) (clone $query)->sum('total')), 'icon' => 'fa-coins'],
            ['label' => 'Jumlah Item', 'value' => number_format(PurchaseReturnItem::query()->whereIn('purchase_return_id', $ids)->count()), 'icon' => 'fa-boxes-stacked'],
            ['label' => 'Qty Diretur', 'value' => \App\Support\Number::quantity(PurchaseReturnItem::query()->whereIn('purchase_return_id', $ids)->sum('quantity')), 'icon' => 'fa-rotate-left'],
        ];
    }

    private function opnameSummary(): array
    {
        $query = StockOpname::query()->whereBetween('opname_date', [$this->from, $this->to]);
        $ids = (clone $query)->pluck('id');

        return [
            ['label' => 'Jumlah Opname', 'value' => number_format($ids->count()), 'icon' => 'fa-clipboard-list'],
            ['label' => 'Disetujui', 'value' => number_format((clone $query)->where('status', 'approved')->count()), 'icon' => 'fa-circle-check'],
            ['label' => 'Barang Berbeda', 'value' => number_format(StockOpnameItem::query()->whereIn('stock_opname_id', $ids)->where('difference', '!=', 0)->count()), 'icon' => 'fa-not-equal'],
            ['label' => 'Total Selisih', 'value' => \App\Support\Number::quantity(StockOpnameItem::query()->whereIn('stock_opname_id', $ids)->sum('difference')), 'icon' => 'fa-scale-balanced'],
        ];
    }

    private function money(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }
}
