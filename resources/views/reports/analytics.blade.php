@extends('layouts.pos')

@section('title', 'Ringkasan POS')
@section('body_class', 'pos-summary-page')

@section('content')
@php
    $isPurchase = ($mode ?? 'sales') === 'purchases';
    $isExpense = ($mode ?? 'sales') === 'expenses';
    $isSales = ! $isPurchase && ! $isExpense;
    $overview = $analytics['overview'] ?? [];
    $payments = $analytics['payments'] ?? [
        'methods' => collect(),
        'total_received' => 0,
        'receivables' => 0,
    ];
    $products = $analytics['products'] ?? [
        'ranking' => collect(),
        'categories' => collect(),
    ];
    $customers = $analytics['customers'] ?? [
        'ranking' => collect(),
    ];
    $cashiersData = $analytics['cashiers'] ?? [
        'ranking' => collect(),
    ];
    $charts = $analytics['charts'] ?? [
        'primary' => collect(),
        'week' => ['points' => collect(), 'label' => '-'],
        'month' => ['points' => collect(), 'label' => '-'],
        'year' => ['points' => collect(), 'label' => '-'],
    ];
    $comparisons = $analytics['comparisons'] ?? [];
    $inventory = $analytics['inventory'] ?? null;
    $expenseStats = $analytics['expense_stats'] ?? null;
    $notifications = $analytics['notifications'] ?? null;
    $activities = $analytics['activities'] ?? null;
    $scope = $analytics['scope'] ?? [
        'mode' => 'mine',
        'label' => 'Transaksi saya',
        'cashier_id' => auth()->id(),
    ];

    $queryBase = array_filter([
        'mode' => $mode,
        'cashier_id' => $cashierId,
    ], fn ($value) => $value !== null && $value !== '');

    $modeUrl = static fn (string $newMode): string => route(
        'reports.analytics',
        array_merge(
            request()->except(['mode']),
            ['mode' => $newMode]
        )
    );

    $latestTransactions = collect(
        $activities['latest_transactions'] ?? []
    );

    $summaryUrl = static fn (
        string $range,
        array $extra = []
    ): string => route(
        'reports.analytics',
        array_merge($queryBase, ['range' => $range], $extra)
    );

    $today = now()->startOfDay();
    $currentWeek = now()->startOfWeek();
    $currentMonth = now()->startOfMonth();
    $currentYear = now()->startOfYear();

    $dayRanges = ['today', 'day'];
    $weekRanges = ['this_week', 'last_week', 'week'];
    $monthRanges = ['this_month', 'last_month', 'month'];
    $yearRanges = ['this_year', 'year'];

    $dayAnchor = in_array(
        $period['range'],
        $dayRanges,
        true
    )
        ? $from->copy()->startOfDay()
        : $today->copy();

    $weekAnchor = in_array(
        $period['range'],
        $weekRanges,
        true
    )
        ? $from->copy()->startOfWeek()
        : $currentWeek->copy();

    $monthAnchor = in_array(
        $period['range'],
        $monthRanges,
        true
    )
        ? $from->copy()->startOfMonth()
        : $currentMonth->copy();

    $yearAnchor = in_array(
        $period['range'],
        $yearRanges,
        true
    )
        ? $from->copy()->startOfYear()
        : $currentYear->copy();

    $dayNext = $dayAnchor->copy()->addDay();
    $weekNext = $weekAnchor->copy()->addWeek();
    $monthNext = $monthAnchor->copy()->addMonth();
    $yearNext = $yearAnchor->copy()->addYear();

    $periodNavigators = [
        [
            'key' => 'day',
            'title' => 'Hari',
            'icon' => 'fa-calendar-day',
            'active' => in_array(
                $period['range'],
                $dayRanges,
                true
            ),
            'previous_url' => $summaryUrl('day', [
                'date' => $dayAnchor
                    ->copy()
                    ->subDay()
                    ->toDateString(),
            ]),
            'current_url' => $dayAnchor->isSameDay($today)
                ? $summaryUrl('today')
                : $summaryUrl('day', [
                    'date' => $dayAnchor->toDateString(),
                ]),
            'next_url' => $dayNext->lte($today)
                ? $summaryUrl('day', [
                    'date' => $dayNext->toDateString(),
                ])
                : null,
            'label' => $dayAnchor->isSameDay($today)
                ? 'Hari Ini'
                : $dayAnchor->translatedFormat('d M Y'),
            'caption' => $dayAnchor
                ->translatedFormat('l'),
        ],
        [
            'key' => 'week',
            'title' => 'Minggu',
            'icon' => 'fa-calendar-week',
            'active' => in_array(
                $period['range'],
                $weekRanges,
                true
            ),
            'previous_url' => $summaryUrl('week', [
                'week' => $weekAnchor
                    ->copy()
                    ->subWeek()
                    ->format('o-\WW'),
            ]),
            'current_url' => $weekAnchor->isSameDay(
                $currentWeek
            )
                ? $summaryUrl('this_week')
                : $summaryUrl('week', [
                    'week' => $weekAnchor->format('o-\WW'),
                ]),
            'next_url' => $weekNext->lte($currentWeek)
                ? $summaryUrl('week', [
                    'week' => $weekNext->format('o-\WW'),
                ])
                : null,
            'label' => $weekAnchor->isSameDay($currentWeek)
                ? 'Minggu Ini'
                : 'Minggu '.$weekAnchor->format('W'),
            'caption' => $weekAnchor
                ->translatedFormat('d M')
                .' – '
                .$weekAnchor
                    ->copy()
                    ->endOfWeek()
                    ->translatedFormat('d M Y'),
        ],
        [
            'key' => 'month',
            'title' => 'Bulan',
            'icon' => 'fa-calendar',
            'active' => $period['range'] === 'this_month'
                || $period['range'] === 'last_month'
                || $period['range'] === 'month',
            'previous_url' => $summaryUrl('month', [
                'month' => $monthAnchor
                    ->copy()
                    ->subMonth()
                    ->format('Y-m'),
            ]),
            'current_url' => $monthAnchor->isSameDay(
                $currentMonth
            )
                ? $summaryUrl('this_month')
                : $summaryUrl('month', [
                    'month' => $monthAnchor->format('Y-m'),
                ]),
            'next_url' => $monthNext->lte($currentMonth)
                ? $summaryUrl('month', [
                    'month' => $monthNext->format('Y-m'),
                ])
                : null,
            'label' => $monthAnchor->isSameDay(
                $currentMonth
            )
                ? 'Bulan Ini'
                : $monthAnchor->translatedFormat('F Y'),
            'caption' => $monthAnchor
                ->translatedFormat('F Y'),
        ],
        [
            'key' => 'year',
            'title' => 'Tahun',
            'icon' => 'fa-chart-column',
            'active' => in_array(
                $period['range'],
                $yearRanges,
                true
            ),
            'previous_url' => $summaryUrl('year', [
                'year' => $yearAnchor
                    ->copy()
                    ->subYear()
                    ->year,
            ]),
            'current_url' => $yearAnchor->isSameDay(
                $currentYear
            )
                ? $summaryUrl('this_year')
                : $summaryUrl('year', [
                    'year' => $yearAnchor->year,
                ]),
            'next_url' => $yearNext->lte($currentYear)
                ? $summaryUrl('year', [
                    'year' => $yearNext->year,
                ])
                : null,
            'label' => $yearAnchor->isSameDay(
                $currentYear
            )
                ? 'Tahun Ini'
                : (string) $yearAnchor->year,
            'caption' => (string) $yearAnchor->year,
        ],
    ];

    $paymentMethods = collect($payments['methods'] ?? [])->take(6);
    $paymentTotal = max(1, (float) ($payments['total_received'] ?? 0));
    $paymentColors = [
        'var(--ga-accent)',
        '#20a968',
        '#f0a624',
        '#8b5bd1',
        '#e34b5b',
        '#1594a7',
    ];
    $paymentCursor = 0;
    $paymentSegments = [];

    foreach ($paymentMethods as $index => $payment) {
        $start = $paymentCursor;
        $paymentCursor += (
            (float) $payment['total'] / $paymentTotal
        ) * 100;
        $paymentSegments[] = $paymentColors[
            $index % count($paymentColors)
        ].' '.number_format($start, 2, '.', '').'% '
            .number_format($paymentCursor, 2, '.', '').'%';
    }

    $paymentGradient = $paymentSegments
        ? 'conic-gradient('.implode(', ', $paymentSegments).')'
        : 'conic-gradient(var(--ga-border) 0 100%)';

    $primaryPoints = collect($charts['primary'] ?? [])->values();
    $chartWidth = 1000;
    $chartHeight = 270;
    $chartLeft = 67;
    $chartRight = 24;
    $chartTop = 22;
    $chartBottom = 45;
    $chartUsableWidth = $chartWidth - $chartLeft - $chartRight;
    $chartUsableHeight = $chartHeight - $chartTop - $chartBottom;
    $chartMax = max(1, (float) $primaryPoints->max('value'));
    $chartStep = $primaryPoints->count() > 1
        ? $chartUsableWidth / ($primaryPoints->count() - 1)
        : 0;
    $chartPointRows = [];

    foreach ($primaryPoints as $index => $point) {
        $x = $chartLeft + ($index * $chartStep);
        $y = $chartTop
            + $chartUsableHeight
            - (
                ((float) $point['value'] / $chartMax)
                * $chartUsableHeight
            );

        $chartPointRows[] = array_merge($point, [
            'x' => round($x, 2),
            'y' => round($y, 2),
        ]);
    }

    $chartPoints = collect($chartPointRows)
        ->map(
            fn (array $point): string =>
                $point['x'].','.$point['y']
        )
        ->implode(' ');

    $chartFloor = $chartTop + $chartUsableHeight;
    $chartAreaPoints = $chartPointRows
        ? $chartLeft.','.$chartFloor.' '.$chartPoints.' '
            .$chartPointRows[array_key_last($chartPointRows)]['x']
            .','.$chartFloor
        : '';

    $labelEvery = max(
        1,
        (int) ceil(max(1, count($chartPointRows)) / 9)
    );

    $moneyCompact = static function (
        float|int $value
    ): string {
        $value = (float) $value;

        if ($value >= 1000000000) {
            return 'Rp '.number_format(
                $value / 1000000000,
                1,
                ',',
                '.'
            ).' M';
        }

        if ($value >= 1000000) {
            return 'Rp '.number_format(
                $value / 1000000,
                1,
                ',',
                '.'
            ).' jt';
        }

        if ($value >= 1000) {
            return 'Rp '.number_format(
                $value / 1000,
                0,
                ',',
                '.'
            ).' rb';
        }

        return 'Rp '.number_format($value, 0, ',', '.');
    };

    $outOfStockItems = collect(
        $notifications['out_of_stock_items'] ?? []
    );
    $lowStockItems = collect(
        $notifications['low_stock_items'] ?? []
    );

    if ($isExpense) {
        $kpiCards = [
            [
                'tone' => 'is-red',
                'icon' => 'fa-wallet',
                'label' => 'Total Pengeluaran',
                'value' => 'Rp '.number_format(
                    $overview['net_sales'] ?? 0,
                    0,
                    ',',
                    '.'
                ),
                'hint' => 'Seluruh biaya pada periode terpilih',
            ],
            [
                'tone' => 'is-blue',
                'icon' => 'fa-receipt',
                'label' => 'Jumlah Catatan',
                'value' => number_format(
                    $overview['transactions'] ?? 0
                ),
                'hint' => $scope['label'],
            ],
            [
                'tone' => 'is-purple',
                'icon' => 'fa-calculator',
                'label' => 'Rata-rata Pengeluaran',
                'value' => 'Rp '.number_format(
                    $overview['average_transaction'] ?? 0,
                    0,
                    ',',
                    '.'
                ),
                'hint' => 'Rata-rata nominal per catatan',
            ],
            [
                'tone' => 'is-green',
                'icon' => 'fa-money-bill-wave',
                'label' => 'Pengeluaran Tunai',
                'value' => 'Rp '.number_format(
                    $overview['cash'] ?? 0,
                    0,
                    ',',
                    '.'
                ),
                'hint' => number_format(
                    $overview['cash_ratio'] ?? 0,
                    1,
                    ',',
                    '.'
                ).'% dari total pengeluaran',
            ],
            [
                'tone' => 'is-cyan',
                'icon' => 'fa-credit-card',
                'label' => 'Pengeluaran Non-tunai',
                'value' => 'Rp '.number_format(
                    $overview['non_cash'] ?? 0,
                    0,
                    ',',
                    '.'
                ),
                'hint' => number_format(
                    $overview['categories'] ?? 0
                ).' kategori tercatat',
            ],
            [
                'tone' => 'is-orange',
                'icon' => 'fa-arrow-trend-up',
                'label' => 'Pengeluaran Terbesar',
                'value' => 'Rp '.number_format(
                    $overview['largest'] ?? 0,
                    0,
                    ',',
                    '.'
                ),
                'hint' => 'Nominal catatan terbesar',
            ],
        ];
    } elseif ($isPurchase) {
        $kpiCards = [
            [
                'tone' => 'is-blue',
                'icon' => 'fa-cart-flatbed',
                'label' => 'Pembelian Bersih',
                'value' => 'Rp '.number_format(
                    $overview['net_sales'] ?? 0,
                    0,
                    ',',
                    '.'
                ),
                'hint' => ($comparisons['month_change'] ?? null)
                    === null
                        ? 'Belum ada pembanding'
                        : number_format(
                            abs($comparisons['month_change']),
                            1,
                            ',',
                            '.'
                        ).'% vs bulan lalu',
            ],
            [
                'tone' => 'is-green',
                'icon' => 'fa-receipt',
                'label' => 'Jumlah Pembelian',
                'value' => number_format(
                    $overview['transactions'] ?? 0
                ),
                'hint' => $scope['label'],
            ],
            [
                'tone' => 'is-purple',
                'icon' => 'fa-wallet',
                'label' => 'Rata-rata Pembelian',
                'value' => 'Rp '.number_format(
                    $overview['average_transaction'] ?? 0,
                    0,
                    ',',
                    '.'
                ),
                'hint' => 'Nilai rata-rata per transaksi',
            ],
            [
                'tone' => 'is-orange',
                'icon' => 'fa-money-check-dollar',
                'label' => 'Sudah Dibayar',
                'value' => 'Rp '.number_format(
                    $overview['paid'] ?? 0,
                    0,
                    ',',
                    '.'
                ),
                'hint' => 'Pembayaran pembelian periode ini',
            ],
            [
                'tone' => 'is-red',
                'icon' => 'fa-file-invoice-dollar',
                'label' => 'Sisa Utang',
                'value' => 'Rp '.number_format(
                    $overview['outstanding'] ?? 0,
                    0,
                    ',',
                    '.'
                ),
                'hint' => 'Kewajiban yang belum dibayar',
            ],
            [
                'tone' => 'is-cyan',
                'icon' => 'fa-rotate-left',
                'label' => 'Retur Pembelian',
                'value' => 'Rp '.number_format(
                    $overview['returns'] ?? 0,
                    0,
                    ',',
                    '.'
                ),
                'hint' => 'Retur pada periode terpilih',
            ],
        ];
    } else {
        $kpiCards = [
            [
                'tone' => 'is-blue',
                'icon' => 'fa-cart-shopping',
                'label' => 'Omzet Bersih',
                'value' => 'Rp '.number_format(
                    $overview['net_sales'] ?? 0,
                    0,
                    ',',
                    '.'
                ),
                'hint' => ($comparisons['month_change'] ?? null)
                    === null
                        ? 'Belum ada pembanding'
                        : number_format(
                            abs($comparisons['month_change']),
                            1,
                            ',',
                            '.'
                        ).'% vs bulan lalu',
            ],
            [
                'tone' => 'is-green',
                'icon' => 'fa-receipt',
                'label' => 'Jumlah Transaksi',
                'value' => number_format(
                    $overview['transactions'] ?? 0
                ),
                'hint' => $scope['label'],
            ],
            [
                'tone' => 'is-purple',
                'icon' => 'fa-wallet',
                'label' => 'Rata-rata Transaksi',
                'value' => 'Rp '.number_format(
                    $overview['average_transaction'] ?? 0,
                    0,
                    ',',
                    '.'
                ),
                'hint' => 'Nilai rata-rata per struk',
            ],
            [
                'tone' => 'is-orange',
                'icon' => 'fa-chart-line',
                'label' => 'Profit Bersih',
                'value' => array_key_exists(
                    'net_profit',
                    $overview
                )
                    ? 'Rp '.number_format(
                        $overview['net_profit'],
                        0,
                        ',',
                        '.'
                    )
                    : 'Terbatas',
                'hint' => array_key_exists(
                    'net_margin',
                    $overview
                )
                    ? number_format(
                        $overview['net_margin'],
                        1,
                        ',',
                        '.'
                    ).'% margin'
                    : 'Memerlukan akses laporan laba',
            ],
            [
                'tone' => 'is-red',
                'icon' => 'fa-tags',
                'label' => 'Total Diskon',
                'value' => 'Rp '.number_format(
                    $overview['discount'] ?? 0,
                    0,
                    ',',
                    '.'
                ),
                'hint' => 'Item, promo, manual, dan poin',
            ],
            [
                'tone' => 'is-cyan',
                'icon' => 'fa-rotate-left',
                'label' => 'Total Retur',
                'value' => 'Rp '.number_format(
                    $overview['returns'] ?? 0,
                    0,
                    ',',
                    '.'
                ),
                'hint' => 'Retur pada periode terpilih',
            ],
        ];
    }

