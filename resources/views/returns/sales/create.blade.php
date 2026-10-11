@extends('layouts.pos')
@section('title', 'Retur '.$sale->number)
@section('body_class', 'pos-sale-return-create-page')

@section('content')
@php
    $currentNetTotal = max(
        0,
        (float) $sale->total - $previousReturnTotal
    );

    $currentBalance = max(
        0,
        $currentNetTotal - (float) $sale->paid_amount
    );

    $paymentMethodsEncoded = base64_encode(
        json_encode(
            config('pos.payment_methods'),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        )
    );
@endphp

<div class="ga-page-head sale-return-page-head">
    <div class="ga-page-title">
        <span class="ga-eyebrow">
            <i class="fa-solid fa-arrow-rotate-left"></i>
            RETUR TRANSAKSI PENJUALAN
        </span>
        <h2>Retur {{ $sale->number }}</h2>
        <p class="mb-0">
            {{ $sale->sold_at->format('d/m/Y H:i') }}
            · {{ $sale->customer?->name ?? 'Pelanggan Umum' }}
            · {{ $sale->warehouse?->name ?? '-' }}
        </p>
    </div>

    <a href="{{ route('sales.show', $sale) }}" class="btn btn-outline-light">
        <i class="fa-solid fa-arrow-left"></i>
        Kembali ke Struk
    </a>
</div>

@if($errors->any())
    <div class="operations-error-alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div>
            <strong>Retur belum dapat disimpan</strong>
            <span>{{ $errors->first() }}</span>
        </div>
    </div>
@endif

<div class="sale-return-accounting-notice">
    <i class="fa-solid fa-scale-balanced"></i>
    <div>
        <strong>Retur mengurangi piutang terlebih dahulu</strong>
        <span>
            Uang hanya dikembalikan jika pembayaran pelanggan melebihi
            total transaksi setelah barang diretur.
        </span>
    </div>
</div>

<div class="sale-return-sale-summary">
    <article>
        <small>Total penjualan awal</small>
        <strong>
            Rp {{ number_format($sale->total, 0, ',', '.') }}
        </strong>
    </article>

    <article>
        <small>Retur sebelumnya</small>
        <strong>
            Rp {{ number_format($previousReturnTotal, 0, ',', '.') }}
        </strong>
    </article>

    <article>
        <small>Nilai penjualan saat ini</small>
        <strong>
            Rp {{ number_format($currentNetTotal, 0, ',', '.') }}
        </strong>
    </article>

    <article>
        <small>Pembayaran yang masih tercatat</small>
        <strong>
            Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}
        </strong>
    </article>

    <article>
        <small>Piutang saat ini</small>
        <strong class="{{ $currentBalance > 0 ? 'text-danger' : 'text-success' }}">
            Rp {{ number_format($currentBalance, 0, ',', '.') }}
        </strong>
    </article>
</div>

@if(! $hasRemainingItems)
    <div class="sale-return-no-items">
        <i class="fa-solid fa-circle-check"></i>
        <div>
            <strong>Semua barang sudah diretur</strong>
            <span>
                Tidak ada sisa kuantitas yang dapat dikembalikan dari
                transaksi ini.
            </span>
        </div>
    </div>
@endif

<form
    method="post"
    action="{{ route('sale-returns.store', $sale) }}"
    class="sale-return-form"
    data-sale-return
    data-sale-number="{{ $sale->number }}"
    data-sale-total="{{ (float) $sale->total }}"
    data-paid-amount="{{ (float) $sale->paid_amount }}"
    data-previous-returns="{{ $previousReturnTotal }}"
    data-payment-methods="{{ $paymentMethodsEncoded }}"
    @if(! $hasRemainingItems) hidden @endif
