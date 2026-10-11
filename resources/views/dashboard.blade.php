@extends('layouts.pos')
@section('title', 'Dashboard')
@section('body_class', 'pos-dashboard-page')

@section('content')
@php
    $access = $dashboard['access'];
    $roles = $dashboard['role_names'];
    $twoFactorFeatureEnabled = (bool) config(
        'pos-features.two_factor_enabled',
        false
    );

    $hasOperationalPanels = $access['cashier']
        || $access['purchases']
        || $access['debts']
        || $access['expenses']
        || $access['promotions']
        || $access['opname'];

    $hasSystemPanels = $access['users']
        || $access['activity'];

    $salesScope = $dashboard['sales_scope'] ?? [
        'mode' => 'all',
        'label' => 'Semua kasir',
        'description' => 'Ringkasan mencakup seluruh kasir.',
    ];
@endphp

<div class="dashboard-shell" data-dashboard>
<section class="dashboard-hero" data-manual-page-head>
    <div class="dashboard-hero-copy">
        <span class="dashboard-eyebrow">
            <i class="fa-solid {{ $dashboard['workspace']['icon'] }}"></i>
            {{ mb_strtoupper($dashboard['workspace']['label']) }}
        </span>

        <h2>
            Selamat {{ now()->hour < 11 ? 'pagi' : (now()->hour < 15 ? 'siang' : (now()->hour < 18 ? 'sore' : 'malam')) }},
            {{ auth()->user()->name }}
        </h2>

        <p>{{ $dashboard['workspace']['description'] }}</p>

        <div class="dashboard-identity-row">
            <span>
                <i class="fa-solid fa-user-shield"></i>
                {{ $roles->isNotEmpty() ? $roles->implode(', ') : 'Tanpa role' }}
            </span>
            <span>
                <i class="fa-solid fa-calendar-day"></i>
                {{ today()->translatedFormat('l, d F Y') }}
            </span>
            <span>
                <i class="fa-solid fa-clock"></i>
                Diperbarui {{ $dashboard['generated_at']->format('H:i') }}
            </span>

            @if($access['sales'])
                <span title="{{ $salesScope['description'] }}">
                    <i class="fa-solid {{ $salesScope['mode'] === 'mine'
                        ? 'fa-user-tag'
                        : 'fa-users' }}"></i>
                    {{ $salesScope['label'] }}
                </span>
            @endif

            <a href="{{ route('reports.analytics') }}">
                <i class="fa-solid fa-chart-simple"></i>
                Ringkasan POS
            </a>
        </div>
    </div>

    <div class="dashboard-hero-actions">
        @forelse($dashboard['quick_actions'] as $action)
            <a
                href="{{ route($action['route']) }}"
                class="dashboard-quick-action is-{{ $action['tone'] }}"
            >
                <span>
                    <i class="fa-solid {{ $action['icon'] }}"></i>
                </span>
                <div>
                    <strong>{{ $action['label'] }}</strong>
                    <small>{{ $action['description'] }}</small>
                </div>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        @empty
            <div class="dashboard-no-action">
                <i class="fa-solid fa-shield-halved"></i>
                <span>
                    Shortcut akan muncul sesuai permission tindakan pada role.
                </span>
            </div>
        @endforelse
    </div>
</section>

<section class="dashboard-control-bar no-print">
    <div class="dashboard-filter-tabs" data-dashboard-filters>
        <button
            type="button"
            class="is-active"
            data-dashboard-filter="all"
        >
            <i class="fa-solid fa-border-all"></i>
            Semua
        </button>

        <button
            type="button"
            data-dashboard-filter="overview"
        >
            <i class="fa-solid fa-gauge-high"></i>
            Ikhtisar
        </button>

        @if($access['sales'])
            <button
                type="button"
                data-dashboard-filter="sales"
            >
                <i class="fa-solid fa-chart-line"></i>
                Penjualan
            </button>
        @endif

        @if($access['stock'])
            <button
                type="button"
                data-dashboard-filter="stock"
            >
                <i class="fa-solid fa-boxes-stacked"></i>
                Persediaan
            </button>
        @endif

        @if($hasOperationalPanels)
            <button
                type="button"
                data-dashboard-filter="operations"
            >
                <i class="fa-solid fa-gears"></i>
                Operasional
            </button>
        @endif

        @if($hasSystemPanels)
            <button
                type="button"
                data-dashboard-filter="system"
            >
                <i class="fa-solid fa-shield-halved"></i>
                Sistem
            </button>
        @endif
    </div>

    <div class="dashboard-control-actions">
        <button
            type="button"
            data-dashboard-density
            aria-pressed="false"
            title="Ubah kerapatan tampilan dashboard"
        >
            <i class="fa-solid fa-compress"></i>
            <span>Ringkas</span>
        </button>

        <a
            href="{{ route('reports.analytics') }}"
            class="dashboard-summary-link"
            title="Buka Ringkasan POS"
        >
            <i class="fa-solid fa-chart-simple"></i>
            <span>Ringkasan</span>
        </a>

        <button
            type="button"
            data-dashboard-refresh
            title="Muat ulang data dashboard"
        >
            <i class="fa-solid fa-rotate"></i>
            <span>Segarkan</span>
        </button>
    </div>