@endphp

<div
    class="summary-shell"
    data-summary-analytics
    data-detail-endpoint="{{ route('reports.analytics.detail') }}"
    data-cashier-id="{{ $cashierId ?? '' }}"
    data-mode="{{ $mode }}"
>
<section class="summary-page-heading">
    <div>
        <span class="summary-eyebrow">
            <i class="fa-solid fa-chart-simple"></i>
            {{ $isExpense
                ? 'ANALITIK PENGELUARAN OPERASIONAL'
                : (
                    $isPurchase
                        ? 'ANALITIK PEMBELIAN & PERSEDIAAN'
                        : 'ANALITIK PENJUALAN & OPERASIONAL'
                ) }}
        </span>
        <h1>Ringkasan POS</h1>
        <p>
            {{ $isExpense
                ? 'Pantau biaya, kategori, metode pembayaran, pencatat, tren, dan aktivitas pengeluaran operasional.'
                : (
                    $isPurchase
                        ? 'Pantau pembelian, supplier, pembayaran, barang masuk, petugas, stok, dan retur pembelian.'
                        : 'Pantau omzet, transaksi, produk, kasir, pembayaran, stok, pelanggan, dan aktivitas sesuai periode serta hak akses.'
                ) }}
        </p>
    </div>

    <div class="summary-heading-badges">
        <span>
            <i class="fa-solid fa-calendar-day"></i>
            {{ $analytics['period']['label'] }}
        </span>
        <span>
            <i class="fa-solid {{
                $scope['mode'] === 'all'
                    ? 'fa-users'
                    : 'fa-user-tag'
            }}"></i>
            {{ $scope['label'] }}
        </span>
    </div>
