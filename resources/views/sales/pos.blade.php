@extends('layouts.pos')
@section('title', 'POS Kasir')
@section('body_class', 'pos-kasir-page')
@php
$saleProducts = $products->map(function ($product) {
    $units = collect([[
        'id' => null, 'symbol' => $product->unit->symbol, 'factor' => 1,
        'price' => (float) $product->selling_price,
        'wholesale' => $product->wholesale_price !== null ? (float) $product->wholesale_price : null,
        'min' => $product->wholesale_min_qty !== null ? (float) $product->wholesale_min_qty : null,
        'barcode' => $product->barcode,
    ]])->concat($product->conversionUnits->where('is_active', true)->map(fn ($unit) => [
        'id' => $unit->id, 'symbol' => $unit->unit->symbol,
        'factor' => (float) $unit->conversion_factor, 'price' => $unit->sellingPrice(),
        'wholesale' => null, 'min' => null, 'barcode' => $unit->barcode,
    ]))->values();

    return [
        'id' => $product->id, 'name' => $product->name, 'sku' => $product->sku,
        'category' => $product->category?->name ?? 'Tanpa kategori',
        'trackStock' => (bool) $product->track_stock,
        'stocks' => $product->sellable_stocks, 'units' => $units,
    ];
})->values();
$saleProductsEncoded = base64_encode(json_encode($saleProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
$paymentMethodsEncoded = base64_encode(json_encode(config('pos.payment_methods'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
$loyaltyEncoded = base64_encode(json_encode(
    $loyalty,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
));
@endphp

@section('content')
<div class="ga-page-head pos-cashier-head {{ $openShift ? 'is-open' : 'is-closed' }}">
    <div class="ga-page-title">
        <span class="ga-eyebrow"><i class="fa-solid fa-bolt"></i> GENERAL AFFAIR · SIGAP &amp; HANDAL</span>
        <div class="pos-cashier-title-line">
            <h2 class="mb-0">POS Kasir</h2>
            <span class="pos-shift-status-badge {{ $openShift ? 'is-open' : 'is-closed' }}">
                <i class="fa-solid {{ $openShift ? 'fa-circle-check' : 'fa-lock' }}"></i>
                {{ $openShift ? 'Shift Aktif' : 'Shift Tutup' }}
            </span>
        </div>
        <p class="mb-0">
            @if($openShift)
                Dibuka pukul {{ $openShift->opened_at->format('H:i') }} · Kas sistem Rp {{ number_format($openShift->expected_cash, 0, ',', '.') }}
            @else
                Buka shift kasir terlebih dahulu untuk mulai menerima transaksi.
            @endif
        </p>
    </div>

    <a wire:navigate href="{{ route('shifts.index') }}"
       class="btn pos-shift-manage-button {{ $openShift ? 'btn-success' : 'btn-warning' }}">
        <i class="fa-solid {{ $openShift ? 'fa-circle-check' : 'fa-door-open' }}"></i>
        {{ $openShift ? 'Shift Aktif · Kelola' : 'Buka Shift' }}
    </a>
</div>

@if(!$openShift)
<div class="pos-shift-locked-notice">
    <span class="pos-shift-locked-icon"><i class="fa-solid fa-lock"></i></span>
    <div>
        <strong>POS sedang dikunci</strong>
        <span>Produk dan keranjang tidak dapat digunakan sampai shift kasir dibuka.</span>
    </div>
    <a wire:navigate href="{{ route('shifts.index') }}" class="btn btn-warning">
        <i class="fa-solid fa-door-open"></i>Buka Shift Sekarang
    </a>
</div>
@endif

<form method="post" action="{{ route('sales.store') }}" id="sale-form"
      class="pos-kasir-form {{ $openShift ? 'is-shift-open' : 'is-shift-closed' }}"
      data-pos-sale data-products="{{ $saleProductsEncoded }}"
      data-payment-methods="{{ $paymentMethodsEncoded }}"
      data-loyalty="{{ $loyaltyEncoded }}"
      data-can-discount="{{ auth()->user()->can('sales.discount') ? 1 : 0 }}"
      data-shift-open="{{ $openShift ? 1 : 0 }}"
      data-shift-url="{{ route('shifts.index') }}">
    @csrf
    <div class="row g-3 pos-kasir-layout">
        <div class="col-xl-6 pos-kasir-column">
            <div class="card pos-kasir-products">
                <div class="card-header pos-kasir-toolbar">
                    <div class="pos-search-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input id="search" class="form-control" value="{{ $search }}"
                               placeholder="Cari nama, SKU, barcode, kategori..." autocomplete="off" @disabled(!$openShift)>
                        <button type="button" id="clear-search" class="pos-search-clear" aria-label="Hapus pencarian" @disabled(!$openShift)><i class="fa-solid fa-xmark"></i></button>
                        <kbd>F2</kbd>
                    </div>
                    <select name="warehouse_id" id="warehouse" class="form-select" aria-label="Pilih gudang" @disabled(!$openShift)>
                        @foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                    </select>
                </div>
                <div class="pos-product-meta">
                    <span><i class="fa-solid fa-boxes-stacked"></i> <b id="product-result-count">{{ $products->count() }}</b> barang ditemukan</span>
                    <span>Enter untuk barcode · F2 untuk fokus pencarian</span>
                </div>
                <div class="card-body pos-products-scroll">
                    <div class="row g-2" id="grid">
                        @foreach($products as $product)
                            @php
                                $searchBarcodes = $product->conversionUnits->pluck('barcode')->filter()->join(' ');
                                $searchText = strtolower(implode(' ', [$product->sku, $product->barcode, $searchBarcodes, $product->name, $product->category?->name, $product->unit?->name, $product->unit?->symbol]));
                            @endphp
                            <div class="col-sm-6 col-lg-6 col-xxl-4 product-card" data-search="{{ $searchText }}">
                                <button type="button" class="pos-product-button add-product" data-id="{{ $product->id }}" @disabled(!$openShift)>
                                    <span class="pos-product-icon"><i class="fa-solid fa-box"></i></span>
                                    <span class="pos-product-info">
                                        <strong>{{ $product->name }}</strong>
                                        <small>{{ $product->sku }} · {{ $product->category?->name ?? 'Tanpa kategori' }}</small>
                                        <b>Rp {{ number_format($product->selling_price, 0, ',', '.') }} / {{ $product->unit->symbol }}</b>
                                        <small class="pos-stock-text" data-stock-label="{{ $product->id }}">
                                            @if($product->track_stock) Stok {{ \App\Support\Number::quantity($product->sellable_stock) }} {{ $product->unit->symbol }}
                                            @else Tanpa pelacakan stok @endif
                                        </small>
                                    </span>
                                    <i class="fa-solid fa-plus pos-product-add"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <div id="product-empty" class="pos-product-empty d-none">
                        <i class="fa-solid fa-box-open"></i><strong>Barang tidak ditemukan</strong><span>Coba nama, SKU, barcode, atau kategori lain.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 pos-kasir-column">
            <div class="card pos-kasir-cart">
                @if(!$openShift)
                <div class="pos-cart-lock-overlay">
                    <div class="pos-cart-lock-card">
                        <span><i class="fa-solid fa-cart-shopping"></i></span>
                        <strong>Keranjang belum dapat digunakan</strong>
                        <p>Buka shift kasir agar produk, pembayaran, dan penyimpanan transaksi aktif.</p>
                        <a wire:navigate href="{{ route('shifts.index') }}" class="btn btn-warning">
                            <i class="fa-solid fa-door-open"></i>Buka Shift
                        </a>
                    </div>
                </div>
                @endif
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="fa-solid fa-cart-shopping me-2 text-warning"></i>Keranjang</strong>
                    <span class="badge rounded-pill text-bg-primary" id="cart-count">0 item</span>
                </div>
                <div class="card-body pos-cart-scroll">
                    <div id="cart"></div>
                    <div class="pos-checkout-fields">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label">Member / Pelanggan</label>
                                <div class="pos-customer-picker">
                                    <select
                                        name="customer_id"
                                        class="form-select"
                                        data-placeholder="Pilih member atau pelanggan"
                                        @disabled(!$openShift)
                                    >
                                        <option value="">
                                            Pelanggan umum (non-member)
                                        </option>
                                        @foreach($customers as $customer)
                                            <option
                                                value="{{ $customer->id }}"
                                                data-name="{{ $customer->name }}"
                                                data-phone="{{ $customer->phone }}"
                                                data-points="{{ $customer->points ?? 0 }}"
                                            >
                                                {{ $customer->name }}{{ $customer->phone ? ' · '.$customer->phone : '' }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <button
                                        type="button"
                                        class="btn pos-clear-customer d-none"
                                        data-clear-customer
                                        title="Hapus pelanggan dan kembali ke Pelanggan Umum"
                                        @disabled(!$openShift)
                                    >
                                        <i class="fa-solid fa-user-xmark"></i>
                                        <span>Hapus</span>
                                    </button>
                                </div>
                                <small class="ga-field-hint">
                                    Kosongkan untuk pelanggan umum.
                                    @if($loyalty['enabled'])
                                        Pilih member untuk mendapatkan atau
                                        menggunakan poin.
                                    @else
                                        Fitur poin sedang dinonaktifkan.
                                    @endif
                                    Histori belanja dan piutang tetap tercatat.
                                </small>
                                <div class="pos-customer-summary d-none" id="customer-summary">
                                    <div class="pos-customer-summary-icon"><i class="fa-solid fa-id-card"></i></div>
                                    <div class="pos-customer-summary-body">
                                        <strong id="customer-summary-name">Member dipilih</strong>
                                        <span id="customer-summary-meta">Nomor telepon dan poin akan tampil di sini.</span>
                                    </div>
                                </div>

                                @if($loyalty['enabled'])
                                    <section
                                        class="pos-loyalty-panel d-none"
                                        id="loyalty-panel"
                                    >
                                        <header>
                                            <span>
                                                <i class="fa-solid fa-coins"></i>
                                            </span>
                                            <div>
                                                <strong>Poin Pelanggan</strong>
                                                <small id="loyalty-balance-text">
                                                    Pilih pelanggan untuk melihat poin.
                                                </small>
                                            </div>
                                            <b id="loyalty-current-points">
                                                0 poin
                                            </b>
                                        </header>

                                        <div class="pos-loyalty-redeem">
                                            <div>
                                                <label class="form-label">
                                                    Gunakan poin
                                                </label>
                                                <div class="input-group">
                                                    <input
                                                        name="redeem_points"
                                                        id="redeem-points"
                                                        type="number"
                                                        min="0"
                                                        value="0"
                                                        class="form-control"
                                                        inputmode="numeric"
                                                    >
                                                    <span class="input-group-text">
                                                        poin
                                                    </span>
                                                </div>
                                            </div>

                                            <button
                                                type="button"
                                                class="btn pos-use-max-points"
                                                id="use-max-points"
                                                title="Gunakan poin maksimal yang diperbolehkan"
                                            >
                                                <i class="fa-solid fa-coins"></i>
                                                <span>Pakai Maks.</span>
                                            </button>
                                        </div>

                                        <div class="pos-loyalty-result">
                                            <span>
                                                <small>Potongan poin</small>
                                                <b id="loyalty-discount">
                                                    Rp 0
                                                </b>
                                            </span>
                                            <span>
                                                <small>Poin dari transaksi</small>
                                                <b id="loyalty-earned">
                                                    0 poin
                                                </b>
                                            </span>
                                        </div>

                                        <div
                                            class="pos-loyalty-notice"
                                            id="loyalty-notice"
                                        >
                                            Poin diberikan setelah transaksi lunas.
                                        </div>
                                    </section>
                                @endif
                            </div>
                            <div class="col-6">
                                <label class="form-label">Promo</label>
                                <select name="promotion_id" class="form-select" @disabled(!$openShift)>
                                    <option value="">Tanpa promo</option>
                                    @foreach($promotions as $promotion)
                                        <option value="{{ $promotion->id }}"
                                                data-name="{{ $promotion->code }} - {{ $promotion->name }}"
                                                data-type="{{ $promotion->type }}"
                                                data-value="{{ (float) $promotion->value }}"
                                                data-minimum="{{ (float) $promotion->minimum_transaction }}"
                                                data-maximum="{{ $promotion->maximum_discount !== null ? (float) $promotion->maximum_discount : '' }}"
                                                data-products="{{ $promotion->products->pluck('id')->implode(',') }}">
                                            {{ $promotion->code }} - {{ $promotion->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6"><label class="form-label">Diskon</label><input name="manual_discount" id="manual-discount" type="number" min="0" value="0" class="form-control" @disabled(!$openShift) @cannot('sales.discount') readonly @endcannot></div>
                            <div class="col-6"><label class="form-label">Jatuh Tempo</label><input name="due_date" type="date" class="form-control" @disabled(!$openShift)></div>
                            <div class="col-6"><label class="form-label">Catatan</label><input name="notes" class="form-control" @disabled(!$openShift)></div>
                        </div>
                        <div class="pos-payment-section">
                            <div class="pos-payment-heading">
                                <div>
                                    <strong>Pembayaran</strong>
                                    <small>Bisa memakai satu atau beberapa metode pembayaran.</small>
                                </div>
                                <button type="button" id="add-payment" class="btn btn-sm btn-outline-primary" @disabled(!$openShift)>
                                    <i class="fa-solid fa-plus me-1"></i>Tambah Metode
                                </button>
                            </div>
                            <div id="payments" class="pos-payment-list"></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer pos-cart-footer">
                    <div class="pos-total-lines">
                        <span>Total <strong id="total">Rp 0</strong></span>
                        <small id="total-breakdown">Subtotal Rp 0 · Diskon Rp 0</small>
                        <small>Dibayar <b id="paid-total">Rp 0</b> · Kembalian <b id="change">Rp 0</b></small>
                    </div>
                    <button class="btn btn-primary pos-pay-button" @disabled(!$openShift)><i class="fa-solid fa-floppy-disk me-2"></i>Bayar & Simpan</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
