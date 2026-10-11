<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BusinessAnalyticsService
{
    public function dashboard(User $user, array $access): array
    {
        return $this->build(
            $user,
            now()->startOfDay(),
            now(),
            [
                'sales' => (bool) ($access['sales'] ?? false),
                'profit' => (bool) ($access['profit'] ?? false),
                'stock' => (bool) ($access['stock'] ?? false),
                'debts' => (bool) ($access['debts'] ?? false),
                'cashier' => (bool) ($access['cashier'] ?? false),
                'activity' => (bool) ($access['activity'] ?? false),
            ],
            true,
            null
        );
    }

    public function report(
        User $user,
        Carbon $from,
        Carbon $to,
        ?int $cashierId = null,
        string $mode = 'sales'
    ): array {
        if ($mode === 'expenses') {
            return $this->expenseReport(
                $user,
                $from,
                $to,
                $cashierId
            );
        }

        if ($mode === 'purchases') {
            return $this->purchaseReport(
                $user,
                $from,
                $to,
                $cashierId
            );
        }

        return $this->build(
            $user,
            $from,
            $to,
            [
                'sales' => true,
                'profit' => $user->can('reports.profit'),
                'stock' => $user->can('reports.stocks')
                    || $user->can('stock.view'),
                'debts' => $user->can('reports.debts'),
                'cashier' => $user->can('reports.sales'),
                'activity' => $user->can('audit.view'),
            ],
            false,
            $cashierId
        );
    }

    public function pointDetail(
        User $user,
        string $metric,
        string $key,
        ?int $cashierId = null,
        string $mode = 'sales'
    ): array {
        if ($mode === 'expenses') {
            return $this->expensePointDetail(
                $user,
                $metric,
                $key,
                $cashierId
            );
        }

        if ($mode === 'purchases') {
            return $this->purchasePointDetail(
                $user,
                $metric,
                $key,
                $cashierId
            );
        }

        return $this->salesPointDetail(
            $user,
            $metric,
            $key,
            $cashierId
        );
    }

    private function salesPointDetail(
        User $user,
        string $metric,
        string $key,
        ?int $cashierId
    ): array {
        [$scopeUser, $allSales, $scope] = $this->resolveSalesScope(
            $user,
            $cashierId
        );

        [$from, $to, $title] = $this->pointPeriod($metric, $key);

        $sales = $this->saleQuery($scopeUser, $allSales)
            ->whereBetween('sold_at', [$from, $to])
            ->with([
                'customer:id,name',
                'cashier:id,name',
                'payments:id,sale_id,method,amount',
            ])
            ->withSum('returns as returned_total', 'total')
            ->latest('sold_at')
            ->take(100)
            ->get();

        $saleIds = $sales->pluck('id');

        $products = $saleIds->isEmpty()
            ? collect()
            : SaleItem::query()
                ->with([
                    'unit:id,name,symbol',
                    'product.unit:id,name,symbol',
                ])
                ->whereIn('sale_id', $saleIds)
                ->get()
                ->groupBy(
                    fn (SaleItem $item): string =>
                        ((string) $item->product_id)
                        .'|'.$item->sku
                )
                ->map(function (Collection $items): array {
                    $first = $items->first();

                    return [
                        'name' => $first->product_name,
                        'sku' => $first->sku,
                        'quantity' => (float) $items->sum(
                            fn (SaleItem $item): float =>
                                (float) $item->base_quantity > 0
                                    ? (float) $item->base_quantity
                                    : (float) $item->quantity
                        ),
                        'unit' => $first->product?->unit?->symbol
                            ?? $first->unit?->symbol
                            ?? 'dasar',
                        'value' => (float) $items->sum('subtotal'),
                    ];
                })
                ->sortByDesc('quantity')
                ->values();

        $transactions = $sales->map(function (Sale $sale): array {
            $methods = $sale->payments
                ->pluck('method')
                ->filter()
                ->unique()
                ->map(
                    fn (string $method): string =>
                        $this->paymentLabel($method)
                )
                ->implode(' + ');

            return [
                'id' => $sale->id,
                'number' => $sale->number,
                'time' => $sale->sold_at->format('d/m/Y H:i'),
                'customer' => $sale->customer?->name
                    ?? 'Pelanggan Umum',
                'cashier' => $sale->cashier?->name ?? '-',
                'method' => $methods ?: '-',
                'status' => $sale->payment_status,
                'value' => max(
                    0,
                    (float) $sale->total
                        - (float) ($sale->returned_total ?? 0)
                ),
            ];
        })->values();

        return [
            'mode' => 'sales',
            'title' => $title,
            'period' => $from->isSameDay($to)
                ? $from->translatedFormat('d F Y H:i')
                    .' – '.$to->format('H:i')
                : $from->translatedFormat('d M Y')
                    .' – '.$to->translatedFormat('d M Y'),
            'scope' => $scope,
            'labels' => [
                'transactions' => 'Daftar Penjualan',
                'products' => 'Produk Terjual',
            ],
            'summary' => [
                'transactions' => $transactions->count(),
                'products' => $products->count(),
                'value' => (float) $transactions->sum('value'),
            ],
            'transactions' => $transactions,
            'products' => $products,
        ];
    }

    private function expenseReport(
        User $user,
        Carbon $from,
        Carbon $to,
        ?int $staffId
    ): array {
        [$staff, $all, $scope] = $this->resolveExpenseScope(
            $user,
            $staffId
        );

        $overview = $this->expenseOverview(
            $staff,
            $all,
            $from,
            $to
        );
        $categories = $this->expenseCategoryData(
            $staff,
            $all,
            $from,
            $to
        );
        $payments = $this->expensePaymentData(
            $staff,
            $all,
            $from,
            $to
        );
        $users = $this->expenseUserData(
            $staff,
            $all,
            $from,
            $to
        );

        return [
            'mode' => 'expenses',
            'period' => [
                'from' => $from->copy(),
                'to' => $to->copy(),
                'label' => $from->isSameDay($to)
                    ? $from->translatedFormat('d F Y')
                    : $from->translatedFormat('d M Y')
                        .' – '.$to->translatedFormat('d M Y'),
            ],
            'scope' => $scope,
            'access' => [
                'sales' => false,
                'profit' => false,
                'stock' => false,
                'debts' => false,
                'cashier' => true,
                'activity' => $user->can('audit.view'),
            ],
            'overview' => $overview,
            'payments' => $payments,
            'products' => $categories,
            'customers' => [
                'customers' => $overview['categories'],
                'new_customers' => $payments['methods']->count(),
                'member_transactions' => $overview['transactions'],
                'repeat_rate' => $overview['top_category_share'],
                'top_customer' => $categories['top'],
                'ranking' => $categories['ranking'],
            ],
            'cashiers' => $users,
            'charts' => $this->expenseChartData(
                $staff,
                $all,
                $from,
                $to
            ),
            'comparisons' => $this->expenseComparisonData(
                $staff,
                $all,
                $to
            ),
            'activities' => $this->expenseActivityData(
                $staff,
                $all,
                $from,
                $to,
                $user->can('audit.view')
            ),
            'expense_stats' => [
                'category_count' => $overview['categories'],
                'method_count' => $payments['methods']->count(),
                'user_count' => $users['ranking']->count(),
                'largest' => $overview['largest'],
                'cash_ratio' => $overview['cash_ratio'],
            ],
        ];
    }

    private function expensePointDetail(
        User $user,
        string $metric,
        string $key,
        ?int $staffId
    ): array {
        [$staff, $all, $scope] = $this->resolveExpenseScope(
            $user,
            $staffId
        );

        [$from, $to, $salesTitle] = $this->pointPeriod(
            $metric,
            $key
        );

        $query = $this->expenseQuery($staff, $all);

        if ($metric === 'hour') {
            $query->whereBetween('created_at', [$from, $to]);
        } else {
            $query->whereBetween(
                'expense_date',
                [$from->toDateString(), $to->toDateString()]
            );
        }

        $expenses = $query
            ->with('creator:id,name')
            ->latest('created_at')
            ->take(100)
            ->get();

        $categories = $expenses
            ->groupBy(
                fn (Expense $expense): string =>
                    trim($expense->category) ?: 'Tanpa kategori'
            )
            ->map(
                function (
                    Collection $rows,
                    string $category
                ): array {
                    return [
                        'name' => $category,
                        'sku' => $rows->count().' catatan',
                        'quantity' => (float) $rows->count(),
                        'unit' => 'catatan',
                        'value' => (float) $rows->sum('amount'),
                    ];
                }
            )
            ->sortByDesc('value')
            ->values();

        $transactions = $expenses
            ->map(fn (Expense $expense): array => [
                'id' => $expense->id,
                'number' => 'EXP-'.str_pad(
                    (string) $expense->id,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),
                'time' => $expense->created_at
                    ->format('d/m/Y H:i'),
                'customer' => $expense->category,
                'cashier' => $expense->creator?->name ?? '-',
                'method' => $this->paymentLabel(
                    $expense->payment_method
                ),
                'status' => 'recorded',
                'value' => (float) $expense->amount,
            ])
            ->values();

        return [
            'mode' => 'expenses',
            'title' => str_replace(
                'penjualan',
                'pengeluaran',
                $salesTitle
            ),
            'period' => $from->isSameDay($to)
                ? $from->translatedFormat('d F Y H:i')
                    .' – '.$to->format('H:i')
                : $from->translatedFormat('d M Y')
                    .' – '.$to->translatedFormat('d M Y'),
            'scope' => $scope,
            'labels' => [
                'transactions' => 'Daftar Pengeluaran',
                'products' => 'Kategori Pengeluaran',
            ],
            'summary' => [
                'transactions' => $transactions->count(),
                'products' => $categories->count(),
                'value' => (float) $transactions->sum('value'),
            ],
            'transactions' => $transactions,
            'products' => $categories,
        ];
    }

    private function resolveExpenseScope(
        User $user,
        ?int $staffId
    ): array {
        if ($staffId && $user->can('expenses.view')) {
            $staff = User::query()
                ->whereKey($staffId)
                ->where('is_active', true)
                ->first();

            if ($staff) {
                return [
                    $staff,
                    false,
                    [
                        'mode' => 'staff',
                        'label' => 'Pencatat: '.$staff->name,
                        'cashier_id' => $staff->id,
                    ],
                ];
            }
        }

        return [
            $user,
            true,
            [
                'mode' => 'all',
                'label' => 'Semua pencatat',
                'cashier_id' => null,
            ],
        ];
    }

    private function expenseQuery(
        User $staff,
        bool $all
    ): Builder {
        return Expense::query()->when(
            ! $all,
            fn (Builder $query): Builder => $query->where(
                'created_by',
                $staff->id
            )
        );
    }

    private function expenseOverview(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to
    ): array {
        $rows = $this->expenseQuery($staff, $all)
            ->whereBetween(
                'expense_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->get([
                'id',
                'category',
                'amount',
                'payment_method',
            ]);

        $total = (float) $rows->sum('amount');
        $count = $rows->count();
        $cash = (float) $rows
            ->where('payment_method', 'cash')
            ->sum('amount');
        $nonCash = max(0, $total - $cash);
        $categoryCount = $rows
            ->pluck('category')
            ->map(fn ($category): string => trim((string) $category))
            ->filter()
            ->unique()
            ->count();

        $topCategoryValue = (float) $rows
            ->groupBy(
                fn (Expense $expense): string =>
                    trim($expense->category) ?: 'Tanpa kategori'
            )
            ->map(
                fn (Collection $items): float =>
                    (float) $items->sum('amount')
            )
            ->sortDesc()
            ->first();

        return [
            'gross_sales' => $total,
            'returns' => 0,
            'net_sales' => $total,
            'transactions' => $count,
            'average_transaction' => $count > 0
                ? $total / $count
                : 0,
            'discount' => 0,
            'cash' => $cash,
            'non_cash' => $nonCash,
            'largest' => (float) $rows->max('amount'),
            'categories' => $categoryCount,
            'cash_ratio' => $total > 0
                ? ($cash / $total) * 100
                : 0,
            'top_category_share' => $total > 0
                ? ($topCategoryValue / $total) * 100
                : 0,
        ];
    }

    private function expensePaymentData(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to
    ): array {
        $methods = $this->expenseQuery($staff, $all)
            ->whereBetween(
                'expense_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->selectRaw(
                'payment_method AS method, '
                .'COALESCE(SUM(amount), 0) AS total'
            )
            ->selectRaw('COUNT(*) AS transactions')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(fn (Expense $expense): array => [
                'method' => $expense->method,
                'label' => $this->paymentLabel(
                    $expense->method
                ),
                'icon' => $this->paymentIcon(
                    $expense->method
                ),
                'total' => (float) $expense->total,
                'transactions' => (int) $expense->transactions,
            ])
            ->values();

        return [
            'methods' => $methods,
            'total_received' => (float) $methods->sum('total'),
            'receivables' => 0,
        ];
    }

    private function expenseCategoryData(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to
    ): array {
        $rows = $this->expenseQuery($staff, $all)
            ->whereBetween(
                'expense_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->get(['category', 'amount']);

        $ranking = $rows
            ->groupBy(
                fn (Expense $expense): string =>
                    trim($expense->category) ?: 'Tanpa kategori'
            )
            ->map(
                function (
                    Collection $items,
                    string $category
                ): array {
                    return [
                        'product_id' => null,
                        'name' => $category,
                        'sku' => $items->count().' catatan',
                        'unit' => 'catatan',
                        'category' => 'Kategori pengeluaran',
                        'quantity' => (float) $items->count(),
                        'revenue' => (float) $items->sum('amount'),
                        'value' => (float) $items->sum('amount'),
                    ];
                }
            )
            ->sortByDesc('revenue')
            ->values();

        return [
            'top' => $ranking->first(),
            'least' => $ranking->sortBy('revenue')->first(),
            'ranking' => $ranking->take(7)->values(),
            'top_category' => $ranking->first(),
            'categories' => $ranking->take(6)->values(),
        ];
    }

    private function expenseUserData(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to
    ): array {
        $rows = $this->expenseQuery($staff, $all)
            ->whereBetween(
                'expense_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->selectRaw(
                'created_by, COUNT(*) AS transactions'
            )
            ->selectRaw(
                'COALESCE(SUM(amount), 0) AS gross_value'
            )
            ->groupBy('created_by')
            ->get();

        $users = User::query()
            ->whereIn('id', $rows->pluck('created_by'))
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        $ranking = $rows
            ->map(function ($row) use ($users): array {
                $user = $users->get($row->created_by);

                return [
                    'cashier_id' => (int) $row->created_by,
                    'name' => $user?->name
                        ?? 'Pengguna dihapus',
                    'email' => $user?->email,
                    'transactions' => (int) $row->transactions,
                    'sales' => (float) $row->gross_value,
                    'average' => (int) $row->transactions > 0
                        ? (float) $row->gross_value
                            / (int) $row->transactions
                        : 0,
                ];
            })
            ->sortByDesc('sales')
            ->values();

        $overview = $this->expenseOverview(
            $staff,
            $all,
            $from,
            $to
        );

        return [
            'ranking' => $ranking->take(8)->values(),
            'top' => $ranking->first(),
            'open_shifts' => collect(),
            'open_count' => 0,
            'cash_in' => 0,
            'cash_out' => (float) $overview['net_sales'],
            'total' => (float) $overview['net_sales'],
            'paid' => (float) $overview['cash'],
            'outstanding' => (float) $overview['non_cash'],
        ];
    }

    private function expenseChartData(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to
    ): array {
        $daySpan = $from->copy()->startOfDay()->diffInDays(
            $to->copy()->startOfDay()
        );

        $primaryType = $from->isSameDay($to)
            ? 'hour'
            : ($daySpan <= 45 ? 'day' : 'month');

        $primary = $this->expenseChartSeries(
            $staff,
            $all,
            $from,
            $to,
            $primaryType
        );

        $anchor = $to->copy();
        $now = now();

        $weekFrom = $anchor->copy()->startOfWeek();
        $weekTo = $anchor->copy()->endOfWeek();
        $monthFrom = $anchor->copy()->startOfMonth();
        $monthTo = $anchor->copy()->endOfMonth();
        $yearFrom = $anchor->copy()->startOfYear();
        $yearTo = $anchor->copy()->endOfYear();

        foreach (['weekTo', 'monthTo', 'yearTo'] as $name) {
            if ($$name->isFuture()) {
                $$name = $now->copy()->endOfDay();
            }
        }

        return [
            'primary' => $primary,
            'primary_type' => $primaryType,
            'primary_title' => match ($primaryType) {
                'hour' => 'Grafik Pengeluaran per Jam',
                'day' => 'Grafik Pengeluaran Harian',
                default => 'Grafik Pengeluaran Bulanan',
            },
            'primary_description' => match ($primaryType) {
                'hour' => 'Klik titik untuk melihat pengeluaran pada jam tersebut.',
                'day' => 'Klik titik untuk melihat pengeluaran pada tanggal tersebut.',
                default => 'Klik titik untuk melihat pengeluaran pada bulan tersebut.',
            },
            'primary_peak' => $primary
                ->sortByDesc('value')
                ->first(),
            'week' => [
                'label' => $weekFrom->translatedFormat('d M')
                    .' – '.$weekTo->translatedFormat('d M Y'),
                'points' => $this->expenseChartSeries(
                    $staff,
                    $all,
                    $weekFrom,
                    $weekTo,
                    'day'
                ),
            ],
            'month' => [
                'label' => $monthFrom->translatedFormat('F Y'),
                'points' => $this->expenseChartSeries(
                    $staff,
                    $all,
                    $monthFrom,
                    $monthTo,
                    'day'
                ),
            ],
            'year' => [
                'label' => $yearFrom->translatedFormat('Y'),
                'points' => $this->expenseChartSeries(
                    $staff,
                    $all,
                    $yearFrom,
                    $yearTo,
                    'month'
                ),
            ],
        ];
    }

    private function expenseChartSeries(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to,
        string $type
    ): Collection {
        $rows = $this->expenseQuery($staff, $all)
            ->whereBetween(
                'expense_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->get([
                'expense_date',
                'created_at',
                'amount',
            ]);

        if ($type === 'hour') {
            $date = $from->copy()->startOfDay();

            $points = collect(range(0, 23))->map(
                function (int $hour) use (
                    $date,
                    $rows
                ): array {
                    $items = $rows->filter(
                        fn (Expense $expense): bool =>
                            $expense->created_at
                                ->isSameDay($date)
                            && $expense->created_at->hour
                                === $hour
                    );

                    return [
                        'key' => $date->format('Y-m-d')
                            .'-'.str_pad(
                                (string) $hour,
                                2,
                                '0',
                                STR_PAD_LEFT
                            ),
                        'detail_type' => 'hour',
                        'label' => str_pad(
                            (string) $hour,
                            2,
                            '0',
                            STR_PAD_LEFT
                        ).':00',
                        'short_label' => str_pad(
                            (string) $hour,
                            2,
                            '0',
                            STR_PAD_LEFT
                        ),
                        'value' => (float) $items->sum('amount'),
                        'transactions' => $items->count(),
                    ];
                }
            );

            return $this->withPercent($points);
        }

        if ($type === 'month') {
            $start = $from->copy()->startOfMonth();
            $end = $to->copy()->startOfMonth();
            $count = $start->diffInMonths($end);

            $groups = $rows->groupBy(
                fn (Expense $expense): string =>
                    $expense->expense_date->format('Y-m')
            );

            $points = collect(range(0, $count))->map(
                function (int $index) use (
                    $start,
                    $groups
                ): array {
                    $month = $start->copy()->addMonths($index);
                    $key = $month->format('Y-m');
                    $items = $groups[$key] ?? collect();

                    return [
                        'key' => $key,
                        'detail_type' => 'month',
                        'label' => $month->translatedFormat('F Y'),
                        'short_label' => $month->translatedFormat('M'),
                        'value' => (float) $items->sum('amount'),
                        'transactions' => $items->count(),
                    ];
                }
            );

            return $this->withPercent($points);
        }

        $start = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        $count = $start->diffInDays($end);

        $groups = $rows->groupBy(
            fn (Expense $expense): string =>
                $expense->expense_date->format('Y-m-d')
        );

        $points = collect(range(0, $count))->map(
            function (int $index) use (
                $start,
                $groups
            ): array {
                $date = $start->copy()->addDays($index);
                $key = $date->format('Y-m-d');
                $items = $groups[$key] ?? collect();

                return [
                    'key' => $key,
                    'detail_type' => 'day',
                    'label' => $date->translatedFormat('d F Y'),
                    'short_label' => $date->translatedFormat('d M'),
                    'value' => (float) $items->sum('amount'),
                    'transactions' => $items->count(),
                ];
            }
        );

        return $this->withPercent($points);
    }

    private function expenseComparisonData(
        User $staff,
        bool $all,
        Carbon $anchor
    ): array {
        $currentFrom = $anchor->copy()->startOfMonth();
        $currentTo = min(
            $anchor->copy()->endOfDay(),
            now()->endOfDay()
        );
        $previousFrom = $currentFrom
            ->copy()
            ->subMonth()
            ->startOfMonth();
        $previousTo = $previousFrom
            ->copy()
            ->addDays(
                min(
                    $anchor->day - 1,
                    $previousFrom->daysInMonth - 1
                )
            )
            ->endOfDay();

        $current = $this->netExpensesBetween(
            $staff,
            $all,
            $currentFrom,
            $currentTo
        );
        $previous = $this->netExpensesBetween(
            $staff,
            $all,
            $previousFrom,
            $previousTo
        );

        return [
            'month_current' => $current,
            'month_previous' => $previous,
            'month_change' => $this->growth(
                $current,
                $previous
            ),
            'year_previous' => 0,
            'year_change' => null,
        ];
    }

    private function expenseActivityData(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to,
        bool $includeAudit
    ): array {
        $latest = $this->expenseQuery($staff, $all)
            ->with('creator:id,name')
            ->whereBetween(
                'expense_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->latest('created_at')
            ->take(8)
            ->get();

        $largest = $this->expenseQuery($staff, $all)
            ->with('creator:id,name')
            ->whereBetween(
                'expense_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->orderByDesc('amount')
            ->latest('created_at')
            ->take(6)
            ->get();

        return [
            'latest_transactions' => $latest->map(
                fn (Expense $expense): array => [
                    'time' => $expense->created_at->format('H:i'),
                    'number' => 'EXP-'.str_pad(
                        (string) $expense->id,
                        6,
                        '0',
                        STR_PAD_LEFT
                    ),
                    'party' => $expense->category,
                    'staff' => $expense->creator?->name ?? '-',
                    'status' => $this->paymentLabel(
                        $expense->payment_method
                    ),
                    'value' => (float) $expense->amount,
                ]
            )->values(),
            'largest_expenses' => $largest,
            'latest_returns' => collect(),
            'cancelled' => 0,
            'logs' => $includeAudit
                ? ActivityLog::query()
                    ->with('user')
                    ->latest()
                    ->take(8)
                    ->get()
                : collect(),
        ];
    }

    private function netExpensesBetween(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to
    ): float {
        return (float) $this->expenseQuery($staff, $all)
            ->whereBetween(
                'expense_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->sum('amount');
    }

    private function purchaseReport(
        User $user,
        Carbon $from,
        Carbon $to,
        ?int $staffId
    ): array {
        [$staff, $all, $scope] = $this->resolvePurchaseScope(
            $user,
            $staffId
        );

        $access = [
            'sales' => false,
            'profit' => false,
            'stock' => $user->can('reports.stocks')
                || $user->can('stock.view'),
            'debts' => $user->can('reports.debts'),
            'cashier' => true,
            'activity' => $user->can('audit.view'),
        ];

        $data = [
            'mode' => 'purchases',
            'period' => [
                'from' => $from->copy(),
                'to' => $to->copy(),
                'label' => $from->isSameDay($to)
                    ? $from->translatedFormat('d F Y')
                    : $from->translatedFormat('d M Y')
                        .' – '.$to->translatedFormat('d M Y'),
            ],
            'scope' => $scope,
            'access' => $access,
            'overview' => $this->purchaseOverview(
                $staff,
                $all,
                $from,
                $to
            ),
            'payments' => $this->purchasePaymentData(
                $staff,
                $all,
                $from,
                $to
            ),
            'products' => $this->purchaseProductData(
                $staff,
                $all,
                $from,
                $to
            ),
            'customers' => $this->supplierData(
                $staff,
                $all,
                $from,
                $to
            ),
            'cashiers' => $this->purchaseStaffData(
                $staff,
                $all,
                $from,
                $to
            ),
            'charts' => $this->purchaseChartData(
                $staff,
                $all,
                $from,
                $to
            ),
            'comparisons' => $this->purchaseComparisonData(
                $staff,
                $all,
                $to
            ),
            'activities' => $this->purchaseActivityData(
                $staff,
                $all,
                $from,
                $to,
                $access['activity']
            ),
        ];

        if ($access['stock']) {
            $data['inventory'] = $this->inventoryData($from, $to);
        }

        if ($access['debts'] || $access['stock']) {
            $data['notifications'] = $this->notificationData(
                $user,
                true,
                $access
            );
        }

        return $data;
    }

    private function purchasePointDetail(
        User $user,
        string $metric,
        string $key,
        ?int $staffId
    ): array {
        [$staff, $all, $scope] = $this->resolvePurchaseScope(
            $user,
            $staffId
        );

        [$from, $to, $salesTitle] = $this->pointPeriod(
            $metric,
            $key
        );

        $query = $this->purchaseQuery($staff, $all);

        if ($metric === 'hour') {
            $query->whereBetween('created_at', [$from, $to]);
        } else {
            $query->whereBetween(
                'purchase_date',
                [$from->toDateString(), $to->toDateString()]
            );
        }

        $purchases = $query
            ->with([
                'supplier:id,name',
                'creator:id,name',
                'payments:id,purchase_id,method,amount',
            ])
            ->withSum('returns as returned_total', 'total')
            ->latest('created_at')
            ->take(100)
            ->get();

        $purchaseIds = $purchases->pluck('id');

        $products = $purchaseIds->isEmpty()
            ? collect()
            : PurchaseItem::query()
                ->with([
                    'product:id,name,sku,unit_id',
                    'product.unit:id,name,symbol',
                    'unit:id,name,symbol',
                ])
                ->whereIn('purchase_id', $purchaseIds)
                ->get()
                ->groupBy(
                    fn (PurchaseItem $item): string =>
                        (string) $item->product_id
                )
                ->map(function (Collection $items): array {
                    $first = $items->first();

                    return [
                        'name' => $first->product?->name
                            ?? 'Barang dihapus',
                        'sku' => $first->product?->sku ?? '-',
                        'quantity' => (float) $items->sum(
                            fn (PurchaseItem $item): float =>
                                (float) $item->base_quantity > 0
                                    ? (float) $item->base_quantity
                                    : (float) $item->quantity
                        ),
                        'unit' => $first->product?->unit?->symbol
                            ?? $first->unit?->symbol
                            ?? 'dasar',
                        'value' => (float) $items->sum('subtotal'),
                    ];
                })
                ->sortByDesc('quantity')
                ->values();

        $transactions = $purchases->map(
            function (Purchase $purchase): array {
                $methods = $purchase->payments
                    ->pluck('method')
                    ->filter()
                    ->unique()
                    ->map(
                        fn (string $method): string =>
                            $this->paymentLabel($method)
                    )
                    ->implode(' + ');

                return [
                    'id' => $purchase->id,
                    'number' => $purchase->number,
                    'time' => $purchase->created_at
                        ->format('d/m/Y H:i'),
                    'customer' => $purchase->supplier?->name
                        ?? 'Tanpa supplier',
                    'cashier' => $purchase->creator?->name ?? '-',
                    'method' => $methods ?: '-',
                    'status' => $purchase->payment_status,
                    'value' => max(
                        0,
                        (float) $purchase->total
                            - (float) (
                                $purchase->returned_total ?? 0
                            )
                    ),
                ];
            }
        )->values();

        return [
            'mode' => 'purchases',
            'title' => str_replace(
                'penjualan',
                'pembelian',
                $salesTitle
            ),
            'period' => $from->isSameDay($to)
                ? $from->translatedFormat('d F Y H:i')
                    .' – '.$to->format('H:i')
                : $from->translatedFormat('d M Y')
                    .' – '.$to->translatedFormat('d M Y'),
            'scope' => $scope,
            'labels' => [
                'transactions' => 'Daftar Pembelian',
                'products' => 'Barang Dibeli',
            ],
            'summary' => [
                'transactions' => $transactions->count(),
                'products' => $products->count(),
                'value' => (float) $transactions->sum('value'),
            ],
            'transactions' => $transactions,
            'products' => $products,
        ];
    }

    private function build(
        User $user,
        Carbon $from,
        Carbon $to,
        array $access,
        bool $dashboard,
        ?int $cashierId = null
    ): array {
        [$scopeUser, $allSales, $scope] = $this->resolveSalesScope(
            $user,
            $cashierId
        );

        $data = [
            'mode' => 'sales',
            'period' => [
                'from' => $from->copy(),
                'to' => $to->copy(),
                'label' => $from->isSameDay($to)
                    ? $from->translatedFormat('d F Y')
                    : $from->translatedFormat('d M Y')
                        .' – '.$to->translatedFormat('d M Y'),
            ],
            'scope' => $scope,
            'access' => $access,
        ];

        if ($access['sales']) {
            $overview = $this->salesOverview(
                $scopeUser,
                $allSales,
                $from,
                $to,
                $access['profit']
            );

            $data['overview'] = $overview;
            $data['payments'] = $this->paymentData(
                $scopeUser,
                $allSales,
                $from,
                $to
            );
            $data['products'] = $this->productData(
                $scopeUser,
                $allSales,
                $from,
                $to
            );
            $data['customers'] = $this->customerData(
                $scopeUser,
                $allSales,
                $from,
                $to
            );
            $data['cashiers'] = $this->cashierData(
                $scopeUser,
                $allSales,
                $from,
                $to
            );
            $data['charts'] = $this->chartData(
                $scopeUser,
                $allSales,
                $from,
                $to,
                $dashboard
            );
            $data['comparisons'] = $this->comparisonData(
                $scopeUser,
                $allSales,
                $to
            );
        }

        if ($access['stock']) {
            $data['inventory'] = $this->inventoryData($from, $to);
        }

        if ($access['debts'] || $access['stock']) {
            $data['notifications'] = $this->notificationData(
                $scopeUser,
                $allSales,
                $access
            );
        }

        if ($access['activity'] || $access['sales']) {
            $data['activities'] = $this->activityData(
                $scopeUser,
                $allSales,
                $from,
                $to,
                $access['activity']
            );
        }

        return $data;
    }

    private function resolvePurchaseScope(
        User $user,
        ?int $staffId
    ): array {
        if ($staffId && $user->can('purchases.view')) {
            $staff = User::query()
                ->whereKey($staffId)
                ->where('is_active', true)
                ->first();

            if ($staff) {
                return [
                    $staff,
                    false,
                    [
                        'mode' => 'staff',
                        'label' => 'Petugas: '.$staff->name,
                        'cashier_id' => $staff->id,
                    ],
                ];
            }
        }

        return [
            $user,
            true,
            [
                'mode' => 'all',
                'label' => 'Semua petugas',
                'cashier_id' => null,
            ],
        ];
    }

    private function purchaseQuery(
        User $staff,
        bool $all
    ): Builder {
        return Purchase::query()->when(
            ! $all,
            fn (Builder $query): Builder => $query->where(
                'created_by',
                $staff->id
            )
        );
    }

    private function purchaseReturnQuery(
        User $staff,
        bool $all
    ): Builder {
        return PurchaseReturn::query()->when(
            ! $all,
            fn (Builder $query): Builder => $query->whereHas(
                'purchase',
                fn (Builder $purchase): Builder =>
                    $purchase->where(
                        'created_by',
                        $staff->id
                    )
            )
        );
    }

    private function purchaseOverview(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to
    ): array {
        $rows = $this->purchaseQuery($staff, $all)
            ->withSum('returns as returned_total', 'total')
            ->whereBetween(
                'purchase_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->get([
                'id',
                'total',
                'paid_amount',
                'discount',
                'additional_cost',
            ]);

        $itemDiscount = (float) PurchaseItem::query()
            ->whereHas(
                'purchase',
                function (Builder $query) use (
                    $staff,
                    $all,
                    $from,
                    $to
                ): void {
                    $query->whereBetween(
                        'purchase_date',
                        [
                            $from->toDateString(),
                            $to->toDateString(),
                        ]
                    );

                    if (! $all) {
                        $query->where(
                            'created_by',
                            $staff->id
                        );
                    }
                }
            )
            ->sum('discount');

        $gross = (float) $rows->sum('total');
        $returns = (float) $rows->sum(
            fn (Purchase $purchase): float =>
                (float) ($purchase->returned_total ?? 0)
        );
        $net = max(0, $gross - $returns);
        $count = $rows->count();
        $paid = (float) $rows->sum('paid_amount');
        $outstanding = (float) $rows->sum(
            fn (Purchase $purchase): float => max(
                0,
                (float) $purchase->total
                    - (float) (
                        $purchase->returned_total ?? 0
                    )
                    - (float) $purchase->paid_amount
            )
        );

        return [
            'gross_sales' => $gross,
            'returns' => $returns,
            'net_sales' => $net,
            'transactions' => $count,
            'average_transaction' => $count > 0
                ? $net / $count
                : 0,
            'discount' => $itemDiscount
                + (float) $rows->sum('discount'),
            'additional_cost' => (float) $rows->sum(
                'additional_cost'
            ),
            'paid' => $paid,
            'outstanding' => $outstanding,
            'tax' => null,
        ];
    }

    private function purchasePaymentData(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to
    ): array {
        $methods = PurchasePayment::query()
            ->selectRaw(
                'method, COALESCE(SUM(amount), 0) AS total'
            )
            ->selectRaw('COUNT(*) AS transactions')
            ->whereBetween('paid_at', [$from, $to])
            ->whereHas(
                'purchase',
                function (Builder $query) use (
                    $staff,
                    $all
                ): void {
                    if (! $all) {
                        $query->where(
                            'created_by',
                            $staff->id
                        );
                    }
                }
            )
            ->groupBy('method')
            ->orderByDesc('total')
            ->get()
            ->map(fn (PurchasePayment $payment): array => [
                'method' => $payment->method,
                'label' => $this->paymentLabel(
                    $payment->method
                ),
                'icon' => $this->paymentIcon(
                    $payment->method
                ),
                'total' => (float) $payment->total,
                'transactions' => (int) $payment->transactions,
            ])
            ->values();

        $rows = $this->purchaseQuery($staff, $all)
            ->withSum('returns as returned_total', 'total')
            ->whereBetween(
                'purchase_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->whereIn(
                'payment_status',
                ['unpaid', 'partial']
            )
            ->get(['id', 'total', 'paid_amount']);

        $payables = $rows->sum(
            fn (Purchase $purchase): float => max(
                0,
                (float) $purchase->total
                    - (float) (
                        $purchase->returned_total ?? 0
                    )
                    - (float) $purchase->paid_amount
            )
        );

        return [
            'methods' => $methods,
            'total_received' => (float) $methods->sum('total'),
            'receivables' => (float) $payables,
        ];
    }

    private function purchaseProductData(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to
    ): array {
        $items = PurchaseItem::query()
            ->with([
                'product:id,name,sku,unit_id,product_category_id',
                'product.unit:id,name,symbol',
                'product.category:id,name',
                'unit:id,name,symbol',
            ])
            ->whereHas(
                'purchase',
                function (Builder $query) use (
                    $staff,
                    $all,
                    $from,
                    $to
                ): void {
                    $query->whereBetween(
                        'purchase_date',
                        [
                            $from->toDateString(),
                            $to->toDateString(),
                        ]
                    );

                    if (! $all) {
                        $query->where(
                            'created_by',
                            $staff->id
                        );
                    }
                }
            )
            ->get();

        $performance = $items
            ->groupBy(
                fn (PurchaseItem $item): string =>
                    (string) $item->product_id
            )
            ->map(function (Collection $rows): array {
                $first = $rows->first();

                return [
                    'product_id' => $first->product_id,
                    'name' => $first->product?->name
                        ?? 'Barang dihapus',
                    'sku' => $first->product?->sku ?? '-',
                    'unit' => $first->product?->unit?->symbol
                        ?? $first->unit?->symbol
                        ?? 'dasar',
                    'category' => $first->product?->category?->name
                        ?? 'Tanpa kategori',
                    'quantity' => (float) $rows->sum(
                        fn (PurchaseItem $item): float =>
                            (float) $item->base_quantity > 0
                                ? (float) $item->base_quantity
                                : (float) $item->quantity
                    ),
                    'revenue' => (float) $rows->sum('subtotal'),
                ];
            })
            ->sortByDesc('quantity')
            ->values();

        $categoryPerformance = $performance
            ->groupBy('category')
            ->map(
                fn (
                    Collection $rows,
                    string $category
                ): array => [
                    'name' => $category,
                    'quantity' => (float) $rows->sum(
                        'quantity'
                    ),
                    'revenue' => (float) $rows->sum(
                        'revenue'
                    ),
                ]
            )
            ->sortByDesc('revenue')
            ->values();

        return [
            'top' => $performance->first(),
            'least' => $performance->sortBy(
                'quantity'
            )->first(),
            'ranking' => $performance->take(7)->values(),
            'top_category' => $categoryPerformance->first(),
            'categories' => $categoryPerformance
                ->take(6)
                ->values(),
        ];
    }

    private function supplierData(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to
    ): array {
        $rows = $this->purchaseQuery($staff, $all)
            ->whereBetween(
                'purchase_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->whereNotNull('supplier_id')
            ->selectRaw(
                'supplier_id, COUNT(*) AS transactions'
            )
            ->selectRaw(
                'COALESCE(SUM(total), 0) AS gross_value'
            )
            ->groupBy('supplier_id')
            ->get();

        $returns = $this->purchaseReturnQuery($staff, $all)
            ->join(
                'purchases',
                'purchases.id',
                '=',
                'purchase_returns.purchase_id'
            )
            ->whereBetween('returned_at', [$from, $to])
            ->whereNotNull('purchases.supplier_id')
            ->selectRaw('purchases.supplier_id')
            ->selectRaw(
                'COALESCE(SUM(purchase_returns.total), 0) AS total'
            )
            ->groupBy('purchases.supplier_id')
            ->get()
            ->keyBy('supplier_id');

        $suppliers = Supplier::query()
            ->whereIn('id', $rows->pluck('supplier_id'))
            ->get(['id', 'name', 'code', 'phone'])
            ->keyBy('id');

        $ranking = $rows
            ->map(
                function ($row) use (
                    $returns,
                    $suppliers
                ): array {
                    $supplier = $suppliers->get(
                        $row->supplier_id
                    );

                    return [
                        'customer_id' => (int) $row->supplier_id,
                        'name' => $supplier?->name
                            ?? 'Supplier dihapus',
                        'code' => $supplier?->code,
                        'phone' => $supplier?->phone,
                        'transactions' => (int) $row->transactions,
                        'value' => max(
                            0,
                            (float) $row->gross_value
                                - (float) (
                                    $returns->get(
                                        $row->supplier_id
                                    )?->total ?? 0
                                )
                        ),
                    ];
                }
            )
            ->sortByDesc('value')
            ->values();

        $distinct = $rows->count();
        $repeat = $rows->filter(
            fn ($row): bool =>
                (int) $row->transactions >= 2
        )->count();

        return [
            'customers' => $distinct,
            'new_customers' => Supplier::query()
                ->whereBetween('created_at', [$from, $to])
                ->count(),
            'member_transactions' => (int) $rows->sum(
                'transactions'
            ),
            'top_customer' => $ranking->first(),
            'ranking' => $ranking->take(7)->values(),
            'repeat_customers' => $repeat,
            'repeat_rate' => $distinct > 0
                ? ($repeat / $distinct) * 100
                : 0,
        ];
    }

    private function purchaseStaffData(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to
    ): array {
        $rows = $this->purchaseQuery($staff, $all)
            ->whereBetween(
                'purchase_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->selectRaw(
                'created_by, COUNT(*) AS transactions'
            )
            ->selectRaw(
                'COALESCE(SUM(total), 0) AS gross_value'
            )
            ->selectRaw(
                'COALESCE(SUM(paid_amount), 0) AS paid_value'
            )
            ->groupBy('created_by')
            ->get();

        $users = User::query()
            ->whereIn('id', $rows->pluck('created_by'))
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        $ranking = $rows
            ->map(function ($row) use ($users): array {
                $user = $users->get($row->created_by);

                return [
                    'cashier_id' => (int) $row->created_by,
                    'name' => $user?->name
                        ?? 'Pengguna dihapus',
                    'email' => $user?->email,
                    'transactions' => (int) $row->transactions,
                    'sales' => (float) $row->gross_value,
                    'average' => (int) $row->transactions > 0
                        ? (float) $row->gross_value
                            / (int) $row->transactions
                        : 0,
                    'paid' => (float) $row->paid_value,
                ];
            })
            ->sortByDesc('sales')
            ->values();

        $overview = $this->purchaseOverview(
            $staff,
            $all,
            $from,
            $to
        );

        return [
            'ranking' => $ranking->take(8)->values(),
            'top' => $ranking->first(),
            'open_shifts' => collect(),
            'open_count' => 0,
            'cash_in' => (float) $overview['paid'],
            'cash_out' => (float) $overview['returns'],
            'total' => (float) $overview['net_sales'],
            'paid' => (float) $overview['paid'],
            'outstanding' => (float) $overview['outstanding'],
        ];
    }

    private function purchaseChartData(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to
    ): array {
        $daySpan = $from->copy()->startOfDay()->diffInDays(
            $to->copy()->startOfDay()
        );

        $primaryType = $from->isSameDay($to)
            ? 'hour'
            : ($daySpan <= 45 ? 'day' : 'month');

        $primary = $this->purchaseChartSeries(
            $staff,
            $all,
            $from,
            $to,
            $primaryType
        );

        $anchor = $to->copy();
        $weekFrom = $anchor->copy()->startOfWeek();
        $weekTo = $anchor->copy()->endOfWeek();
        $monthFrom = $anchor->copy()->startOfMonth();
        $monthTo = $anchor->copy()->endOfMonth();
        $yearFrom = $anchor->copy()->startOfYear();
        $yearTo = $anchor->copy()->endOfYear();

        return [
            'primary' => $primary,
            'primary_type' => $primaryType,
            'primary_title' => match ($primaryType) {
                'hour' => 'Grafik Pembelian per Jam',
                'day' => 'Grafik Pembelian Harian',
                default => 'Grafik Pembelian Bulanan',
            },
            'primary_description' => match ($primaryType) {
                'hour' => 'Klik titik untuk melihat pembelian dan barang pada jam tersebut.',
                'day' => 'Klik titik untuk melihat pembelian dan barang pada tanggal tersebut.',
                default => 'Klik titik untuk melihat pembelian dan barang pada bulan tersebut.',
            },
            'primary_peak' => $primary
                ->sortByDesc('value')
                ->first(),
            'week' => [
                'label' => $weekFrom->translatedFormat('d M')
                    .' – '.$weekTo->translatedFormat('d M Y'),
                'points' => $this->purchaseChartSeries(
                    $staff,
                    $all,
                    $weekFrom,
                    $weekTo,
                    'day'
                ),
            ],
            'month' => [
                'label' => $monthFrom->translatedFormat('F Y'),
                'points' => $this->purchaseChartSeries(
                    $staff,
                    $all,
                    $monthFrom,
                    $monthTo,
                    'day'
                ),
            ],
            'year' => [
                'label' => $yearFrom->translatedFormat('Y'),
                'points' => $this->purchaseChartSeries(
                    $staff,
                    $all,
                    $yearFrom,
                    $yearTo,
                    'month'
                ),
            ],
        ];
    }

    private function purchaseChartSeries(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to,
        string $type
    ): Collection {
        $purchases = $this->purchaseQuery($staff, $all)
            ->whereBetween(
                'purchase_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->get(['purchase_date', 'created_at', 'total']);

        $returns = $this->purchaseReturnQuery($staff, $all)
            ->whereBetween('returned_at', [$from, $to])
            ->get(['returned_at', 'total']);

        if ($type === 'hour') {
            $date = $from->copy()->startOfDay();

            $rows = collect(range(0, 23))->map(
                function (int $hour) use (
                    $date,
                    $purchases,
                    $returns
                ): array {
                    $grossRows = $purchases->filter(
                        fn (Purchase $purchase): bool =>
                            $purchase->created_at
                                ->isSameDay($date)
                            && $purchase->created_at->hour
                                === $hour
                    );

                    $returnRows = $returns->filter(
                        fn (PurchaseReturn $return): bool =>
                            $return->returned_at
                                ->isSameDay($date)
                            && $return->returned_at->hour
                                === $hour
                    );

                    return [
                        'key' => $date->format('Y-m-d')
                            .'-'.str_pad(
                                (string) $hour,
                                2,
                                '0',
                                STR_PAD_LEFT
                            ),
                        'detail_type' => 'hour',
                        'label' => str_pad(
                            (string) $hour,
                            2,
                            '0',
                            STR_PAD_LEFT
                        ).':00',
                        'short_label' => str_pad(
                            (string) $hour,
                            2,
                            '0',
                            STR_PAD_LEFT
                        ),
                        'value' => max(
                            0,
                            (float) $grossRows->sum('total')
                                - (float) $returnRows->sum(
                                    'total'
                                )
                        ),
                        'transactions' => $grossRows->count(),
                    ];
                }
            );

            return $this->withPercent($rows);
        }

        if ($type === 'month') {
            $start = $from->copy()->startOfMonth();
            $end = $to->copy()->startOfMonth();
            $count = $start->diffInMonths($end);

            $purchaseGroups = $purchases->groupBy(
                fn (Purchase $purchase): string =>
                    $purchase->purchase_date->format('Y-m')
            );
            $returnGroups = $returns->groupBy(
                fn (PurchaseReturn $return): string =>
                    $return->returned_at->format('Y-m')
            );

            $rows = collect(range(0, $count))->map(
                function (int $index) use (
                    $start,
                    $purchaseGroups,
                    $returnGroups
                ): array {
                    $month = $start->copy()->addMonths(
                        $index
                    );
                    $key = $month->format('Y-m');
                    $grossRows = $purchaseGroups[$key]
                        ?? collect();
                    $returnRows = $returnGroups[$key]
                        ?? collect();

                    return [
                        'key' => $key,
                        'detail_type' => 'month',
                        'label' => $month
                            ->translatedFormat('F Y'),
                        'short_label' => $month
                            ->translatedFormat('M'),
                        'value' => max(
                            0,
                            (float) $grossRows->sum('total')
                                - (float) $returnRows->sum(
                                    'total'
                                )
                        ),
                        'transactions' => $grossRows->count(),
                    ];
                }
            );

            return $this->withPercent($rows);
        }

        $start = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        $count = $start->diffInDays($end);

        $purchaseGroups = $purchases->groupBy(
            fn (Purchase $purchase): string =>
                $purchase->purchase_date->format('Y-m-d')
        );
        $returnGroups = $returns->groupBy(
            fn (PurchaseReturn $return): string =>
                $return->returned_at->format('Y-m-d')
        );

        $rows = collect(range(0, $count))->map(
            function (int $index) use (
                $start,
                $purchaseGroups,
                $returnGroups
            ): array {
                $date = $start->copy()->addDays($index);
                $key = $date->format('Y-m-d');
                $grossRows = $purchaseGroups[$key]
                    ?? collect();
                $returnRows = $returnGroups[$key]
                    ?? collect();

                return [
                    'key' => $key,
                    'detail_type' => 'day',
                    'label' => $date
                        ->translatedFormat('d F Y'),
                    'short_label' => $date
                        ->translatedFormat('d M'),
                    'value' => max(
                        0,
                        (float) $grossRows->sum('total')
                            - (float) $returnRows->sum(
                                'total'
                            )
                    ),
                    'transactions' => $grossRows->count(),
                ];
            }
        );

        return $this->withPercent($rows);
    }

    private function purchaseComparisonData(
        User $staff,
        bool $all,
        Carbon $anchor
    ): array {
        $currentFrom = $anchor->copy()->startOfMonth();
        $currentTo = $anchor->copy()->endOfDay();
        $previousFrom = $currentFrom
            ->copy()
            ->subMonth()
            ->startOfMonth();
        $previousTo = $previousFrom
            ->copy()
            ->addDays(
                min(
                    $anchor->day - 1,
                    $previousFrom->daysInMonth - 1
                )
            )
            ->endOfDay();
        $yearFrom = $currentFrom->copy()->subYear();
        $yearTo = $currentTo->copy()->subYear();

        $current = $this->netPurchasesBetween(
            $staff,
            $all,
            $currentFrom,
            $currentTo
        );
        $previous = $this->netPurchasesBetween(
            $staff,
            $all,
            $previousFrom,
            $previousTo
        );
        $lastYear = $this->netPurchasesBetween(
            $staff,
            $all,
            $yearFrom,
            $yearTo
        );

        return [
            'month_current' => $current,
            'month_previous' => $previous,
            'month_change' => $this->growth(
                $current,
                $previous
            ),
            'year_previous' => $lastYear,
            'year_change' => $this->growth(
                $current,
                $lastYear
            ),
        ];
    }

    private function purchaseActivityData(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to,
        bool $includeAudit
    ): array {
        $latest = $this->purchaseQuery($staff, $all)
            ->with(['supplier', 'creator'])
            ->whereBetween(
                'purchase_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->latest('created_at')
            ->take(5)
            ->get();

        return [
            'latest_transactions' => $latest->map(
                fn (Purchase $purchase): array => [
                    'time' => $purchase->created_at
                        ->format('H:i'),
                    'number' => $purchase->number,
                    'party' => $purchase->supplier?->name
                        ?? 'Tanpa supplier',
                    'staff' => $purchase->creator?->name
                        ?? '-',
                    'status' => $purchase->payment_status,
                    'value' => (float) $purchase->total,
                ]
            )->values(),
            'latest_purchases' => $latest,
            'latest_returns' => $this
                ->purchaseReturnQuery($staff, $all)
                ->with(['purchase.supplier'])
                ->whereBetween('returned_at', [$from, $to])
                ->latest('returned_at')
                ->take(5)
                ->get(),
            'cancelled' => $this->purchaseQuery($staff, $all)
                ->whereBetween(
                    'purchase_date',
                    [
                        $from->toDateString(),
                        $to->toDateString(),
                    ]
                )
                ->where('status', 'cancelled')
                ->count(),
            'logs' => $includeAudit
                ? ActivityLog::query()
                    ->with('user')
                    ->latest()
                    ->take(8)
                    ->get()
                : collect(),
        ];
    }

    private function netPurchasesBetween(
        User $staff,
        bool $all,
        Carbon $from,
        Carbon $to
    ): float {
        $gross = (float) $this
            ->purchaseQuery($staff, $all)
            ->whereBetween(
                'purchase_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->sum('total');

        $returns = (float) $this
            ->purchaseReturnQuery($staff, $all)
            ->whereBetween('returned_at', [$from, $to])
            ->sum('total');

        return max(0, $gross - $returns);
    }

    private function resolveSalesScope(
        User $user,
        ?int $cashierId
    ): array {
        $canChooseCashier = $user->can('reports.sales');

        if ($canChooseCashier && $cashierId) {
            $cashier = User::query()
                ->whereKey($cashierId)
                ->where('is_active', true)
                ->first();

            if ($cashier) {
                return [
                    $cashier,
                    false,
                    [
                        'mode' => 'cashier',
                        'label' => 'Kasir: '.$cashier->name,
                        'cashier_id' => $cashier->id,
                    ],
                ];
            }
        }

        if ($canChooseCashier) {
            return [
                $user,
                true,
                [
                    'mode' => 'all',
                    'label' => 'Semua kasir',
                    'cashier_id' => null,
                ],
            ];
        }

        return [
            $user,
            false,
            [
                'mode' => 'mine',
                'label' => 'Transaksi saya',
                'cashier_id' => $user->id,
            ],
        ];
    }

    private function pointPeriod(
        string $metric,
        string $key
    ): array {
        try {
            if (
                $metric === 'hour'
                && preg_match(
                    '/^\d{4}-\d{2}-\d{2}-\d{2}$/',
                    $key
                )
            ) {
                $from = Carbon::createFromFormat(
                    'Y-m-d-H',
                    $key
                )->startOfHour();

                return [
                    $from,
                    $from->copy()->endOfHour(),
                    'Rincian penjualan pukul '.$from->format('H:00'),
                ];
            }

            if (
                $metric === 'day'
                && preg_match('/^\d{4}-\d{2}-\d{2}$/', $key)
            ) {
                $from = Carbon::createFromFormat(
                    'Y-m-d',
                    $key
                )->startOfDay();

                return [
                    $from,
                    $from->copy()->endOfDay(),
                    'Rincian penjualan '
                        .$from->translatedFormat('d F Y'),
                ];
            }

            if (
                $metric === 'month'
                && preg_match('/^\d{4}-\d{2}$/', $key)
            ) {
                $from = Carbon::createFromFormat(
                    'Y-m',
                    $key
                )->startOfMonth();

                return [
                    $from,
                    $from->copy()->endOfMonth(),
                    'Rincian penjualan '
                        .$from->translatedFormat('F Y'),
                ];
            }
        } catch (\Throwable) {
            // Handled by the invalid period exception below.
        }

        throw new \InvalidArgumentException(
            'Periode grafik tidak valid.'
        );
    }

    private function saleQuery(User $user, bool $all): Builder
    {
        return Sale::query()->when(
            ! $all,
            fn (Builder $query): Builder => $query->where(
                'cashier_id',
                $user->id
            )
        );
    }

    private function returnQuery(User $user, bool $all): Builder
    {
        return SaleReturn::query()->when(
            ! $all,
            fn (Builder $query): Builder => $query->whereHas(
                'sale',
                fn (Builder $sales): Builder => $sales->where(
                    'cashier_id',
                    $user->id
                )
            )
        );
    }

    private function salesOverview(
        User $user,
        bool $all,
        Carbon $from,
        Carbon $to,
        bool $includeProfit
    ): array {
        $sales = $this->saleQuery($user, $all)
            ->whereBetween('sold_at', [$from, $to]);

        $summary = (clone $sales)
            ->selectRaw('COUNT(*) AS transactions')
            ->selectRaw('COALESCE(SUM(total), 0) AS gross_sales')
            ->selectRaw('COALESCE(SUM(promotion_discount), 0) AS promotion_discount')
            ->selectRaw('COALESCE(SUM(manual_discount), 0) AS manual_discount')
            ->first();

        $itemDiscount = (float) SaleItem::query()
            ->whereHas('sale', function (Builder $query) use (
                $user,
                $all,
                $from,
                $to
            ): void {
                $query->whereBetween('sold_at', [$from, $to]);
                if (! $all) {
                    $query->where('cashier_id', $user->id);
                }
            })
            ->sum('discount');

        $pointsDiscount = Schema::hasColumn('sales', 'points_discount')
            ? (float) (clone $sales)->sum('points_discount')
            : 0.0;

        $returns = (float) $this->returnQuery($user, $all)
            ->whereBetween('returned_at', [$from, $to])
            ->sum('total');

        $grossSales = (float) ($summary->gross_sales ?? 0);
        $transactions = (int) ($summary->transactions ?? 0);
        $netSales = max(0, $grossSales - $returns);
        $discount = $itemDiscount
            + (float) ($summary->promotion_discount ?? 0)
            + (float) ($summary->manual_discount ?? 0)
            + $pointsDiscount;

        $result = [
            'gross_sales' => $grossSales,
            'returns' => $returns,
            'net_sales' => $netSales,
            'transactions' => $transactions,
            'average_transaction' => $transactions > 0
                ? $netSales / $transactions
                : 0,
            'discount' => $discount,
            'tax' => null,
        ];

        if (! $includeProfit) {
            return $result;
        }

        $cogs = (float) SaleItem::query()
            ->whereHas('sale', function (Builder $query) use (
                $user,
                $all,
                $from,
                $to
            ): void {
                $query->whereBetween('sold_at', [$from, $to]);
                if (! $all) {
                    $query->where('cashier_id', $user->id);
                }
            })
            ->selectRaw(
                'COALESCE(SUM(quantity * unit_cost), 0) AS total'
            )
            ->value('total');

        $returnedCost = (float) SaleReturnItem::query()
            ->join(
                'sale_returns',
                'sale_returns.id',
                '=',
                'sale_return_items.sale_return_id'
            )
            ->join(
                'sale_items',
                'sale_items.id',
                '=',
                'sale_return_items.sale_item_id'
            )
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sale_returns.returned_at', [$from, $to])
            ->where('sale_return_items.condition', 'restock')
            ->when(
                ! $all,
                fn ($query) => $query->where(
                    'sales.cashier_id',
                    $user->id
                )
            )
            ->selectRaw(
                'COALESCE(SUM('
                .'sale_return_items.quantity * sale_items.unit_cost'
                .'), 0) AS total'
            )
            ->value('total');

        $expenses = (float) Expense::query()
            ->whereBetween(
                'expense_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->when(
                ! $all,
                fn (Builder $query): Builder => $query->where(
                    'created_by',
                    $user->id
                )
            )
            ->sum('amount');

        $netCogs = max(0, $cogs - $returnedCost);
        $grossProfit = $netSales - $netCogs;
        $netProfit = $grossProfit - $expenses;

        return array_merge($result, [
            'cogs' => $netCogs,
            'expenses' => $expenses,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'gross_margin' => $netSales > 0
                ? ($grossProfit / $netSales) * 100
                : 0,
            'net_margin' => $netSales > 0
                ? ($netProfit / $netSales) * 100
                : 0,
        ]);
    }

    private function paymentData(
        User $user,
        bool $all,
        Carbon $from,
        Carbon $to
    ): array {
        $methods = SalePayment::query()
            ->selectRaw('method, COALESCE(SUM(amount), 0) AS total')
            ->selectRaw('COUNT(*) AS transactions')
            ->whereBetween('paid_at', [$from, $to])
            ->whereHas('sale', function (Builder $query) use (
                $user,
                $all
            ): void {
                if (! $all) {
                    $query->where('cashier_id', $user->id);
                }
            })
            ->groupBy('method')
            ->orderByDesc('total')
            ->get()
            ->map(fn (SalePayment $payment): array => [
                'method' => $payment->method,
                'label' => $this->paymentLabel($payment->method),
                'icon' => $this->paymentIcon($payment->method),
                'total' => (float) $payment->total,
                'transactions' => (int) $payment->transactions,
            ])
            ->values();

        $receivables = $this->saleQuery($user, $all)
            ->withSum('returns as returned_total', 'total')
            ->whereBetween('sold_at', [$from, $to])
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->get(['id', 'total', 'paid_amount'])
            ->sum(fn (Sale $sale): float => max(
                0,
                (float) $sale->total
                    - (float) ($sale->returned_total ?? 0)
                    - (float) $sale->paid_amount
            ));

        return [
            'methods' => $methods,
            'total_received' => (float) $methods->sum('total'),
            'receivables' => (float) $receivables,
        ];
    }

    private function productData(
        User $user,
        bool $all,
        Carbon $from,
        Carbon $to
    ): array {
        $soldRows = SaleItem::query()
            ->selectRaw('product_id, product_name, sku')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN base_quantity > 0 '
                .'THEN base_quantity ELSE quantity END), 0) AS quantity_sold'
            )
            ->selectRaw('COALESCE(SUM(subtotal), 0) AS revenue')
            ->whereHas('sale', function (Builder $query) use (
                $user,
                $all,
                $from,
                $to
            ): void {
                $query->whereBetween('sold_at', [$from, $to]);
                if (! $all) {
                    $query->where('cashier_id', $user->id);
                }
            })
            ->groupBy('product_id', 'product_name', 'sku')
            ->get();

        $returnedRows = SaleReturnItem::query()
            ->join(
                'sale_returns',
                'sale_returns.id',
                '=',
                'sale_return_items.sale_return_id'
            )
            ->join(
                'sale_items',
                'sale_items.id',
                '=',
                'sale_return_items.sale_item_id'
            )
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sale_returns.returned_at', [$from, $to])
            ->when(
                ! $all,
                fn ($query) => $query->where(
                    'sales.cashier_id',
                    $user->id
                )
            )
            ->selectRaw('sale_return_items.product_id')
            ->selectRaw(
                'COALESCE(SUM('
                .'sale_return_items.quantity * '
                .'CASE WHEN sale_items.conversion_factor > 0 '
                .'THEN sale_items.conversion_factor ELSE 1 END'
                .'), 0) AS returned_quantity'
            )
            ->selectRaw(
                'COALESCE(SUM(sale_return_items.subtotal), 0) '
                .'AS returned_value'
            )
            ->groupBy('sale_return_items.product_id')
            ->get()
            ->keyBy('product_id');

        $products = Product::query()
            ->with(['unit', 'category'])
            ->whereIn('id', $soldRows->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $performance = $soldRows
            ->map(function ($row) use ($returnedRows, $products): array {
                $returned = $returnedRows->get($row->product_id);
                $product = $products->get($row->product_id);

                return [
                    'product_id' => (int) $row->product_id,
                    'name' => $row->product_name,
                    'sku' => $row->sku,
                    'unit' => $product?->unit?->symbol ?? 'dasar',
                    'category' => $product?->category?->name ?? 'Tanpa kategori',
                    'quantity' => max(
                        0,
                        (float) $row->quantity_sold
                            - (float) ($returned->returned_quantity ?? 0)
                    ),
                    'revenue' => max(
                        0,
                        (float) $row->revenue
                            - (float) ($returned->returned_value ?? 0)
                    ),
                ];
            })
            ->filter(fn (array $row): bool => $row['quantity'] > 0)
            ->sortByDesc('quantity')
            ->values();

        $stockProducts = Product::query()
            ->where('track_stock', true)
            ->where('is_active', true)
            ->withSum('stocks as stock_total', 'quantity')
            ->get(['id', 'minimum_stock']);

        $categoryPerformance = $performance
            ->groupBy('category')
            ->map(fn (Collection $rows, string $category): array => [
                'name' => $category,
                'quantity' => (float) $rows->sum('quantity'),
                'revenue' => (float) $rows->sum('revenue'),
            ])
            ->sortByDesc('revenue')
            ->values();

        return [
            'top' => $performance->first(),
            'least' => $performance->sortBy('quantity')->first(),
            'ranking' => $performance->take(7)->values(),
            'top_category' => $categoryPerformance->first(),
            'categories' => $categoryPerformance->take(6)->values(),
            'out_of_stock' => $stockProducts->filter(
                fn (Product $product): bool =>
                    (float) ($product->stock_total ?? 0) <= 0
            )->count(),
            'low_stock' => $stockProducts->filter(
                fn (Product $product): bool =>
                    (float) ($product->stock_total ?? 0) > 0
                    && (float) ($product->stock_total ?? 0)
                        <= (float) $product->minimum_stock
            )->count(),
        ];
    }

    private function customerData(
        User $user,
        bool $all,
        Carbon $from,
        Carbon $to
    ): array {
        $sales = $this->saleQuery($user, $all)
            ->whereBetween('sold_at', [$from, $to]);

        $memberRows = (clone $sales)
            ->whereNotNull('customer_id')
            ->selectRaw('customer_id, COUNT(*) AS transactions')
            ->selectRaw('COALESCE(SUM(total), 0) AS gross_value')
            ->groupBy('customer_id')
            ->get();

        $returnRows = $this->returnQuery($user, $all)
            ->join('sales', 'sales.id', '=', 'sale_returns.sale_id')
            ->whereBetween('sale_returns.returned_at', [$from, $to])
            ->whereNotNull('sales.customer_id')
            ->selectRaw('sales.customer_id')
            ->selectRaw('COALESCE(SUM(sale_returns.total), 0) AS total')
            ->groupBy('sales.customer_id')
            ->get()
            ->keyBy('customer_id');

        $customers = Customer::query()
            ->whereIn('id', $memberRows->pluck('customer_id'))
            ->get(['id', 'name', 'code', 'phone'])
            ->keyBy('id');

        $ranking = $memberRows
            ->map(function ($row) use ($returnRows, $customers): array {
                $customer = $customers->get($row->customer_id);

                return [
                    'customer_id' => (int) $row->customer_id,
                    'name' => $customer?->name ?? 'Pelanggan dihapus',
                    'code' => $customer?->code,
                    'phone' => $customer?->phone,
                    'transactions' => (int) $row->transactions,
                    'value' => max(
                        0,
                        (float) $row->gross_value
                            - (float) ($returnRows->get(
                                $row->customer_id
                            )?->total ?? 0)
                    ),
                ];
            })
            ->sortByDesc('value')
            ->values();

        $distinctCustomers = $memberRows->count();
        $repeatCustomers = $memberRows->filter(
            fn ($row): bool => (int) $row->transactions >= 2
        )->count();

        return [
            'customers' => $distinctCustomers,
            'new_customers' => Customer::query()
                ->whereBetween('created_at', [$from, $to])
                ->count(),
            'member_transactions' => (clone $sales)
                ->whereNotNull('customer_id')
                ->count(),
            'top_customer' => $ranking->first(),
            'ranking' => $ranking->take(7)->values(),
            'repeat_customers' => $repeatCustomers,
            'repeat_rate' => $distinctCustomers > 0
                ? ($repeatCustomers / $distinctCustomers) * 100
                : 0,
        ];
    }

    private function cashierData(
        User $user,
        bool $all,
        Carbon $from,
        Carbon $to
    ): array {
        $salesRows = $this->saleQuery($user, $all)
            ->whereBetween('sold_at', [$from, $to])
            ->selectRaw('cashier_id, COUNT(*) AS transactions')
            ->selectRaw('COALESCE(SUM(total), 0) AS gross_value')
            ->groupBy('cashier_id')
            ->get();

        $returns = $this->returnQuery($user, $all)
            ->join('sales', 'sales.id', '=', 'sale_returns.sale_id')
            ->whereBetween('sale_returns.returned_at', [$from, $to])
            ->selectRaw('sales.cashier_id')
            ->selectRaw('COALESCE(SUM(sale_returns.total), 0) AS total')
            ->groupBy('sales.cashier_id')
            ->get()
            ->keyBy('cashier_id');

        $users = User::query()
            ->whereIn('id', $salesRows->pluck('cashier_id'))
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        $ranking = $salesRows
            ->map(function ($row) use ($returns, $users): array {
                $cashier = $users->get($row->cashier_id);
                $net = max(
                    0,
                    (float) $row->gross_value
                        - (float) ($returns->get(
                            $row->cashier_id
                        )?->total ?? 0)
                );

                return [
                    'cashier_id' => (int) $row->cashier_id,
                    'name' => $cashier?->name ?? 'Pengguna dihapus',
                    'email' => $cashier?->email,
                    'transactions' => (int) $row->transactions,
                    'sales' => $net,
                    'average' => (int) $row->transactions > 0
                        ? $net / (int) $row->transactions
                        : 0,
                ];
            })
            ->sortByDesc('sales')
            ->values();

        $cashFlow = DB::table('cash_transactions')
            ->join(
                'cashier_shifts',
                'cashier_shifts.id',
                '=',
                'cash_transactions.cashier_shift_id'
            )
            ->whereBetween(
                'cash_transactions.created_at',
                [$from, $to]
            )
            ->when(
                ! $all,
                fn ($query) => $query->where(
                    'cashier_shifts.user_id',
                    $user->id
                )
            )
            ->selectRaw('cash_transactions.type')
            ->selectRaw('COALESCE(SUM(cash_transactions.amount), 0) AS total')
            ->groupBy('cash_transactions.type')
            ->pluck('total', 'type');

        $openShifts = CashierShift::query()
            ->with('user')
            ->where('status', 'open')
            ->when(
                ! $all,
                fn (Builder $query): Builder => $query->where(
                    'user_id',
                    $user->id
                )
            )
            ->latest('opened_at')
            ->get();

        return [
            'ranking' => $ranking->take(8)->values(),
            'top' => $ranking->first(),
            'open_shifts' => $openShifts,
            'open_count' => $openShifts->count(),
            'cash_in' => (float) ($cashFlow['in'] ?? 0),
            'cash_out' => (float) ($cashFlow['out'] ?? 0),
        ];
    }

    private function chartData(
        User $user,
        bool $all,
        Carbon $from,
        Carbon $to,
        bool $dashboard
    ): array {
        $daySpan = $from->copy()->startOfDay()->diffInDays(
            $to->copy()->startOfDay()
        );

        $primaryType = $from->isSameDay($to)
            ? 'hour'
            : ($daySpan <= 45 ? 'day' : 'month');

        $primary = $this->chartSeries(
            $user,
            $all,
            $from,
            $to,
            $primaryType
        );

        $anchor = $to->copy();

        $weekFrom = $anchor->copy()->startOfWeek();
        $weekTo = $anchor->copy()->endOfWeek();

        $monthFrom = $anchor->copy()->startOfMonth();
        $monthTo = $anchor->copy()->endOfMonth();

        $yearFrom = $anchor->copy()->startOfYear();
        $yearTo = $anchor->copy()->endOfYear();

        return [
            'primary' => $primary,
            'primary_type' => $primaryType,
            'primary_title' => match ($primaryType) {
                'hour' => 'Grafik Penjualan per Jam',
                'day' => 'Grafik Penjualan Harian',
                default => 'Grafik Penjualan Bulanan',
            },
            'primary_description' => match ($primaryType) {
                'hour' => 'Klik titik untuk melihat transaksi dan produk pada jam tersebut.',
                'day' => 'Klik titik untuk melihat transaksi dan produk pada tanggal tersebut.',
                default => 'Klik titik untuk melihat transaksi dan produk pada bulan tersebut.',
            },
            'primary_peak' => $primary->sortByDesc('value')->first(),
            'week' => [
                'label' => $weekFrom->translatedFormat('d M')
                    .' – '.$weekTo->translatedFormat('d M Y'),
                'points' => $this->chartSeries(
                    $user,
                    $all,
                    $weekFrom,
                    $weekTo,
                    'day'
                ),
            ],
            'month' => [
                'label' => $monthFrom->translatedFormat('F Y'),
                'points' => $this->chartSeries(
                    $user,
                    $all,
                    $monthFrom,
                    $monthTo,
                    'day'
                ),
            ],
            'year' => [
                'label' => $yearFrom->translatedFormat('Y'),
                'points' => $this->chartSeries(
                    $user,
                    $all,
                    $yearFrom,
                    $yearTo,
                    'month'
                ),
            ],
        ];
    }

    private function chartSeries(
        User $user,
        bool $all,
        Carbon $from,
        Carbon $to,
        string $type
    ): Collection {
        $salesRows = $this->saleQuery($user, $all)
            ->whereBetween('sold_at', [$from, $to])
            ->get(['sold_at', 'total']);

        $returnRows = $this->returnQuery($user, $all)
            ->whereBetween('returned_at', [$from, $to])
            ->get(['returned_at', 'total']);

        if ($type === 'hour') {
            $date = $from->copy()->startOfDay();

            $rows = collect(range(0, 23))->map(
                function (int $hour) use (
                    $date,
                    $salesRows,
                    $returnRows
                ): array {
                    $grossSales = $salesRows->filter(
                        fn (Sale $sale): bool =>
                            $sale->sold_at->isSameDay($date)
                            && $sale->sold_at->hour === $hour
                    );
                    $returns = $returnRows->filter(
                        fn (SaleReturn $return): bool =>
                            $return->returned_at->isSameDay($date)
                            && $return->returned_at->hour === $hour
                    );

                    return [
                        'key' => $date->format('Y-m-d')
                            .'-'.str_pad(
                                (string) $hour,
                                2,
                                '0',
                                STR_PAD_LEFT
                            ),
                        'detail_type' => 'hour',
                        'label' => str_pad(
                            (string) $hour,
                            2,
                            '0',
                            STR_PAD_LEFT
                        ).':00',
                        'short_label' => str_pad(
                            (string) $hour,
                            2,
                            '0',
                            STR_PAD_LEFT
                        ),
                        'value' => max(
                            0,
                            (float) $grossSales->sum('total')
                                - (float) $returns->sum('total')
                        ),
                        'transactions' => $grossSales->count(),
                    ];
                }
            );

            return $this->withPercent($rows);
        }

        if ($type === 'month') {
            $start = $from->copy()->startOfMonth();
            $end = $to->copy()->startOfMonth();
            $monthCount = $start->diffInMonths($end);

            $salesByMonth = $salesRows->groupBy(
                fn (Sale $sale): string =>
                    $sale->sold_at->format('Y-m')
            );
            $returnsByMonth = $returnRows->groupBy(
                fn (SaleReturn $return): string =>
                    $return->returned_at->format('Y-m')
            );

            $rows = collect(range(0, $monthCount))->map(
                function (int $index) use (
                    $start,
                    $salesByMonth,
                    $returnsByMonth
                ): array {
                    $month = $start->copy()->addMonths($index);
                    $key = $month->format('Y-m');
                    $grossSales = $salesByMonth[$key] ?? collect();
                    $returns = $returnsByMonth[$key] ?? collect();

                    return [
                        'key' => $key,
                        'detail_type' => 'month',
                        'label' => $month->translatedFormat('F Y'),
                        'short_label' => $month->translatedFormat('M'),
                        'value' => max(
                            0,
                            (float) $grossSales->sum('total')
                                - (float) $returns->sum('total')
                        ),
                        'transactions' => $grossSales->count(),
                    ];
                }
            );

            return $this->withPercent($rows);
        }

        $start = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        $dayCount = $start->diffInDays($end);

        $salesByDay = $salesRows->groupBy(
            fn (Sale $sale): string =>
                $sale->sold_at->format('Y-m-d')
        );
        $returnsByDay = $returnRows->groupBy(
            fn (SaleReturn $return): string =>
                $return->returned_at->format('Y-m-d')
        );

        $rows = collect(range(0, $dayCount))->map(
            function (int $index) use (
                $start,
                $salesByDay,
                $returnsByDay
            ): array {
                $date = $start->copy()->addDays($index);
                $key = $date->format('Y-m-d');
                $grossSales = $salesByDay[$key] ?? collect();
                $returns = $returnsByDay[$key] ?? collect();

                return [
                    'key' => $key,
                    'detail_type' => 'day',
                    'label' => $date->translatedFormat('d F Y'),
                    'short_label' => $date->translatedFormat('d M'),
                    'value' => max(
                        0,
                        (float) $grossSales->sum('total')
                            - (float) $returns->sum('total')
                    ),
                    'transactions' => $grossSales->count(),
                ];
            }
        );

        return $this->withPercent($rows);
    }

    private function comparisonData(
        User $user,
        bool $all,
        Carbon $anchor
    ): array {
        $currentFrom = $anchor->copy()->startOfMonth();
        $currentTo = $anchor->copy()->endOfDay();
        $previousFrom = $currentFrom->copy()->subMonth()->startOfMonth();
        $previousTo = $previousFrom->copy()->addDays(
            min(
                $anchor->day - 1,
                $previousFrom->daysInMonth - 1
            )
        )->endOfDay();
        $yearFrom = $currentFrom->copy()->subYear();
        $yearTo = $currentTo->copy()->subYear();

        $current = $this->netSalesBetween(
            $user,
            $all,
            $currentFrom,
            $currentTo
        );
        $previous = $this->netSalesBetween(
            $user,
            $all,
            $previousFrom,
            $previousTo
        );
        $lastYear = $this->netSalesBetween(
            $user,
            $all,
            $yearFrom,
            $yearTo
        );

        return [
            'month_current' => $current,
            'month_previous' => $previous,
            'month_change' => $this->growth($current, $previous),
            'year_previous' => $lastYear,
            'year_change' => $this->growth($current, $lastYear),
        ];
    }

    private function inventoryData(Carbon $from, Carbon $to): array
    {
        $stocks = Stock::query()
            ->with(['product.unit', 'warehouse'])
            ->get();

        $movements = StockMovement::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('direction, COALESCE(SUM(quantity), 0) AS total')
            ->groupBy('direction')
            ->pluck('total', 'direction');

        $expired = ProductBatch::query()
            ->where('quantity', '>', 0)
            ->whereNotNull('expired_at')
            ->whereDate('expired_at', '<', today())
            ->count();

        $expiring = ProductBatch::query()
            ->with(['product', 'warehouse'])
            ->where('quantity', '>', 0)
            ->whereNotNull('expired_at')
            ->whereBetween(
                'expired_at',
                [today(), today()->addDays(30)]
            )
            ->orderBy('expired_at')
            ->take(7)
            ->get();

        return [
            'value' => (float) $stocks->sum(
                fn (Stock $stock): float =>
                    (float) $stock->quantity
                    * (float) $stock->product->average_cost
            ),
            'quantity' => (float) $stocks->sum('quantity'),
            'incoming' => (float) ($movements['in'] ?? 0),
            'outgoing' => (float) ($movements['out'] ?? 0),
            'expired' => $expired,
            'expiring_count' => $expiring->count(),
            'expiring' => $expiring,
        ];
    }

    private function notificationData(
        User $user,
        bool $all,
        array $access
    ): array {
        $stockProducts = $access['stock']
            ? Product::query()
                ->where('track_stock', true)
                ->where('is_active', true)
                ->with('unit:id,name,symbol')
                ->withSum('stocks as stock_total', 'quantity')
                ->get([
                    'id',
                    'unit_id',
                    'name',
                    'sku',
                    'minimum_stock',
                ])
            : collect();

        $outOfStockItems = $stockProducts
            ->filter(
                fn (Product $product): bool =>
                    (float) ($product->stock_total ?? 0) <= 0
            )
            ->sortBy('name')
            ->take(5)
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantity' => (float) ($product->stock_total ?? 0),
                'minimum' => (float) $product->minimum_stock,
                'unit' => $product->unit?->symbol ?? 'dasar',
            ])
            ->values();

        $lowStockItems = $stockProducts
            ->filter(
                fn (Product $product): bool =>
                    (float) ($product->stock_total ?? 0) > 0
                    && (float) ($product->stock_total ?? 0)
                        <= (float) $product->minimum_stock
            )
            ->sortBy('stock_total')
            ->take(5)
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantity' => (float) ($product->stock_total ?? 0),
                'minimum' => (float) $product->minimum_stock,
                'unit' => $product->unit?->symbol ?? 'dasar',
            ])
            ->values();

        $overdueReceivables = 0;
        $overduePayables = 0;

        if ($access['debts']) {
            $overdueReceivables = $this->saleQuery($user, $all)
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', today())
                ->count();

            $overduePayables = Purchase::query()
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', today())
                ->count();
        }

        return [
            'out_of_stock' => $stockProducts->filter(
                fn (Product $product): bool =>
                    (float) ($product->stock_total ?? 0) <= 0
            )->count(),
            'low_stock' => $stockProducts->filter(
                fn (Product $product): bool =>
                    (float) ($product->stock_total ?? 0) > 0
                    && (float) ($product->stock_total ?? 0)
                        <= (float) $product->minimum_stock
            )->count(),
            'overdue_receivables' => $overdueReceivables,
            'overdue_payables' => $overduePayables,
            'expired' => $access['stock']
                ? ProductBatch::query()
                    ->where('quantity', '>', 0)
                    ->whereNotNull('expired_at')
                    ->whereDate('expired_at', '<', today())
                    ->count()
                : 0,
            'expiring' => $access['stock']
                ? ProductBatch::query()
                    ->where('quantity', '>', 0)
                    ->whereNotNull('expired_at')
                    ->whereBetween(
                        'expired_at',
                        [today(), today()->addDays(30)]
                    )
                    ->count()
                : 0,
            'out_of_stock_items' => $outOfStockItems,
            'low_stock_items' => $lowStockItems,
        ];
    }

    private function activityData(
        User $user,
        bool $all,
        Carbon $from,
        Carbon $to,
        bool $includeAudit
    ): array {
        $latest = $this->saleQuery($user, $all)
            ->with(['customer', 'cashier'])
            ->whereBetween('sold_at', [$from, $to])
            ->latest('sold_at')
            ->take(5)
            ->get();

        return [
            'latest_transactions' => $latest->map(
                fn (Sale $sale): array => [
                    'time' => $sale->sold_at->format('H:i'),
                    'number' => $sale->number,
                    'party' => $sale->customer?->name
                        ?? 'Pelanggan Umum',
                    'staff' => $sale->cashier?->name ?? '-',
                    'status' => $sale->payment_status,
                    'value' => (float) $sale->total,
                ]
            )->values(),
            'latest_sales' => $latest,
            'latest_returns' => $this->returnQuery($user, $all)
                ->with(['sale.customer'])
                ->whereBetween('returned_at', [$from, $to])
                ->latest('returned_at')
                ->take(5)
                ->get(),
            'cancelled' => $this->saleQuery($user, $all)
                ->whereBetween('sold_at', [$from, $to])
                ->where('status', 'cancelled')
                ->count(),
            'logs' => $includeAudit
                ? ActivityLog::query()
                    ->with('user')
                    ->latest()
                    ->take(8)
                    ->get()
                : collect(),
        ];
    }

    private function netSalesBetween(
        User $user,
        bool $all,
        Carbon $from,
        Carbon $to
    ): float {
        $sales = (float) $this->saleQuery($user, $all)
            ->whereBetween('sold_at', [$from, $to])
            ->sum('total');
        $returns = (float) $this->returnQuery($user, $all)
            ->whereBetween('returned_at', [$from, $to])
            ->sum('total');

        return max(0, $sales - $returns);
    }

    private function withPercent(Collection $rows): Collection
    {
        $max = max(1, (float) $rows->max('value'));

        return $rows->map(function (array $row) use ($max): array {
            $row['percent'] = (float) $row['value'] > 0
                ? max(4, (int) round(($row['value'] / $max) * 100))
                : 0;

            return $row;
        });
    }

    private function growth(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return (($current - $previous) / $previous) * 100;
    }

    private function paymentLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'Tunai',
            'qris' => 'QRIS',
            'debit' => 'Debit',
            'ewallet' => 'E-Wallet',
            'transfer' => 'Transfer',
            'credit' => 'Kartu Kredit',
            default => str($method)->replace('_', ' ')->title()->toString(),
        };
    }

    private function paymentIcon(string $method): string
    {
        return match ($method) {
            'cash' => 'fa-money-bill-wave',
            'qris' => 'fa-qrcode',
            'debit', 'credit' => 'fa-credit-card',
            'ewallet' => 'fa-wallet',
            'transfer' => 'fa-building-columns',
            default => 'fa-circle-dollar-to-slot',
        };
    }
}
