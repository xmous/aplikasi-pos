<div class="report-module">
    @php($filterActive=$search!=='' || $from!==now()->startOfMonth()->toDateString() || $to!==now()->toDateString())

    <div class="ga-page-head report-page-head">
        <div class="ga-page-title">
            <span class="ga-eyebrow"><i class="fa-solid fa-gears"></i> AKTIVITAS OPERASIONAL</span>
            <h2>Laporan Operasional</h2>
            <p class="mb-0">Pembelian, mutasi, batch, shift, pengeluaran, retur, dan stok opname dalam satu pusat laporan.</p>
        </div>
        @can('reports.export')
            <div class="report-export-actions">
                <a class="btn btn-outline-light" href="{{ route('reports.operations.excel',['type'=>$tab,'from'=>$from,'to'=>$to,'search'=>$search]) }}"><i class="fa-solid fa-file-excel"></i> Excel</a>
                <a class="btn btn-warning" href="{{ route('reports.operations.pdf',['type'=>$tab,'from'=>$from,'to'=>$to,'search'=>$search]) }}"><i class="fa-solid fa-file-pdf"></i> PDF</a>
            </div>
        @endcan
    </div>

    <div class="operations-report-tabs">
        @foreach($tabs as $key=>$item)
            <button
                type="button"
                wire:click="selectTab('{{ $key }}')"
                class="report-tab-{{ $key }} {{ $tab === $key ? 'is-active' : '' }}"
            >
                <i class="fa-solid {{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </button>
        @endforeach
    </div>

    <div class="report-stat-grid">
        @foreach($summary as $card)
            <article><span><i class="fa-solid {{ $card['icon'] }}"></i></span><div><small>{{ $card['label'] }}</small><strong>{{ $card['value'] }}</strong></div></article>
        @endforeach
    </div>

    <section class="report-list-card">
        <div class="report-filter-grid operations-report-filter">
            <div><label>Dari</label><input wire:model.live="from" type="date" class="form-control"></div>
            <div><label>Sampai</label><input wire:model.live="to" type="date" class="form-control"></div>
            <div class="report-search-field"><label>Pencarian</label><span><i class="fa-solid fa-magnifying-glass"></i><input wire:model.live.debounce.350ms="search" class="form-control" placeholder="Cari sesuai jenis laporan..."></span></div>
            @if($filterActive)<div class="report-filter-reset"><button type="button" wire:click="clearFilters" class="btn btn-light"><i class="fa-solid fa-xmark"></i> Bersihkan</button></div>@endif
        </div>

        <div class="report-list-meta">
            <span><i class="fa-solid {{ $tabs[$tab]['icon'] }}"></i> <b>{{ number_format($rows->total()) }}</b> data {{ strtolower($tabs[$tab]['label']) }} ditemukan</span>
            <label>Tampilkan <select wire:model.live="perPage" class="form-select form-select-sm" data-no-select2><option value="15">15</option><option value="20">20</option><option value="50">50</option></select></label>
        </div>

        <div class="table-responsive report-table-wrap">
            <table class="table report-data-table align-middle mb-0">
                @if($tab==='purchases')
                    <thead><tr><th>Transaksi</th><th>Supplier & Gudang</th><th>Total Kotor</th><th>Retur</th><th>Total Bersih</th><th>Dibayar</th><th>Sisa Utang</th><th>Status</th></tr></thead>
                    <tbody>@forelse($rows as $row)<tr><td><div class="report-primary-cell"><a href="{{ route('purchases.show',$row) }}">{{ $row->number }}</a><small>{{ $row->purchase_date->format('d/m/Y') }}</small></div></td><td><div class="report-primary-cell"><strong>{{ $row->supplier?->name??'Tanpa Supplier' }}</strong><small>{{ $row->warehouse?->name??'-' }}</small></div></td><td>Rp {{ number_format($row->total,0,',','.') }}</td><td class="text-danger">Rp {{ number_format($row->returns_sum_total??0,0,',','.') }}</td><td><strong>Rp {{ number_format($row->net_total,0,',','.') }}</strong></td><td>Rp {{ number_format($row->paid_amount,0,',','.') }}</td><td><strong class="{{ $row->balance>0?'text-danger':'text-success' }}">Rp {{ number_format($row->balance,0,',','.') }}</strong></td><td><span class="report-status is-{{ $row->payment_status }}">{{ ['paid'=>'Lunas','partial'=>'Sebagian','unpaid'=>'Belum Bayar'][$row->payment_status]??$row->payment_status }}</span></td></tr>@empty<tr><td colspan="8"><div class="report-empty"><i class="fa-solid fa-cart-shopping"></i><strong>Tidak ada data pembelian</strong></div></td></tr>@endforelse</tbody>
                @elseif($tab==='movements')
                    <thead><tr><th>Waktu</th><th>Barang</th><th>Gudang & Batch</th><th>Sumber</th><th>Perubahan</th><th>Saldo</th><th>Petugas</th><th>Catatan</th></tr></thead>
                    <tbody>@forelse($rows as $row)<tr><td><div class="report-primary-cell"><strong>{{ $row->created_at->format('d/m/Y') }}</strong><small>{{ $row->created_at->format('H:i:s') }}</small></div></td><td><div class="report-primary-cell"><strong>{{ $row->product->name }}</strong><small>{{ $row->product->sku }}</small></div></td><td><div class="report-primary-cell"><strong>{{ $row->warehouse->name }}</strong><small>{{ $row->batch?->batch_number??'GENERAL' }}</small></div></td><td><span class="report-method-badge">{{ str($row->type)->replace('_',' ')->title() }}</span></td><td><strong class="{{ $row->direction==='in'?'text-success':'text-danger' }}">{{ $row->direction==='in'?'+':'-' }}{{ \App\Support\Number::quantity($row->quantity) }}</strong></td><td>{{ \App\Support\Number::quantity($row->stock_before) }} → <strong>{{ \App\Support\Number::quantity($row->stock_after) }}</strong></td><td>{{ $row->user?->name??'Sistem' }}</td><td><span class="report-truncate">{{ $row->notes?:'-' }}</span></td></tr>@empty<tr><td colspan="8"><div class="report-empty"><i class="fa-solid fa-arrow-right-arrow-left"></i><strong>Tidak ada mutasi</strong></div></td></tr>@endforelse</tbody>
                @elseif($tab==='expiry')
                    <thead><tr><th>Barang</th><th>Gudang</th><th>Batch</th><th>Kedaluwarsa</th><th>Sisa Hari</th><th>Jumlah</th><th>Nilai Modal</th><th>Status</th></tr></thead>
                    <tbody>@forelse($rows as $row)@php($days=today()->diffInDays($row->expired_at,false))<tr><td><div class="report-primary-cell"><strong>{{ $row->product->name }}</strong><small>{{ $row->product->sku }}</small></div></td><td>{{ $row->warehouse->name }}</td><td><span class="report-method-badge">{{ $row->batch_number }}</span></td><td>{{ $row->expired_at->format('d/m/Y') }}</td><td><strong class="{{ $days<0?'text-danger':($days<=30?'text-warning':'') }}">{{ $days<0?abs($days).' hari lewat':$days.' hari' }}</strong></td><td>{{ \App\Support\Number::quantity($row->quantity) }} {{ $row->product->unit?->symbol }}</td><td>Rp {{ number_format((float)$row->quantity*(float)$row->product->average_cost,0,',','.') }}</td><td><span class="batch-report-status is-{{ $days<0?'expired':($days<=30?'warning':'safe') }}">{{ $days<0?'Kedaluwarsa':($days<=30?'Segera Kedaluwarsa':'Masih Aman') }}</span></td></tr>@empty<tr><td colspan="8"><div class="report-empty"><i class="fa-solid fa-calendar-xmark"></i><strong>Tidak ada batch pada periode ini</strong></div></td></tr>@endforelse</tbody>
                @elseif($tab==='shifts')
                    <thead><tr><th>Kasir</th><th>Waktu Buka</th><th>Waktu Tutup</th><th>Kas Awal</th><th>Kas Sistem</th><th>Kas Fisik</th><th>Selisih</th><th>Status</th></tr></thead>
                    <tbody>@forelse($rows as $row)<tr><td>{{ $row->user?->name??'-' }}</td><td>{{ $row->opened_at->format('d/m/Y H:i') }}</td><td>{{ $row->closed_at?->format('d/m/Y H:i')??'-' }}</td><td>Rp {{ number_format($row->opening_cash,0,',','.') }}</td><td>Rp {{ number_format($row->expected_cash,0,',','.') }}</td><td>{{ $row->actual_cash!==null?'Rp '.number_format($row->actual_cash,0,',','.'):'-' }}</td><td><strong class="{{ (float)$row->cash_difference<0?'text-danger':((float)$row->cash_difference>0?'text-success':'') }}">{{ $row->cash_difference!==null?'Rp '.number_format($row->cash_difference,0,',','.'):'-' }}</strong></td><td><span class="shift-report-status is-{{ $row->status }}">{{ $row->status==='open'?'Terbuka':'Ditutup' }}</span></td></tr>@empty<tr><td colspan="8"><div class="report-empty"><i class="fa-solid fa-cash-register"></i><strong>Tidak ada shift</strong></div></td></tr>@endforelse</tbody>
                @elseif($tab==='expenses')
                    <thead><tr><th>Tanggal</th><th>Kategori</th><th>Metode</th><th>Petugas</th><th>Nominal</th><th>Status Shift</th><th>Catatan</th></tr></thead>
                    <tbody>@forelse($rows as $row)<tr><td>{{ $row->expense_date->format('d/m/Y') }}</td><td><span class="report-method-badge">{{ $row->category }}</span></td><td>{{ config('pos.payment_methods.'.$row->payment_method,$row->payment_method) }}</td><td>{{ $row->creator?->name??'-' }}</td><td><strong class="text-danger">Rp {{ number_format($row->amount,0,',','.') }}</strong></td><td>{{ $row->cashierShift ? ($row->cashierShift->status === 'open' ? 'Shift Aktif' : 'Shift Ditutup') : 'Tidak melalui kas' }}</td><td><span class="report-truncate">{{ $row->notes }}</span></td></tr>@empty<tr><td colspan="7"><div class="report-empty"><i class="fa-solid fa-money-bill-transfer"></i><strong>Tidak ada pengeluaran</strong></div></td></tr>@endforelse</tbody>
                @elseif($tab==='sale_returns')
                    <thead><tr><th>Retur</th><th>Penjualan & Pelanggan</th><th>Item</th><th>Nilai Retur</th><th>Pengurang Piutang</th><th>Refund Aktual</th><th>Penyelesaian</th><th>Petugas</th></tr></thead>
                    <tbody>@forelse($rows as $row)@php($credit=max(0,(float)$row->total-(float)$row->refund_amount))<tr><td><div class="report-primary-cell"><a href="{{ route('sale-returns.show',$row) }}">{{ $row->number }}</a><small>{{ $row->returned_at->format('d/m/Y H:i') }}</small></div></td><td><div class="report-primary-cell"><strong>{{ $row->sale->number }}</strong><small>{{ $row->sale->customer?->name??'Pelanggan Umum' }}</small></div></td><td>{{ number_format($row->items_count) }}</td><td>Rp {{ number_format($row->total,0,',','.') }}</td><td class="text-warning">Rp {{ number_format($credit,0,',','.') }}</td><td class="text-danger">Rp {{ number_format($row->refund_amount,0,',','.') }}</td><td><span class="report-method-badge">{{ $row->refund_method==='credit_adjustment'?'Pengurang Piutang':config('pos.payment_methods.'.$row->refund_method,$row->refund_method) }}</span></td><td>{{ $row->creator?->name??'-' }}</td></tr>@empty<tr><td colspan="8"><div class="report-empty"><i class="fa-solid fa-arrow-rotate-left"></i><strong>Tidak ada retur penjualan</strong></div></td></tr>@endforelse</tbody>
                @elseif($tab==='purchase_returns')
                    <thead><tr><th>Retur</th><th>Pembelian & Supplier</th><th>Item</th><th>Total</th><th>Petugas</th><th>Catatan</th></tr></thead>
                    <tbody>@forelse($rows as $row)<tr><td><div class="report-primary-cell"><a href="{{ route('purchase-returns.show',$row) }}">{{ $row->number }}</a><small>{{ $row->returned_at->format('d/m/Y H:i') }}</small></div></td><td><div class="report-primary-cell"><strong>{{ $row->purchase->number }}</strong><small>{{ $row->purchase->supplier?->name??'Tanpa Supplier' }}</small></div></td><td>{{ number_format($row->items_count) }}</td><td><strong>Rp {{ number_format($row->total,0,',','.') }}</strong></td><td>{{ $row->creator?->name??'-' }}</td><td><span class="report-truncate">{{ $row->notes }}</span></td></tr>@empty<tr><td colspan="6"><div class="report-empty"><i class="fa-solid fa-rotate-left"></i><strong>Tidak ada retur pembelian</strong></div></td></tr>@endforelse</tbody>
                @else
                    <thead><tr><th>Opname</th><th>Gudang</th><th>Petugas</th><th>Barang</th><th>Berbeda</th><th>Total Selisih</th><th>Status</th><th>Penyetuju</th></tr></thead>
                    <tbody>@forelse($rows as $row)<tr><td><div class="report-primary-cell"><a href="{{ route('opnames.show',$row) }}">{{ $row->number }}</a><small>{{ $row->opname_date->format('d/m/Y') }}</small></div></td><td>{{ $row->warehouse->name }}</td><td>{{ $row->creator?->name??'-' }}</td><td>{{ number_format($row->items_count) }}</td><td>{{ number_format($row->difference_items_count) }}</td><td><strong class="{{ (float)$row->difference_sum<0?'text-danger':((float)$row->difference_sum>0?'text-success':'') }}">{{ (float)$row->difference_sum>0?'+':'' }}{{ \App\Support\Number::quantity($row->difference_sum??0) }}</strong></td><td><span class="opname-report-status is-{{ $row->status }}">{{ ['draft'=>'Draft','submitted'=>'Menunggu Persetujuan','approved'=>'Disetujui'][$row->status]??$row->status }}</span></td><td>{{ $row->approver?->name??'-' }}</td></tr>@empty<tr><td colspan="8"><div class="report-empty"><i class="fa-solid fa-clipboard-check"></i><strong>Tidak ada stok opname</strong></div></td></tr>@endforelse</tbody>
                @endif
            </table>
        </div>
        @if($rows->hasPages())<div class="report-pagination">{{ $rows->links() }}</div>@endif
    </section>
</div>