</section>

<section class="summary-toolbar no-print">
    <div class="summary-mode-switch">
        <a
            href="{{ $modeUrl('sales') }}"
            class="{{ $isSales ? 'is-active' : '' }}"
        >
            <i class="fa-solid fa-cash-register"></i>
            Penjualan
        </a>

        @if($canViewPurchases)
            <a
                href="{{ $modeUrl('purchases') }}"
                class="{{ $isPurchase ? 'is-active' : '' }}"
            >
                <i class="fa-solid fa-cart-flatbed"></i>
                Pembelian
            </a>
        @endif

        @if($canViewExpenses)
            <a
                href="{{ $modeUrl('expenses') }}"
                class="{{ $isExpense ? 'is-active' : '' }}"
            >
                <i class="fa-solid fa-wallet"></i>
                Pengeluaran
            </a>
        @endif
    </div>

    <nav
        class="summary-period-navigator"
        aria-label="Navigasi periode ringkasan"
    >
        @foreach($periodNavigators as $navigator)
            <section
                class="summary-period-group {{
                    $navigator['active']
                        ? 'is-active'
                        : ''
                }}"
                data-period-group="{{ $navigator['key'] }}"
            >
                <div class="summary-period-group-title">
                    <i class="fa-solid {{
                        $navigator['icon']
                    }}"></i>
                    <span>{{ $navigator['title'] }}</span>
                </div>

                <div class="summary-period-controls">
                    <a
                        href="{{ $navigator['previous_url'] }}"
                        class="summary-period-step"
                        title="{{
                            $navigator['title']
                        }} sebelumnya"
                        aria-label="{{
                            $navigator['title']
                        }} sebelumnya"
                    >
                        <i class="fa-solid fa-chevron-left"></i>
                        <span>Sebelum</span>
                    </a>

                    <a
                        href="{{ $navigator['current_url'] }}"
                        class="summary-period-current"
                    >
                        <strong>
                            {{ $navigator['label'] }}
                        </strong>
                        <small>
                            {{ $navigator['caption'] }}
                        </small>
                    </a>

                    @if($navigator['next_url'])
                        <a
                            href="{{ $navigator['next_url'] }}"
                            class="summary-period-step"
                            title="{{
                                $navigator['title']
                            }} setelahnya"
                            aria-label="{{
                                $navigator['title']
                            }} setelahnya"
                        >
                            <span>Setelah</span>
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    @else
                        <span
                            class="summary-period-step is-disabled"
                            title="Sudah mencapai periode sekarang"
                            aria-disabled="true"
                        >
                            <span>Setelah</span>
                            <i class="fa-solid fa-chevron-right"></i>
                        </span>
                    @endif
                </div>
            </section>
        @endforeach
    </nav>

    <form method="get" class="summary-filter-form">
        <input type="hidden" name="mode" value="{{ $mode }}">

        @if($cashiers->isNotEmpty())
            <label class="summary-cashier-filter">
                <span>
                    {{ $isExpense
                        ? 'Pencatat'
                        : ($isPurchase ? 'Petugas' : 'Kasir') }}
                </span>
                <select
                    name="cashier_id"
                    class="form-select"
                    data-no-select2
                    data-summary-cashier-select
                >
                    <option value="">
                        {{ $isExpense
                            ? 'Semua pencatat'
                            : (
                                $isPurchase
                                    ? 'Semua petugas'
                                    : 'Semua kasir'
                            ) }}
                    </option>
                    @foreach($cashiers as $cashier)
                        <option
                            value="{{ $cashier->id }}"
                            @selected((int) $cashierId === $cashier->id)
                        >
                            {{ $cashier->name }}
                        </option>
                    @endforeach
                </select>
            </label>
        @endif

        <label>
            <span>Jenis periode</span>
            <select
                name="range"
                class="form-select"
                data-no-select2
                data-summary-range-select
            >
                <option value="day" @selected($period['range'] === 'day')>
                    Pilih hari
                </option>
                <option value="week" @selected($period['range'] === 'week')>
                    Pilih minggu
                </option>
                <option value="month" @selected($period['range'] === 'month')>
                    Pilih bulan
                </option>
                <option value="year" @selected($period['range'] === 'year')>
                    Pilih tahun
                </option>
                <option value="custom" @selected($period['range'] === 'custom')>
                    Rentang tanggal
                </option>
            </select>
        </label>

        <label
            class="summary-period-field"
            data-summary-range-field="day"
        >
            <span>Tanggal</span>
            <input
                type="date"
                name="date"
                value="{{ $period['date'] }}"
                class="form-control"
            >
        </label>

        <label
            class="summary-period-field"
            data-summary-range-field="week"
        >
            <span>Minggu</span>
            <select
                name="week"
                class="form-select"
                data-no-select2
            >
                @foreach($weekOptions as $weekOption)
                    <option
                        value="{{ $weekOption['value'] }}"
                        @selected(
                            $period['week']
                                === $weekOption['value']
                        )
                    >
                        {{ $weekOption['label'] }}
                    </option>
                @endforeach
            </select>
        </label>

        <label
            class="summary-period-field"
            data-summary-range-field="month"
        >
            <span>Bulan</span>
            <input
                type="month"
                name="month"
                value="{{ $period['month'] }}"
                class="form-control"
            >
        </label>

        <label
            class="summary-period-field"
            data-summary-range-field="year"
        >
            <span>Tahun</span>
            <input
                type="number"
                name="year"
                min="2000"
                max="2100"
                value="{{ $period['year'] }}"
                class="form-control"
            >
        </label>

        <div
            class="summary-custom-fields"
            data-summary-range-field="custom"
        >
            <label>
                <span>Dari</span>
                <input
                    type="date"
                    name="from"
                    value="{{ $period['from'] }}"
                    class="form-control"
                >
            </label>
            <label>
                <span>Sampai</span>
                <input
                    type="date"
                    name="to"
                    value="{{ $period['to'] }}"
                    class="form-control"
                >
            </label>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-filter"></i>
            Tampilkan
        </button>
    </form>
