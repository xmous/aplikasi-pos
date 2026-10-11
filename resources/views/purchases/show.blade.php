@extends('layouts.pos')
@section('title', $purchase->number)
@section('body_class', 'pos-purchase-detail-page')

@section('content')
    @php
        $returnedTotal = (float) $purchase->returns->sum('total');
        $statusLabels = [
            'paid' => 'Lunas',
            'partial' => 'Dibayar Sebagian',
            'unpaid' => 'Belum Dibayar',
        ];
        $isOverdue = $purchase->balance > 0 && $purchase->due_date && $purchase->due_date->isPast();
    @endphp

    <div class="ga-page-head purchase-detail-head">
        <div class="ga-page-title">
            <span class="ga-eyebrow">
                <i class="fa-solid fa-file-invoice"></i>
                TRANSAKSI PEMBELIAN
            </span>
            <div class="purchase-detail-title-line">
                <h2>{{ $purchase->number }}</h2>
                <span class="purchase-final-badge">
                    <i class="fa-solid fa-lock"></i>
                    Transaksi Final
                </span>
            </div>
            <p class="mb-0">
                {{ $purchase->purchase_date->format('d/m/Y') }}
                · {{ $purchase->supplier?->name ?? 'Tanpa supplier' }}
                · {{ $purchase->warehouse?->name ?? 'Gudang tidak tersedia' }}
            </p>
        </div>

        <div class="purchase-detail-actions no-print">
            <button type="button" class="btn btn-outline-light" data-print-page>
                <i class="fa-solid fa-print"></i>
                Cetak
            </button>
            <a wire:navigate href="{{ route('purchases.index') }}" class="btn btn-light">
                <i class="fa-solid fa-arrow-left"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="purchase-detail-lock-notice no-print">
        <i class="fa-solid fa-shield-halved"></i>
        <div>
            <strong>Pembelian tidak diedit atau dihapus setelah diposting</strong>
            <span>Transaksi ini sudah membentuk stok, batch, harga rata-rata, dan utang supplier. Gunakan retur pembelian
                untuk mengoreksi barang.</span>
        </div>
        @can('purchase_returns.create')
            <a href="{{ route('purchase-returns.create', $purchase) }}" class="btn btn-outline-danger">
                <i class="fa-solid fa-rotate-left"></i>
                Buat Retur
            </a>
        @endcan
    </div>

    <div class="purchase-detail-stats">
        <article>
            <span><i class="fa-solid fa-coins"></i></span>
            <div><small>Total Bersih</small><strong>Rp {{ number_format($purchase->net_total, 0, ',', '.') }}</strong></div>
        </article>
        <article class="is-paid">
            <span><i class="fa-solid fa-wallet"></i></span>
            <div><small>Sudah Dibayar</small><strong>Rp {{ number_format($purchase->paid_amount, 0, ',', '.') }}</strong>
            </div>
        </article>
        <article class="{{ $purchase->balance > 0 ? 'is-debt' : 'is-settled' }}">
            <span><i class="fa-solid {{ $purchase->balance > 0 ? 'fa-clock' : 'fa-circle-check' }}"></i></span>
            <div><small>Sisa Utang</small><strong>Rp {{ number_format($purchase->balance, 0, ',', '.') }}</strong></div>
        </article>
        <article class="is-return">
            <span><i class="fa-solid fa-rotate-left"></i></span>
            <div><small>Total Retur</small><strong>Rp {{ number_format($returnedTotal, 0, ',', '.') }}</strong></div>
        </article>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <section class="purchase-detail-info-card h-100">
                <header>
                    <i class="fa-solid fa-building"></i>
                    <strong>Informasi Supplier</strong>
                </header>
                <dl>
                    <div>
                        <dt>Supplier</dt>
                        <dd>{{ $purchase->supplier?->name ?? 'Tanpa supplier' }}</dd>
                    </div>
                    <div>
                        <dt>Invoice</dt>
                        <dd>{{ $purchase->supplier_invoice ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>Gudang</dt>
                        <dd>{{ $purchase->warehouse?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt>Dicatat oleh</dt>
                        <dd>{{ $purchase->creator?->name ?? '-' }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <div class="col-lg-4">
            <section class="purchase-detail-info-card h-100">
                <header>
                    <i class="fa-solid fa-calendar-days"></i>
                    <strong>Waktu & Pembayaran</strong>
                </header>
                <dl>
                    <div>
                        <dt>Tanggal beli</dt>
                        <dd>{{ $purchase->purchase_date->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt>Jatuh tempo</dt>
                        <dd class="{{ $isOverdue ? 'text-danger fw-bold' : '' }}">
                            {{ $purchase->due_date?->format('d/m/Y') ?? '-' }}
                            @if ($isOverdue)
                                · Terlambat
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd>{{ $statusLabels[$purchase->payment_status] ?? $purchase->payment_status }}</dd>
                    </div>
                    <div>
                        <dt>Jumlah pembayaran</dt>
                        <dd>{{ number_format($purchase->payments->count()) }} kali</dd>
                    </div>
                </dl>
            </section>
        </div>

        <div class="col-lg-4">
            <section class="purchase-detail-info-card h-100">
                <header>
                    <i class="fa-solid fa-calculator"></i>
                    <strong>Perhitungan</strong>
                </header>
                <dl>
                    <div>
                        <dt>Subtotal</dt>
                        <dd>Rp {{ number_format($purchase->subtotal, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt>Diskon</dt>
                        <dd>- Rp {{ number_format($purchase->discount, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt>Biaya tambahan</dt>
                        <dd>Rp {{ number_format($purchase->additional_cost, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt>Total bruto</dt>
                        <dd>Rp {{ number_format($purchase->total, 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>

    <section class="purchase-detail-card mb-3">
        <header class="purchase-detail-card-head">
            <div>
                <span><i class="fa-solid fa-boxes-stacked"></i></span>
                <div><strong>Barang Pembelian</strong><small>{{ number_format($purchase->items->count()) }} jenis
                        barang</small></div>
            </div>
        </header>

        <div class="table-responsive">
            <table class="table purchase-detail-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Satuan</th>
                        <th>Qty</th>
                        <th>Setara Dasar</th>
                        <th>Harga</th>
                        <th>Diskon</th>
                        <th>Batch & Kedaluwarsa</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchase->items as $item)
                        <tr>
                            <td>
                                <div class="purchase-detail-product">
                                    <strong>{{ $item->product->name }}</strong>
                                    <small>{{ $item->product->sku }}</small>
                                </div>
                            </td>
                            <td>{{ $item->unit?->symbol ?? '-' }}</td>
                            <td>{{ \App\Support\Number::quantity($item->quantity) }}</td>
                            <td>
                                {{ \App\Support\Number::quantity($item->base_quantity) }}
                                {{ $item->product->unit?->symbol }}
                            </td>
                            <td>Rp {{ number_format($item->unit_cost, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($item->discount, 0, ',', '.') }}</td>
                            <td>
                                <div class="purchase-detail-batch">
                                    <strong>{{ $item->batch_number ?: 'GENERAL' }}</strong>
                                    <small>{{ $item->expired_at?->format('d/m/Y') ?? 'Tanpa kedaluwarsa' }}</small>
                                </div>
                            </td>
                            <td class="text-end fw-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($purchase->balance > 0)
        @can('purchases.payment')
            <form method="post" action="{{ route('purchases.payments.store', $purchase) }}"
                class="purchase-payment-form mb-3 no-print" data-confirm="Simpan pembayaran utang supplier ini?">
                @csrf
                <div class="purchase-payment-form-head">
                    <div>
                        <span><i class="fa-solid fa-wallet"></i></span>
                        <div>
                            <strong>Bayar Utang Supplier</strong>
                            <small>Sisa utang Rp {{ number_format($purchase->balance, 0, ',', '.') }}</small>
                        </div>
                    </div>
                </div>

                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Nominal</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input name="amount" type="number" min="1" max="{{ $purchase->balance }}" step="0.01"
                                value="{{ old('amount') }}" class="form-control" required>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Metode</label>
                        <select name="method" class="form-select" required>
                            @foreach (config('pos.payment_methods') as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Nomor Referensi</label>
                        <input name="reference_number" class="form-control" maxlength="255" placeholder="Opsional">
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <button class="btn btn-primary w-100">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Simpan Pembayaran
                        </button>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Catatan Pembayaran</label>
                        <textarea name="notes" rows="2" maxlength="2000" class="form-control" placeholder="Opsional"></textarea>
                    </div>
                </div>
            </form>
        @endcan
    @endif

    <section class="purchase-detail-card">
        <header class="purchase-detail-card-head">
            <div>
                <span><i class="fa-solid fa-clock-rotate-left"></i></span>
                <div><strong>Riwayat Pembayaran</strong><small>Semua pembayaran yang telah dicatat</small></div>
            </div>
        </header>

        <div class="table-responsive">
            <table class="table purchase-detail-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Metode</th>
                        <th>Jumlah</th>
                        <th>Referensi</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchase->payments->sortByDesc('paid_at') as $payment)
                        <tr>
                            <td>{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                            <td>{{ config('pos.payment_methods.' . $payment->method, $payment->method) }}</td>
                            <td class="fw-bold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td>{{ $payment->reference_number ?: '-' }}</td>
                            <td>{{ $payment->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="purchase-payment-empty">
                                    <i class="fa-solid fa-wallet"></i>
                                    <span>Belum ada pembayaran.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @php
        $printStore = $purchase->warehouse?->store;
        $printStatus = $statusLabels[$purchase->payment_status] ?? ucfirst($purchase->payment_status);
    @endphp

    <section class="purchase-print-sheet" aria-hidden="true">
        <header class="purchase-print-header">
            <div class="purchase-print-brand">
                <div class="purchase-print-logo">
                    @if ($printStore?->logo_path)
                        <img src="{{ asset('storage/' . $printStore->logo_path) }}" alt="{{ $printStore->name }}">
                    @else
                        <span>{{ mb_strtoupper(mb_substr($printStore?->name ?? config('app.name'), 0, 1)) }}</span>
                    @endif
                </div>

                <div>
                    <h1>{{ $printStore?->name ?? config('app.name') }}</h1>
                    @if ($printStore?->address)
                        <p>{{ $printStore->address }}</p>
                    @endif
                    <p>
                        @if ($printStore?->phone)
                            Telp. {{ $printStore->phone }}
                        @endif
                        @if ($printStore?->phone && $printStore?->email)
                            ·
                        @endif
                        @if ($printStore?->email)
                            {{ $printStore->email }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="purchase-print-document-title">
                <span>BUKTI PEMBELIAN</span>
                <strong>{{ $purchase->number }}</strong>
                <small>Dicetak {{ now()->format('d/m/Y H:i') }}</small>
            </div>
        </header>

        <div class="purchase-print-divider"></div>

        <section class="purchase-print-information">
            <div>
                <h2>Informasi Transaksi</h2>
                <dl>
                    <div>
                        <dt>Tanggal Pembelian</dt>
                        <dd>{{ $purchase->purchase_date->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt>Invoice Supplier</dt>
                        <dd>{{ $purchase->supplier_invoice ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>Gudang Tujuan</dt>
                        <dd>{{ $purchase->warehouse?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt>Petugas</dt>
                        <dd>{{ $purchase->creator?->name ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <div>
                <h2>Informasi Supplier</h2>
                <dl>
                    <div>
                        <dt>Supplier</dt>
                        <dd>{{ $purchase->supplier?->name ?? 'Tanpa supplier' }}</dd>
                    </div>
                    <div>
                        <dt>Telepon</dt>
                        <dd>{{ $purchase->supplier?->phone ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>Jatuh Tempo</dt>
                        <dd>{{ $purchase->due_date?->format('d/m/Y') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt>Status Bayar</dt>
                        <dd>{{ $printStatus }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <table class="purchase-print-table">
            <thead>
                <tr>
                    <th class="is-number">No.</th>
                    <th>Barang</th>
                    <th class="is-center">Qty</th>
                    <th class="is-center">Satuan</th>
                    <th class="is-right">Harga</th>
                    <th class="is-right">Diskon</th>
                    <th class="is-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchase->items as $item)
                    <tr>
                        <td class="is-number">{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $item->product->name }}</strong>
                            <small>
                                SKU: {{ $item->product->sku }}
                                @if ($item->batch_number)
                                    · Batch: {{ $item->batch_number }}
                                @endif
                                @if ($item->expired_at)
                                    · Exp: {{ $item->expired_at->format('d/m/Y') }}
                                @endif
                            </small>
                        </td>
                        <td class="is-center">{{ \App\Support\Number::quantity($item->quantity) }}</td>
                        <td class="is-center">{{ $item->unit?->symbol ?? '-' }}</td>
                        <td class="is-right">Rp {{ number_format($item->unit_cost, 0, ',', '.') }}</td>
                        <td class="is-right">Rp {{ number_format($item->discount, 0, ',', '.') }}</td>
                        <td class="is-right">
                            <strong>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</strong>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <section class="purchase-print-bottom">
            <div class="purchase-print-notes">
                <h2>Catatan</h2>
                <p>{{ $purchase->notes ?: 'Tidak ada catatan.' }}</p>

                @if ($returnedTotal > 0)
                    <div class="purchase-print-return-note">
                        Transaksi ini mempunyai retur sebesar
                        <strong>Rp {{ number_format($returnedTotal, 0, ',', '.') }}</strong>.
                    </div>
                @endif
            </div>

            <div class="purchase-print-totals">
                <span>
                    <small>Subtotal</small>
                    <b>Rp {{ number_format($purchase->subtotal, 0, ',', '.') }}</b>
                </span>
                <span>
                    <small>Diskon</small>
                    <b>- Rp {{ number_format($purchase->discount, 0, ',', '.') }}</b>
                </span>
                <span>
                    <small>Biaya Tambahan</small>
                    <b>Rp {{ number_format($purchase->additional_cost, 0, ',', '.') }}</b>
                </span>
                <span>
                    <small>Total Bruto</small>
                    <b>Rp {{ number_format($purchase->total, 0, ',', '.') }}</b>
                </span>
                @if ($returnedTotal > 0)
                    <span>
                        <small>Retur</small>
                        <b>- Rp {{ number_format($returnedTotal, 0, ',', '.') }}</b>
                    </span>
                @endif
                <span class="is-grand">
                    <small>Total Bersih</small>
                    <b>Rp {{ number_format($purchase->net_total, 0, ',', '.') }}</b>
                </span>
                <span>
                    <small>Sudah Dibayar</small>
                    <b>Rp {{ number_format($purchase->paid_amount, 0, ',', '.') }}</b>
                </span>
                <span class="is-balance">
                    <small>Sisa Utang</small>
                    <b>Rp {{ number_format($purchase->balance, 0, ',', '.') }}</b>
                </span>
            </div>
        </section>

        <section class="purchase-print-signatures">
            <div>
                <span>Supplier</span>
                <strong>{{ $purchase->supplier?->name ?? '________________________' }}</strong>
            </div>
            <div>
                <span>Penerima Barang</span>
                <strong>{{ $purchase->creator?->name ?? '________________________' }}</strong>
            </div>
            <div>
                <span>Mengetahui</span>
                <strong>________________________</strong>
            </div>
        </section>

        <footer class="purchase-print-footer">
            <span>Dokumen dibuat otomatis oleh sistem.</span>
            <span>{{ $purchase->number }}</span>
        </footer>
    </section>

@endsection