>
    @csrf

    <section class="sale-return-create-card">
        <header>
            <div>
                <span><i class="fa-solid fa-box-open"></i></span>
                <div>
                    <strong>Pilih Barang Retur</strong>
                    <small>
                        Nilai retur mengikuti harga bersih setelah diskon
                        transaksi.
                    </small>
                </div>
            </div>

            <strong id="sale-return-total">Rp 0</strong>
        </header>

        <div class="table-responsive sale-return-create-table-wrap">
            <table class="table sale-return-create-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Dibeli</th>
                        <th>Sudah Retur</th>
                        <th>Sisa</th>
                        <th>Nilai/Unit</th>
                        <th>Qty Retur</th>
                        <th>Kondisi</th>
                        <th>Batch Restok</th>
                        <th class="text-end">Nilai Retur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                        @php
                            $returned = (float) $item->returnItems->sum('quantity');
                            $remaining = max(
                                0,
                                (float) $item->quantity - $returned
                            );

                            $lineNetUnit = (float) $item->quantity > 0
                                ? (float) $item->subtotal
                                    / (float) $item->quantity
                                : 0;

                            $transactionFactor = (float) $sale->subtotal > 0
                                ? min(
                                    1,
                                    (float) $sale->total
                                        / (float) $sale->subtotal
                                )
                                : 0;

                            $returnUnitValue = round(
                                $lineNetUnit * $transactionFactor,
                                2
                            );
                        @endphp

                        <tr
                            class="sale-return-row {{ $remaining <= 0 ? 'is-complete' : '' }}"
                            data-name="{{ $item->product_name }}"
                            data-price="{{ $returnUnitValue }}"
                            data-factor="{{ (float) $item->conversion_factor }}"
                            data-unit="{{ $item->unit?->symbol ?? '-' }}"
                            data-base-unit="{{ $item->product->unit?->symbol ?? '-' }}"
                            data-expiry="{{ $item->product->has_expiry ? '1' : '0' }}"
                        >
                            <td>
                                <div class="sale-return-product-cell">
                                    <strong>{{ $item->product_name }}</strong>
                                    <small>
                                        {{ $item->sku }}
                                        · {{ $item->unit?->symbol }}
                                    </small>
                                </div>
                            </td>

                            <td>
                                {{ \App\Support\Number::quantity(
                                    $item->quantity
                                ) }}
                                {{ $item->unit?->symbol }}
                            </td>

                            <td>
                                {{ \App\Support\Number::quantity($returned) }}
                                {{ $item->unit?->symbol }}
                            </td>

                            <td>
                                <strong>
                                    {{ \App\Support\Number::quantity($remaining) }}
                                    {{ $item->unit?->symbol }}
                                </strong>
                            </td>

                            <td>
                                Rp {{ number_format(
                                    $returnUnitValue,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            <td>
                                <div class="input-group sale-return-quantity-group">
                                    <input
                                        name="items[{{ $item->id }}][quantity]"
                                        type="number"
                                        min="0"
                                        max="{{ $remaining }}"
                                        step=".001"
                                        value="{{ old(
                                            'items.'.$item->id.'.quantity',
                                            0
                                        ) }}"
                                        class="form-control sale-return-quantity"
                                        @disabled($remaining <= 0)
                                    >
                                    <span class="input-group-text">
                                        {{ $item->unit?->symbol }}
                                    </span>
                                </div>

                                <small>
                                    = <span class="sale-return-base-preview">0</span>
                                    {{ $item->product->unit?->symbol }}
                                </small>
                            </td>

                            <td>
                                <select
                                    name="items[{{ $item->id }}][condition]"
                                    class="form-select sale-return-condition"
                                    data-no-select2
                                    @disabled($remaining <= 0)
                                >
                                    <option
                                        value="restock"
                                        @selected(
                                            old(
                                                'items.'.$item->id.'.condition',
                                                'restock'
                                            ) === 'restock'
                                        )
                                    >
                                        Layak dijual kembali
                                    </option>
                                    <option
                                        value="discard"
                                        @selected(
                                            old(
                                                'items.'.$item->id.'.condition'
                                            ) === 'discard'
                                        )
                                    >
                                        Rusak / tidak direstok
                                    </option>
                                </select>
                            </td>

                            <td>
                                @if($item->product->has_expiry)
                                    <div class="sale-return-batch-fields">
                                        <input
                                            name="items[{{ $item->id }}][batch_number]"
                                            value="{{ old(
                                                'items.'.$item->id.'.batch_number'
                                            ) }}"
                                            class="form-control sale-return-batch"
                                            maxlength="100"
                                            placeholder="Nomor batch"
                                            @disabled($remaining <= 0)
                                        >
                                        <input
                                            name="items[{{ $item->id }}][expired_at]"
                                            type="date"
                                            min="{{ today()->format('Y-m-d') }}"
                                            value="{{ old(
                                                'items.'.$item->id.'.expired_at'
                                            ) }}"
                                            class="form-control sale-return-expiry"
                                            @disabled($remaining <= 0)
                                        >
                                    </div>
                                @else
                                    <span class="sale-return-batch-auto">
                                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                                        Otomatis
                                    </span>
                                    <input
                                        name="items[{{ $item->id }}][batch_number]"
                                        type="hidden"
                                        class="sale-return-batch"
                                    >
                                    <input
                                        name="items[{{ $item->id }}][expired_at]"
                                        type="hidden"
                                        class="sale-return-expiry"
                                    >
                                @endif
                            </td>

                            <td class="text-end">
                                <strong class="sale-return-line-value">
                                    Rp 0
                                </strong>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="row g-3 mt-0">
        <div class="col-lg-7">
            <section class="sale-return-reason-card h-100">
                <header>
                    <span><i class="fa-solid fa-note-sticky"></i></span>
                    <div>
                        <strong>Alasan Retur</strong>
                        <small>
                            Wajib untuk audit dan penjelasan kepada pelanggan.
                        </small>
                    </div>
                </header>

                <div class="sale-return-reason-presets">
                    @foreach([
                        'Barang rusak',
                        'Barang tidak sesuai',
                        'Salah pilih barang',
                        'Jumlah berlebih',
                        'Keluhan kualitas',
                    ] as $reason)
                        <button
                            type="button"
                            data-sale-return-reason="{{ $reason }}"
                        >
                            {{ $reason }}
                        </button>
                    @endforeach
                </div>

                <textarea
                    name="notes"
                    id="sale-return-notes"
                    rows="5"
                    minlength="5"
                    maxlength="2000"
                    class="form-control"
                    required
                    placeholder="Jelaskan kondisi barang dan alasan pengembalian..."
                >{{ old('notes') }}</textarea>
            </section>
        </div>

        <div class="col-lg-5">
            <section class="sale-return-refund-card h-100">
                <header>
                    <span><i class="fa-solid fa-calculator"></i></span>
                    <div>
                        <strong>Perhitungan Penyelesaian</strong>
                        <small>
                            Sistem membedakan nilai retur dan refund aktual.
                        </small>
                    </div>
                </header>

                <div class="sale-return-breakdown">
                    <span>
                        Nilai retur barang
                        <b id="sale-return-value-preview">Rp 0</b>
                    </span>
                    <span>
                        Total transaksi baru
                        <b id="sale-return-new-net">Rp 0</b>
                    </span>
                    <span>
                        Pengurang piutang
                        <b id="sale-return-credit-preview">Rp 0</b>
                    </span>
                    <span class="is-refund">
                        Refund aktual
                        <b id="sale-return-refund-preview">Rp 0</b>
                    </span>
                    <span>
                        Pembayaran tersisa
                        <b id="sale-return-paid-preview">Rp 0</b>
                    </span>
                    <span>
                        Piutang setelah retur
                        <b id="sale-return-balance-preview">Rp 0</b>
                    </span>
                </div>

                <div
                    class="sale-return-refund-method"
                    id="sale-return-refund-method-panel"
                >
                    <label class="form-label">
                        Metode Pengembalian Dana
                    </label>
                    <select
                        name="refund_method"
                        id="sale-return-refund-method"
                        class="form-select"
                        data-no-select2
                    >
                        @foreach(config('pos.payment_methods') as $key => $label)
                            <option
                                value="{{ $key }}"
                                @selected(old('refund_method', 'cash') === $key)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <small id="sale-return-refund-caption">
                        Dipakai hanya jika ada uang yang harus dikembalikan.
                    </small>
                </div>

                <div
                    class="sale-return-settlement-status"
                    id="sale-return-settlement-status"
                >
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <div>
                        <strong>Belum ada barang dipilih</strong>
                        <small>
                            Isi qty retur untuk menghitung penyelesaian.
                        </small>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <footer class="sale-return-form-footer">
        <a href="{{ route('sales.show', $sale) }}" class="btn btn-light">
            <i class="fa-solid fa-xmark"></i>
            Batal
        </a>

        <button type="submit" class="btn btn-danger">
            <i class="fa-solid fa-arrow-rotate-left"></i>
            Periksa & Simpan Retur
        </button>
    </footer>
</form>
@endsection
