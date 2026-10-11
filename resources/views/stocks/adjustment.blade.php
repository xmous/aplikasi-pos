@extends('layouts.pos')

@section('title', 'Penyesuaian Stok')

@section('content')
    <div class="stock-adjustment-head">
        <div class="ga-page-title">
            <span class="ga-eyebrow">
                <i class="fa-solid fa-sliders"></i>
                KOREKSI PERSEDIAAN
            </span>
            <h2 class="mb-1">Penyesuaian Stok</h2>
            <p class="mb-0">
                Untuk stok awal, barang rusak, hilang, kedaluwarsa, atau koreksi fisik.
            </p>
        </div>

        <div class="stock-adjustment-actions">
            <a href="{{ route('stocks.movements') }}" class="btn btn-outline-light">
                <i class="fa-solid fa-clock-rotate-left"></i>
                Riwayat
            </a>
        </div>
    </div>

    <div class="ga-notice ga-notice-warning mb-3">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <strong>Saldo stok langsung berubah</strong><br>
        Pembelian, penjualan, retur, dan opname sudah membuat mutasi otomatis.
        Jangan dicatat ulang melalui menu ini.
    </div>

    <form method="post" action="{{ route('stocks.adjustment.store') }}">
        @csrf

        <div class="card border-0 stock-adjustment-form-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Gudang</label>
                        <select name="warehouse_id" class="form-select">
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Barang</label>
                        <select name="product_id" class="form-select">
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->sku }} - {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Jenis</label>
                        <select name="direction" class="form-select">
                            <option value="in">Tambah</option>
                            <option value="out">Kurangi</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Jumlah</label>
                        <input name="quantity" type="number" min=".001" step=".001" class="form-control" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Batch (stok masuk)</label>
                        <input name="batch_number" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Kedaluwarsa</label>
                        <input name="expired_at" type="date" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Alasan</label>
                        <input name="reason" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="card-footer text-end">
                <button class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Simpan Penyesuaian
                </button>
            </div>
        </div>
    </form>
@endsection
