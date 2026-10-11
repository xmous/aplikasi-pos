@extends('layouts.pos')

@php
    $receiptAlignment = $sale->cashier
        ? $sale->cashier->preferredReceiptAlignment()
        : \App\Models\User::RECEIPT_ALIGNMENT_CENTER;

    $receiptPaperSize = $sale->cashier
        ? $sale->cashier->preferredReceiptPaperSize()
        : \App\Models\User::RECEIPT_PAPER_SIZE_58;

    $receiptAlignmentLabels = [
        'left' => 'Menjorok ke kiri',
        'center' => 'Posisi tengah',
        'right' => 'Menjorok ke kanan',
    ];
@endphp

@section('title', 'Penjualan '.$sale->number)
@section(
    'body_class',
    'thermal-receipt-page thermal-receipt-align-'.$receiptAlignment
)

@php
    $store = \App\Models\Store::query()->find($sale->store_id)
        ?? \App\Models\Store::query()->first();

    $returnTotal = (float) $sale->returns->sum('total');
    $netTotal = max(0, (float) $sale->total - $returnTotal);
    $balance = max(0, $netTotal - (float) $sale->paid_amount);

    $paymentLabels = config('pos.payment_methods', []);
    $customerName = $sale->customer?->name ?? 'Pelanggan Umum';

    $paymentStatus = match ($sale->payment_status) {
        'paid' => ['label' => 'Lunas', 'class' => 'is-paid'],
        'partial' => ['label' => 'Sebagian', 'class' => 'is-partial'],
        default => ['label' => 'Belum Lunas', 'class' => 'is-unpaid'],
    };
@endphp

@section('content')
<section class="thermal-receipt-head thermal-screen-only" data-manual-page-head>
    <div>
        <span class="ga-eyebrow">
            <i class="fa-solid fa-receipt"></i>
            DETAIL PENJUALAN
        </span>
        <h2>{{ $sale->number }}</h2>
        <p>
            {{ $sale->sold_at->translatedFormat('d F Y, H:i') }}
            · {{ $sale->cashier?->name ?? '-' }}
            · {{ $customerName }}
        </p>
    </div>

    <div class="thermal-receipt-actions">
        <a
            href="{{ route('sales.thermal-text', $sale) }}"
            class="btn thermal-txt-button"
            title="Unduh struk dalam format TXT"
            download
        >
            <i class="fa-solid fa-download"></i>
            Unduh TXT
        </a>

        <a
            href="{{ route('sales.pos') }}"
            class="btn btn-light"
            data-thermal-back
        >
            <i class="fa-solid fa-arrow-left"></i>
            Kembali ke Kasir
        </a>
    </div>
</section>

<div
    class="thermal-receipt-layout"
    data-thermal-receipt
    data-default-size="{{ $receiptPaperSize }}"
    data-default-alignment="{{ $receiptAlignment }}"
