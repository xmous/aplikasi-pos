@extends('layouts.pos')
@section('title', $return->number)
@section('body_class', 'pos-sale-return-detail-page')

@section('content')
@php
    $sale = $return->sale;
    $debtReduction = max(
        0,
        (float) $return->total - (float) $return->refund_amount
    );

    $refundLabel = $return->refund_method === 'credit_adjustment'
        ? 'Pengurang Piutang'
        : config(
            'pos.payment_methods.'.$return->refund_method,
            $return->refund_method
        );
@endphp

<div class="ga-page-head sale-return-page-head">
    <div class="ga-page-title">
        <span class="ga-eyebrow">
            <i class="fa-solid fa-arrow-rotate-left"></i>
            DETAIL RETUR PENJUALAN
        </span>

        <div class="sale-return-detail-title">
            <h2>{{ $return->number }}</h2>
            <span>
                <i class="fa-solid fa-lock"></i>
                Transaksi Final
            </span>
        </div>

        <p class="mb-0">
            {{ $return->returned_at->format('d/m/Y H:i') }}
            · Penjualan {{ $sale->number }}
            · {{ $sale->customer?->name ?? 'Pelanggan Umum' }}
        </p>
    </div>

    <div class="sale-return-detail-actions no-print">
        <button
            type="button"
            class="btn btn-outline-light"
            data-print-page
        >
            <i class="fa-solid fa-print"></i>
            Cetak
        </button>

        <a href="{{ route('sale-returns.index') }}" class="btn btn-light">
            <i class="fa-solid fa-arrow-left"></i>
            Kembali
        </a>
    </div>
</div>

<div class="sale-return-final-notice no-print">
    <i class="fa-solid fa-shield-halved"></i>
    <div>
        <strong>Retur telah diterapkan ke transaksi</strong>
        <span>
            Dokumen ini tidak dapat diedit atau dihapus karena sudah
            memengaruhi stok, pembayaran, piutang, dan poin pelanggan.
        </span>
    </div>
</div>

<div class="sale-return-detail-stats">
    <article>
        <small>Nilai Barang Retur</small>
        <strong>
            Rp {{ number_format($return->total, 0, ',', '.') }}
        </strong>
    </article>

    <article class="is-credit">
        <small>Pengurang Piutang</small>
        <strong>
            Rp {{ number_format($debtReduction, 0, ',', '.') }}
        </strong>
    </article>

    <article class="is-refund">
        <small>Refund Aktual</small>
        <strong>
            Rp {{ number_format(
                $return->refund_amount,
                0,
                ',',
                '.'
            ) }}
        </strong>
    </article>

    <article>
        <small>Metode Penyelesaian</small>
        <strong>{{ $refundLabel }}</strong>
    </article>
</div>

<section class="sale-return-detail-card">
    <header>
        <div>
            <span><i class="fa-solid fa-file-invoice"></i></span>
            <div>
                <strong>Informasi Retur</strong>
                <small>
                    Dicatat oleh {{ $return->creator?->name ?? '-' }}
                </small>
            </div>
        </div>

        <a
            href="{{ route('sales.show', $sale) }}"
            class="btn btn-sm btn-outline-primary no-print"
        >
            <i class="fa-solid fa-eye"></i>
            Lihat Penjualan
        </a>
    </header>

    <div class="sale-return-detail-info">
        <span>
            <small>Nomor Retur</small>
            <b>{{ $return->number }}</b>
        </span>
        <span>
            <small>Nomor Penjualan</small>
            <b>{{ $sale->number }}</b>
        </span>
        <span>
            <small>Pelanggan</small>
            <b>{{ $sale->customer?->name ?? 'Pelanggan Umum' }}</b>
        </span>
        <span>
            <small>Gudang</small>
            <b>{{ $sale->warehouse?->name ?? '-' }}</b>
        </span>
        <span>
            <small>Kasir Penjualan</small>
            <b>{{ $sale->cashier?->name ?? '-' }}</b>
        </span>
        <span>
            <small>Petugas Retur</small>
            <b>{{ $return->creator?->name ?? '-' }}</b>
        </span>
        <span>
            <small>Waktu Retur</small>
            <b>{{ $return->returned_at->format('d/m/Y H:i') }}</b>
        </span>
        <span>
            <small>Penyelesaian</small>
            <b>{{ $refundLabel }}</b>
        </span>
    </div>

    <div class="table-responsive">
        <table class="table sale-return-detail-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Barang</th>
                    <th>Satuan</th>
                    <th>Qty Retur</th>
                    <th>Setara Dasar</th>
                    <th>Kondisi</th>
                    <th>Batch & Kedaluwarsa</th>
                    <th>Nilai/Unit</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($return->items as $item)
                    <tr>
                        <td>
                            <div class="sale-return-product-cell">
                                <strong>{{ $item->product->name }}</strong>
                                <small>{{ $item->product->sku }}</small>
                            </div>
                        </td>

                        <td>
                            {{ $item->saleItem?->unit?->symbol ?? '-' }}
                        </td>

                        <td>
                            {{ \App\Support\Number::quantity($item->quantity) }}
                        </td>

                        <td>
                            {{ \App\Support\Number::quantity(
                                (float) $item->quantity
                                    * (float) (
                                        $item->saleItem?->conversion_factor
                                        ?? 1
                                    )
                            ) }}
                            {{ $item->product->unit?->symbol }}
                        </td>

                        <td>
                            <span class="sale-return-condition-badge is-{{ $item->condition }}">
                                <i class="fa-solid {{ $item->condition === 'restock' ? 'fa-boxes-stacked' : 'fa-trash-can' }}"></i>
                                {{ $item->condition === 'restock'
                                    ? 'Kembali ke Stok'
                                    : 'Rusak / Tidak Direstok' }}
                            </span>
                        </td>

                        <td>
                            @if($item->condition === 'restock')
                                <div class="sale-return-detail-batch">
                                    <strong>
                                        {{ $item->batch_number
                                            ?: 'RETURN-'.$return->number }}
                                    </strong>
                                    <small>
                                        {{ $item->expired_at?->format('d/m/Y')
                                            ?? 'Tanpa kedaluwarsa' }}
                                    </small>
                                </div>
                            @else
                                <span class="text-muted">Tidak direstok</span>
                            @endif
                        </td>

                        <td>
                            Rp {{ number_format(
                                $item->unit_price,
                                0,
                                ',',
                                '.'
                            ) }}
                        </td>

                        <td class="text-end fw-bold">
                            Rp {{ number_format(
                                $item->subtotal,
                                0,
                                ',',
                                '.'
                            ) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="sale-return-detail-bottom">
        <div>
            <small>Alasan Retur</small>
            <p>{{ $return->notes }}</p>
        </div>

        <div class="sale-return-detail-totals">
            <span>
                Nilai barang retur
                <b>
                    Rp {{ number_format(
                        $return->total,
                        0,
                        ',',
                        '.'
                    ) }}
                </b>
            </span>
            <span>
                Pengurang piutang
                <b>
                    Rp {{ number_format(
                        $debtReduction,
                        0,
                        ',',
                        '.'
                    ) }}
                </b>
            </span>
            <span class="is-refund">
                Refund aktual
                <b>
                    Rp {{ number_format(
                        $return->refund_amount,
                        0,
                        ',',
                        '.'
                    ) }}
                </b>
            </span>
        </div>
    </div>
</section>
@endsection
