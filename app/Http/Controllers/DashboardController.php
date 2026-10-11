<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CashierShift;
use App\Models\Expense;
use App\Models\ProductBatch;
use App\Models\Promotion;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Stock;
use App\Models\StockOpname;
use App\Models\User;
use App\Support\DashboardAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $access = DashboardAccess::forUser($user);
        $canViewAllSales = $user->can('reports.sales');

        $dashboard = [
            'access' => $access,
            'role_names' => $user->getRoleNames()
                ->map(fn (string $role): string => str($role)
                    ->replace('_', ' ')
                    ->title()
                    ->toString())
                ->values(),
            'workspace' => $this->workspaceDescription($access),
            'quick_actions' => $this->quickActions($user),
            'sales_scope' => [
                'mode' => $canViewAllSales ? 'all' : 'mine',
                'label' => $canViewAllSales
                    ? 'Semua kasir'
                    : 'Transaksi saya',
                'description' => $canViewAllSales
                    ? 'Ringkasan mencakup seluruh kasir.'
                    : 'Ringkasan hanya mencakup transaksi akun ini.',
            ],
            'generated_at' => now(),
        ];

        if ($access['sales']) {
            $dashboard['sales'] = $this->salesData(
                $user,
                $canViewAllSales
            );
        }

        if ($access['profit']) {
            $dashboard['profit'] = $this->profitData(
                $user,
                $canViewAllSales,
                $dashboard['sales']['net_today'] ?? null
            );
        }

        if ($access['stock']) {
            $dashboard['stock'] = $this->stockData();
        }

        if ($access['purchases']) {
            $dashboard['purchases'] = $this->purchaseData();
        }

        if ($access['debts']) {
            $dashboard['debts'] = $this->debtData();
        }

        if ($access['expenses']) {
            $dashboard['expenses'] = $this->expenseData();
        }

        if ($access['cashier']) {
            $dashboard['shifts'] = $this->shiftData($user->id);
        }

        if ($access['promotions']) {
            $dashboard['promotions'] = $this->promotionData();
        }

        if ($access['opname']) {
            $dashboard['opnames'] = $this->opnameData();
        }

        if ($access['users']) {
            $dashboard['users'] = $this->userData();
        }

        if ($access['activity']) {
            $dashboard['activities'] = ActivityLog::query()
                ->with('user')
                ->latest()
                ->take(8)
                ->get();
        }

        $dashboard['priorities'] = $this->priorities(
            $dashboard,
            $user
        );
        $dashboard['kpis'] = $this->kpis($dashboard);

        return view('dashboard', compact('dashboard'));
    }

    private function saleQuery(
        User $user,
        bool $canViewAllSales
    ): Builder {
        return Sale::query()
            ->when(
                ! $canViewAllSales,
                fn (Builder $query): Builder => $query->where(
                    'cashier_id',
                    $user->id
                )
            );
    }

    private function saleReturnQuery(
        User $user,
        bool $canViewAllSales
    ): Builder {
        return SaleReturn::query()
            ->when(
                ! $canViewAllSales,
                fn (Builder $query): Builder => $query->whereHas(
                    'sale',
                    fn (Builder $saleQuery): Builder => $saleQuery->where(
                        'cashier_id',
                        $user->id
                    )
                )
            );
    }

    private function salesData(
        User $user,
        bool $canViewAllSales
    ): array {
        $grossToday = (float) $this
            ->saleQuery($user, $canViewAllSales)
            ->whereDate('sold_at', today())
            ->sum('total');

        $returnsToday = (float) $this
            ->saleReturnQuery($user, $canViewAllSales)
            ->whereDate('returned_at', today())
            ->sum('total');

        $netToday = max(0, $grossToday - $returnsToday);

        $transactionsToday = $this
            ->saleQuery($user, $canViewAllSales)
            ->whereDate('sold_at', today())
            ->count();

        $paidToday = (float) $this
            ->saleQuery($user, $canViewAllSales)
            ->whereDate('sold_at', today())
            ->sum('paid_amount');

        $startDate = today()->subDays(6);

        $salesRows = $this
            ->saleQuery($user, $canViewAllSales)
            ->whereDate('sold_at', '>=', $startDate)
            ->get(['sold_at', 'total'])
            ->groupBy(fn (Sale $sale): string => $sale->sold_at
                ->format('Y-m-d'));

        $returnRows = $this
            ->saleReturnQuery($user, $canViewAllSales)
            ->whereDate('returned_at', '>=', $startDate)
            ->get(['returned_at', 'total'])
            ->groupBy(fn (SaleReturn $return): string => $return
                ->returned_at
                ->format('Y-m-d'));

        $trend = collect(range(0, 6))
            ->map(function (int $index) use (
                $startDate,
                $salesRows,
                $returnRows
            ): array {
                $date = $startDate->copy()->addDays($index);
                $key = $date->format('Y-m-d');

                $gross = (float) ($salesRows[$key] ?? collect())
                    ->sum('total');

                $returns = (float) ($returnRows[$key] ?? collect())
                    ->sum('total');

                return [
                    'date' => $key,
                    'label' => $date->translatedFormat('D'),
                    'full_label' => $date->translatedFormat('d M'),
                    'value' => max(0, $gross - $returns),
                ];
            });

        $maxTrend = max(
            1,
            (float) $trend->max('value')
        );

        $trend = $trend->map(function (array $point) use ($maxTrend): array {
            $point['percent'] = max(
                3,
                (int) round(
                    ((float) $point['value'] / $maxTrend) * 100
                )
            );

            return $point;
        });

        return [
            'scope' => $canViewAllSales ? 'all' : 'mine',
            'gross_today' => $grossToday,
            'returns_today' => $returnsToday,
            'net_today' => $netToday,
            'transactions_today' => $transactionsToday,
            'paid_today' => $paidToday,
            'average_today' => $transactionsToday > 0
                ? $netToday / $transactionsToday
                : 0,
            'unpaid_today' => $this
                ->saleQuery($user, $canViewAllSales)
                ->whereDate('sold_at', today())
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->count(),
            'trend' => $trend,
            'recent' => $this
                ->saleQuery($user, $canViewAllSales)
                ->with(['customer', 'cashier'])
                ->withSum('returns as returned_total', 'total')
                ->latest('sold_at')
                ->take(7)
                ->get(),
        ];
    }

    private function profitData(
        User $user,
        bool $canViewAllSales,
        ?float $netSalesToday = null
    ): array {
        $netSalesToday ??= max(
            0,
            (float) $this
                ->saleQuery($user, $canViewAllSales)
                ->whereDate('sold_at', today())
                ->sum('total')
            - (float) $this
                ->saleReturnQuery($user, $canViewAllSales)
                ->whereDate('returned_at', today())
                ->sum('total')
        );

        $cogsToday = (float) SaleItem::query()
            ->whereHas(
                'sale',
                function (Builder $query) use (
                    $user,
                    $canViewAllSales
                ): void {
                    $query->whereDate('sold_at', today());

                    if (! $canViewAllSales) {
                        $query->where('cashier_id', $user->id);
                    }
                }
            )
            ->selectRaw(
                'COALESCE(SUM(quantity * unit_cost), 0) AS total'
            )
            ->value('total');

        $returnedCostToday = (float) SaleReturnItem::query()
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
            ->join(
                'sales',
                'sales.id',
                '=',
                'sale_items.sale_id'
            )
            ->whereDate('sale_returns.returned_at', today())
            ->where('sale_return_items.condition', 'restock')
            ->when(
                ! $canViewAllSales,
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

        $netCogs = max(0, $cogsToday - $returnedCostToday);

        $expensesToday = (float) Expense::query()
            ->whereDate('expense_date', today())
            ->when(
                ! $canViewAllSales,
                fn (Builder $query): Builder => $query->where(
                    'created_by',
                    $user->id
                )
            )
            ->sum('amount');

        $grossProfit = $netSalesToday - $netCogs;
        $netProfit = $grossProfit - $expensesToday;

        return [
            'scope' => $canViewAllSales ? 'all' : 'mine',
            'cogs_today' => $netCogs,
            'expenses_today' => $expensesToday,
            'gross_profit_today' => $grossProfit,
            'net_profit_today' => $netProfit,
            'gross_margin' => $netSalesToday > 0
                ? ($grossProfit / $netSalesToday) * 100
                : 0,
            'net_margin' => $netSalesToday > 0
                ? ($netProfit / $netSalesToday) * 100
                : 0,
        ];
    }

    private function stockData(): array
    {
        $stocks = Stock::query()
            ->with(['product.unit', 'warehouse'])
            ->whereHas('product', fn ($query) => $query
                ->where('track_stock', true)
                ->where('is_active', true))
            ->get();

        $lowStocks = $stocks
            ->filter(fn (Stock $stock): bool =>
                (float) $stock->quantity
                    <= (float) $stock->product->minimum_stock
            )
            ->sortBy(fn (Stock $stock): float => (float) $stock->quantity)
            ->take(8)
            ->values();

        $warehouseSummary = $stocks
            ->groupBy('warehouse_id')
            ->map(function (Collection $warehouseStocks): array {
                $first = $warehouseStocks->first();

                return [
                    'id' => $first->warehouse_id,
                    'name' => $first->warehouse?->name ?? '-',
                    'products' => $warehouseStocks
                        ->pluck('product_id')
                        ->unique()
                        ->count(),
                    'quantity' => (float) $warehouseStocks
                        ->sum('quantity'),
                    'value' => (float) $warehouseStocks
                        ->sum(fn (Stock $stock): float =>
                            (float) $stock->quantity
                            * (float) $stock->product->average_cost
                        ),
                ];
            })
            ->sortByDesc('value')
            ->take(5)
            ->values();

        return [
            'stock_value' => (float) $stocks
                ->sum(fn (Stock $stock): float =>
                    (float) $stock->quantity
                    * (float) $stock->product->average_cost
                ),
            'total_quantity' => (float) $stocks->sum('quantity'),
            'product_count' => $stocks
                ->pluck('product_id')
                ->unique()
                ->count(),
            'low_count' => $lowStocks->count(),
            'out_count' => $stocks
                ->filter(fn (Stock $stock): bool =>
                    (float) $stock->quantity <= 0
                )
                ->count(),
            'low_stocks' => $lowStocks,
            'warehouse_summary' => $warehouseSummary,
            'expiring' => ProductBatch::query()
                ->with(['product.unit', 'warehouse'])
                ->where('quantity', '>', 0)
                ->whereBetween(
                    'expired_at',
                    [today(), today()->addDays(30)]
                )
                ->orderBy('expired_at')
                ->take(8)
                ->get(),
            'expiring_count' => ProductBatch::query()
                ->where('quantity', '>', 0)
                ->whereBetween(
                    'expired_at',
                    [today(), today()->addDays(30)]
                )
                ->count(),
            'expired_count' => ProductBatch::query()
                ->where('quantity', '>', 0)
                ->whereNotNull('expired_at')
                ->whereDate('expired_at', '<', today())
                ->count(),
        ];
    }

    private function purchaseData(): array
    {
        $grossToday = (float) Purchase::query()
            ->whereDate('purchase_date', today())
            ->sum('total');

        $returnsToday = (float) PurchaseReturn::query()
            ->whereDate('returned_at', today())
            ->sum('total');

        return [
            'count_today' => Purchase::query()
                ->whereDate('purchase_date', today())
                ->count(),
            'gross_today' => $grossToday,
            'returns_today' => $returnsToday,
            'net_today' => max(0, $grossToday - $returnsToday),
            'recent' => Purchase::query()
                ->with(['supplier', 'warehouse'])
                ->withSum('returns as returned_total', 'total')
                ->latest('purchase_date')
                ->latest('id')
                ->take(6)
                ->get(),
        ];
    }

    private function debtData(): array
    {
        $receivables = Sale::query()
            ->with('customer')
            ->withSum('returns as returned_total', 'total')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->get()
            ->map(function (Sale $sale): Sale {
                $balance = max(
                    0,
                    (float) $sale->total
                    - (float) ($sale->returned_total ?? 0)
                    - (float) $sale->paid_amount
                );

                $sale->setAttribute('dashboard_balance', $balance);

                return $sale;
            })
            ->filter(fn (Sale $sale): bool =>
                (float) $sale->dashboard_balance > 0
            );

        $payables = Purchase::query()
            ->with('supplier')
            ->withSum('returns as returned_total', 'total')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->get()
            ->map(function (Purchase $purchase): Purchase {
                $balance = max(
                    0,
                    (float) $purchase->total
                    - (float) ($purchase->returned_total ?? 0)
                    - (float) $purchase->paid_amount
                );

                $purchase->setAttribute(
                    'dashboard_balance',
                    $balance
                );

                return $purchase;
            })
            ->filter(fn (Purchase $purchase): bool =>
                (float) $purchase->dashboard_balance > 0
            );

        $overdueReceivables = $receivables
            ->filter(fn (Sale $sale): bool =>
                $sale->due_date
                && $sale->due_date->lt(today())
            )
            ->sortBy('due_date')
            ->values();

        $overduePayables = $payables
            ->filter(fn (Purchase $purchase): bool =>
                $purchase->due_date
                && $purchase->due_date->lt(today())
            )
            ->sortBy('due_date')
            ->values();

        return [
            'receivables' => (float) $receivables
                ->sum('dashboard_balance'),
            'payables' => (float) $payables
                ->sum('dashboard_balance'),
            'receivable_count' => $receivables->count(),
            'payable_count' => $payables->count(),
            'overdue_receivable_count' => $overdueReceivables->count(),
            'overdue_payable_count' => $overduePayables->count(),
            'overdue_receivable_value' => (float) $overdueReceivables
                ->sum('dashboard_balance'),
            'overdue_payable_value' => (float) $overduePayables
                ->sum('dashboard_balance'),
            'overdue_receivables' => $overdueReceivables
                ->take(5),
            'overdue_payables' => $overduePayables
                ->take(5),
        ];
    }

    private function expenseData(): array
    {
        return [
            'today' => (float) Expense::query()
                ->whereDate('expense_date', today())
                ->sum('amount'),
            'month' => (float) Expense::query()
                ->whereBetween(
                    'expense_date',
                    [
                        now()->startOfMonth()->toDateString(),
                        now()->endOfMonth()->toDateString(),
                    ]
                )
                ->sum('amount'),
            'count_today' => Expense::query()
                ->whereDate('expense_date', today())
                ->count(),
            'top_categories' => Expense::query()
                ->selectRaw(
                    'category, COALESCE(SUM(amount), 0) AS total'
                )
                ->whereBetween(
                    'expense_date',
                    [
                        now()->startOfMonth()->toDateString(),
                        now()->endOfMonth()->toDateString(),
                    ]
                )
                ->groupBy('category')
                ->orderByDesc('total')
                ->take(5)
                ->get(),
            'recent' => Expense::query()
                ->with('creator')
                ->latest('expense_date')
                ->latest('id')
                ->take(6)
                ->get(),
        ];
    }

    private function shiftData(int $userId): array
    {
        return [
            'mine' => CashierShift::query()
                ->where('user_id', $userId)
                ->where('status', 'open')
                ->latest('opened_at')
                ->first(),
            'open_count' => CashierShift::query()
                ->where('status', 'open')
                ->count(),
            'open_shifts' => CashierShift::query()
                ->with('user')
                ->where('status', 'open')
                ->latest('opened_at')
                ->take(5)
                ->get(),
        ];
    }

    private function promotionData(): array
    {
        return [
            'active_count' => Promotion::query()
                ->currentlyActive()
                ->count(),
            'scheduled_count' => Promotion::query()
                ->where('is_active', true)
                ->where('starts_at', '>', now())
                ->count(),
            'active' => Promotion::query()
                ->currentlyActive()
                ->orderBy('ends_at')
                ->take(5)
                ->get(),
        ];
    }

    private function opnameData(): array
    {
        return [
            'draft_count' => StockOpname::query()
                ->where('status', 'draft')
                ->count(),
            'submitted_count' => StockOpname::query()
                ->where('status', 'submitted')
                ->count(),
            'pending' => StockOpname::query()
                ->with(['warehouse', 'creator'])
                ->withCount('items')
                ->whereIn('status', ['draft', 'submitted'])
                ->latest('opname_date')
                ->take(5)
                ->get(),
        ];
    }

    private function userData(): array
    {
        return [
            'total' => User::query()->count(),
            'active' => User::query()
                ->where('is_active', true)
                ->count(),
            'inactive' => User::query()
                ->where('is_active', false)
                ->count(),
            'unverified' => User::query()
                ->whereNull('email_verified_at')
                ->count(),
            'two_factor' => config(
                'pos-features.two_factor_enabled',
                false
            )
                ? User::query()
                    ->whereNotNull('two_factor_confirmed_at')
                    ->count()
                : 0,
        ];
    }

    private function quickActions(User $user): array
    {
        $actions = [
            [
                'permission' => 'sales.create',
                'route' => 'sales.pos',
                'label' => 'Buka Kasir',
                'description' => 'Mulai transaksi penjualan',
                'icon' => 'fa-cash-register',
                'tone' => 'primary',
            ],
            [
                'permission' => 'purchases.create',
                'route' => 'purchases.create',
                'label' => 'Tambah Pembelian',
                'description' => 'Catat barang dari supplier',
                'icon' => 'fa-cart-plus',
                'tone' => 'success',
            ],
            [
                'permission' => 'stock.adjust',
                'route' => 'stocks.adjustment',
                'label' => 'Penyesuaian Stok',
                'description' => 'Koreksi saldo persediaan',
                'icon' => 'fa-sliders',
                'tone' => 'warning',
            ],
            [
                'permission' => 'opname.create',
                'route' => 'opnames.create',
                'label' => 'Buat Opname',
                'description' => 'Mulai penghitungan fisik',
                'icon' => 'fa-clipboard-check',
                'tone' => 'info',
            ],
            [
                'permission' => 'expenses.create',
                'route' => 'expenses.index',
                'label' => 'Catat Pengeluaran',
                'description' => 'Masukkan biaya operasional',
                'icon' => 'fa-money-bill-transfer',
                'tone' => 'danger',
            ],
            [
                'permission' => 'products.create',
                'route' => 'products.index',
                'label' => 'Tambah Barang',
                'description' => 'Kelola master barang',
                'icon' => 'fa-box-open',
                'tone' => 'purple',
            ],
            [
                'permission' => 'users.manage',
                'route' => 'users.index',
                'label' => 'Kelola Pengguna',
                'description' => 'Atur akun dan role',
                'icon' => 'fa-users-gear',
                'tone' => 'slate',
            ],
            [
                'permission' => 'settings.manage',
                'route' => 'settings.edit',
                'label' => 'Pengaturan POS',
                'description' => 'Identitas dan konfigurasi',
                'icon' => 'fa-gears',
                'tone' => 'slate',
            ],
        ];

        return collect($actions)
            ->filter(fn (array $action): bool =>
                $user->can($action['permission'])
            )
            ->take(6)
            ->values()
            ->all();
    }

    private function priorities(array $dashboard, User $user): array
    {
        $priorities = collect();

        if (isset($dashboard['stock'])) {
            if ($dashboard['stock']['out_count'] > 0) {
                $priorities->push([
                    'tone' => 'danger',
                    'icon' => 'fa-box-open',
                    'title' => $dashboard['stock']['out_count']
                        .' stok gudang habis',
                    'description' => 'Segera lakukan pembelian, transfer, atau penyesuaian.',
                    'route' => $user->can('reports.stocks')
                        ? route('reports.stocks')
                        : route('stocks.movements'),
                ]);
            }

            if ($dashboard['stock']['expiring_count'] > 0) {
                $priorities->push([
                    'tone' => 'warning',
                    'icon' => 'fa-calendar-xmark',
                    'title' => $dashboard['stock']['expiring_count']
                        .' batch kedaluwarsa ≤ 30 hari',
                    'description' => 'Prioritaskan penjualan menggunakan FEFO.',
                    'route' => route('stocks.batches'),
                ]);
            }
        }

        if (isset($dashboard['debts'])) {
            if ($dashboard['debts']['overdue_receivable_count'] > 0) {
                $priorities->push([
                    'tone' => 'warning',
                    'icon' => 'fa-hand-holding-dollar',
                    'title' => $dashboard['debts']['overdue_receivable_count']
                        .' piutang terlambat',
                    'description' => 'Nilai Rp '
                        .number_format(
                            $dashboard['debts']['overdue_receivable_value'],
                            0,
                            ',',
                            '.'
                        ).'.',
                    'route' => $user->can('reports.debts')
                        ? route('reports.debts', ['due' => 'overdue'])
                        : null,
                ]);
            }

            if ($dashboard['debts']['overdue_payable_count'] > 0) {
                $priorities->push([
                    'tone' => 'danger',
                    'icon' => 'fa-file-invoice-dollar',
                    'title' => $dashboard['debts']['overdue_payable_count']
                        .' utang supplier terlambat',
                    'description' => 'Nilai Rp '
                        .number_format(
                            $dashboard['debts']['overdue_payable_value'],
                            0,
                            ',',
                            '.'
                        ).'.',
                    'route' => $user->can('reports.debts')
                        ? route('reports.debts', ['due' => 'overdue'])
                        : null,
                ]);
            }
        }

        if (
            isset($dashboard['opnames'])
            && $dashboard['opnames']['submitted_count'] > 0
        ) {
            $priorities->push([
                'tone' => 'info',
                'icon' => 'fa-clipboard-check',
                'title' => $dashboard['opnames']['submitted_count']
                    .' opname menunggu persetujuan',
                'description' => 'Periksa selisih sebelum diterapkan ke stok.',
                'route' => route('opnames.index', [
                    'status' => 'submitted',
                ]),
            ]);
        }

        if (
            isset($dashboard['shifts'])
            && $user->can('sales.create')
            && ! $dashboard['shifts']['mine']
        ) {
            $priorities->push([
                'tone' => 'primary',
                'icon' => 'fa-cash-register',
                'title' => 'Shift kasir Anda belum dibuka',
                'description' => 'Buka shift sebelum menerima transaksi tunai.',
                'route' => $user->can('shifts.manage')
                    ? route('shifts.index')
                    : null,
            ]);
        }

        return $priorities->take(6)->values()->all();
    }

    private function kpis(array $dashboard): array
    {
        $kpis = collect();

        if (isset($dashboard['sales'])) {
            $kpis->push([
                'label' => $dashboard['sales']['scope'] === 'mine'
                    ? 'Penjualan Saya Hari Ini'
                    : 'Penjualan Bersih Hari Ini',
                'value' => $dashboard['sales']['net_today'],
                'format' => 'money',
                'icon' => 'fa-chart-column',
                'tone' => 'blue',
                'foot' => number_format(
                    $dashboard['sales']['transactions_today']
                ).' transaksi',
            ]);

            $kpis->push([
                'label' => $dashboard['sales']['scope'] === 'mine'
                    ? 'Rata-rata Transaksi Saya'
                    : 'Rata-rata Transaksi',
                'value' => $dashboard['sales']['average_today'],
                'format' => 'money',
                'icon' => 'fa-receipt',
                'tone' => 'cyan',
                'foot' => number_format(
                    $dashboard['sales']['unpaid_today']
                ).' belum lunas',
            ]);
        }

        if (isset($dashboard['profit'])) {
            $kpis->push([
                'label' => ($dashboard['profit']['scope'] ?? 'all')
                    === 'mine'
                    ? 'Estimasi Laba Transaksi Saya'
                    : 'Estimasi Laba Bersih',
                'value' => $dashboard['profit']['net_profit_today'],
                'format' => 'money',
                'icon' => 'fa-wallet',
                'tone' => $dashboard['profit']['net_profit_today'] >= 0
                    ? 'green'
                    : 'red',
                'foot' => number_format(
                    $dashboard['profit']['net_margin'],
                    1,
                    ',',
                    '.'
                ).'% margin bersih',
            ]);
        }

        if (isset($dashboard['stock'])) {
            $kpis->push([
                'label' => 'Nilai Persediaan',
                'value' => $dashboard['stock']['stock_value'],
                'format' => 'money',
                'icon' => 'fa-boxes-stacked',
                'tone' => 'purple',
                'foot' => number_format(
                    $dashboard['stock']['product_count']
                ).' barang tersimpan',
            ]);

            $kpis->push([
                'label' => 'Stok Butuh Perhatian',
                'value' => $dashboard['stock']['low_count'],
                'format' => 'number',
                'icon' => 'fa-triangle-exclamation',
                'tone' => $dashboard['stock']['low_count'] > 0
                    ? 'orange'
                    : 'green',
                'foot' => number_format(
                    $dashboard['stock']['out_count']
                ).' stok habis',
            ]);
        }

        if (isset($dashboard['purchases'])) {
            $kpis->push([
                'label' => 'Pembelian Bersih Hari Ini',
                'value' => $dashboard['purchases']['net_today'],
                'format' => 'money',
                'icon' => 'fa-cart-shopping',
                'tone' => 'gold',
                'foot' => number_format(
                    $dashboard['purchases']['count_today']
                ).' transaksi',
            ]);
        }

        if (isset($dashboard['debts'])) {
            $kpis->push([
                'label' => 'Piutang Aktif',
                'value' => $dashboard['debts']['receivables'],
                'format' => 'money',
                'icon' => 'fa-hand-holding-dollar',
                'tone' => 'orange',
                'foot' => number_format(
                    $dashboard['debts']['receivable_count']
                ).' transaksi',
            ]);

            $kpis->push([
                'label' => 'Utang Supplier',
                'value' => $dashboard['debts']['payables'],
                'format' => 'money',
                'icon' => 'fa-file-invoice-dollar',
                'tone' => 'red',
                'foot' => number_format(
                    $dashboard['debts']['payable_count']
                ).' transaksi',
            ]);
        }

        if (isset($dashboard['expenses'])) {
            $kpis->push([
                'label' => 'Pengeluaran Hari Ini',
                'value' => $dashboard['expenses']['today'],
                'format' => 'money',
                'icon' => 'fa-money-bill-transfer',
                'tone' => 'red',
                'foot' => number_format(
                    $dashboard['expenses']['count_today']
                ).' catatan biaya',
            ]);
        }

        if (isset($dashboard['promotions'])) {
            $kpis->push([
                'label' => 'Promo Aktif',
                'value' => $dashboard['promotions']['active_count'],
                'format' => 'number',
                'icon' => 'fa-tags',
                'tone' => 'purple',
                'foot' => number_format(
                    $dashboard['promotions']['scheduled_count']
                ).' terjadwal',
            ]);
        }

        if (isset($dashboard['opnames'])) {
            $kpis->push([
                'label' => 'Opname Tertunda',
                'value' => $dashboard['opnames']['draft_count']
                    + $dashboard['opnames']['submitted_count'],
                'format' => 'number',
                'icon' => 'fa-clipboard-check',
                'tone' => 'cyan',
                'foot' => number_format(
                    $dashboard['opnames']['submitted_count']
                ).' menunggu persetujuan',
            ]);
        }

        if (isset($dashboard['users'])) {
            $kpis->push([
                'label' => 'Pengguna Aktif',
                'value' => $dashboard['users']['active'],
                'format' => 'number',
                'icon' => 'fa-users',
                'tone' => 'blue',
                'foot' => config(
                    'pos-features.two_factor_enabled',
                    false
                )
                    ? number_format(
                        $dashboard['users']['two_factor']
                    ).' memakai 2FA'
                    : number_format(
                        $dashboard['users']['unverified']
                    ).' belum verifikasi email',
            ]);
        }

        return $kpis->take(9)->values()->all();
    }

    private function workspaceDescription(array $access): array
    {
        if (
            $access['profit']
            || $access['debts']
            || $access['users']
            || $access['activity']
        ) {
            return [
                'label' => 'Pusat Kendali Operasional',
                'description' => 'Pantau kondisi usaha, prioritas, dan aktivitas penting sesuai hak akses Anda.',
                'icon' => 'fa-gauge-high',
            ];
        }

        if (
            $access['stock']
            || $access['purchases']
            || $access['opname']
        ) {
            return [
                'label' => 'Ruang Kerja Persediaan',
                'description' => 'Fokus pada ketersediaan barang, pembelian, batch, dan ketepatan stok.',
                'icon' => 'fa-warehouse',
            ];
        }

        if ($access['cashier'] || $access['sales']) {
            return [
                'label' => 'Ruang Kerja Kasir',
                'description' => 'Lihat status shift, transaksi hari ini, dan akses cepat menuju kasir.',
                'icon' => 'fa-cash-register',
            ];
        }

        return [
            'label' => 'Dashboard Anda',
            'description' => 'Panel akan bertambah saat role memperoleh permission modul.',
            'icon' => 'fa-table-columns',
        ];
    }
}
