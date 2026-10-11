@extends('layouts.pos')
@section('title', 'Retur Penjualan')
@section('body_class', 'pos-sale-return-index-page')

@section('content')
@php
    $filterActive = $filters['q'] !== ''
        || $filters['refund_method'] !== 'all'
        || $filters['date_from'] !== ''
        || $filters['date_to'] !== '';

    $refundLabels = array_merge(
        ['credit_adjustment' => 'Pengurang Piutang'],
        config('pos.payment_methods')
    );
@endphp

<div class="ga-page-head sale-return-page-head">
    <div class="ga-page-title">
        <span class="ga-eyebrow">
            <i class="fa-solid fa-arrow-rotate-left"></i>
            PENGEMBALIAN BARANG PELANGGAN
        </span>
        <h2>Retur Penjualan</h2>
        <p class="mb-0">
            Pantau nilai barang kembali, pengurangan piutang, refund pelanggan,
            dan stok yang dikembalikan.
        </p>
    </div>

    <a href="{{ route('sales.pos') }}" class="btn btn-outline-light">
        <i class="fa-solid fa-cash-register"></i>
        Buka Kasir
    </a>
</div>

<div class="sale-return-final-notice">
    <i class="fa-solid fa-lock"></i>
    <div>
        <strong>Retur penjualan merupakan transaksi final</strong>
        <span>
            Retur dibuat dari struk penjualan. Setelah disimpan, stok,
            pembayaran, dan piutang langsung diperbarui serta tidak dapat
            diedit atau dihapus.
        </span>
    </div>
</div>

<div class="sale-return-stat-grid">
    <article>
        <span><i class="fa-solid fa-file-arrow-left"></i></span>
        <div>
            <small>Total retur</small>
            <strong>{{ number_format($stats['total']) }}</strong>
        </div>
    </article>

    <article class="is-month">
        <span><i class="fa-solid fa-calendar-days"></i></span>
        <div>
            <small>Retur bulan ini</small>
            <strong>{{ number_format($stats['month_count']) }}</strong>
        </div>
    </article>

    <article class="is-value">
        <span><i class="fa-solid fa-box-open"></i></span>
        <div>
            <small>Nilai barang kembali</small>
            <strong>
                Rp {{ number_format($stats['month_value'], 0, ',', '.') }}
            </strong>
        </div>
    </article>

    <article class="is-refund">
        <span><i class="fa-solid fa-money-bill-transfer"></i></span>
        <div>
            <small>Uang dikembalikan</small>
            <strong>
                Rp {{ number_format($stats['month_refund'], 0, ',', '.') }}
            </strong>
        </div>
    </article>
</div>