>
    <div class="thermal-receipt-workspace">
        <aside class="thermal-receipt-overview thermal-screen-only">
            <section class="thermal-info-card thermal-summary-card">
                        <header>
                            <span>
                                <i class="fa-solid fa-circle-info"></i>
                            </span>
                            <div>
                                <strong>Ringkasan Transaksi</strong>
                                <small>Status pembayaran dan transaksi.</small>
                            </div>
                        </header>

                        <div class="thermal-screen-summary">
                            <div>
                                <span>Total bersih</span>
                                <b>Rp {{ number_format($netTotal, 0, ',', '.') }}</b>
                            </div>
                            <div>
                                <span>Sudah dibayar</span>
                                <b>
                                    Rp
                                    {{ number_format(
                                        $sale->paid_amount,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </b>
                            </div>
                            <div>
                                <span>Kembalian</span>
                                <b>
                                    Rp
                                    {{ number_format(
                                        $sale->change_amount,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </b>
                            </div>
                            <div>
                                <span>Sisa piutang</span>
                                <b>Rp {{ number_format($balance, 0, ',', '.') }}</b>
                            </div>
                        </div>

                        <div class="thermal-status {{ $paymentStatus['class'] }}">
                            <i class="fa-solid fa-circle-check"></i>
                            {{ $paymentStatus['label'] }}
                        </div>
                    

                        <div class="thermal-keyboard-flow">
                            <span>
                                <kbd>Enter</kbd>
                                Cetak struk
                            </span>
                            <i class="fa-solid fa-arrow-right"></i>
                            <span>
                                <kbd>Enter</kbd>
                                Kembali ke kasir
                            </span>
                        </div>
                    </section>

            @can('sale_returns.create')
                <a
                    href="{{ route('sale-returns.create', $sale) }}"
                    class="btn btn-outline-danger w-100 thermal-overview-return"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    Buat Retur Penjualan
                </a>
            @endcan
        </aside>

        <div class="thermal-paper-stage">
        <div
            class="thermal-paper is-{{ $receiptPaperSize }}mm is-align-{{ $receiptAlignment }}"
            data-thermal-paper
            data-receipt-alignment="{{ $receiptAlignment }}"
        >
            <header class="thermal-brand">
                <strong>{{ $store?->name ?? 'POS & Stok' }}</strong>

                @if($store?->address)
                    <span>{{ $store->address }}</span>
                @endif

                @if($store?->phone)
                    <span>{{ $store->phone }}</span>
                @endif

                @if($store?->email)
                    <span>{{ $store->email }}</span>
                @endif
            </header>

            <div class="thermal-rule"></div>

            <section class="thermal-meta thermal-meta-aligned">
                <div class="thermal-meta-row thermal-meta-receipt">
                    <span class="thermal-meta-label">No. Struk</span>
                    <b class="thermal-meta-value" title="{{ $sale->number }}">
                        {{ $sale->number }}
                    </b>
                </div>
                <div class="thermal-meta-row">
                    <span class="thermal-meta-label">Tanggal</span>
                    <b class="thermal-meta-value">{{ $sale->sold_at->format('d/m/Y H:i') }}</b>
                </div>
                <div class="thermal-meta-row">
                    <span class="thermal-meta-label">Kasir</span>
                    <b class="thermal-meta-value">{{ $sale->cashier?->name ?? '-' }}</b>
                </div>
                <div class="thermal-meta-row">
                    <span class="thermal-meta-label">
                        {{ $sale->customer_id ? 'Member' : 'Pelanggan' }}
                    </span>
                    <b class="thermal-meta-value">{{ $customerName }}</b>
                </div>
            </section>

            <div class="thermal-rule"></div>

            <section class="thermal-items">
                @foreach($sale->items as $item)
                    <article class="thermal-item">
                        <strong>{{ $item->product_name }}</strong>

                        <div class="thermal-item-calculation">
                            <span>
                                {{ \App\Support\Number::quantity(
                                    $item->quantity
                                ) }}
                                {{ $item->unit?->symbol }}
                                ×
                                {{ number_format(
                                    $item->unit_price,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </span>

                            <b>
                                {{ number_format(
                                    $item->subtotal,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </b>
                        </div>

                        @if((float) $item->discount > 0)
                            <div class="thermal-item-discount">
                                <span>Diskon item</span>
                                <b>
                                    -
                                    {{ number_format(
                                        $item->discount,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </b>
                            </div>
                        @endif
                    </article>
                @endforeach
            </section>

            <div class="thermal-rule"></div>

            <section class="thermal-totals">
                <div>
                    <span>Subtotal</span>
                    <b>
                        Rp
                        {{ number_format(
                            $sale->subtotal,
                            0,
                            ',',
                            '.'
                        ) }}
                    </b>
                </div>

                @if((float) $sale->promotion_discount > 0)
                    <div>
                        <span>
                            Promo
                            @if($sale->promotion?->name)
                                ({{ $sale->promotion->name }})
                            @endif
                        </span>
                        <b>
                            - Rp
                            {{ number_format(
                                $sale->promotion_discount,
                                0,
                                ',',
                                '.'
                            ) }}
                        </b>
                    </div>
                @endif

                @if((float) $sale->manual_discount > 0)
                    <div>
                        <span>Diskon transaksi</span>
                        <b>
                            - Rp
                            {{ number_format(
                                $sale->manual_discount,
                                0,
                                ',',
                                '.'
                            ) }}
                        </b>
                    </div>
                @endif

                @if((float) $sale->points_discount > 0)
                    <div>
                        <span>
                            Poin digunakan
                            ({{ number_format(
                                $sale->points_redeemed,
                                0,
                                ',',
                                '.'
                            ) }})
                        </span>
                        <b>
                            - Rp
                            {{ number_format(
                                $sale->points_discount,
                                0,
                                ',',
                                '.'
                            ) }}
                        </b>
                    </div>
                @endif

                <div class="is-grand">
                    <span>Total</span>
                    <b>
                        Rp
                        {{ number_format(
                            $sale->total,
                            0,
                            ',',
                            '.'
                        ) }}
                    </b>
                </div>

                @if($returnTotal > 0)
                    <div>
                        <span>Retur</span>
                        <b>
                            - Rp
                            {{ number_format(
                                $returnTotal,
                                0,
                                ',',
                                '.'
                            ) }}
                        </b>
                    </div>

                    <div class="is-net">
                        <span>Total bersih</span>
                        <b>
                            Rp
                            {{ number_format(
                                $netTotal,
                                0,
                                ',',
                                '.'
                            ) }}
                        </b>
                    </div>
                @endif
            </section>

            <div class="thermal-rule"></div>

            <section class="thermal-payments">
                @foreach($sale->payments as $payment)
                    <div>
                        <span>
                            {{ $paymentLabels[$payment->method]
                                ?? str($payment->method)->headline() }}
                            @if($payment->reference_number)
                                <small>
                                    {{ $payment->reference_number }}
                                </small>
                            @endif
                        </span>

                        <b>
                            Rp
                            {{ number_format(
                                $payment->amount,
                                0,
                                ',',
                                '.'
                            ) }}
                        </b>
                    </div>
                @endforeach

                <div>
                    <span>Total dibayar</span>
                    <b>
                        Rp
                        {{ number_format(
                            $sale->paid_amount,
                            0,
                            ',',
                            '.'
                        ) }}
                    </b>
                </div>

                @if((float) $sale->change_amount > 0)
                    <div class="is-change">
                        <span>Kembalian</span>
                        <b>
                            Rp
                            {{ number_format(
                                $sale->change_amount,
                                0,
                                ',',
                                '.'
                            ) }}
                        </b>
                    </div>
                @endif

                @if($balance > 0)
                    <div class="is-balance">
                        <span>Sisa piutang</span>
                        <b>
                            Rp
                            {{ number_format(
                                $balance,
                                0,
                                ',',
                                '.'
                            ) }}
                        </b>
                    </div>

                    @if($sale->due_date)
                        <div>
                            <span>Jatuh tempo</span>
                            <b>{{ $sale->due_date->format('d/m/Y') }}</b>
                        </div>
                    @endif
                @endif
            </section>


            @if(
                $sale->customer_id
                && (
                    (int) $sale->points_redeemed > 0
                    || (int) $sale->points_earned > 0
                )
            )
                <div class="thermal-rule"></div>

                <section class="thermal-loyalty">
                    @if((int) $sale->points_redeemed > 0)
                        <div>
                            <span>Poin digunakan</span>
                            <b>
                                {{ number_format(
                                    $sale->points_redeemed,
                                    0,
                                    ',',
                                    '.'
                                ) }} poin
                            </b>
                        </div>
                    @endif

                    @if((int) $sale->points_earned > 0)
                        <div>
                            <span>
                                {{ $sale->points_awarded_at
                                    ? 'Poin diperoleh'
                                    : 'Poin menunggu lunas' }}
                            </span>
                            <b>
                                {{ number_format(
                                    max(
                                        0,
                                        (int) $sale->points_earned
                                            - (int) $sale->points_reversed
                                    ),
                                    0,
                                    ',',
                                    '.'
                                ) }} poin
                            </b>
                        </div>
                    @endif
                </section>
            @endif

            @if($sale->notes)
                <div class="thermal-rule"></div>
                <section class="thermal-notes">
                    <strong>Catatan</strong>
                    <span>{{ $sale->notes }}</span>
                </section>
            @endif

            <div class="thermal-rule"></div>

            <footer class="thermal-footer">
                <span>
                    {{ $store?->receipt_footer
                        ?: 'Terima kasih sudah berbelanja.' }}
                </span>
                <small>
                    {{ $store?->receipt_policy_text
                        ?? 'Barang yang sudah dibeli mengikuti ketentuan retur toko.' }}
                </small>
            </footer>
        </div>
    </div>
    </div>

    <aside class="thermal-receipt-side thermal-screen-only">
        <section class="thermal-info-card">
            <header>
                <span>
                    <i class="fa-solid fa-print"></i>
                </span>
                <div>
                    <strong>Cetak Thermal</strong>
                    <small>Pilih ukuran sesuai kertas printer.</small>
                </div>
            </header>


            <button
                type="button"
                class="btn btn-primary w-100 thermal-main-print"
                data-thermal-print
                data-thermal-autofocus
            >
                <i class="fa-solid fa-print"></i>
                Cetak Struk {{ $receiptPaperSize }} mm
            </button>

            <div class="thermal-size-options">
                <button
                    type="button"
                    data-thermal-size="58"
                    class="thermal-size-card is-recommended {{
                        $receiptPaperSize === 58
                            ? 'is-selected'
                            : ''
                    }}"
                    aria-pressed="{{
                        $receiptPaperSize === 58
                            ? 'true'
                            : 'false'
                    }}"
                >
                    <span>58</span>
                    <div>
                        <strong>58 mm</strong>
                        <small>
                            Cocok untuk POS-58 dan printer mini.
                        </small>
                    </div>
                    @if($receiptPaperSize === 58)
                        <em>Default</em>
                    @else
                        <em>Disarankan</em>
                    @endif
                </button>

                <button
                    type="button"
                    data-thermal-size="80"
                    class="thermal-size-card {{
                        $receiptPaperSize === 80
                            ? 'is-selected'
                            : ''
                    }}"
                    aria-pressed="{{
                        $receiptPaperSize === 80
                            ? 'true'
                            : 'false'
                    }}"
                >
                    <span>80</span>
                    <div>
                        <strong>80 mm</strong>
                        <small>
                            Lebih lebar dan nyaman dibaca.
                        </small>
                    </div>
                    @if($receiptPaperSize === 80)
                        <em>Default</em>
                    @endif
                </button>
            </div>

            <div class="thermal-selected-size">
                <span>
                    <i class="fa-solid fa-ruler-horizontal"></i>
                    Ukuran dipilih
                </span>
                <strong data-thermal-current-size>{{ $receiptPaperSize }} mm</strong>
            </div>

            <small class="thermal-size-default-note">
                Default {{ $sale->cashier?->name
                    ?? 'kasir transaksi' }}:
                <b>{{ $receiptPaperSize }} mm</b>.
                Pilihan di halaman ini hanya berlaku untuk sekali cetak.
            </small>

            <div class="thermal-print-alignment">
                <div class="thermal-print-alignment-head">
                    <span>
                        <i class="fa-solid fa-arrows-left-right"></i>
                        Posisi isi struk
                    </span>
                    <strong data-thermal-current-alignment>
                        {{ $receiptAlignmentLabels[$receiptAlignment] }}
                    </strong>
                </div>

                <div
                    class="thermal-alignment-options"
                    role="group"
                    aria-label="Pilih posisi isi struk untuk cetakan ini"
                >
                    @foreach($receiptAlignmentLabels as $key => $label)
                        <button
                            type="button"
                            class="thermal-alignment-card {{
                                $receiptAlignment === $key
                                    ? 'is-selected'
                                    : ''
                            }}"
                            data-thermal-alignment="{{ $key }}"
                            data-thermal-alignment-label="{{ $label }}"
                            aria-pressed="{{
                                $receiptAlignment === $key
                                    ? 'true'
                                    : 'false'
                            }}"
                        >
                            <i class="fa-solid {{ match($key) {
                                'left' => 'fa-align-left',
                                'right' => 'fa-align-right',
                                default => 'fa-align-center',
                            } }}"></i>
                            <span>{{ match($key) {
                                'left' => 'Kiri',
                                'right' => 'Kanan',
                                default => 'Tengah',
                            } }}</span>
                            @if($receiptAlignment === $key)
                                <em>Default</em>
                            @endif
                        </button>
                    @endforeach
                </div>

                <small>
                    Default {{ $sale->cashier?->name
                        ?? 'kasir transaksi' }}:
                    <b>{{ $receiptAlignmentLabels[$receiptAlignment] }}</b>.
                    Pilihan di halaman ini hanya berlaku untuk sekali cetak.
                </small>
            </div>


            <div class="thermal-print-guide">
                <strong>Pengaturan dialog printer</strong>
                <span>
                    Pilih printer thermal, margin <b>None/Tidak Ada</b>,
                    skala <b>100%</b>, lalu matikan
                    <b>Headers and footers</b>.
                </span>
            </div>
        </section>

        @if($balance > 0)
            <form
                method="post"
                action="{{ route('sales.payments.store', $sale) }}"
                class="thermal-info-card"
            >
                @csrf

                <header>
                    <span>
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </span>
                    <div>
                        <strong>Bayar Piutang</strong>
                        <small>
                            Sisa Rp {{ number_format($balance, 0, ',', '.') }}
                        </small>
                    </div>
                </header>

                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label">Nominal</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input
                                name="amount"
                                type="number"
                                min="1"
                                max="{{ $balance }}"
                                value="{{ old('amount', $balance) }}"
                                class="form-control"
                                required
                            >
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Metode</label>
                        <select
                            name="method"
                            class="form-select"
                            required
                        >
                            @foreach($paymentLabels as $key => $label)
                                <option
                                    value="{{ $key }}"
                                    @selected(old('method') === $key)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Referensi</label>
                        <input
                            name="reference_number"
                            value="{{ old('reference_number') }}"
                            class="form-control"
                            maxlength="255"
                            placeholder="Opsional"
                        >
                    </div>

                    <div class="col-12">
                        <button class="btn btn-primary w-100">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Simpan Pembayaran
                        </button>
                    </div>
                </div>
            </form>
        @endif

    </aside>
</div>
@endsection