</section>

@if(
    $access['cashier']
    && isset($dashboard['shifts'])
)
    @php
        $myShift = $dashboard['shifts']['mine'];
    @endphp

    <section
        class="dashboard-shift-banner {{ $myShift ? 'is-open' : 'is-closed' }}"
        data-dashboard-section="overview"
    >
        <span>
            <i class="fa-solid {{ $myShift ? 'fa-cash-register' : 'fa-lock' }}"></i>
        </span>

        <div>
            <small>SHIFT KASIR ANDA</small>
            @if($myShift)
                <strong>
                    Aktif sejak {{ $myShift->opened_at->format('H:i') }}
                </strong>
                <p>
                    Kas sistem:
                    <b>
                        Rp {{ number_format(
                            $myShift->expected_cash,
                            0,
                            ',',
                            '.'
                        ) }}
                    </b>
                    · {{ number_format($dashboard['shifts']['open_count']) }}
                    shift aktif
                </p>
            @else
                <strong>Belum ada shift aktif</strong>
                <p>Buka shift sebelum menerima transaksi tunai.</p>
            @endif
        </div>

        @can('shifts.manage')
            <a href="{{ route('shifts.index') }}" class="btn btn-sm">
                {{ $myShift ? 'Kelola Shift' : 'Buka Shift' }}
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        @endcan
    </section>
@endif

@if(count($dashboard['kpis']))
    <section
        class="dashboard-kpi-grid"
        data-dashboard-section="overview"
    >
        @foreach($dashboard['kpis'] as $kpi)
            <article class="dashboard-kpi is-{{ $kpi['tone'] }}">
                <span class="dashboard-kpi-icon">
                    <i class="fa-solid {{ $kpi['icon'] }}"></i>
                </span>

                <div>
                    <small>{{ $kpi['label'] }}</small>
                    <strong>
                        @if($kpi['format'] === 'money')
                            Rp {{ number_format(
                                $kpi['value'],
                                0,
                                ',',
                                '.'
                            ) }}
                        @else
                            {{ number_format($kpi['value']) }}
                        @endif
                    </strong>
                    <em>{{ $kpi['foot'] }}</em>
                </div>
            </article>
        @endforeach
    </section>
@endif