<section class="sale-return-list-card">
    <form
        method="get"
        action="{{ route('sale-returns.index') }}"
        class="sale-return-filter"
    >
        <div class="operations-search-field">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
                name="q"
                value="{{ $filters['q'] }}"
                class="form-control"
                placeholder="Nomor retur, transaksi, pelanggan, barang..."
                autocomplete="off"
            >
        </div>

        <select name="refund_method" class="form-select">
            <option value="all">Semua penyelesaian</option>
            @foreach($refundLabels as $key => $label)
                <option
                    value="{{ $key }}"
                    @selected($filters['refund_method'] === $key)
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <input
            name="date_from"
            value="{{ $filters['date_from'] }}"
            type="date"
            class="form-control"
            aria-label="Tanggal awal"
        >

        <input
            name="date_to"
            value="{{ $filters['date_to'] }}"
            type="date"
            class="form-control"
            aria-label="Tanggal akhir"
        >

        <button class="btn btn-primary">
            <i class="fa-solid fa-filter"></i>
            Filter
        </button>

        @if($filterActive)
            <a href="{{ route('sale-returns.index') }}" class="btn btn-light">
                <i class="fa-solid fa-xmark"></i>
                Bersihkan
            </a>
        @endif
    </form>

    <div class="operations-list-meta">
        <span>
            <i class="fa-solid fa-list"></i>
            <b>{{ number_format($returns->total()) }}</b>
            retur ditemukan
        </span>

        <form method="get">
            @foreach(request()->except('per_page', 'page') as $key => $value)
                @if(is_scalar($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <label>
                Tampilkan
                <select
                    name="per_page"
                    class="form-select form-select-sm"
                    onchange="this.form.submit()"
                >
                    @foreach([15, 20, 50] as $size)
                        <option
                            value="{{ $size }}"
                            @selected($filters['per_page'] === $size)
                        >
                            {{ $size }}
                        </option>
                    @endforeach
                </select>
            </label>
        </form>
    </div>

    <div class="table-responsive operations-table-wrap">
        <table class="table operations-data-table sale-return-data-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Retur</th>
                    <th>Penjualan</th>
                    <th>Pelanggan</th>
                    <th>Barang</th>
                    <th>Nilai Retur</th>
                    <th>Pengurang Piutang</th>
                    <th>Refund Aktual</th>
                    <th>Penyelesaian</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($returns as $return)
                    @php
                        $debtReduction = max(
                            0,
                            (float) $return->total
                                - (float) $return->refund_amount
                        );
                    @endphp

                    <tr>
                        <td>
                            <div class="sale-return-number-cell">
                                <a href="{{ route('sale-returns.show', $return) }}">
                                    {{ $return->number }}
                                </a>
                                <small>
                                    {{ $return->returned_at->format('d/m/Y H:i') }}
                                </small>
                            </div>
                        </td>

                        <td>
                            <a
                                href="{{ route('sales.show', $return->sale) }}"
                                class="sale-return-sale-link"
                            >
                                {{ $return->sale->number }}
                            </a>
                        </td>

                        <td>
                            <div class="sale-return-customer-cell">
                                <strong>
                                    {{ $return->sale->customer?->name
                                        ?? 'Pelanggan Umum' }}
                                </strong>
                                <small>
                                    {{ $return->sale->customer?->phone ?: '-' }}
                                </small>
                            </div>
                        </td>

                        <td>
                            <div class="sale-return-items-cell">
                                <strong>
                                    {{ number_format($return->items_count) }}
                                    item
                                </strong>
                                <small>
                                    {{ \App\Support\Number::quantity(
                                        $return->restocked_quantity ?? 0
                                    ) }}
                                    unit direstok
                                </small>
                            </div>
                        </td>

                        <td>
                            <strong class="sale-return-value">
                                Rp {{ number_format(
                                    $return->total,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </strong>
                        </td>

                        <td>
                            <strong class="sale-return-credit-value">
                                Rp {{ number_format(
                                    $debtReduction,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </strong>
                        </td>

                        <td>
                            <strong class="sale-return-refund-value">
                                Rp {{ number_format(
                                    $return->refund_amount,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </strong>
                        </td>

                        <td>
                            <span class="sale-return-method is-{{ $return->refund_method === 'credit_adjustment' ? 'credit' : 'refund' }}">
                                <i class="fa-solid {{ $return->refund_method === 'credit_adjustment' ? 'fa-file-invoice-dollar' : 'fa-money-bill-transfer' }}"></i>
                                {{ $refundLabels[$return->refund_method]
                                    ?? $return->refund_method }}
                            </span>
                        </td>

                        <td class="text-end">
                            <a
                                href="{{ route('sale-returns.show', $return) }}"
                                class="btn btn-sm btn-outline-primary operations-icon-button"
                                title="Lihat detail"
                            >
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="operations-empty-state">
                                <i class="fa-solid fa-arrow-rotate-left"></i>
                                <strong>Belum ada retur penjualan</strong>
                                <span>
                                    Retur dibuat melalui tombol Retur pada
                                    detail atau struk penjualan.
                                </span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($returns->hasPages())
        <div class="operations-pagination">
            {{ $returns->links() }}
        </div>
    @endif
</section>
@endsection
