@extends('layouts.pos')
@section('title', 'Barang Masuk')
@section('body_class', 'pos-purchase-page')

@php
    $purchaseProducts = $products
        ->map(
            fn($product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'expiry' => (bool) $product->has_expiry,
                'track_stock' => (bool) $product->track_stock,
                'units' => collect([
                    [
                        'id' => null,
                        'symbol' => $product->unit->symbol,
                        'factor' => 1,
                        'cost' => (float) $product->purchase_price,
                    ],
                ])
                    ->concat(
                        $product->conversionUnits->where('is_active', true)->map(
                            fn($unit) => [
                                'id' => $unit->id,
                                'symbol' => $unit->unit->symbol,
                                'factor' => (float) $unit->conversion_factor,
                                'cost' => $unit->purchasePrice(),
                            ],
                        ),
                    )
                    ->values(),
            ],
        )
        ->values();

    $purchaseProductsEncoded = base64_encode(
        json_encode($purchaseProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    );

    $purchaseInitialItems = collect(old('items', []))
        ->map(
            fn($item) => [
                'product_id' => $item['product_id'] ?? null,
                'product_unit_id' => $item['product_unit_id'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'unit_cost' => $item['unit_cost'] ?? 0,
                'discount' => $item['discount'] ?? 0,
                'batch_number' => $item['batch_number'] ?? null,
                'expired_at' => $item['expired_at'] ?? null,
            ],
        )
        ->values();

    $purchaseInitialItemsEncoded = base64_encode(
        json_encode($purchaseInitialItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    );

    $paymentMethodsEncoded = base64_encode(
        json_encode(config('pos.payment_methods'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    );
@endphp

@section('content')
    <div class="ga-page-head purchase-create-head">
        <div class="ga-page-title">
            <span class="ga-eyebrow">
                <i class="fa-solid fa-cart-flatbed"></i>
                BARANG MASUK & STOK
            </span>
            <h2>Tambah Pembelian</h2>
            <p class="mb-0">Catat barang dari supplier dan konversikan otomatis ke satuan dasar stok.</p>
        </div>

        <a wire:navigate href="{{ route('purchases.index') }}" class="btn btn-outline-light">
            <i class="fa-solid fa-arrow-left"></i>
            Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="purchase-validation-alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>
                <strong>Data pembelian belum dapat disimpan</strong>
                <span>{{ $errors->first() }}</span>
            </div>
        </div>
    @endif

    <div class="purchase-final-notice">
        <span><i class="fa-solid fa-shield-halved"></i></span>
        <div>
            <strong>Transaksi akan langsung memengaruhi stok</strong>
            <p>Periksa barang, satuan, batch, harga, dan gudang. Setelah disimpan transaksi tidak diedit atau dihapus;
                koreksi barang dilakukan melalui retur pembelian.</p>
        </div>
    </div>

    <form method="post" action="{{ route('purchases.store') }}" id="purchase-form" class="purchase-form" data-pos-purchase
        data-products="{{ $purchaseProductsEncoded }}" data-initial-items="{{ $purchaseInitialItemsEncoded }}"
        data-payment-methods="{{ $paymentMethodsEncoded }}">
        @csrf

        <div class="card purchase-meta-card mb-3">
            <div class="card-body">
                <div class="purchase-card-caption">
                    <span class="purchase-section-icon"><i class="fa-solid fa-file-invoice"></i></span>
                    <div>
                        <strong>Informasi Pembelian</strong>
                        <small>Lengkapi asal barang, tanggal transaksi, dan invoice supplier.</small>
                    </div>
                </div>

                <div class="row g-3 purchase-meta-grid">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label" for="warehouse_id">Gudang <b>*</b></label>
                        <select name="warehouse_id" id="warehouse_id" class="form-select" required>
                            <option value="">Pilih gudang tujuan</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id') === (string) $warehouse->id)>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="purchase-field-hint">Stok barang akan masuk ke gudang ini.</small>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label" for="supplier_id">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="form-select">
                            <option value="">Tanpa supplier / pembelian tunai</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>
                                    {{ $supplier->name }}{{ $supplier->code ? ' · ' . $supplier->code : '' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="purchase-field-hint">Wajib dipilih jika pembayaran belum lunas.</small>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label class="form-label" for="purchase_date">Tanggal <b>*</b></label>
                        <input name="purchase_date" id="purchase_date" type="date"
                            value="{{ old('purchase_date', today()->format('Y-m-d')) }}" class="form-control" required>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label class="form-label" for="due_date">Jatuh Tempo</label>
                        <input name="due_date" id="due_date" type="date" value="{{ old('due_date') }}"
                            class="form-control">
                        <small class="purchase-field-hint">Wajib jika masih ada utang.</small>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label class="form-label" for="supplier_invoice">Invoice Supplier</label>
                        <input name="supplier_invoice" id="supplier_invoice" value="{{ old('supplier_invoice') }}"
                            class="form-control" maxlength="255" autocomplete="off" placeholder="Contoh: INV-2026-001">
                    </div>
                </div>
            </div>
        </div>

        <div class="card purchase-items-card mb-3">
            <div class="card-header purchase-card-header">
                <div class="purchase-card-caption mb-0">
                    <span class="purchase-section-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
                    <div>
                        <strong>Daftar Barang</strong>
                        <small>Batch dan kedaluwarsa muncul otomatis pada barang yang membutuhkannya.</small>
                    </div>
                </div>

                <div class="purchase-card-actions">
                    <span class="purchase-item-counter" data-purchase-item-count>0 barang</span>
                    <button type="button" id="add-row" class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-plus"></i>
                        Tambah Baris
                    </button>
                </div>
            </div>

            <div class="card-body purchase-items-body">
                <div id="purchase-items" class="purchase-items-list"></div>
            </div>
        </div>

        <div class="card purchase-summary-card">
            <div class="card-body">
                <div class="row g-4 align-items-stretch">
                    <div class="col-lg-6">
                        <div class="purchase-notes-panel h-100">
                            <div class="purchase-card-caption">
                                <span class="purchase-section-icon"><i class="fa-solid fa-note-sticky"></i></span>
                                <div>
                                    <strong>Catatan Pembelian</strong>
                                    <small>Informasi penerimaan barang untuk gudang atau keuangan.</small>
                                </div>
                            </div>

                            <label class="form-label" for="notes">Catatan</label>
                            <textarea name="notes" id="notes" rows="6" maxlength="2000" class="form-control"
                                placeholder="Contoh: barang diterima lengkap, pembayaran termin 14 hari...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="purchase-totals-panel h-100">
                            <div class="purchase-card-caption">
                                <span class="purchase-section-icon"><i class="fa-solid fa-calculator"></i></span>
                                <div>
                                    <strong>Ringkasan Pembayaran</strong>
                                    <small>Total dan sisa utang dihitung otomatis.</small>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label" for="discount">Diskon Transaksi</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input name="discount" id="discount" type="number" min="0"
                                            step="0.01" value="{{ old('discount', 0) }}" class="form-control">
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="additional">Biaya Tambahan</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input name="additional_cost" id="additional" type="number" min="0"
                                            step="0.01" value="{{ old('additional_cost', 0) }}" class="form-control">
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="paid_amount">Bayar Awal</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input name="paid_amount" id="paid_amount" type="number" min="0"
                                            step="0.01" value="{{ old('paid_amount', 0) }}" class="form-control">
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="payment_method">Metode Pembayaran</label>
                                    <select name="payment_method" id="payment_method" class="form-select">
                                        @foreach (config('pos.payment_methods') as $key => $label)
                                            <option value="{{ $key }}" @selected(old('payment_method', 'cash') === $key)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="purchase-total-breakdown">
                                <span>Subtotal barang <b id="purchase-subtotal">Rp 0</b></span>
                                <span>Diskon transaksi <b id="purchase-discount-preview">Rp 0</b></span>
                                <span>Biaya tambahan <b id="purchase-additional-preview">Rp 0</b></span>
                                <span class="is-total">Total pembelian <b id="grand-total">Rp 0</b></span>
                                <span>Dibayar awal <b id="purchase-paid-preview">Rp 0</b></span>
                                <span class="is-balance">Sisa utang <b id="purchase-balance">Rp 0</b></span>
                            </div>

                            <div class="purchase-payment-preview is-unpaid" id="purchase-payment-preview">
                                <span><i class="fa-solid fa-clock"></i></span>
                                <div>
                                    <small>Status pembayaran</small>
                                    <strong id="purchase-payment-status">Belum dibayar</strong>
                                    <b id="purchase-payment-caption">Supplier dan jatuh tempo wajib dipilih.</b>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer purchase-form-footer">
                <a wire:navigate href="{{ route('purchases.index') }}" class="btn btn-light">
                    <i class="fa-solid fa-xmark"></i>
                    Batal
                </a>
                <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Periksa & Simpan
                </button>
            </div>
        </div>
    </form>
@endsection