</section>

<section class="summary-kpi-grid">
    @foreach($kpiCards as $card)
        <article class="{{ $card['tone'] }}">
            <span>
                <i class="fa-solid {{ $card['icon'] }}"></i>
            </span>
            <div>
                <small>{{ $card['label'] }}</small>
                <strong>{{ $card['value'] }}</strong>
                <em>{{ $card['hint'] }}</em>
            </div>
        </article>
    @endforeach
</section>

<div class="summary-primary-grid">
    <div class="summary-main-column">
    <section class="summary-card summary-sales-chart-card">
        <header>
            <div>
                <span><i class="fa-solid fa-chart-line"></i></span>
                <div>
                    <strong>
                        {{ $charts['primary_title']
                            ?? 'Grafik Penjualan' }}
                    </strong>
                    <small>
                        {{ $charts['primary_description']
                            ?? 'Arahkan atau klik titik grafik.' }}
                    </small>
                </div>
            </div>

            @if($charts['primary_peak'] ?? null)
                <b>
                    Puncak
                    {{ $charts['primary_peak']['short_label'] }}
                    ·
                    {{ $moneyCompact(
                        $charts['primary_peak']['value']
                    ) }}
                </b>
            @endif
        </header>

        <div class="summary-line-chart">
            @if($chartPointRows)
                <svg
                    viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}"
                    role="img"
                    aria-label="Grafik penjualan interaktif"
                >
                    <defs>
                        <linearGradient
                            id="summaryAreaFill"
                            x1="0"
                            y1="0"
                            x2="0"
                            y2="1"
                        >
                            <stop
                                offset="0%"
                                stop-color="var(--ga-accent)"
                                stop-opacity=".3"
                            />
                            <stop
                                offset="100%"
                                stop-color="var(--ga-accent)"
                                stop-opacity=".02"
                            />
                        </linearGradient>
                    </defs>

                    @foreach(range(0, 4) as $tick)
                        @php
                            $tickY = $chartTop
                                + (($chartUsableHeight / 4) * $tick);
                            $tickValue = $chartMax
                                * ((4 - $tick) / 4);
                        @endphp
                        <line
                            x1="{{ $chartLeft }}"
                            y1="{{ $tickY }}"
                            x2="{{ $chartWidth - $chartRight }}"
                            y2="{{ $tickY }}"
                            class="summary-chart-grid-line"
                        />
                        <text
                            x="{{ $chartLeft - 10 }}"
                            y="{{ $tickY + 4 }}"
                            text-anchor="end"
                            class="summary-chart-axis-text"
                        >{{ $moneyCompact($tickValue) }}</text>
                    @endforeach

                    <polygon
                        points="{{ $chartAreaPoints }}"
                        fill="url(#summaryAreaFill)"
                    />
                    <polyline
                        points="{{ $chartPoints }}"
                        class="summary-chart-line"
                    />

                    @foreach($chartPointRows as $index => $point)
                        <g
                            tabindex="0"
                            role="button"
                            data-analytics-point
                            data-metric="{{ $point['detail_type'] }}"
                            data-key="{{ $point['key'] }}"
                            data-label="{{ $point['label'] }}"
                            data-value="{{ $point['value'] }}"
                            data-transactions="{{ $point['transactions'] }}"
                        >
                            <circle
                                cx="{{ $point['x'] }}"
                                cy="{{ $point['y'] }}"
                                r="14"
                                class="summary-chart-hit"
                            />
                            <circle
                                cx="{{ $point['x'] }}"
                                cy="{{ $point['y'] }}"
                                r="5"
                                class="summary-chart-point"
                            />
                        </g>

                        @if(
                            $index % $labelEvery === 0
                            || $index === count($chartPointRows) - 1
                        )
                            <text
                                x="{{ $point['x'] }}"
                                y="{{ $chartHeight - 12 }}"
                                text-anchor="middle"
                                class="summary-chart-axis-text"
                            >{{ $point['short_label'] }}</text>
                        @endif
                    @endforeach
                </svg>

                <div
                    class="summary-chart-tooltip"
                    data-summary-chart-tooltip
                    hidden
                ></div>
            @else
                <div class="summary-empty">
                    Belum ada data penjualan pada periode ini.
                </div>
            @endif
        </div>
    </section>


