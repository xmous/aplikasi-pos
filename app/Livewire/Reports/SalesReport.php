<?php

namespace App\Livewire\Reports;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class SalesReport extends Component
{
    use WithPagination;

    public string $from;
    public string $to;
    public string $search = '';
    public string $paymentStatus = '';
    public string $paymentMethod = '';
    public string $cashierId = '';
    public string $saleStatus = '';
    public int $perPage = 20;

    public function mount(): void
    {
        Gate::authorize('reports.sales');
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function updated(string $property): void
    {
        if ($property === 'perPage' && ! in_array($this->perPage, [15, 20, 50], true)) {
            $this->perPage = 20;
        }

        if (in_array($property, [
            'from',
            'to',
            'search',
            'paymentStatus',
            'paymentMethod',
            'cashierId',
            'saleStatus',
            'perPage',
        ], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->paymentStatus = '';
        $this->paymentMethod = '';
        $this->cashierId = '';
        $this->saleStatus = '';
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('reports.sales');

        $this->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'paymentStatus' => ['nullable', 'in:paid,partial,unpaid'],
            'paymentMethod' => ['nullable', 'in:cash,qris,transfer,debit,ewallet'],
            'cashierId' => ['nullable', 'integer', 'exists:users,id'],
            'saleStatus' => ['nullable', 'in:completed,partially_returned,returned'],
            'search' => ['nullable', 'string', 'max:100'],
            'perPage' => ['integer', 'in:15,20,50'],
        ]);

        $query = $this->query();
        $summaryRows = (clone $query)
            ->withSum('returns', 'total')
            ->get([
                'sales.id',
                'sales.total',
                'sales.paid_amount',
                'sales.promotion_discount',
                'sales.manual_discount',
            ]);

        $transactions = $summaryRows->count();
        $gross = (float) $summaryRows->sum('total');
        $returns = (float) $summaryRows->sum('returns_sum_total');
        $net = (float) $summaryRows->sum(fn (Sale $sale): float => $sale->net_total);
        $paid = (float) $summaryRows->sum('paid_amount');
        $balance = (float) $summaryRows->sum(fn (Sale $sale): float => $sale->balance);

        return view('livewire.reports.sales-report', [
            'sales' => $query
                ->with(['cashier', 'customer', 'payments'])
                ->withSum('returns', 'total')
                ->latest('sold_at')
                ->paginate($this->perPage),
            'summary' => [
                'transactions' => $transactions,
                'gross' => $gross,
                'discounts' => (float) $summaryRows->sum(
                    fn (Sale $sale): float => (float) $sale->promotion_discount
                        + (float) $sale->manual_discount
                ),
                'returns' => $returns,
                'net' => $net,
                'paid' => $paid,
                'balance' => $balance,
                'average' => $transactions > 0 ? $net / $transactions : 0,
            ],
            'cashiers' => User::permission('sales.create')->orderBy('name')->get(),
        ]);
    }

    private function query(): Builder
    {
        return Sale::query()
            ->whereBetween('sold_at', [
                $this->from.' 00:00:00',
                $this->to.' 23:59:59',
            ])
            ->when($this->search !== '', function (Builder $query): void {
                $terms = preg_split('/\s+/', trim($this->search)) ?: [];

                foreach (array_filter($terms) as $term) {
                    $like = '%'.$term.'%';
                    $query->where(function (Builder $search) use ($like): void {
                        $search
                            ->where('number', 'like', $like)
                            ->orWhereHas('customer', function (Builder $customer) use ($like): void {
                                $customer
                                    ->where('name', 'like', $like)
                                    ->orWhere('phone', 'like', $like)
                                    ->orWhere('code', 'like', $like);
                            })
                            ->orWhereHas('items', function (Builder $item) use ($like): void {
                                $item
                                    ->where('product_name', 'like', $like)
                                    ->orWhere('sku', 'like', $like);
                            });
                    });
                }
            })
            ->when(
                $this->paymentStatus !== '',
                fn (Builder $query) => $query->where('payment_status', $this->paymentStatus)
            )
            ->when(
                $this->paymentMethod !== '',
                fn (Builder $query) => $query->whereHas(
                    'payments',
                    fn (Builder $payment) => $payment->where('method', $this->paymentMethod)
                )
            )
            ->when(
                $this->cashierId !== '',
                fn (Builder $query) => $query->where('cashier_id', $this->cashierId)
            )
            ->when(
                $this->saleStatus !== '',
                fn (Builder $query) => $query->where('status', $this->saleStatus)
            );
    }
}