@if(count($dashboard['priorities']) || $access['sales'])
    <div class="dashboard-primary-grid" data-dashboard-row>
        @if($access['sales'])
            <section class="dashboard-panel dashboard-sales-chart" data-dashboard-section="sales" data-dashboard-panel="sales-trend">
                <header class="dashboard-panel-header">
                    <div>
                        <span><i class="fa-solid fa-chart-simple"></i></span>
                        <div>
                            <strong>
                                {{ $salesScope['mode'] === 'mine'
                                    ? 'Tren Penjualan Saya'
                                    : 'Tren Penjualan Bersih' }}
                            </strong>
                            <small>
                                {{ $salesScope['mode'] === 'mine'
                                    ? 'Tujuh hari terakhir dari transaksi akun Anda.'
                                    : 'Tujuh hari terakhir seluruh kasir setelah retur.' }}
                            </small>
                        </div>
                    </div>

                    @can('reports.sales')
                        <a href="{{ route('reports.sales') }}">
                            Lihat laporan
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    @endcan
                </header>

                <div class="dashboard-chart-summary">
                    <div>
                        <small>Hari ini</small>
                        <strong>
                            Rp {{ number_format(
                                $dashboard['sales']['net_today'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </strong>
                    </div>
                    <div>
                        <small>Retur hari ini</small>
                        <strong>
                            Rp {{ number_format(
                                $dashboard['sales']['returns_today'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </strong>
                    </div>
                    <div>
                        <small>Terbayar pada transaksi hari ini</small>
                        <strong>
                            Rp {{ number_format(
                                $dashboard['sales']['paid_today'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </strong>
                    </div>
                </div>

                <div class="dashboard-bar-chart">
                    @foreach($dashboard['sales']['trend'] as $point)
                        <div
                            class="dashboard-bar-column"
                            title="{{ $point['full_label'] }}: Rp {{ number_format($point['value'], 0, ',', '.') }}"
                        >
                            <span>
                                Rp {{ number_format(
                                    $point['value'] / 1000,
                                    0,
                                    ',',
                                    '.'
                                ) }}k
                            </span>
                            <div>
                                <i style="--dashboard-bar: {{ $point['percent'] }}%;"></i>
                            </div>
                            <small>{{ $point['label'] }}</small>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if(count($dashboard['priorities']))
            <section class="dashboard-panel dashboard-priority-panel" data-dashboard-section="overview" data-dashboard-panel="priorities">
                <header class="dashboard-panel-header">
                    <div>
                        <span><i class="fa-solid fa-bell"></i></span>
                        <div>
                            <strong>Prioritas Hari Ini</strong>
                            <small>Hal yang perlu segera ditindaklanjuti.</small>
                        </div>
                    </div>
                </header>

                <div class="dashboard-priority-list">
                    @foreach($dashboard['priorities'] as $priority)
                        @if($priority['route'])
                            <a
                                href="{{ $priority['route'] }}"
                                class="dashboard-priority is-{{ $priority['tone'] }}"
                            >
                        @else
                            <div class="dashboard-priority is-{{ $priority['tone'] }}">
                        @endif
                                <span>
                                    <i class="fa-solid {{ $priority['icon'] }}"></i>
                                </span>
                                <div>
                                    <strong>{{ $priority['title'] }}</strong>
                                    <small>{{ $priority['description'] }}</small>
                                </div>
                                @if($priority['route'])
                                    <i class="fa-solid fa-chevron-right"></i>
                                @endif
                        @if($priority['route'])
                            </a>
                        @else
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endif

@if(
    $access['sales']
    || $access['stock']
)
    <div class="dashboard-content-grid" data-dashboard-row>
        @if($access['sales'])
            <section class="dashboard-panel" data-dashboard-section="sales" data-dashboard-panel="recent-sales">
                <header class="dashboard-panel-header">
                    <div>
                        <span><i class="fa-solid fa-receipt"></i></span>
                        <div>
                            <strong>
                                {{ $salesScope['mode'] === 'mine'
                                    ? 'Penjualan Saya Terbaru'
                                    : 'Penjualan Terbaru' }}
                            </strong>
                            <small>
                                {{ $salesScope['mode'] === 'mine'
                                    ? 'Hanya transaksi yang dibuat oleh akun Anda.'
                                    : 'Transaksi terbaru dari seluruh kasir.' }}
                            </small>
                        </div>
                    </div>
                </header>

                <div class="dashboard-table-wrap">
                    <table class="table dashboard-table mb-0">
                        <thead>
                            <tr>
                                <th>Transaksi</th>
                                <th>Pelanggan</th>
                                <th>Status</th>
                                <th class="text-end">Nilai Bersih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dashboard['sales']['recent'] as $sale)
                                @php
                                    $netSale = max(
                                        0,
                                        (float) $sale->total
                                        - (float) ($sale->returned_total ?? 0)
                                    );
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('sales.show', $sale) }}">
                                            {{ $sale->number }}
                                        </a>
                                        <small>
                                            {{ $sale->sold_at->format('d/m H:i') }}
                                            · {{ $sale->cashier?->name ?? '-' }}
                                        </small>
                                    </td>
                                    <td>
                                        {{ $sale->customer?->name
                                            ?? 'Pelanggan Umum' }}
                                    </td>
                                    <td>
                                        <span class="dashboard-status is-{{ $sale->payment_status }}">
                                            {{ match($sale->payment_status) {
                                                'paid' => 'Lunas',
                                                'partial' => 'Sebagian',
                                                default => 'Belum Bayar',
                                            } }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold">
                                        Rp {{ number_format(
                                            $netSale,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="dashboard-empty">
                                            {{ $salesScope['mode'] === 'mine'
                                                ? 'Belum ada transaksi dari akun Anda.'
                                                : 'Belum ada transaksi penjualan.' }}
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if($access['stock'])
            <section class="dashboard-panel" data-dashboard-section="stock" data-dashboard-panel="low-stock">
                <header class="dashboard-panel-header">
                    <div>
                        <span><i class="fa-solid fa-boxes-stacked"></i></span>
                        <div>
                            <strong>Stok Menipis & Habis</strong>
                            <small>Saldo di bawah atau sama dengan minimum.</small>
                        </div>
                    </div>

                    <a href="{{ route('stocks.movements') }}">
                        Mutasi stok
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </header>

                <div class="dashboard-table-wrap">
                    <table class="table dashboard-table mb-0">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Gudang</th>
                                <th class="text-end">Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dashboard['stock']['low_stocks'] as $stock)
                                <tr>
                                    <td>
                                        <strong>{{ $stock->product->name }}</strong>
                                        <small>
                                            {{ $stock->product->sku }}
                                            · Min.
                                            {{ \App\Support\Number::quantity(
                                                $stock->product->minimum_stock
                                            ) }}
                                        </small>
                                    </td>
                                    <td>{{ $stock->warehouse?->name ?? '-' }}</td>
                                    <td class="text-end">
                                        <span class="dashboard-stock-value {{ (float) $stock->quantity <= 0 ? 'is-out' : 'is-low' }}">
                                            {{ \App\Support\Number::quantity(
                                                $stock->quantity
                                            ) }}
                                            {{ $stock->product->unit?->symbol }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
                                        <div class="dashboard-empty is-success">
                                            Semua stok berada di atas batas minimum.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
@endif

@if($access['stock'])
    <div class="dashboard-content-grid" data-dashboard-row>
        <section class="dashboard-panel" data-dashboard-section="stock" data-dashboard-panel="expiring-batches">
            <header class="dashboard-panel-header">
                <div>
                    <span><i class="fa-solid fa-calendar-xmark"></i></span>
                    <div>
                        <strong>Batch Segera Kedaluwarsa</strong>
                        <small>Batch dengan sisa waktu maksimal 30 hari.</small>
                    </div>
                </div>

                <a href="{{ route('stocks.batches') }}">
                    Semua batch
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </header>

            <div class="dashboard-table-wrap">
                <table class="table dashboard-table mb-0">
                    <thead>
                        <tr>
                            <th>Barang</th>
                            <th>Batch</th>
                            <th>Kedaluwarsa</th>
                            <th class="text-end">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dashboard['stock']['expiring'] as $batch)
                            @php
                                $remainingDays = today()->diffInDays(
                                    $batch->expired_at,
                                    false
                                );
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $batch->product->name }}</strong>
                                    <small>{{ $batch->warehouse?->name ?? '-' }}</small>
                                </td>
                                <td><code>{{ $batch->batch_number }}</code></td>
                                <td>
                                    <span class="dashboard-expiry {{ $remainingDays <= 7 ? 'is-urgent' : '' }}">
                                        {{ $batch->expired_at->format('d/m/Y') }}
                                        <small>
                                            {{ $remainingDays === 0
                                                ? 'Hari ini'
                                                : $remainingDays.' hari lagi' }}
                                        </small>
                                    </span>
                                </td>
                                <td class="text-end fw-bold">
                                    {{ \App\Support\Number::quantity(
                                        $batch->quantity
                                    ) }}
                                    {{ $batch->product->unit?->symbol }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="dashboard-empty is-success">
                                        Tidak ada batch yang segera kedaluwarsa.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dashboard-panel" data-dashboard-section="stock" data-dashboard-panel="warehouse-distribution">
            <header class="dashboard-panel-header">
                <div>
                    <span><i class="fa-solid fa-warehouse"></i></span>
                    <div>
                        <strong>Distribusi Persediaan</strong>
                        <small>Nilai modal dan jumlah barang per gudang.</small>
                    </div>
                </div>
            </header>

            <div class="dashboard-warehouse-list">
                @forelse($dashboard['stock']['warehouse_summary'] as $warehouse)
                    <article>
                        <span><i class="fa-solid fa-warehouse"></i></span>
                        <div>
                            <strong>{{ $warehouse['name'] }}</strong>
                            <small>
                                {{ number_format($warehouse['products']) }}
                                barang ·
                                {{ \App\Support\Number::quantity(
                                    $warehouse['quantity']
                                ) }}
                                unit dasar
                            </small>
                        </div>
                        <b>
                            Rp {{ number_format(
                                $warehouse['value'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </b>
                    </article>
                @empty
                    <div class="dashboard-empty">
                        Belum ada saldo persediaan.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endif

@if(
    $access['purchases']
    || $access['debts']
)
    <div class="dashboard-content-grid" data-dashboard-row>
        @if($access['purchases'])
            <section class="dashboard-panel" data-dashboard-section="operations" data-dashboard-panel="recent-purchases">
                <header class="dashboard-panel-header">
                    <div>
                        <span><i class="fa-solid fa-cart-shopping"></i></span>
                        <div>
                            <strong>Pembelian Terbaru</strong>
                            <small>Transaksi supplier terakhir.</small>
                        </div>
                    </div>

                    <a href="{{ route('purchases.index') }}">
                        Semua pembelian
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </header>

                <div class="dashboard-table-wrap">
                    <table class="table dashboard-table mb-0">
                        <thead>
                            <tr>
                                <th>Pembelian</th>
                                <th>Supplier</th>
                                <th>Status</th>
                                <th class="text-end">Nilai Bersih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dashboard['purchases']['recent'] as $purchase)
                                @php
                                    $netPurchase = max(
                                        0,
                                        (float) $purchase->total
                                        - (float) (
                                            $purchase->returned_total ?? 0
                                        )
                                    );
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('purchases.show', $purchase) }}">
                                            {{ $purchase->number }}
                                        </a>
                                        <small>
                                            {{ $purchase->purchase_date->format('d/m/Y') }}
                                            · {{ $purchase->warehouse?->name ?? '-' }}
                                        </small>
                                    </td>
                                    <td>
                                        {{ $purchase->supplier?->name
                                            ?? 'Tanpa supplier' }}
                                    </td>
                                    <td>
                                        <span class="dashboard-status is-{{ $purchase->payment_status }}">
                                            {{ match($purchase->payment_status) {
                                                'paid' => 'Lunas',
                                                'partial' => 'Sebagian',
                                                default => 'Belum Bayar',
                                            } }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold">
                                        Rp {{ number_format(
                                            $netPurchase,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="dashboard-empty">
                                            Belum ada transaksi pembelian.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if($access['debts'])
            <section class="dashboard-panel" data-dashboard-section="operations" data-dashboard-panel="overdue-debts">
                <header class="dashboard-panel-header">
                    <div>
                        <span><i class="fa-solid fa-file-invoice-dollar"></i></span>
                        <div>
                            <strong>Tagihan Terlambat</strong>
                            <small>Piutang pelanggan dan utang supplier.</small>
                        </div>
                    </div>

                    @can('reports.debts')
                        <a href="{{ route('reports.debts') }}">
                            Laporan
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    @endcan
                </header>

                <div class="dashboard-debt-tabs">
                    <section>
                        <header>
                            <span>Piutang</span>
                            <b>
                                Rp {{ number_format(
                                    $dashboard['debts']['overdue_receivable_value'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </b>
                        </header>

                        @forelse($dashboard['debts']['overdue_receivables'] as $sale)
                            <a href="{{ route('sales.show', $sale) }}">
                                <div>
                                    <strong>
                                        {{ $sale->customer?->name
                                            ?? 'Pelanggan Umum' }}
                                    </strong>
                                    <small>
                                        {{ $sale->number }}
                                        · terlambat
                                        {{ $sale->due_date->diffInDays(today()) }}
                                        hari
                                    </small>
                                </div>
                                <b>
                                    Rp {{ number_format(
                                        $sale->dashboard_balance,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </b>
                            </a>
                        @empty
                            <div class="dashboard-empty is-success">
                                Tidak ada piutang terlambat.
                            </div>
                        @endforelse
                    </section>

                    <section>
                        <header>
                            <span>Utang Supplier</span>
                            <b>
                                Rp {{ number_format(
                                    $dashboard['debts']['overdue_payable_value'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </b>
                        </header>

                        @forelse($dashboard['debts']['overdue_payables'] as $purchase)
                            <a href="{{ route('purchases.show', $purchase) }}">
                                <div>
                                    <strong>
                                        {{ $purchase->supplier?->name
                                            ?? 'Tanpa supplier' }}
                                    </strong>
                                    <small>
                                        {{ $purchase->number }}
                                        · terlambat
                                        {{ $purchase->due_date->diffInDays(today()) }}
                                        hari
                                    </small>
                                </div>
                                <b>
                                    Rp {{ number_format(
                                        $purchase->dashboard_balance,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </b>
                            </a>
                        @empty
                            <div class="dashboard-empty is-success">
                                Tidak ada utang terlambat.
                            </div>
                        @endforelse
                    </section>
                </div>
            </section>
        @endif
    </div>
@endif

@if(
    $access['expenses']
    || $access['promotions']
    || $access['opname']
)
    <div class="dashboard-content-grid" data-dashboard-row>
        @if($access['expenses'])
            <section class="dashboard-panel" data-dashboard-section="operations" data-dashboard-panel="monthly-expenses">
                <header class="dashboard-panel-header">
                    <div>
                        <span><i class="fa-solid fa-money-bill-transfer"></i></span>
                        <div>
                            <strong>Pengeluaran Bulan Ini</strong>
                            <small>Kategori biaya operasional terbesar.</small>
                        </div>
                    </div>

                    @can('expenses.view')
                        <a href="{{ route('expenses.index') }}">
                            Kelola
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    @endcan
                </header>

                <div class="dashboard-expense-summary">
                    <span>
                        <small>Hari ini</small>
                        <b>
                            Rp {{ number_format(
                                $dashboard['expenses']['today'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </b>
                    </span>
                    <span>
                        <small>Bulan berjalan</small>
                        <b>
                            Rp {{ number_format(
                                $dashboard['expenses']['month'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </b>
                    </span>
                </div>

                <div class="dashboard-category-list">
                    @forelse($dashboard['expenses']['top_categories'] as $category)
                        <article>
                            <span>
                                <i class="fa-solid fa-folder-open"></i>
                                {{ $category->category }}
                            </span>
                            <b>
                                Rp {{ number_format(
                                    $category->total,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </b>
                        </article>
                    @empty
                        <div class="dashboard-empty">
                            Belum ada pengeluaran bulan ini.
                        </div>
                    @endforelse
                </div>
            </section>
        @endif

        @if($access['promotions'] || $access['opname'])
            <section class="dashboard-panel" data-dashboard-section="operations" data-dashboard-panel="operational-status">
                <header class="dashboard-panel-header">
                    <div>
                        <span><i class="fa-solid fa-list-check"></i></span>
                        <div>
                            <strong>Status Operasional</strong>
                            <small>Promo, opname, dan proses yang masih aktif.</small>
                        </div>
                    </div>
                </header>

                <div class="dashboard-operation-list">
                    @if($access['promotions'])
                        <a href="{{ route('promotions.index') }}">
                            <span class="is-purple">
                                <i class="fa-solid fa-tags"></i>
                            </span>
                            <div>
                                <strong>
                                    {{ number_format(
                                        $dashboard['promotions']['active_count']
                                    ) }}
                                    promo aktif
                                </strong>
                                <small>
                                    {{ number_format(
                                        $dashboard['promotions']['scheduled_count']
                                    ) }}
                                    promo terjadwal
                                </small>
                            </div>
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    @endif

                    @if($access['opname'])
                        <a href="{{ route('opnames.index') }}">
                            <span class="is-cyan">
                                <i class="fa-solid fa-clipboard-check"></i>
                            </span>
                            <div>
                                <strong>
                                    {{ number_format(
                                        $dashboard['opnames']['draft_count']
                                    ) }}
                                    draft opname
                                </strong>
                                <small>
                                    {{ number_format(
                                        $dashboard['opnames']['submitted_count']
                                    ) }}
                                    menunggu persetujuan
                                </small>
                            </div>
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    @endif

                    @if(
                        $access['cashier']
                        && isset($dashboard['shifts'])
                    )
                        @can('shifts.manage')
                            <a href="{{ route('shifts.index') }}">
                                <span class="is-green">
                                    <i class="fa-solid fa-cash-register"></i>
                                </span>
                                <div>
                                    <strong>
                                        {{ number_format(
                                            $dashboard['shifts']['open_count']
                                        ) }}
                                        shift sedang aktif
                                    </strong>
                                    <small>
                                        Pantau kas sistem dan selisih penutupan.
                                    </small>
                                </div>
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        @endcan
                    @endif
                </div>
            </section>
        @endif
    </div>
@endif

<section class="dashboard-summary-bridge no-print">
    <span><i class="fa-solid fa-chart-simple"></i></span>
    <div>
        <strong>Analitik lengkap dipindahkan ke Ringkasan</strong>
        <small>
            Pembayaran, produk, pelanggan, kasir, grafik per jam,
            dan notifikasi kini tersusun pada satu halaman khusus.
        </small>
    </div>
    <a href="{{ route('reports.analytics') }}">
        Buka Ringkasan <i class="fa-solid fa-arrow-right"></i>
    </a>
</section>

@if(
    $access['users']
    || $access['activity']
)
    <div class="dashboard-content-grid" data-dashboard-row>
        @if($access['users'])
            <section class="dashboard-panel" data-dashboard-section="system" data-dashboard-panel="user-health">
                <header class="dashboard-panel-header">
                    <div>
                        <span><i class="fa-solid fa-users-gear"></i></span>
                        <div>
                            <strong>Kesehatan Akun Pengguna</strong>
                            <small>Status akun dan pengamanan akses.</small>
                        </div>
                    </div>

                    <a href="{{ route('users.index') }}">
                        Kelola pengguna
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </header>

                <div class="dashboard-user-health">
                    <article>
                        <span class="is-green">
                            <i class="fa-solid fa-user-check"></i>
                        </span>
                        <div>
                            <strong>{{ $dashboard['users']['active'] }}</strong>
                            <small>Akun aktif</small>
                        </div>
                    </article>
                    <article>
                        <span class="is-red">
                            <i class="fa-solid fa-user-slash"></i>
                        </span>
                        <div>
                            <strong>{{ $dashboard['users']['inactive'] }}</strong>
                            <small>Akun nonaktif</small>
                        </div>
                    </article>
                    <article>
                        <span class="is-warning">
                            <i class="fa-solid fa-envelope-circle-check"></i>
                        </span>
                        <div>
                            <strong>{{ $dashboard['users']['unverified'] }}</strong>
                            <small>Email belum terverifikasi</small>
                        </div>
                    </article>
                    @if($twoFactorFeatureEnabled)
                        <article>
                            <span class="is-purple">
                                <i class="fa-solid fa-shield-halved"></i>
                            </span>
                            <div>
                                <strong>
                                    {{ $dashboard['users']['two_factor'] }}
                                </strong>
                                <small>Menggunakan 2FA</small>
                            </div>
                        </article>
                    @else
                        <article>
                            <span class="is-purple">
                                <i class="fa-solid fa-users"></i>
                            </span>
                            <div>
                                <strong>
                                    {{ $dashboard['users']['total'] }}
                                </strong>
                                <small>Total pengguna</small>
                            </div>
                        </article>
                    @endif
                </div>
            </section>
        @endif

        @if($access['activity'])
            <section class="dashboard-panel" data-dashboard-section="system" data-dashboard-panel="recent-activity">
                <header class="dashboard-panel-header">
                    <div>
                        <span><i class="fa-solid fa-clock-rotate-left"></i></span>
                        <div>
                            <strong>Aktivitas Terbaru</strong>
                            <small>Perubahan terakhir yang tercatat sistem.</small>
                        </div>
                    </div>

                    <a href="{{ route('activity.index') }}">
                        Semua aktivitas
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </header>

                <div class="dashboard-activity-list">
                    @forelse($dashboard['activities'] as $activity)
                        <article>
                            <span>
                                <i class="fa-solid fa-bolt"></i>
                            </span>
                            <div>
                                <strong>
                                    {{ str($activity->action)
                                        ->replace(['.', '_'], ' ')
                                        ->title() }}
                                </strong>
                                <small>
                                    {{ $activity->user?->name ?? 'Sistem' }}
                                    · {{ $activity->created_at->diffForHumans() }}
                                </small>
                            </div>
                            <code>{{ $activity->ip_address ?: '-' }}</code>
                        </article>
                    @empty
                        <div class="dashboard-empty">
                            Belum ada aktivitas tercatat.
                        </div>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
@endif

@if(
    ! collect($access)->contains(true)
)
    <section class="dashboard-permission-empty" data-dashboard-section="overview">
        <i class="fa-solid fa-table-columns"></i>
        <strong>Dashboard belum mempunyai panel tambahan</strong>
        <span>
            Role Anda hanya memiliki akses dasar dashboard. Hubungi pengelola
            role untuk menambahkan permission modul yang diperlukan.
        </span>
    </section>
@endif
</div>
@endsection