<div class="summary-secondary-grid">
    <section class="summary-card summary-payment-card">
        <header>
            <div>
                <span><i class="fa-solid fa-credit-card"></i></span>
                <div>
                    <strong>
                        {{ $isExpense
                            ? 'Pengeluaran per Metode Pembayaran'
                            : (
                                $isPurchase
                                    ? 'Pembayaran Pembelian per Metode'
                                    : 'Penjualan per Metode Pembayaran'
                            ) }}
                    </strong>
                    <small>
                        {{ $isExpense
                            ? 'Komposisi metode yang digunakan untuk membayar biaya.'
                            : (
                                $isPurchase
                                    ? 'Komposisi pembayaran kepada supplier.'
                                    : 'Komposisi pembayaran yang sudah diterima.'
                            ) }}
                    </small>
                </div>
            </div>
        </header>

        <div class="summary-payment-content">
            <div
                class="summary-donut"
                style="--summary-donut: {{ $paymentGradient }}"
            >
                <div>
                    <small>
                        {{ $isExpense
                            ? 'Total biaya'
                            : ($isPurchase ? 'Dibayar' : 'Diterima') }}
                    </small>
                    <strong>
                        {{ $moneyCompact(
                            $payments['total_received'] ?? 0
                        ) }}
                    </strong>
                </div>
            </div>

            <div class="summary-payment-legend">
                @forelse($paymentMethods as $index => $payment)
                    @php
                        $percentage = $paymentTotal > 0
                            ? (
                                (float) $payment['total']
                                / $paymentTotal
                            ) * 100
                            : 0;
                    @endphp
                    <article>
                        <i
                            style="--legend-color: {{
                                $paymentColors[
                                    $index % count($paymentColors)
                                ]
                            }}"
                        ></i>
                        <div>
                            <strong>{{ $payment['label'] }}</strong>
                            <small>
                                {{ number_format(
                                    $payment['transactions']
                                ) }}
                                pembayaran
                            </small>
                        </div>
                        <span>
                            Rp {{ number_format(
                                $payment['total'],
                                0,
                                ',',
                                '.'
                            ) }}
                            <small>
                                {{ number_format(
                                    $percentage,
                                    1,
                                    ',',
                                    '.'
                                ) }}%
                            </small>
                        </span>
                    </article>
                @empty
                    <div class="summary-empty">
                        Belum ada pembayaran.
                    </div>
                @endforelse

                @if(($payments['receivables'] ?? 0) > 0)
                    <article class="is-receivable">
                        <i style="--legend-color: #e34b5b"></i>
                        <div>
                            <strong>
                                {{ $isPurchase
                                    ? 'Utang Pembelian'
                                    : 'Piutang' }}
                            </strong>
                            <small>
                                {{ $isPurchase
                                    ? 'Belum dibayar'
                                    : 'Belum diterima' }}
                            </small>
                        </div>
                        <span>
                            Rp {{ number_format(
                                $payments['receivables'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </span>
                    </article>
                @endif
            </div>
        </div>
    </section>

    <section class="summary-card summary-cashier-card">
        <header>
            <div>
                <span><i class="fa-solid fa-user-tie"></i></span>
                <div>
                    <strong>
                        @if($isExpense)
                            {{ $scope['mode'] === 'staff'
                                ? 'Pengeluaran Pencatat Terpilih'
                                : 'Pengeluaran per Pencatat' }}
                        @elseif($isPurchase)
                            {{ $scope['mode'] === 'staff'
                                ? 'Pembelian Petugas Terpilih'
                                : 'Pembelian per Petugas' }}
                        @else
                            {{ $scope['mode'] === 'mine'
                                ? 'Performa Saya'
                                : (
                                    $scope['mode'] === 'cashier'
                                        ? 'Performa Kasir Terpilih'
                                        : 'Penjualan per Kasir'
                                ) }}
                        @endif
                    </strong>
                    <small>
                        {{ $isExpense
                            ? 'Total biaya dan jumlah catatan berdasarkan pengguna.'
                            : (
                                $isPurchase
                                    ? 'Nilai pembelian, jumlah transaksi, pembayaran, dan sisa utang.'
                                    : 'Omzet, transaksi, shift, dan arus kas.'
                            ) }}
                    </small>
                </div>
            </div>
        </header>

        <div class="summary-cash-flow">
            @if($isExpense)
                <article>
                    <small>Total pengeluaran</small>
                    <strong>
                        Rp {{ number_format(
                            $cashiersData['total'] ?? 0,
                            0,
                            ',',
                            '.'
                        ) }}
                    </strong>
                </article>
                <article>
                    <small>Tunai</small>
                    <strong>
                        Rp {{ number_format(
                            $cashiersData['paid'] ?? 0,
                            0,
                            ',',
                            '.'
                        ) }}
                    </strong>
                </article>
                <article>
                    <small>Non-tunai</small>
                    <strong>
                        Rp {{ number_format(
                            $cashiersData['outstanding'] ?? 0,
                            0,
                            ',',
                            '.'
                        ) }}
                    </strong>
                </article>
            @elseif($isPurchase)
                <article>
                    <small>Pembelian bersih</small>
                    <strong>
                        Rp {{ number_format(
                            $cashiersData['total'] ?? 0,
                            0,
                            ',',
                            '.'
                        ) }}
                    </strong>
                </article>
                <article>
                    <small>Sudah dibayar</small>
                    <strong>
                        Rp {{ number_format(
                            $cashiersData['paid'] ?? 0,
                            0,
                            ',',
                            '.'
                        ) }}
                    </strong>
                </article>
                <article>
                    <small>Sisa utang</small>
                    <strong>
                        Rp {{ number_format(
                            $cashiersData['outstanding'] ?? 0,
                            0,
                            ',',
                            '.'
                        ) }}
                    </strong>
                </article>
            @else
                <article>
                    <small>Shift aktif</small>
                    <strong>
                        {{ number_format(
                            $cashiersData['open_count'] ?? 0
                        ) }}
                    </strong>
                </article>
                <article>
                    <small>Kas masuk</small>
                    <strong>
                        Rp {{ number_format(
                            $cashiersData['cash_in'] ?? 0,
                            0,
                            ',',
                            '.'
                        ) }}
                    </strong>
                </article>
                <article>
                    <small>Kas keluar</small>
                    <strong>
                        Rp {{ number_format(
                            $cashiersData['cash_out'] ?? 0,
                            0,
                            ',',
                            '.'
                        ) }}
                    </strong>
                </article>
            @endif
        </div>

        <div class="summary-cashier-ranking">
            @forelse(
                collect($cashiersData['ranking'] ?? [])->take(6)
                as $cashier
            )
                <article>
                    <span>
                        {{ mb_strtoupper(
                            mb_substr($cashier['name'], 0, 1)
                        ) }}
                    </span>
                    <div>
                        <strong>{{ $cashier['name'] }}</strong>
                        <small>
                            {{ number_format(
                                $cashier['transactions']
                            ) }}
                            transaksi
                        </small>
                    </div>
                    <em>
                        Rp {{ number_format(
                            $cashier['sales'],
                            0,
                            ',',
                            '.'
                        ) }}
                    </em>
                </article>
            @empty
                <div class="summary-empty">
                    Belum ada transaksi kasir.
                </div>
            @endforelse
        </div>
    </section>
</div>
    </div>

    <div class="summary-side-stack">
        <section class="summary-card summary-compact-list">
            <header>
                <div>
                    <span>
                        <i class="fa-solid fa-ranking-star"></i>
                    </span>
                    <div>
                        <strong>
                            {{ $isExpense
                                ? 'Top 5 Kategori Pengeluaran'
                                : (
                                    $isPurchase
                                        ? 'Top 5 Barang Dibeli'
                                        : 'Top 5 Produk Terlaris'
                                ) }}
                        </strong>
                        <small>
                            {{ $isExpense
                                ? 'Kategori dengan nilai pengeluaran terbesar.'
                                : (
                                    $isPurchase
                                        ? 'Berdasarkan jumlah satuan dasar yang dibeli.'
                                        : 'Berdasarkan jumlah satuan dasar terjual.'
                                ) }}
                        </small>
                    </div>
                </div>
                @if($isExpense)
                    @can('expenses.view')
                        <a href="{{ route('expenses.index') }}">
                            Lihat semua
                        </a>
                    @endcan
                @elseif($isPurchase)
                    @can('purchases.view')
                        <a href="{{ route('purchases.index') }}">
                            Lihat semua
                        </a>
                    @endcan
                @else
                    @can('reports.sales')
                        <a href="{{ route('reports.sales') }}">
                            Lihat semua
                        </a>
                    @endcan
                @endif
            </header>

            <div class="summary-list-body">
                @forelse(
                    collect($products['ranking'] ?? [])->take(5)
                    as $index => $product
                )
                    <article>
                        <b>{{ $index + 1 }}</b>
                        <span class="summary-product-icon">
                            <i class="fa-solid fa-box"></i>
                        </span>
                        <div>
                            <strong>{{ $product['name'] }}</strong>
                            <small>
                                {{ $product['sku'] }}
                                · {{ $product['category'] }}
                            </small>
                        </div>
                        <em>
                            {{ \App\Support\Number::quantity(
                                $product['quantity']
                            ) }}
                            {{ $product['unit'] }}
                            <small>
                                Rp {{ number_format(
                                    $product['revenue'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </small>
                        </em>
                    </article>
                @empty
                    <div class="summary-empty">
                        Belum ada produk terjual.
                    </div>
                @endforelse
            </div>
        </section>

        @if($isExpense)
        <section class="summary-card summary-expense-largest-card">
            <header>
                <div>
                    <span>
                        <i class="fa-solid fa-arrow-trend-up"></i>
                    </span>
                    <div>
                        <strong>Pengeluaran Terbesar</strong>
                        <small>
                            Catatan dengan nominal paling tinggi.
                        </small>
                    </div>
                </div>
                @can('expenses.view')
                    <a href="{{ route('expenses.index') }}">
                        Lihat semua
                    </a>
                @endcan
            </header>

            <div class="summary-expense-largest-list">
                @forelse(
                    collect(
                        $activities['largest_expenses'] ?? []
                    )->take(6)
                    as $expense
                )
                    <article>
                        <span>
                            <i class="fa-solid fa-wallet"></i>
                        </span>
                        <div>
                            <strong>{{ $expense->category }}</strong>
                            <small>
                                {{ $expense->expense_date
                                    ->translatedFormat('d M Y') }}
                                ·
                                {{ $expense->creator?->name ?? '-' }}
                            </small>
                            <p>
                                {{ \Illuminate\Support\Str::limit(
                                    $expense->notes,
                                    58
                                ) }}
                            </p>
                        </div>
                        <em>
                            Rp {{ number_format(
                                $expense->amount,
                                0,
                                ',',
                                '.'
                            ) }}
                            <small>
                                {{ config(
                                    'pos.payment_methods.'
                                        .$expense->payment_method,
                                    $expense->payment_method
                                ) }}
                            </small>
                        </em>
                    </article>
                @empty
                    <div class="summary-empty">
                        Belum ada pengeluaran pada periode ini.
                    </div>
                @endforelse
            </div>
        </section>
    @else
<section class="summary-card summary-stock-card">
            <header>
                <div>
                    <span>
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </span>
                    <div>
                        <strong>Stok Menipis & Habis</strong>
                        <small>
                            Dipisahkan berdasarkan tingkat urgensi.
                        </small>
                    </div>
                </div>
                @can('stock.view')
                    <a href="{{ route('stocks.movements') }}">
                        Lihat semua
                    </a>
                @endcan
            </header>

            <div class="summary-stock-groups">
                <section class="is-out">
                    <header>
                        <span>Habis</span>
                        <b>{{ number_format(
                            $notifications['out_of_stock'] ?? 0
                        ) }}</b>
                    </header>

                    @forelse($outOfStockItems->take(4) as $item)
                        <article>
                            <span>
                                <i class="fa-solid fa-box-open"></i>
                            </span>
                            <div>
                                <strong>{{ $item['name'] }}</strong>
                                <small>
                                    {{ $item['sku'] }}
                                    · Minimum
                                    {{ \App\Support\Number::quantity(
                                        $item['minimum']
                                    ) }}
                                    {{ $item['unit'] }}
                                </small>
                            </div>
                            <em>
                                {{ \App\Support\Number::quantity(
                                    $item['quantity']
                                ) }}
                                {{ $item['unit'] }}
                            </em>
                        </article>
                    @empty
                        <div class="summary-stock-empty">
                            Tidak ada stok habis.
                        </div>
                    @endforelse
                </section>

                <section class="is-low">
                    <header>
                        <span>Menipis</span>
                        <b>{{ number_format(
                            $notifications['low_stock'] ?? 0
                        ) }}</b>
                    </header>

                    @forelse($lowStockItems->take(4) as $item)
                        <article>
                            <span>
                                <i class="fa-solid fa-box"></i>
                            </span>
                            <div>
                                <strong>{{ $item['name'] }}</strong>
                                <small>
                                    {{ $item['sku'] }}
                                    · Minimum
                                    {{ \App\Support\Number::quantity(
                                        $item['minimum']
                                    ) }}
                                    {{ $item['unit'] }}
                                </small>
                            </div>
                            <em>
                                {{ \App\Support\Number::quantity(
                                    $item['quantity']
                                ) }}
                                {{ $item['unit'] }}
                            </em>
                        </article>
                    @empty
                        <div class="summary-stock-empty">
                            Tidak ada stok menipis.
                        </div>
                    @endforelse
                </section>
            </div>
        </section>

        
    @endif

        <section class="summary-card summary-compact-list">
            <header>
                <div>
                    <span>
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </span>
                    <div>
                        <strong>
                            {{ $isExpense
                                ? 'Pengeluaran Terakhir'
                                : (
                                    $isPurchase
                                        ? 'Pembelian Terakhir'
                                        : 'Transaksi Terakhir'
                                ) }}
                        </strong>
                        <small>
                            {{ $isExpense
                                ? 'Catatan biaya terbaru pada periode terpilih.'
                                : (
                                    $isPurchase
                                        ? 'Pembelian terbaru pada periode terpilih.'
                                        : 'Transaksi terbaru pada periode terpilih.'
                                ) }}
                        </small>
                    </div>
                </div>
                @if($isExpense)
                    @can('expenses.view')
                        <a href="{{ route('expenses.index') }}">
                            Lihat semua
                        </a>
                    @endcan
                @elseif($isPurchase)
                    @can('purchases.view')
                        <a href="{{ route('purchases.index') }}">
                            Lihat semua
                        </a>
                    @endcan
                @else
                    @can('reports.sales')
                        <a href="{{ route('reports.sales') }}">
                            Lihat semua
                        </a>
                    @endcan
                @endif
            </header>

            <div class="summary-list-body is-transactions">
                @forelse($latestTransactions->take(5) as $row)
                    <article>
                        <time>{{ $row['time'] }}</time>
                        <div>
                            <strong>{{ $row['number'] }}</strong>
                            <small>
                                {{ $row['party'] }}
                                · {{ $row['staff'] }}
                            </small>
                        </div>
                        <span class="summary-method-badge">
                            {{ str(
                                $row['status']
                            )->replace('_', ' ')->title() }}
                        </span>
                        <em>
                            Rp {{ number_format(
                                $row['value'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </em>
                    </article>
                @empty
                    <div class="summary-empty">
                        Belum ada
                        {{ $isExpense
                            ? 'pengeluaran'
                            : ($isPurchase ? 'pembelian' : 'penjualan') }}
                        pada periode ini.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>

@if($notifications)
<section class="summary-card summary-notification-card">
    <header>
        <div>
            <span><i class="fa-solid fa-bell"></i></span>
            <div>
                <strong>Notifikasi & Pengingat</strong>
                <small>Hal yang perlu segera diperiksa.</small>
            </div>
        </div>
    </header>

    <div class="summary-notification-grid">
        <a
            href="{{ auth()->user()->can('stock.view')
                ? route('stocks.movements')
                : '#' }}"
            class="is-warning"
        >
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Stok Menipis</strong>
                <small>
                    {{ number_format(
                        $notifications['low_stock']
                    ) }}
                    produk
                </small>
            </div>
            <span>Lihat detail</span>
        </a>

        <a
            href="{{ auth()->user()->can('stock.view')
                ? route('stocks.movements')
                : '#' }}"
            class="is-danger"
        >
            <i class="fa-solid fa-box-open"></i>
            <div>
                <strong>Stok Habis</strong>
                <small>
                    {{ number_format(
                        $notifications['out_of_stock']
                    ) }}
                    produk
                </small>
            </div>
            <span>Lihat detail</span>
        </a>

        <a
            href="{{ auth()->user()->can('reports.debts')
                ? route('reports.debts')
                : '#' }}"
            class="is-purple"
        >
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <div>
                <strong>Utang Supplier</strong>
                <small>
                    {{ number_format(
                        $notifications['overdue_payables']
                    ) }}
                    jatuh tempo
                </small>
            </div>
            <span>Lihat detail</span>
        </a>

        <a
            href="{{ auth()->user()->can('reports.debts')
                ? route('reports.debts')
                : '#' }}"
            class="is-green"
        >
            <i class="fa-solid fa-hand-holding-dollar"></i>
            <div>
                <strong>Piutang Pelanggan</strong>
                <small>
                    {{ number_format(
                        $notifications['overdue_receivables']
                    ) }}
                    jatuh tempo
                </small>
            </div>
            <span>Lihat detail</span>
        </a>

        <a
            href="{{ auth()->user()->can('stock.view')
                ? route('stocks.batches')
                : '#' }}"
            class="is-red"
        >
            <i class="fa-solid fa-calendar-xmark"></i>
            <div>
                <strong>Produk Expired</strong>
                <small>
                    {{ number_format(
                        $notifications['expired']
                    ) }}
                    batch
                </small>
            </div>
            <span>Lihat detail</span>
        </a>

        <a
            href="{{ auth()->user()->can('stock.view')
                ? route('stocks.batches')
                : '#' }}"
            class="is-blue"
        >
            <i class="fa-solid fa-calendar-day"></i>
            <div>
                <strong>Segera Expired</strong>
                <small>
                    {{ number_format(
                        $notifications['expiring']
                    ) }}
                    batch
                </small>
            </div>
            <span>Lihat detail</span>
        </a>
    </div>
