@extends('layouts.pos')
@section('title', 'Pembelian')
@section('body_class', 'pos-purchases-page')

@section('content')
    @php
        $filterActive =
            $filters['search'] !== '' ||
            $filters['supplier_id'] !== '' ||
            $filters['payment_status'] !== 'all' ||
            $filters['date_from'] !== '' ||
            $filters['date_to'] !== '';

        $statusLabels = [
            'paid' => 'Lunas',
            'partial' => 'Sebagian',
            'unpaid' => 'Belum Bayar',
        ];
    @endphp

    <div class="ga-page-head purchase-index-head">
        <div class="ga-page-title">
            <span class="ga-eyebrow">
                <i class="fa-solid fa-cart-shopping"></i>
                PERSEDIAAN & UTANG SUPPLIER
            </span>
            <h2>Pembelian</h2>
            <p class="mb-0">Pantau barang masuk, pembayaran supplier, dan transaksi yang masih mempunyai utang.</p>
        </div>

        @can('purchases.create')
            <a href="{{ route('purchases.create') }}" class="btn btn-warning purchase-create-button">
                <i class="fa-solid fa-plus"></i>
                Barang Masuk
            </a>
        @endcan
    </div>

    <div class="purchase-index-stats">
        <article>
            <span><i class="fa-solid fa-file-invoice"></i></span>
            <div><small>Total transaksi</small><strong>{{ number_format($stats['total']) }}</strong></div>
        </article>
        <article class="is-month">
            <span><i class="fa-solid fa-calendar-days"></i></span>
            <div><small>Pembelian bulan ini</small><strong>{{ number_format($stats['month_count']) }}</strong></div>
        </article>
        <article class="is-value">
            <span><i class="fa-solid fa-coins"></i></span>
            <div><small>Nilai bulan ini</small><strong>Rp {{ number_format($stats['month_total'], 0, ',', '.') }}</strong>
            </div>
        </article>
        <article class="is-debt">
            <span><i class="fa-solid fa-clock"></i></span>
            <div><small>Belum lunas</small><strong>{{ number_format($stats['open_debt']) }}</strong></div>
        </article>
    </div>

    <section class="purchase-index-card">
        <form method="get" action="{{ route('purchases.index') }}" class="purchase-filter-form">
            <div class="purchase-index-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input name="search" value="{{ $filters['search'] }}" class="form-control"
                    placeholder="Cari nomor, invoice, supplier, atau gudang..." autocomplete="off">
            </div>

            <select name="supplier_id" class="form-select">
                <option value="">Semua supplier</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) $supplier->id === $filters['supplier_id'])>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>

            <select name="payment_status" class="form-select">
                <option value="all" @selected($filters['payment_status'] === 'all')>Semua pembayaran</option>
                <option value="paid" @selected($filters['payment_status'] === 'paid')>Lunas</option>
                <option value="partial" @selected($filters['payment_status'] === 'partial')>Sebagian</option>
                <option value="unpaid" @selected($filters['payment_status'] === 'unpaid')>Belum bayar</option>
            </select>

            <input name="date_from" value="{{ $filters['date_from'] }}" type="date" class="form-control"
                aria-label="Tanggal awal">
            <input name="date_to" value="{{ $filters['date_to'] }}" type="date" class="form-control"
                aria-label="Tanggal akhir">

            <button class="btn btn-primary" type="submit">
                <i class="fa-solid fa-filter"></i>
                Filter
            </button>

            @if ($filterActive)
                <a href="{{ route('purchases.index') }}" class="btn btn-light purchase-filter-reset">
                    <i class="fa-solid fa-xmark"></i>
                    Bersihkan
                </a>
            @endif
        </form>

        <div class="purchase-index-meta">
            <span>
                <i class="fa-solid fa-list"></i>
                <b>{{ number_format($purchases->total()) }}</b> transaksi ditemukan
            </span>

            <form method="get" action="{{ route('purchases.index') }}">
                @foreach (request()->except('per_page', 'page') as $key => $value)
                    @if (is_scalar($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label>
                    Tampilkan
                    <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach ([15, 25, 50] as $size)
                            <option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
        </div>

        <div class="table-responsive purchase-index-table-wrap">
            <table class="table purchase-index-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Transaksi</th>
                        <th>Supplier</th>
                        <th>Gudang</th>
                        <th>Barang</th>
                        <th>Total</th>
                        <th>Sisa Utang</th>
                        <th>Pembayaran</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                        @php
                            $returnedTotal = (float) ($purchase->returns_sum_total ?? 0);
                            $status = $purchase->payment_status;
                        @endphp
                        <tr>
                            <td>
                                <div class="purchase-number-cell">
                                    <a href="{{ route('purchases.show', $purchase) }}">{{ $purchase->number }}</a>
                                    <small>
                                        {{ $purchase->purchase_date->format('d/m/Y') }}
                                        @if ($purchase->supplier_invoice)
                                            · Inv. {{ $purchase->supplier_invoice }}
                                        @endif
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="purchase-supplier-cell">
                                    <strong>{{ $purchase->supplier?->name ?? 'Tanpa supplier' }}</strong>
                                    <small>{{ $purchase->supplier?->phone ?: 'Kontak tidak tersedia' }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="purchase-warehouse-cell">
                                    <i class="fa-solid fa-warehouse"></i>
                                    <span>{{ $purchase->warehouse?->name ?? '-' }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="purchase-item-count">
                                    {{ number_format($purchase->items_count) }} item
                                </span>
                            </td>
                            <td>
                                <div class="purchase-money-cell">
                                    <strong>Rp {{ number_format($purchase->net_total, 0, ',', '.') }}</strong>
                                    @if ($returnedTotal > 0)
                                        <small>Retur Rp {{ number_format($returnedTotal, 0, ',', '.') }}</small>
                                    @else
                                        <small>Bruto Rp {{ number_format($purchase->total, 0, ',', '.') }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="purchase-balance-cell {{ $purchase->balance > 0 ? 'has-debt' : 'is-paid' }}">
                                    <strong>Rp {{ number_format($purchase->balance, 0, ',', '.') }}</strong>
                                    @if ($purchase->balance > 0 && $purchase->due_date)
                                        <small>
                                            Jatuh tempo {{ $purchase->due_date->format('d/m/Y') }}
                                        </small>
                                    @else
                                        <small>{{ $purchase->balance > 0 ? 'Belum ada jatuh tempo' : 'Tidak ada utang' }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="purchase-payment-badge is-{{ $status }}">
                                    <i
                                        class="fa-solid {{ $status === 'paid' ? 'fa-circle-check' : ($status === 'partial' ? 'fa-circle-half-stroke' : 'fa-clock') }}"></i>
                                    {{ $statusLabels[$status] ?? ucfirst($status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('purchases.show', $purchase) }}"
                                    class="btn btn-sm btn-outline-primary purchase-view-button" title="Lihat detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="purchase-index-empty">
                                    <i class="fa-solid fa-cart-flatbed"></i>
                                    <strong>Belum ada transaksi pembelian</strong>
                                    <span>Tambahkan barang masuk atau ubah filter pencarian.</span>
                                    @can('purchases.create')
                                        <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-primary">
                                            Tambah Pembelian
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($purchases->hasPages())
            <div class="purchase-index-pagination">{{ $purchases->links() }}</div>
        @endif
    </section>
@endsection
