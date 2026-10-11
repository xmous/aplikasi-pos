@extends('layouts.pos')
@section('title','Mutasi Stok')
@section('body_class','pos-stock-module')
@section('content')
@php
$active=$filters['q']!==''||$filters['warehouse_id']!==''||$filters['type']!=='all'||$filters['direction']!=='all'||$filters['date_from']!==''||$filters['date_to']!=='';
@endphp
<div class="ga-page-head stock-head"><div class="ga-page-title"><span class="ga-eyebrow"><i class="fa-solid fa-arrow-right-arrow-left"></i> RIWAYAT PERSEDIAAN</span><h2>Mutasi Stok</h2><p class="mb-0">Telusuri semua stok masuk dan keluar beserta gudang, batch, pengguna, dan sumber transaksinya.</p></div>@can('stock.adjust')<a href="{{ route('stocks.adjustment') }}" class="btn btn-warning"><i class="fa-solid fa-sliders"></i> Penyesuaian</a>@endcan</div>
<div class="stock-stat-grid">
<article><i class="fa-solid fa-list-check"></i><span><small>Mutasi hari ini</small><b>{{ number_format($stats['today_count']) }}</b></span></article>
<article class="in"><i class="fa-solid fa-arrow-down"></i><span><small>Masuk hari ini</small><b>+{{ \App\Support\Number::quantity($stats['today_in']) }}</b></span></article>
<article class="out"><i class="fa-solid fa-arrow-up"></i><span><small>Keluar hari ini</small><b>-{{ \App\Support\Number::quantity($stats['today_out']) }}</b></span></article>
<article class="warn"><i class="fa-solid fa-screwdriver-wrench"></i><span><small>Penyesuaian bulan ini</small><b>{{ number_format($stats['adjustments']) }}</b></span></article>
</div>
<section class="stock-card">
<form method="get" class="stock-filter stock-filter-wide">
<div class="stock-search"><i class="fa-solid fa-magnifying-glass"></i><input name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Barang, SKU, batch, gudang, atau pengguna..."></div>
<select name="warehouse_id" class="form-select"><option value="">Semua gudang</option>@foreach($warehouses as $w)<option value="{{ $w->id }}" @selected((string)$w->id===$filters['warehouse_id'])>{{ $w->name }}</option>@endforeach</select>
<select name="type" class="form-select"><option value="all">Semua sumber</option>@foreach($movementTypes as $k=>$v)<option value="{{ $k }}" @selected($filters['type']===$k)>{{ $v }}</option>@endforeach</select>
<select name="direction" class="form-select"><option value="all">Masuk & keluar</option><option value="in" @selected($filters['direction']==='in')>Stok masuk</option><option value="out" @selected($filters['direction']==='out')>Stok keluar</option></select>
<input name="date_from" value="{{ $filters['date_from'] }}" type="date" class="form-control"><input name="date_to" value="{{ $filters['date_to'] }}" type="date" class="form-control">
<button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>@if($active)<a href="{{ route('stocks.movements') }}" class="btn btn-light"><i class="fa-solid fa-xmark"></i></a>@endif
</form>
<div class="stock-meta"><span><b>{{ number_format($movements->total()) }}</b> mutasi ditemukan</span><form method="get">@foreach(request()->except('per_page','page') as $k=>$v)@if(is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach<select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">@foreach([20,30,50] as $n)<option value="{{ $n }}" @selected($filters['per_page']===$n)>{{ $n }}</option>@endforeach</select></form></div>
<div class="table-responsive"><table class="table stock-table align-middle mb-0"><thead><tr><th>Waktu</th><th>Barang</th><th>Gudang / Batch</th><th>Sumber</th><th>Perubahan</th><th>Saldo</th><th>Pengguna</th><th>Catatan</th></tr></thead><tbody>
@forelse($movements as $m)
@php
$url=match($m->type){'purchase'=>$m->reference_id?route('purchases.show',$m->reference_id):null,'sale'=>$m->reference_id?route('sales.show',$m->reference_id):null,'stock_opname'=>$m->reference_id?route('opnames.show',$m->reference_id):null,'purchase_return'=>$m->reference_id?route('purchase-returns.show',$m->reference_id):null,default=>null};
@endphp
<tr><td><div class="cell-stack"><b>{{ $m->created_at->format('d/m/Y') }}</b><small>{{ $m->created_at->format('H:i:s') }}</small></div></td><td><div class="cell-stack"><b>{{ $m->product->name }}</b><small>{{ $m->product->sku }} · {{ $m->product->unit?->symbol }}</small></div></td><td><div class="cell-stack"><b>{{ $m->warehouse->name }}</b><small>{{ $m->batch?->batch_number??'GENERAL' }}</small></div></td><td>@if($url)<a href="{{ $url }}" class="source-pill">{{ $movementTypes[$m->type]??str($m->type)->headline() }}</a>@else<span class="source-pill">{{ $movementTypes[$m->type]??str($m->type)->headline() }}</span>@endif</td><td><span class="qty-pill {{ $m->direction }}">{{ $m->direction==='in'?'+':'-' }}{{ \App\Support\Number::quantity($m->quantity) }}</span></td><td><span class="balance-flow">{{ \App\Support\Number::quantity($m->stock_before) }} <i class="fa-solid fa-arrow-right"></i> <b>{{ \App\Support\Number::quantity($m->stock_after) }}</b></span></td><td>{{ $m->user?->name??'Sistem' }}</td><td><span class="truncate-note" title="{{ $m->notes }}">{{ $m->notes?:'-' }}</span></td></tr>
@empty<tr><td colspan="8"><div class="stock-empty"><i class="fa-solid fa-arrow-right-arrow-left"></i><b>Mutasi tidak ditemukan</b><span>Belum ada transaksi atau filter terlalu spesifik.</span></div></td></tr>@endforelse
</tbody></table></div>@if($movements->hasPages())<div class="stock-pagination">{{ $movements->links() }}</div>@endif
</section>
@endsection