</section>
@endif

<details class="summary-advanced" open>
    <summary>
        <span>
            <i class="fa-solid fa-chart-column"></i>
            Analitik Lanjutan
        </span>
        <small>
            Minggu, bulan, tahun, pelanggan, persediaan, dan aktivitas
        </small>
        <i class="fa-solid fa-chevron-down"></i>
    </summary>

    <div class="summary-advanced-content">
        @foreach([
            'week' => [
                'title' => 'Grafik Mingguan',
                'icon' => 'fa-calendar-week',
            ],
            'month' => [
                'title' => 'Grafik Bulanan',
                'icon' => 'fa-calendar',
            ],
            'year' => [
                'title' => 'Grafik Tahunan',
                'icon' => 'fa-calendar-days',
            ],
        ] as $chartKey => $chartOption)
            <section class="summary-card summary-mini-chart-card">
                <header>
                    <div>
                        <span>
                            <i class="fa-solid {{
                                $chartOption['icon']
                            }}"></i>
                        </span>
                        <div>
                            <strong>{{ $chartOption['title'] }}</strong>
                            <small>
                                {{ $charts[$chartKey]['label'] ?? '-' }}
                                · klik batang untuk rincian
                            </small>
                        </div>
                    </div>
                </header>

                <div class="summary-mini-bars">
                    @foreach(
                        collect(
                            $charts[$chartKey]['points'] ?? []
                        )
                        as $point
                    )
                        <button
                            type="button"
                            data-analytics-point
                            data-metric="{{ $point['detail_type'] }}"
                            data-key="{{ $point['key'] }}"
                            data-label="{{ $point['label'] }}"
                            data-value="{{ $point['value'] }}"
                            data-transactions="{{
                                $point['transactions']
                            }}"
                            title="{{
                                $point['label']
                            }} · Rp {{
                                number_format(
                                    $point['value'],
                                    0,
                                    ',',
                                    '.'
                                )
                            }}"
                        >
                            <small>
                                {{ $moneyCompact($point['value']) }}
                            </small>
                            <span>
                                <i
                                    style="--summary-bar: {{
                                        $point['percent']
                                    }}"
                                ></i>
                            </span>
                            <b>{{ $point['short_label'] }}</b>
                        </button>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section class="summary-card summary-insight-card">
            <header>
                <div>
                    <span><i class="fa-solid fa-users"></i></span>
                    <div>
                        <strong>
                            {{ $isExpense
                                ? 'Kategori Pengeluaran'
                                : ($isPurchase ? 'Supplier' : 'Pelanggan') }}
                        </strong>
                        <small>
                            {{ $isExpense
                            ? 'Sebaran kategori dan konsentrasi biaya.'
                            : (
                                $isPurchase
                                    ? 'Pemasok dan frekuensi pembelian.'
                                    : 'Member dan pembelian berulang.'
                            ) }}
                        </small>
                    </div>
                </div>
            </header>

            <div class="summary-insight-metrics">
                <article>
                    <small>
                        {{ $isExpense
                            ? 'Jumlah kategori'
                            : (
                                $isPurchase
                                    ? 'Supplier aktif'
                                    : 'Pelanggan'
                            ) }}
                    </small>
                    <strong>
                        {{ number_format(
                            $customers['customers'] ?? 0
                        ) }}
                    </strong>
                </article>
                <article>
                    <small>
                        {{ $isExpense
                            ? 'Metode pembayaran'
                            : (
                                $isPurchase
                                    ? 'Supplier baru'
                                    : 'Baru'
                            ) }}
                    </small>
                    <strong>
                        {{ number_format(
                            $customers['new_customers'] ?? 0
                        ) }}
                    </strong>
                </article>
                <article>
                    <small>
                        {{ $isExpense
                            ? 'Jumlah catatan'
                            : (
                                $isPurchase
                                    ? 'Pembelian supplier'
                                    : 'Transaksi member'
                            ) }}
                    </small>
                    <strong>
                        {{ number_format(
                            $customers['member_transactions'] ?? 0
                        ) }}
                    </strong>
                </article>
                <article>
                    <small>
                        {{ $isExpense
                            ? 'Porsi kategori utama'
                            : (
                                $isPurchase
                                    ? 'Repeat supplier'
                                    : 'Repeat rate'
                            ) }}
                    </small>
                    <strong>
                        {{ number_format(
                            $customers['repeat_rate'] ?? 0,
                            1,
                            ',',
                            '.'
                        ) }}%
                    </strong>
                </article>
            </div>

            <div class="summary-top-customer">
                <i class="fa-solid fa-crown"></i>
                <div>
                    <small>
                    {{ $isExpense
                        ? 'Kategori terbesar'
                        : (
                            $isPurchase
                                ? 'Top supplier'
                                : 'Top customer'
                        ) }}
                </small>
                    <strong>
                        {{ $customers['top_customer']['name']
                            ?? 'Belum tersedia' }}
                    </strong>
                </div>
                <span>
                    Rp {{ number_format(
                        $customers['top_customer']['value'] ?? 0,
                        0,
                        ',',
                        '.'
                    ) }}
                </span>
            </div>
        </section>

        <section class="summary-card summary-insight-card">
            <header>
                <div>
                    <span>
                        <i class="fa-solid {{
                            $isExpense
                                ? 'fa-chart-pie'
                                : 'fa-warehouse'
                        }}"></i>
                    </span>
                    <div>
                        <strong>
                            {{ $isExpense
                                ? 'Statistik Pengeluaran'
                                : 'Persediaan' }}
                        </strong>
                        <small>
                            {{ $isExpense
                                ? 'Ringkasan struktur biaya operasional.'
                                : 'Nilai stok dan pergerakan barang.' }}
                        </small>
                    </div>
                </div>
            </header>

            @if($isExpense && $expenseStats)
                <div class="summary-insight-metrics">
                    <article>
                        <small>Kategori</small>
                        <strong>
                            {{ number_format(
                                $expenseStats['category_count'] ?? 0
                            ) }}
                        </strong>
                    </article>
                    <article>
                        <small>Metode pembayaran</small>
                        <strong>
                            {{ number_format(
                                $expenseStats['method_count'] ?? 0
                            ) }}
                        </strong>
                    </article>
                    <article>
                        <small>Pencatat aktif</small>
                        <strong>
                            {{ number_format(
                                $expenseStats['user_count'] ?? 0
                            ) }}
                        </strong>
                    </article>
                    <article>
                        <small>Pengeluaran terbesar</small>
                        <strong>
                            Rp {{ number_format(
                                $expenseStats['largest'] ?? 0,
                                0,
                                ',',
                                '.'
                            ) }}
                        </strong>
                    </article>
                </div>

                <div class="summary-expense-ratio">
                    <div>
                        <span>Proporsi pembayaran tunai</span>
                        <strong>
                            {{ number_format(
                                $expenseStats['cash_ratio'] ?? 0,
                                1,
                                ',',
                                '.'
                            ) }}%
                        </strong>
                    </div>
                    <span>
                        <i
                            style="width: {{
                                min(
                                    100,
                                    max(
                                        0,
                                        $expenseStats['cash_ratio']
                                            ?? 0
                                    )
                                )
                            }}%"
                        ></i>
                    </span>
                </div>
            @elseif($inventory)
                <div class="summary-insight-metrics">
                    <article>
                        <small>Nilai stok</small>
                        <strong>
                            {{ $moneyCompact($inventory['value']) }}
                        </strong>
                    </article>
                    <article>
                        <small>Barang masuk</small>
                        <strong>
                            {{ \App\Support\Number::quantity(
                                $inventory['incoming']
                            ) }}
                        </strong>
                    </article>
                    <article>
                        <small>Barang keluar</small>
                        <strong>
                            {{ \App\Support\Number::quantity(
                                $inventory['outgoing']
                            ) }}
                        </strong>
                    </article>
                    <article>
                        <small>Kedaluwarsa</small>
                        <strong>
                            {{ number_format(
                                $inventory['expired']
                            ) }}
                        </strong>
                    </article>
                </div>
            @else
                <div class="summary-empty">
                    Data tidak tersedia untuk role ini.
                </div>
            @endif
        </section>

        @unless($isExpense)
        <section class="summary-card summary-activity-card">
            <header>
                <div>
                    <span><i class="fa-solid fa-rotate-left"></i></span>
                    <div>
                        <strong>
                            {{ $isPurchase
                                ? 'Retur Pembelian & Pembatalan'
                                : 'Retur & Pembatalan' }}
                        </strong>
                        <small>Aktivitas koreksi transaksi.</small>
                    </div>
                </div>
            </header>

            <div class="summary-activity-list">
                <article class="is-count">
                    <i class="fa-solid fa-ban"></i>
                    <div>
                        <strong>
                            {{ number_format(
                                $activities['cancelled'] ?? 0
                            ) }}
                            pembatalan
                        </strong>
                        <small>Pada periode terpilih</small>
                    </div>
                </article>

                @forelse(
                    collect(
                        $activities['latest_returns'] ?? []
                    )->take(4)
                    as $return
                )
                    <article>
                        <i class="fa-solid fa-rotate-left"></i>
                        <div>
                            <strong>{{ $return->number }}</strong>
                            <small>
                                Dari {{
                                    $isPurchase
                                        ? $return->purchase?->number
                                        : $return->sale?->number
                                }}
                            </small>
                        </div>
                        <span>
                            - Rp {{ number_format(
                                $return->total,
                                0,
                                ',',
                                '.'
                            ) }}
                        </span>
                    </article>
                @empty
                    <div class="summary-empty">
                        Belum ada retur.
                    </div>
                @endforelse
            </div>
        </section>

                @endunless

        <section class="summary-card summary-activity-card">
            <header>
                <div>
                    <span><i class="fa-solid fa-user-clock"></i></span>
                    <div>
                        <strong>Aktivitas Pengguna</strong>
                        <small>
                            Log terbaru sesuai permission audit.
                        </small>
                    </div>
                </div>
            </header>

            <div class="summary-activity-list">
                @forelse(
                    collect($activities['logs'] ?? [])->take(5)
                    as $log
                )
                    <article>
                        <i class="fa-solid fa-bolt"></i>
                        <div>
                            <strong>
                                {{ str(
                                    $log->action
                                )->replace(
                                    ['.', '_'],
                                    ' '
                                )->title() }}
                            </strong>
                            <small>
                                {{ $log->user?->name ?? 'Sistem' }}
                                · {{ $log->created_at->diffForHumans() }}
                            </small>
                        </div>
                    </article>
                @empty
                    <div class="summary-empty">
                        Log tidak tersedia untuk role ini.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</details>

