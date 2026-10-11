<div class="report-module report-page report-sales-page">
    @php
        $filterActive = $search !== ''
            || $paymentStatus !== ''
            || $paymentMethod !== ''
            || $cashierId !== ''
            || $saleStatus !== ''
            || $from !== now()->startOfMonth()->toDateString()
            || $to !== now()->toDateString();

        $activeFilterCount = collect([
            $search,
            $paymentStatus,
            $paymentMethod,
            $cashierId,
            $saleStatus,
        ])->filter(fn ($value) => filled($value))->count();

        $paymentLabels = [
            'paid' => 'Lunas',
            'partial' => 'Sebagian',
            'unpaid' => 'Belum Bayar',
        ];

        $saleLabels = [
            'completed' => 'Selesai',
            'partially_returned' => 'Retur Sebagian',
            'returned' => 'Retur Penuh',
        ];
    @endphp

    <div class="ga-page-head report-page-head">
        <div class="ga-page-title">
            <span class="ga-eyebrow report-page-eyebrow">
                <i class="fa-solid fa-chart-line"></i>
                Kinerja Penjualan
            </span>
            <h2>Laporan Penjualan</h2>
            <p class="mb-0">
                Pantau omzet, retur, penerimaan, dan piutang berdasarkan
                transaksi penjualan pada periode terpilih.
            </p>
        </div>

        @can('reports.export')
            <div class="report-export-actions">
                <a
                    class="btn btn-outline-light"
                    href="{{ route('reports.sales.excel', [
                        'from' => $from,
                        'to' => $to,
                        'search' => $search,
                        'payment_status' => $paymentStatus,
                        'payment_method' => $paymentMethod,
                        'cashier_id' => $cashierId,
                        'status' => $saleStatus,
                    ]) }}"
                >
                    <i class="fa-solid fa-file-excel"></i>
                    Excel
                </a>
                <a
                    class="btn btn-warning"
                    href="{{ route('reports.sales.pdf', [
                        'from' => $from,
                        'to' => $to,
                        'search' => $search,
                        'payment_status' => $paymentStatus,
                        'payment_method' => $paymentMethod,
                        'cashier_id' => $cashierId,
                        'status' => $saleStatus,
                    ]) }}"
                >
                    <i class="fa-solid fa-file-pdf"></i>
                    PDF
                </a>
            </div>
        @endcan
    </div>

    <div class="report-stat-grid report-stat-grid-6">
        <article>
            <span><i class="fa-solid fa-receipt"></i></span>
            <div>
                <small>Transaksi</small>
                <strong>{{ number_format($summary['transactions'] ?? 0) }}</strong>
            </div>
        </article>
        <article class="is-primary">
            <span><i class="fa-solid fa-coins"></i></span>
            <div>
                <small>Omzet Kotor</small>
                <strong>Rp {{ number_format($summary['gross'] ?? 0, 0, ',', '.') }}</strong>
            </div>
        </article>
        <article class="is-danger">
            <span><i class="fa-solid fa-arrow-rotate-left"></i></span>
            <div>
                <small>Retur</small>
                <strong>Rp {{ number_format($summary['returns'] ?? 0, 0, ',', '.') }}</strong>
            </div>
        </article>
        <article class="is-success">
            <span><i class="fa-solid fa-chart-line"></i></span>
            <div>
                <small>Penjualan Bersih</small>
                <strong>Rp {{ number_format($summary['net'] ?? 0, 0, ',', '.') }}</strong>
            </div>
        </article>
        <article class="is-paid">
            <span><i class="fa-solid fa-wallet"></i></span>
            <div>
                <small>Terbayar</small>
                <strong>Rp {{ number_format($summary['paid'] ?? 0, 0, ',', '.') }}</strong>
            </div>
        </article>
        <article class="is-warning">
            <span><i class="fa-solid fa-clock"></i></span>
            <div>
                <small>Sisa Piutang</small>
                <strong>Rp {{ number_format($summary['balance'] ?? 0, 0, ',', '.') }}</strong>
            </div>
        </article>
    </div>

    <section class="report-list-card report-filter-compatible-card">
        <div class="report-filter-panel report-filter-compatible">
            <header class="report-filter-head">
                <div>
                    <span><i class="fa-solid fa-filter"></i></span>
                    <div>
                        <strong>Filter Laporan Penjualan</strong>
                        <small>
                            Periode dan pencarian dipisahkan dari filter status
                            agar bidang tidak saling berdempetan.
                        </small>
                    </div>
                </div>

                <span class="report-filter-count">
                    <i class="fa-solid fa-sliders"></i>
                    {{ $activeFilterCount }} filter tambahan
                </span>
            </header>

            <div class="report-filter-row report-filter-row-primary">
                <fieldset class="report-period-group">
                    <legend>Periode transaksi</legend>
                    <div class="report-date-fields">
                        <label class="report-filter-field">
                            <span>Dari tanggal</span>
                            <input
                                wire:model.live="from"
                                type="date"
                                class="form-control"
                            >
                        </label>

                        <label class="report-filter-field">
                            <span>Sampai tanggal</span>
                            <input
                                wire:model.live="to"
                                type="date"
                                class="form-control"
                            >
                        </label>
                    </div>
                </fieldset>

                <label class="report-filter-field report-filter-search">
                    <span>Pencarian transaksi</span>
                    <div class="report-input-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input
                            wire:model.live.debounce.350ms="search"
                            class="form-control"
                            placeholder="Nomor, pelanggan, barang, atau SKU..."
                        >
                    </div>
                </label>
            </div>

            <div class="report-filter-divider"></div>

            <div class="report-filter-row report-filter-sales-secondary-v2">
                <label class="report-filter-field">
                    <span>Status pembayaran</span>
                    <select
                        wire:model.live="paymentStatus"
                        class="form-select"
                        data-no-select2
                    >
                        <option value="">Semua status</option>
                        <option value="paid">Lunas</option>
                        <option value="partial">Sebagian</option>
                        <option value="unpaid">Belum Bayar</option>
                    </select>
                </label>

                <label class="report-filter-field">
                    <span>Metode pembayaran</span>
                    <select
                        wire:model.live="paymentMethod"
                        class="form-select"
                        data-no-select2
                    >
                        <option value="">Semua metode</option>
                        @foreach(config('pos.payment_methods') as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="report-filter-field">
                    <span>Status transaksi</span>
                    <select
                        wire:model.live="saleStatus"
                        class="form-select"
                        data-no-select2
                    >
                        <option value="">Semua transaksi</option>
                        <option value="completed">Selesai</option>
                        <option value="partially_returned">Retur Sebagian</option>
                        <option value="returned">Retur Penuh</option>
                    </select>
                </label>

                <label class="report-filter-field">
                    <span>Kasir</span>
                    <select
                        wire:model.live="cashierId"
                        class="form-select"
                        data-no-select2
                    >
                        <option value="">Semua kasir</option>
                        @foreach($cashiers as $cashier)
                            <option value="{{ $cashier->id }}">
                                {{ $cashier->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>

            <footer class="report-filter-foot">
                <span>
                    <i class="fa-solid fa-circle-info"></i>
                    Ringkasan dan tabel diperbarui otomatis saat filter berubah.
                </span>

                <div class="report-filter-foot-actions">
                    <span
                        wire:loading
                        wire:target="from,to,search,paymentStatus,paymentMethod,cashierId,saleStatus"
                    >
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        Memperbarui laporan...
                    </span>

                    @if($filterActive)
                        <button
                            type="button"
                            wire:click="clearFilters"
                            class="btn btn-light"
                        >
                            <i class="fa-solid fa-xmark"></i>
                            Bersihkan
                        </button>
                    @endif
                </div>
            </footer>
        </div>

        <div class="report-list-meta">
            <span>
                <i class="fa-solid fa-list"></i>
                <b>{{ number_format($sales->total()) }}</b> transaksi ditemukan
                · Rata-rata
                <b>Rp {{ number_format($summary['average'] ?? 0, 0, ',', '.') }}</b>
            </span>
            <label>
                Tampilkan
                <select
                    wire:model.live="perPage"
                    class="form-select form-select-sm"
                    data-no-select2
                >
                    <option value="15">15</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
            </label>
        </div>

        <div class="table-responsive report-table-wrap">
            <table class="table report-data-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Transaksi</th>
                        <th>Kasir & Pelanggan</th>
                        <th>Metode</th>
                        <th>Total Kotor</th>
                        <th>Retur</th>
                        <th>Penjualan Bersih</th>
                        <th>Dibayar</th>
                        <th>Piutang</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        @php($returnValue = (float) ($sale->returns_sum_total ?? 0))
                        <tr>
                            <td>
                                <div class="report-primary-cell">
                                    <a href="{{ route('sales.show', $sale) }}">
                                        {{ $sale->number }}
                                    </a>
                                    <small>{{ $sale->sold_at->format('d/m/Y H:i') }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="report-primary-cell">
                                    <strong>{{ $sale->customer?->name ?? 'Pelanggan Umum' }}</strong>
                                    <small>Kasir: {{ $sale->cashier?->name ?? '-' }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="report-method-badge">
                                    {{ $sale->payments
                                        ->pluck('method')
                                        ->unique()
                                        ->map(fn ($method) => config(
                                            'pos.payment_methods.'.$method,
                                            $method
                                        ))
                                        ->join(' + ') ?: '-' }}
                                </span>
                            </td>
                            <td>Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
                            <td class="text-danger">
                                {{ $returnValue > 0
                                    ? 'Rp '.number_format($returnValue, 0, ',', '.')
                                    : '-' }}
                            </td>
                            <td><strong>Rp {{ number_format($sale->net_total, 0, ',', '.') }}</strong></td>
                            <td>Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</td>
                            <td>
                                <strong class="{{ $sale->balance > 0 ? 'text-danger' : 'text-success' }}">
                                    Rp {{ number_format($sale->balance, 0, ',', '.') }}
                                </strong>
                            </td>
                            <td>
                                <div class="report-status-stack">
                                    <span class="report-status is-{{ $sale->payment_status }}">
                                        {{ $paymentLabels[$sale->payment_status]
                                            ?? $sale->payment_status }}
                                    </span>
                                    <small>
                                        {{ $saleLabels[$sale->status]
                                            ?? $sale->status }}
                                    </small>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="report-empty">
                                    <i class="fa-solid fa-chart-line"></i>
                                    <strong>Transaksi tidak ditemukan</strong>
                                    <span>Ubah periode atau filter pencarian.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
            <div class="report-pagination">{{ $sales->links() }}</div>
        @endif
    </section>
</div>
