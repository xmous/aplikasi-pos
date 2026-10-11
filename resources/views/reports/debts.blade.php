@extends('layouts.pos')
@section('title', 'Utang & Piutang')
@section('body_class', 'pos-report-page pos-debt-report-page')

@section('content')
@php($filterActive=$filters['search']!=='' || $filters['due_status']!=='all')
<div class="report-module">
    <div class="ga-page-head report-page-head">
        <div class="ga-page-title">
            <span class="ga-eyebrow"><i class="fa-solid fa-scale-balanced"></i> KEWAJIBAN & TAGIHAN</span>
            <h2>Utang & Piutang</h2>
            <p class="mb-0">Pantau tagihan pelanggan dan kewajiban supplier setelah memperhitungkan retur serta pembayaran.</p>
        </div>
        @can('reports.export')<div class="report-export-actions"><a class="btn btn-outline-light" href="{{ route('reports.debts.excel',$filters) }}"><i class="fa-solid fa-file-excel"></i> Excel</a><a class="btn btn-warning" href="{{ route('reports.debts.pdf',$filters) }}"><i class="fa-solid fa-file-pdf"></i> PDF</a></div>@endcan
    </div>

    <div class="report-stat-grid report-stat-grid-6">
        <article><span><i class="fa-solid fa-user-clock"></i></span><div><small>Transaksi Piutang</small><strong>{{ number_format($summary['receivable_count']) }}</strong></div></article>
        <article class="is-success"><span><i class="fa-solid fa-hand-holding-dollar"></i></span><div><small>Total Piutang</small><strong>Rp {{ number_format($summary['receivable_total'],0,',','.') }}</strong></div></article>
        <article class="is-danger"><span><i class="fa-solid fa-triangle-exclamation"></i></span><div><small>Piutang Terlambat</small><strong>Rp {{ number_format($summary['receivable_overdue'],0,',','.') }}</strong></div></article>
        <article><span><i class="fa-solid fa-building-circle-exclamation"></i></span><div><small>Transaksi Utang</small><strong>{{ number_format($summary['payable_count']) }}</strong></div></article>
        <article class="is-warning"><span><i class="fa-solid fa-file-invoice-dollar"></i></span><div><small>Total Utang</small><strong>Rp {{ number_format($summary['payable_total'],0,',','.') }}</strong></div></article>
        <article class="{{ $summary['net_position']>=0?'is-primary':'is-loss' }}"><span><i class="fa-solid fa-scale-balanced"></i></span><div><small>Posisi Bersih</small><strong>Rp {{ number_format($summary['net_position'],0,',','.') }}</strong></div></article>
    </div>

    <section class="debt-filter-card"><form method="get" action="{{ route('reports.debts') }}"><div class="operations-search-field"><i class="fa-solid fa-magnifying-glass"></i><input name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="Nomor transaksi, pelanggan, supplier, invoice..."></div><select name="due_status" class="form-select"><option value="all">Semua Jatuh Tempo</option><option value="overdue" @selected($filters['due_status']==='overdue')>Terlambat</option><option value="due_7" @selected($filters['due_status']==='due_7')>Jatuh Tempo ≤ 7 Hari</option><option value="no_due" @selected($filters['due_status']==='no_due')>Tanpa Jatuh Tempo</option></select><button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>@if($filterActive)<a href="{{ route('reports.debts') }}" class="btn btn-light"><i class="fa-solid fa-xmark"></i> Bersihkan</a>@endif</form></section>

    <div class="debt-report-layout">
        <section class="report-list-card">
            <header class="report-card-header is-receivable"><div><span><i class="fa-solid fa-hand-holding-dollar"></i></span><div><strong>Piutang Pelanggan</strong><small>Uang yang masih harus diterima.</small></div></div><b>Rp {{ number_format($summary['receivable_total'],0,',','.') }}</b></header>
            <div class="table-responsive report-table-wrap"><table class="table report-data-table align-middle mb-0"><thead><tr><th>Transaksi</th><th>Pelanggan</th><th>Total Bersih</th><th>Dibayar</th><th>Sisa</th><th>Jatuh Tempo</th></tr></thead><tbody>
                @forelse($receivables as $sale)@php($days=$sale->due_date?today()->diffInDays($sale->due_date,false):null)<tr><td><div class="report-primary-cell"><a href="{{ route('sales.show',$sale) }}">{{ $sale->number }}</a><small>{{ $sale->sold_at->format('d/m/Y') }}</small></div></td><td><div class="report-primary-cell"><strong>{{ $sale->customer?->name??'Pelanggan Umum' }}</strong><small>{{ $sale->customer?->phone?:'-' }}</small></div></td><td>Rp {{ number_format($sale->net_total,0,',','.') }}</td><td>Rp {{ number_format($sale->paid_amount,0,',','.') }}</td><td><strong class="text-success">Rp {{ number_format($sale->balance,0,',','.') }}</strong></td><td>@if($days===null)<span class="due-status is-none">Tanpa tanggal</span>@elseif($days<0)<span class="due-status is-overdue">{{ abs($days) }} hari terlambat</span>@elseif($days===0)<span class="due-status is-today">Hari ini</span>@else<span class="due-status is-upcoming">{{ $days }} hari lagi</span>@endif</td></tr>@empty<tr><td colspan="6"><div class="report-empty"><i class="fa-solid fa-circle-check"></i><strong>Tidak ada piutang</strong><span>Semua tagihan pelanggan sudah lunas atau tidak sesuai filter.</span></div></td></tr>@endforelse
            </tbody></table></div>@if($receivables->hasPages())<div class="report-pagination">{{ $receivables->links() }}</div>@endif
        </section>

        <section class="report-list-card">
            <header class="report-card-header is-payable"><div><span><i class="fa-solid fa-file-invoice-dollar"></i></span><div><strong>Utang Supplier</strong><small>Kewajiban yang masih harus dibayar.</small></div></div><b>Rp {{ number_format($summary['payable_total'],0,',','.') }}</b></header>
            <div class="table-responsive report-table-wrap"><table class="table report-data-table align-middle mb-0"><thead><tr><th>Pembelian</th><th>Supplier</th><th>Total Bersih</th><th>Dibayar</th><th>Sisa</th><th>Jatuh Tempo</th></tr></thead><tbody>
                @forelse($payables as $purchase)@php($days=$purchase->due_date?today()->diffInDays($purchase->due_date,false):null)<tr><td><div class="report-primary-cell"><a href="{{ route('purchases.show',$purchase) }}">{{ $purchase->number }}</a><small>{{ $purchase->purchase_date->format('d/m/Y') }} · {{ $purchase->supplier_invoice?:'Tanpa invoice' }}</small></div></td><td><div class="report-primary-cell"><strong>{{ $purchase->supplier?->name??'Tanpa Supplier' }}</strong><small>{{ $purchase->supplier?->phone?:'-' }}</small></div></td><td>Rp {{ number_format($purchase->net_total,0,',','.') }}</td><td>Rp {{ number_format($purchase->paid_amount,0,',','.') }}</td><td><strong class="text-danger">Rp {{ number_format($purchase->balance,0,',','.') }}</strong></td><td>@if($days===null)<span class="due-status is-none">Tanpa tanggal</span>@elseif($days<0)<span class="due-status is-overdue">{{ abs($days) }} hari terlambat</span>@elseif($days===0)<span class="due-status is-today">Hari ini</span>@else<span class="due-status is-upcoming">{{ $days }} hari lagi</span>@endif</td></tr>@empty<tr><td colspan="6"><div class="report-empty"><i class="fa-solid fa-circle-check"></i><strong>Tidak ada utang</strong><span>Semua kewajiban supplier sudah lunas atau tidak sesuai filter.</span></div></td></tr>@endforelse
            </tbody></table></div>@if($payables->hasPages())<div class="report-pagination">{{ $payables->links() }}</div>@endif
        </section>
    </div>
</div>
@endsection