<div class="summary-scope-note">
    <i class="fa-solid fa-circle-info"></i>
    <span>
        Cabang, outlet, pajak, dan target penjualan belum ditampilkan
        karena struktur transaksi saat ini belum memiliki data tersebut.
    </span>
</div>

<div
    class="summary-detail-modal"
    data-summary-detail-modal
    hidden
>
    <div
        class="summary-detail-backdrop"
        data-summary-detail-close
    ></div>

    <section
        class="summary-detail-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="summary-detail-title"
    >
        <header>
            <div>
                <span>
                    <i class="fa-solid fa-chart-line"></i>
                </span>
                <div>
                    <h2 id="summary-detail-title">
                        Rincian Grafik
                    </h2>
                    <p data-summary-detail-period>
                        Memuat periode...
                    </p>
                </div>
            </div>

            <button
                type="button"
                data-summary-detail-close
                aria-label="Tutup rincian"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </header>

        <div class="summary-detail-summary">
            <article>
                <small>Nilai bersih</small>
                <strong data-summary-detail-value>Rp 0</strong>
            </article>
            <article>
                <small>Transaksi</small>
                <strong data-summary-detail-transactions>0</strong>
            </article>
            <article>
                <small>Jenis barang</small>
                <strong data-summary-detail-products>0</strong>
            </article>
        </div>

        <div class="summary-detail-content">
            <section>
                <header>
                    <strong data-summary-transaction-title>
                        {{ $isExpense
                            ? 'Daftar Pengeluaran'
                            : (
                                $isPurchase
                                    ? 'Daftar Pembelian'
                                    : 'Daftar Penjualan'
                            ) }}
                    </strong>
                    <span data-summary-transaction-count>0 data</span>
                </header>
                <div
                    class="summary-detail-list"
                    data-summary-transactions
                ></div>
            </section>

            <section>
                <header>
                    <strong data-summary-product-title>
                        {{ $isExpense
                            ? 'Kategori Pengeluaran'
                            : (
                                $isPurchase
                                    ? 'Barang Dibeli'
                                    : 'Produk Terjual'
                            ) }}
                    </strong>
                    <span data-summary-product-count>0 barang</span>
                </header>
                <div
                    class="summary-detail-list"
                    data-summary-products
                ></div>
            </section>
        </div>

        <footer>
            <span>
                Maksimal 100 transaksi ditampilkan pada satu rincian.
            </span>
            <button
                type="button"
                class="btn btn-secondary"
                data-summary-detail-close
            >
                Tutup
            </button>
        </footer>
    </section>
</div>
</div>
@endsection
