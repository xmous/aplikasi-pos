@extends('layouts.pos')
@section('title', 'Laba Rugi')
@section('body_class', 'pos-report-page pos-profit-report-page')

@section('content')
@php
    $netChange = $comparison['net_profit'] != 0
        ? (($data['net_profit'] - $comparison['net_profit']) / abs($comparison['net_profit'])) * 100
        : null;
@endphp

<div class="report-module">
    <div class="ga-page-head report-page-head">
        <div class="ga-page-title">
            <span class="ga-eyebrow"><i class="fa-solid fa-coins"></i> KINERJA KEUANGAN</span>
            <h2>Laporan Laba Rugi</h2>
            <p class="mb-0">Perhitungan memakai penjualan dan retur pada periode, harga modal item, serta pengeluaran operasional.</p>
        </div>
        @can('reports.export')
            <div class="report-export-actions">
                <a class="btn btn-outline-light" href="{{ route('reports.profit.excel',['from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')]) }}"><i class="fa-solid fa-file-excel"></i> Excel</a>
                <a class="btn btn-warning" href="{{ route('reports.profit.pdf',['from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')]) }}"><i class="fa-solid fa-file-pdf"></i> PDF</a>
            </div>
        @endcan
    </div>

    <section class="profit-filter-card">
        <form method="get" action="{{ route('reports.profit') }}">
            <div><label>Dari</label><input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="form-control"></div>
            <div><label>Sampai</label><input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="form-control"></div>
            <button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Tampilkan</button>
            <a href="{{ route('reports.profit') }}" class="btn btn-light"><i class="fa-solid fa-rotate-left"></i> Bulan Ini</a>
        </form>
    </section>

    <div class="report-stat-grid report-stat-grid-6">
        <article><span><i class="fa-solid fa-receipt"></i></span><div><small>Transaksi</small><strong>{{ number_format($data['sales_transactions']) }}</strong></div></article>
        <article class="is-primary"><span><i class="fa-solid fa-chart-line"></i></span><div><small>Penjualan Bersih</small><strong>Rp {{ number_format($data['net_revenue'],0,',','.') }}</strong></div></article>
        <article class="is-danger"><span><i class="fa-solid fa-boxes-stacked"></i></span><div><small>HPP Bersih</small><strong>Rp {{ number_format($data['net_cogs'],0,',','.') }}</strong></div></article>
        <article class="is-success"><span><i class="fa-solid fa-sack-dollar"></i></span><div><small>Laba Kotor</small><strong>Rp {{ number_format($data['gross_profit'],0,',','.') }}</strong></div></article>
        <article class="is-warning"><span><i class="fa-solid fa-money-bill-transfer"></i></span><div><small>Biaya Operasional</small><strong>Rp {{ number_format($data['expenses'],0,',','.') }}</strong></div></article>
        <article class="{{ $data['net_profit']>=0?'is-profit':'is-loss' }}"><span><i class="fa-solid {{ $data['net_profit']>=0?'fa-arrow-trend-up':'fa-arrow-trend-down' }}"></i></span><div><small>Laba Bersih</small><strong>Rp {{ number_format($data['net_profit'],0,',','.') }}</strong></div></article>
    </div>

    <div class="profit-layout">
        <section class="profit-statement-card">
            <header><div><span><i class="fa-solid fa-file-invoice-dollar"></i></span><div><strong>Rincian Laba Rugi</strong><small>{{ $from->format('d/m/Y') }} s.d. {{ $to->format('d/m/Y') }}</small></div></div></header>
            <div class="profit-statement-body">
                <div class="profit-section-label">Pendapatan</div>
                <div class="profit-line"><span>Subtotal item</span><b>Rp {{ number_format($data['sales_subtotal'],0,',','.') }}</b></div>
                <div class="profit-line is-minus"><span>Diskon penjualan</span><b>(Rp {{ number_format($data['sales_discounts'],0,',','.') }})</b></div>
                <div class="profit-line"><span>Penjualan kotor</span><b>Rp {{ number_format($data['gross_sales'],0,',','.') }}</b></div>
                <div class="profit-line is-minus"><span>Retur penjualan</span><b>(Rp {{ number_format($data['returns'],0,',','.') }})</b></div>
                <div class="profit-line is-subtotal"><span>Penjualan bersih</span><b>Rp {{ number_format($data['net_revenue'],0,',','.') }}</b></div>

                <div class="profit-section-label">Harga Pokok Penjualan</div>
                <div class="profit-line"><span>HPP barang terjual</span><b>Rp {{ number_format($data['sales_cogs'],0,',','.') }}</b></div>
                <div class="profit-line is-plus"><span>Pemulihan HPP dari retur yang direstok</span><b>Rp {{ number_format($data['returned_cogs'],0,',','.') }}</b></div>
                <div class="profit-line is-subtotal"><span>HPP bersih</span><b>Rp {{ number_format($data['net_cogs'],0,',','.') }}</b></div>

                <div class="profit-line is-gross"><span>Laba kotor</span><b>Rp {{ number_format($data['gross_profit'],0,',','.') }}</b></div>
                <div class="profit-line is-minus"><span>Pengeluaran operasional</span><b>(Rp {{ number_format($data['expenses'],0,',','.') }})</b></div>
                <div class="profit-line is-net {{ $data['net_profit']<0?'is-negative':'' }}"><span>Estimasi laba bersih</span><b>Rp {{ number_format($data['net_profit'],0,',','.') }}</b></div>
            </div>
        </section>

        <aside class="profit-insight-card">
            <header><i class="fa-solid fa-gauge-high"></i><strong>Indikator Periode</strong></header>
            <div class="profit-metric"><span>Margin Kotor</span><strong>{{ number_format($data['gross_margin'],2,',','.') }}%</strong><div><i style="width:{{ min(100,max(0,$data['gross_margin'])) }}%"></i></div></div>
            <div class="profit-metric"><span>Margin Bersih</span><strong>{{ number_format($data['net_margin'],2,',','.') }}%</strong><div><i style="width:{{ min(100,max(0,$data['net_margin'])) }}%"></i></div></div>
            <div class="profit-insight-row"><span>Rata-rata transaksi</span><b>Rp {{ number_format($data['average_transaction'],0,',','.') }}</b></div>
            <div class="profit-insight-row"><span>Transaksi retur</span><b>{{ number_format($data['return_transactions']) }}</b></div>
            <div class="profit-insight-row"><span>Refund aktual</span><b>Rp {{ number_format($data['refunds'],0,',','.') }}</b></div>
            <div class="profit-insight-row"><span>Jumlah pengeluaran</span><b>{{ number_format($data['expense_transactions']) }}</b></div>
            <div class="profit-comparison {{ $netChange!==null && $netChange<0?'is-down':'is-up' }}">
                <small>Dibanding periode sebelumnya</small>
                <strong>@if($netChange===null) Belum ada pembanding @else {{ $netChange>=0?'+':'' }}{{ number_format($netChange,1,',','.') }}% @endif</strong>
                <span>{{ $previousFrom->format('d/m/Y') }} s.d. {{ $previousTo->format('d/m/Y') }}</span>
            </div>
        </aside>
    </div>

    <section class="report-list-card profit-daily-card">
        <header class="report-card-header"><div><span><i class="fa-solid fa-calendar-days"></i></span><div><strong>Pergerakan Harian</strong><small>Penjualan dan retur dikelompokkan berdasarkan tanggal transaksinya masing-masing.</small></div></div></header>
        <div class="table-responsive report-table-wrap">
            <table class="table report-data-table align-middle mb-0"><thead><tr><th>Tanggal</th><th>Penjualan</th><th>Retur</th><th>Penjualan Bersih</th><th>Pengeluaran</th><th>Arus Bersih Sebelum HPP</th></tr></thead><tbody>
                @foreach($data['daily'] as $day)<tr><td>{{ $day['date']->format('d/m/Y') }}</td><td>Rp {{ number_format($day['sales'],0,',','.') }}</td><td class="text-danger">Rp {{ number_format($day['returns'],0,',','.') }}</td><td><strong>Rp {{ number_format($day['net_sales'],0,',','.') }}</strong></td><td class="text-warning">Rp {{ number_format($day['expenses'],0,',','.') }}</td><td><strong class="{{ $day['net_sales']-$day['expenses']<0?'text-danger':'text-success' }}">Rp {{ number_format($day['net_sales']-$day['expenses'],0,',','.') }}</strong></td></tr>@endforeach
            </tbody></table>
        </div>
    </section>
</div>
@endsection
