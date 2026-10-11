<?php

namespace App\Http\Controllers;

use App\Exports\DebtReportExport;
use App\Exports\OperationalReportExport;
use App\Exports\ProfitReportExport;
use App\Exports\SalesExport;
use App\Exports\StocksExport;
use App\Models\Expense;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Stock;
use App\Models\User;
use App\Services\BusinessAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function analytics(
        Request $request,
        BusinessAnalyticsService $analyticsService
    ): View {
        $filters = $request->validate([
            'mode' => [
                'nullable',
                'in:sales,purchases,expenses',
            ],
            'range' => [
                'nullable',
                'in:today,this_week,last_week,this_month,last_month,'
                .'this_year,day,week,month,year,custom',
            ],
            'date' => ['nullable', 'date'],
            'week' => [
                'nullable',
                'regex:/^\\d{4}-W\\d{2}$/',
            ],
            'month' => ['nullable', 'date_format:Y-m'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'cashier_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
        ]);

        [$from, $to, $period] = $this->analyticsPeriod(
            $filters
        );

        $canViewPurchases = $request->user()->can(
            'purchases.view'
        );
        $canViewExpenses = $request->user()->can(
            'expenses.view'
        );

        $requestedMode = $filters['mode'] ?? 'sales';

        $mode = match (true) {
            $requestedMode === 'purchases'
                && $canViewPurchases => 'purchases',
            $requestedMode === 'expenses'
                && $canViewExpenses => 'expenses',
            default => 'sales',
        };

        $canChooseUser = match ($mode) {
            'purchases' => $canViewPurchases,
            'expenses' => $canViewExpenses,
            default => $request->user()->can('reports.sales'),
        };

        $cashierId = $canChooseUser
            ? ($filters['cashier_id'] ?? null)
            : null;

        $analytics = $analyticsService->report(
            $request->user(),
            $from,
            $to,
            $cashierId ? (int) $cashierId : null,
            $mode
        );

        $cashiers = $canChooseUser
            ? User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
            : collect();

        $currentWeek = now()->startOfWeek();

        $earliestDate = match ($mode) {
            'purchases' => Purchase::query()->min(
                'purchase_date'
            ),
            'expenses' => Expense::query()->min(
                'expense_date'
            ),
            default => Sale::query()->min('sold_at'),
        };

        $earliestWeek = $earliestDate
            ? Carbon::parse($earliestDate)->startOfWeek()
            : now()->startOfYear()->startOfWeek();

        if ($from->copy()->startOfWeek()->lt($earliestWeek)) {
            $earliestWeek = $from->copy()->startOfWeek();
        }

        if ($earliestWeek->gt($currentWeek)) {
            $earliestWeek = $currentWeek->copy();
        }

        $weekCount = $earliestWeek->diffInWeeks(
            $currentWeek
        );

        $weekOptions = collect(range(0, $weekCount))
            ->map(function (int $offset) use (
                $currentWeek
            ): array {
                $start = $currentWeek
                    ->copy()
                    ->subWeeks($offset);
                $end = $start->copy()->endOfWeek();

                if ($end->isFuture()) {
                    $end = now()->endOfDay();
                }

                return [
                    'value' => $start->format('o-\WW'),
                    'label' => 'Minggu '
                        .$start->format('W')
                        .' · '
                        .$start->translatedFormat('d M')
                        .' – '
                        .$end->translatedFormat('d M Y'),
                    'start' => $start->timestamp,
                ];
            })
            ->values();

        return view('reports.analytics', compact(
            'from',
            'to',
            'analytics',
            'cashiers',
            'cashierId',
            'period',
            'mode',
            'canViewPurchases',
            'canViewExpenses',
            'weekOptions'
        ));
    }

    public function analyticsDetail(
        Request $request,
        BusinessAnalyticsService $analyticsService
    ): JsonResponse {
        $data = $request->validate([
            'mode' => [
                'nullable',
                'in:sales,purchases,expenses',
            ],
            'metric' => ['required', 'in:hour,day,month'],
            'key' => ['required', 'string', 'max:20'],
            'cashier_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
        ]);

        $requestedMode = $data['mode'] ?? 'sales';

        $mode = match (true) {
            $requestedMode === 'purchases'
                && $request->user()->can(
                    'purchases.view'
                ) => 'purchases',
            $requestedMode === 'expenses'
                && $request->user()->can(
                    'expenses.view'
                ) => 'expenses',
            default => 'sales',
        };

        $canChooseUser = match ($mode) {
            'purchases' => $request->user()->can(
                'purchases.view'
            ),
            'expenses' => $request->user()->can(
                'expenses.view'
            ),
            default => $request->user()->can(
                'reports.sales'
            ),
        };

        $cashierId = $canChooseUser
            ? ($data['cashier_id'] ?? null)
            : null;

        try {
            $detail = $analyticsService->pointDetail(
                $request->user(),
                $data['metric'],
                $data['key'],
                $cashierId ? (int) $cashierId : null,
                $mode
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json($detail);
    }

    public function profit(Request $request): View
    {
        [$from, $to] = $this->period($request);
        $data = $this->profitData($from, $to);

        $days = max(1, $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1);
        $previousTo = $from->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subDays($days - 1)->startOfDay();
        $comparison = $this->profitData($previousFrom, $previousTo);

        return view('reports.profit', compact(
            'from',
            'to',
            'data',
            'comparison',
            'previousFrom',
            'previousTo'
        ));
    }

    public function debts(Request $request): View
    {
        $filters = $this->debtFilters($request);
        $receivablesQuery = $this->receivablesQuery($filters);
        $payablesQuery = $this->payablesQuery($filters);

        $receivableSummaryRows = (clone $receivablesQuery)
            ->withSum('returns', 'total')
            ->get(['sales.id', 'sales.total', 'sales.paid_amount', 'sales.due_date']);

        $payableSummaryRows = (clone $payablesQuery)
            ->withSum('returns', 'total')
            ->get(['purchases.id', 'purchases.total', 'purchases.paid_amount', 'purchases.due_date']);

        $receivableTotal = (float) $receivableSummaryRows->sum(
            fn (Sale $sale): float => $sale->balance
        );
        $payableTotal = (float) $payableSummaryRows->sum(
            fn (Purchase $purchase): float => $purchase->balance
        );

        $summary = [
            'receivable_count' => $receivableSummaryRows->count(),
            'receivable_total' => $receivableTotal,
            'receivable_overdue' => (float) $receivableSummaryRows
                ->filter(fn (Sale $sale): bool => $sale->due_date?->lt(today()) ?? false)
                ->sum(fn (Sale $sale): float => $sale->balance),
            'payable_count' => $payableSummaryRows->count(),
            'payable_total' => $payableTotal,
            'payable_overdue' => (float) $payableSummaryRows
                ->filter(fn (Purchase $purchase): bool => $purchase->due_date?->lt(today()) ?? false)
                ->sum(fn (Purchase $purchase): float => $purchase->balance),
            'net_position' => $receivableTotal - $payableTotal,
        ];

        return view('reports.debts', [
            'receivables' => $receivablesQuery
                ->withSum('returns', 'total')
                ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('due_date')
                ->orderByDesc('id')
                ->paginate(15, ['*'], 'receivables')
                ->withQueryString(),
            'payables' => $payablesQuery
                ->withSum('returns', 'total')
                ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('due_date')
                ->orderByDesc('id')
                ->paginate(15, ['*'], 'payables')
                ->withQueryString(),
            'filters' => $filters,
            'summary' => $summary,
        ]);
    }

    public function salesExcel(Request $request)
    {
        $filters = $this->salesFilters($request);

        return Excel::download(
            new SalesExport($filters),
            'laporan-penjualan-'
                .substr($filters['from'], 0, 10)
                .'-'
                .substr($filters['to'], 0, 10)
                .'.xlsx'
        );
    }

    public function stocksExcel(Request $request)
    {
        return Excel::download(
            new StocksExport($this->stockFilters($request)),
            'laporan-stok-'.today()->format('Ymd').'.xlsx'
        );
    }

    public function profitExcel(Request $request)
    {
        [$from, $to] = $this->period($request);

        return Excel::download(
            new ProfitReportExport($from, $to, $this->profitData($from, $to)),
            'laporan-laba-rugi-'.$from->format('Ymd').'-'.$to->format('Ymd').'.xlsx'
        );
    }

    public function debtsExcel(Request $request)
    {
        return Excel::download(
            new DebtReportExport($this->debtFilters($request)),
            'laporan-utang-piutang-'.today()->format('Ymd').'.xlsx'
        );
    }

    public function salesPdf(Request $request)
    {
        $filters = $this->salesFilters($request);
        $sales = $this->salesQuery($filters)
            ->with(['cashier', 'customer', 'payments'])
            ->withSum('returns', 'total')
            ->orderBy('sold_at')
            ->get();

        $summary = [
            'transactions' => $sales->count(),
            'gross' => (float) $sales->sum('total'),
            'returns' => (float) $sales->sum('returns_sum_total'),
            'net' => (float) $sales->sum(fn (Sale $sale): float => $sale->net_total),
            'paid' => (float) $sales->sum('paid_amount'),
            'balance' => (float) $sales->sum(fn (Sale $sale): float => $sale->balance),
        ];

        return Pdf::loadView('reports.pdf.sales', [
            'sales' => $sales,
            'from' => $filters['from_carbon'],
            'to' => $filters['to_carbon'],
            'summary' => $summary,
        ])->setPaper('a4', 'landscape')->download('laporan-penjualan.pdf');
    }

    public function stocksPdf(Request $request)
    {
        $filters = $this->stockFilters($request);
        $stocks = $this->stocksQuery($filters)
            ->with([
                'product.category',
                'product.unit',
                'product.conversionUnits.unit',
                'warehouse',
            ])
            ->addSelect($this->expiredQuantitySelect())
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->get();

        $summary = [
            'rows' => $stocks->count(),
            'quantity' => (float) $stocks->sum('quantity'),
            'inventory_value' => (float) $stocks->sum(
                fn (Stock $stock): float => (float) $stock->quantity
                    * (float) $stock->product->average_cost
            ),
            'expired_value' => (float) $stocks->sum(
                fn (Stock $stock): float => (float) $stock->expired_quantity
                    * (float) $stock->product->average_cost
            ),
            'low_count' => $stocks->filter(
                fn (Stock $stock): bool => (float) $stock->quantity > 0
                    && (float) $stock->quantity <= (float) $stock->product->minimum_stock
            )->count(),
            'out_count' => $stocks->filter(
                fn (Stock $stock): bool => (float) $stock->quantity <= 0
            )->count(),
        ];

        return Pdf::loadView('reports.pdf.stocks', compact('stocks', 'summary'))
            ->setPaper('a4', 'landscape')
            ->download('laporan-stok.pdf');
    }

    public function profitPdf(Request $request)
    {
        [$from, $to] = $this->period($request);
        $data = $this->profitData($from, $to);

        return Pdf::loadView('reports.pdf.profit', compact('from', 'to', 'data'))
            ->setPaper('a4', 'portrait')
            ->download('laporan-laba-rugi.pdf');
    }

    public function debtsPdf(Request $request)
    {
        $filters = $this->debtFilters($request);
        $receivables = $this->receivablesQuery($filters)
            ->withSum('returns', 'total')
            ->orderBy('due_date')
            ->get();
        $payables = $this->payablesQuery($filters)
            ->withSum('returns', 'total')
            ->orderBy('due_date')
            ->get();

        return Pdf::loadView(
            'reports.pdf.debts',
            compact('receivables', 'payables', 'filters')
        )->setPaper('a4', 'landscape')->download('laporan-utang-piutang.pdf');
    }

    public function operationalExcel(Request $request, string $type)
    {
        [$from, $to, $search] = $this->operationalFilters($request, $type);

        return Excel::download(
            new OperationalReportExport(
                $type,
                $from->toDateString(),
                $to->toDateString(),
                $search
            ),
            'laporan-'.$type.'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.xlsx'
        );
    }

    public function operationalPdf(Request $request, string $type)
    {
        [$from, $to, $search] = $this->operationalFilters($request, $type);
        $export = new OperationalReportExport(
            $type,
            $from->toDateString(),
            $to->toDateString(),
            $search
        );

        return Pdf::loadView('reports.pdf.operations', [
            'type' => $type,
            'title' => $export->title(),
            'headings' => $export->headings(),
            'rows' => $export->rawRows(),
            'from' => $from,
            'to' => $to,
        ])->setPaper('a4', 'landscape')->download('laporan-'.$type.'.pdf');
    }

    private function profitData(Carbon $from, Carbon $to): array
    {
        $sales = Sale::query()
            ->whereBetween('sold_at', [$from, $to])
            ->selectRaw(
                'COUNT(*) transactions,
                 COALESCE(SUM(subtotal),0) subtotal,
                 COALESCE(SUM(promotion_discount + manual_discount),0) discounts,
                 COALESCE(SUM(total),0) sales_total,
                 COALESCE(SUM(paid_amount),0) paid_total'
            )
            ->first();

        $returns = SaleReturn::query()
            ->whereBetween('returned_at', [$from, $to])
            ->selectRaw(
                'COUNT(*) transactions,
                 COALESCE(SUM(total),0) value,
                 COALESCE(SUM(refund_amount),0) refunds'
            )
            ->first();

        $salesCogs = (float) SaleItem::query()
            ->whereHas(
                'sale',
                fn (Builder $query) => $query->whereBetween('sold_at', [$from, $to])
            )
            ->selectRaw('COALESCE(SUM(quantity * unit_cost),0) value')
            ->value('value');

        $returnedCogs = (float) SaleReturnItem::query()
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
            ->whereBetween('sale_returns.returned_at', [$from, $to])
            ->where('sale_return_items.condition', 'restock')
            ->selectRaw(
                'COALESCE(SUM(sale_return_items.quantity * sale_items.unit_cost),0) value'
            )
            ->value('value');

        $expenses = Expense::query()
            ->whereBetween(
                'expense_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->selectRaw('COUNT(*) transactions, COALESCE(SUM(amount),0) value')
            ->first();

        $grossSales = (float) $sales->sales_total;
        $returnValue = (float) $returns->value;
        $netRevenue = $grossSales - $returnValue;
        $netCogs = max(0, $salesCogs - $returnedCogs);
        $grossProfit = $netRevenue - $netCogs;
        $operatingExpenses = (float) $expenses->value;
        $netProfit = $grossProfit - $operatingExpenses;

        $dailySales = Sale::query()
            ->whereBetween('sold_at', [$from, $to])
            ->selectRaw('DATE(sold_at) report_date, COALESCE(SUM(total),0) value')
            ->groupByRaw('DATE(sold_at)')
            ->pluck('value', 'report_date');

        $dailyReturns = SaleReturn::query()
            ->whereBetween('returned_at', [$from, $to])
            ->selectRaw('DATE(returned_at) report_date, COALESCE(SUM(total),0) value')
            ->groupByRaw('DATE(returned_at)')
            ->pluck('value', 'report_date');

        $dailyExpenses = Expense::query()
            ->whereBetween(
                'expense_date',
                [$from->toDateString(), $to->toDateString()]
            )
            ->selectRaw('expense_date report_date, COALESCE(SUM(amount),0) value')
            ->groupBy('expense_date')
            ->pluck('value', 'report_date');

        $daily = collect();
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $saleValue = (float) ($dailySales[$date] ?? 0);
            $returnDay = (float) ($dailyReturns[$date] ?? 0);
            $expenseDay = (float) ($dailyExpenses[$date] ?? 0);

            $daily->push([
                'date' => $cursor->copy(),
                'sales' => $saleValue,
                'returns' => $returnDay,
                'net_sales' => $saleValue - $returnDay,
                'expenses' => $expenseDay,
            ]);

            $cursor->addDay();
        }

        return [
            'sales_transactions' => (int) $sales->transactions,
            'sales_subtotal' => (float) $sales->subtotal,
            'sales_discounts' => (float) $sales->discounts,
            'gross_sales' => $grossSales,
            'return_transactions' => (int) $returns->transactions,
            'returns' => $returnValue,
            'refunds' => (float) $returns->refunds,
            'net_revenue' => $netRevenue,
            'sales_cogs' => $salesCogs,
            'returned_cogs' => $returnedCogs,
            'net_cogs' => $netCogs,
            'gross_profit' => $grossProfit,
            'expense_transactions' => (int) $expenses->transactions,
            'expenses' => $operatingExpenses,
            'net_profit' => $netProfit,
            'gross_margin' => $netRevenue > 0
                ? ($grossProfit / $netRevenue) * 100
                : 0,
            'net_margin' => $netRevenue > 0
                ? ($netProfit / $netRevenue) * 100
                : 0,
            'average_transaction' => (int) $sales->transactions > 0
                ? $netRevenue / (int) $sales->transactions
                : 0,
            'daily' => $daily,
        ];
    }

    private function salesQuery(array $filters): Builder
    {
        return Sale::query()
            ->whereBetween('sold_at', [$filters['from'], $filters['to']])
            ->when(
                $filters['search'] ?? null,
                function (Builder $query, string $value): void {
                    $terms = preg_split('/\s+/', trim($value)) ?: [];

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
                }
            )
            ->when(
                $filters['payment_status'] ?? null,
                fn (Builder $query, string $value) => $query->where('payment_status', $value)
            )
            ->when(
                $filters['payment_method'] ?? null,
                fn (Builder $query, string $value) => $query->whereHas(
                    'payments',
                    fn (Builder $payment) => $payment->where('method', $value)
                )
            )
            ->when(
                $filters['cashier_id'] ?? null,
                fn (Builder $query, int|string $value) => $query->where('cashier_id', $value)
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $value) => $query->where('status', $value)
            );
    }

    private function stocksQuery(array $filters): Builder
    {
        return Stock::query()
            ->when(
                $filters['warehouse_id'] ?? null,
                fn (Builder $query, int|string $value) => $query->where('warehouse_id', $value)
            )
            ->when(
                $filters['category_id'] ?? null,
                fn (Builder $query, int|string $value) => $query->whereHas(
                    'product',
                    fn (Builder $product) => $product->where('product_category_id', $value)
                )
            )
            ->when(
                $filters['search'] ?? null,
                function (Builder $query, string $value): void {
                    $terms = preg_split('/\s+/', trim($value)) ?: [];

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
                }
            )
            ->when(
                ($filters['stock_status'] ?? 'all') === 'out',
                fn (Builder $query) => $query->where('quantity', '<=', 0)
            )
            ->when(
                ($filters['stock_status'] ?? 'all') === 'low',
                fn (Builder $query) => $query
                    ->where('quantity', '>', 0)
                    ->whereRaw(
                        'stocks.quantity <= (select minimum_stock from products where products.id = stocks.product_id)'
                    )
            )
            ->when(
                ($filters['stock_status'] ?? 'all') === 'healthy',
                fn (Builder $query) => $query->whereRaw(
                    'stocks.quantity > (select minimum_stock from products where products.id = stocks.product_id)'
                )
            )
            ->when(
                $filters['expired_only'] ?? false,
                fn (Builder $query) => $query->whereExists(function ($sub): void {
                    $sub->selectRaw('1')
                        ->from('product_batches')
                        ->whereColumn('product_batches.product_id', 'stocks.product_id')
                        ->whereColumn('product_batches.warehouse_id', 'stocks.warehouse_id')
                        ->where('product_batches.quantity', '>', 0)
                        ->whereNotNull('product_batches.expired_at')
                        ->whereDate('product_batches.expired_at', '<', today());
                })
            );
    }

    private function expiredQuantitySelect(): array
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
        ];
    }

    private function receivablesQuery(array $filters): Builder
    {
        return Sale::query()
            ->with(['customer', 'cashier'])
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->when(
                $filters['search'] !== '',
                function (Builder $query) use ($filters): void {
                    $like = '%'.$filters['search'].'%';
                    $query->where(function (Builder $search) use ($like): void {
                        $search
                            ->where('number', 'like', $like)
                            ->orWhereHas('customer', function (Builder $customer) use ($like): void {
                                $customer
                                    ->where('name', 'like', $like)
                                    ->orWhere('phone', 'like', $like)
                                    ->orWhere('code', 'like', $like);
                            });
                    });
                }
            )
            ->when(
                $filters['due_status'] === 'overdue',
                fn (Builder $query) => $query
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', today())
            )
            ->when(
                $filters['due_status'] === 'due_7',
                fn (Builder $query) => $query->whereBetween(
                    'due_date',
                    [today(), today()->addDays(7)]
                )
            )
            ->when(
                $filters['due_status'] === 'no_due',
                fn (Builder $query) => $query->whereNull('due_date')
            );
    }

    private function payablesQuery(array $filters): Builder
    {
        return Purchase::query()
            ->with(['supplier', 'warehouse'])
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->when(
                $filters['search'] !== '',
                function (Builder $query) use ($filters): void {
                    $like = '%'.$filters['search'].'%';
                    $query->where(function (Builder $search) use ($like): void {
                        $search
                            ->where('number', 'like', $like)
                            ->orWhere('supplier_invoice', 'like', $like)
                            ->orWhereHas('supplier', function (Builder $supplier) use ($like): void {
                                $supplier
                                    ->where('name', 'like', $like)
                                    ->orWhere('phone', 'like', $like)
                                    ->orWhere('code', 'like', $like);
                            });
                    });
                }
            )
            ->when(
                $filters['due_status'] === 'overdue',
                fn (Builder $query) => $query
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', today())
            )
            ->when(
                $filters['due_status'] === 'due_7',
                fn (Builder $query) => $query->whereBetween(
                    'due_date',
                    [today(), today()->addDays(7)]
                )
            )
            ->when(
                $filters['due_status'] === 'no_due',
                fn (Builder $query) => $query->whereNull('due_date')
            );
    }

    private function analyticsPeriod(array $filters): array
    {
        $range = $filters['range'] ?? 'today';
        $now = now();

        if ($range === 'this_week') {
            $from = $now->copy()->startOfWeek();
            $to = $now->copy()->endOfWeek();
        } elseif ($range === 'last_week') {
            $from = $now->copy()->subWeek()->startOfWeek();
            $to = $from->copy()->endOfWeek();
        } elseif ($range === 'this_month') {
            $from = $now->copy()->startOfMonth();
            $to = $now->copy()->endOfMonth();
        } elseif ($range === 'last_month') {
            $from = $now->copy()->subMonthNoOverflow()->startOfMonth();
            $to = $from->copy()->endOfMonth();
        } elseif ($range === 'this_year') {
            $from = $now->copy()->startOfYear();
            $to = $now->copy()->endOfYear();
        } elseif ($range === 'day') {
            $from = isset($filters['date'])
                ? Carbon::parse($filters['date'])->startOfDay()
                : $now->copy()->startOfDay();
            $to = $from->copy()->endOfDay();
        } elseif (
            $range === 'week'
            && isset($filters['week'])
            && preg_match(
                '/^(\\d{4})-W(\\d{2})$/',
                $filters['week'],
                $match
            )
        ) {
            $from = $now->copy()
                ->setISODate((int) $match[1], (int) $match[2])
                ->startOfDay();
            $to = $from->copy()->endOfWeek();
        } elseif ($range === 'month' && isset($filters['month'])) {
            $from = Carbon::createFromFormat(
                'Y-m',
                $filters['month']
            )->startOfMonth();
            $to = $from->copy()->endOfMonth();
        } elseif ($range === 'year' && isset($filters['year'])) {
            $from = Carbon::create(
                (int) $filters['year'],
                1,
                1
            )->startOfYear();
            $to = $from->copy()->endOfYear();
        } elseif ($range === 'custom') {
            $from = isset($filters['from'])
                ? Carbon::parse($filters['from'])->startOfDay()
                : $now->copy()->startOfMonth();
            $to = isset($filters['to'])
                ? Carbon::parse($filters['to'])->endOfDay()
                : $now->copy()->endOfDay();
        } else {
            $range = 'today';
            $from = $now->copy()->startOfDay();
            $to = $now->copy()->endOfDay();
        }

        if ($to->isFuture()) {
            $to = now()->endOfDay();
        }

        return [
            $from,
            $to,
            [
                'range' => $range,
                'date' => $from->toDateString(),
                'week' => $from->format('o-\\WW'),
                'month' => $from->format('Y-m'),
                'year' => $from->year,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ];
    }

    private function period(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($data['from'])
            ? Carbon::parse($data['from'])->startOfDay()
            : now()->startOfMonth();
        $to = isset($data['to'])
            ? Carbon::parse($data['to'])->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }

    private function salesFilters(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:100'],
            'payment_status' => ['nullable', 'in:paid,partial,unpaid'],
            'payment_method' => ['nullable', 'in:cash,qris,transfer,debit,ewallet'],
            'cashier_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'in:completed,partially_returned,returned'],
        ]);

        [$from, $to] = $this->period($request);

        return array_merge($data, [
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'from_carbon' => $from,
            'to_carbon' => $to,
        ]);
    }

    private function stockFilters(Request $request): array
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'stock_status' => ['nullable', 'in:all,healthy,low,out'],
            'expired_only' => ['nullable', 'boolean'],
        ]);

        $data['stock_status'] = $data['stock_status'] ?? 'all';
        $data['expired_only'] = $request->boolean('expired_only');

        return $data;
    }

    private function debtFilters(Request $request): array
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'due_status' => ['nullable', 'in:all,overdue,due_7,no_due'],
        ]);

        return [
            'search' => trim((string) ($data['search'] ?? '')),
            'due_status' => $data['due_status'] ?? 'all',
        ];
    }

    private function operationalFilters(Request $request, string $type): array
    {
        $types = [
            'purchases',
            'movements',
            'expiry',
            'shifts',
            'expenses',
            'sale_returns',
            'purchase_returns',
            'opnames',
        ];

        abort_unless(in_array($type, $types, true), 404);
        [$from, $to] = $this->period($request);
        $search = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ])['search'] ?? '';

        return [$from, $to, trim($search)];
    }
}
