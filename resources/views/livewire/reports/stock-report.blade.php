<div class="report-module report-page report-stock-page">
    @php
        $filterActive = $search !== ''
            || $warehouseId !== ''
            || $categoryId !== ''
            || $stockStatus !== 'all'
            || $expiredOnly;

        $activeFilterCount = collect([
            $search,
            $warehouseId,
            $categoryId,
            $stockStatus !== 'all' ? $stockStatus : null,
            $expiredOnly ? 'expired' : null,
        ])->filter(fn ($value) => filled($value))->count();
    @endphp

    <div class="ga-page-head report-page-head">
        <div class="ga-page-title">
            <span class="ga-eyebrow report-page-eyebrow">
                <i class="fa-solid fa-boxes-stacked"></i>
                Nilai Persediaan
            </span>
            <h2>Laporan Stok</h2>
            <p class="mb-0">
                Saldo memakai satuan dasar. Nilai kedaluwarsa dihitung dari
                batch berisi stok yang tanggalnya sudah lewat.
            </p>
        </div>

        @can('reports.export')
            <div class="report-export-actions">
                <a
                    class="btn btn-outline-light"
                    href="{{ route('reports.stocks.excel', [
                        'search' => $search,
                        'warehouse_id' => $warehouseId,
                        'category_id' => $categoryId,
                        'stock_status' => $stockStatus,
                        'expired_only' => $expiredOnly ? 1 : 0,
                    ]) }}"
                >
                    <i class="fa-solid fa-file-excel"></i>
                    Excel
                </a>
                <a
                    class="btn btn-warning"
                    href="{{ route('reports.stocks.pdf', [
                        'search' => $search,
                        'warehouse_id' => $warehouseId,
                        'category_id' => $categoryId,
                        'stock_status' => $stockStatus,
                        'expired_only' => $expiredOnly ? 1 : 0,
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
            <span><i class="fa-solid fa-table-list"></i></span>
            <div>
                <small>Baris Stok</small>
                <strong>{{ number_format($summary['rows'] ?? 0) }}</strong>
            </div>
        </article>
        <article class="is-primary">
            <span><i class="fa-solid fa-cubes"></i></span>
            <div>
                <small>Total Qty Dasar</small>
                <strong>{{ \App\Support\Number::quantity($summary['quantity'] ?? 0) }}</strong>
            </div>
        </article>
        <article class="is-success">
            <span><i class="fa-solid fa-coins"></i></span>
            <div>
                <small>Nilai Persediaan</small>
                <strong>Rp {{ number_format($summary['inventory_value'] ?? 0, 0, ',', '.') }}</strong>
            </div>
        </article>
        <article class="is-danger">
            <span><i class="fa-solid fa-calendar-xmark"></i></span>
            <div>
                <small>Nilai Kedaluwarsa</small>
                <strong>Rp {{ number_format($summary['expired_value'] ?? 0, 0, ',', '.') }}</strong>
            </div>
        </article>
        <article class="is-warning">
            <span><i class="fa-solid fa-triangle-exclamation"></i></span>
            <div>
                <small>Stok Menipis</small>
                <strong>{{ number_format($summary['low_count'] ?? 0) }}</strong>
            </div>
        </article>
        <article class="is-dark">
            <span><i class="fa-solid fa-box-open"></i></span>
            <div>
                <small>Stok Habis</small>
                <strong>{{ number_format($summary['out_count'] ?? 0) }}</strong>
            </div>
        </article>
    </div>

    <section class="report-list-card report-filter-compatible-card">
        <div class="report-filter-panel report-filter-compatible">
            <header class="report-filter-head">
                <div>
                    <span><i class="fa-solid fa-filter"></i></span>
                    <div>
                        <strong>Filter Laporan Stok</strong>
                        <small>
                            Pencarian berada pada baris tersendiri. Gudang,
                            kategori, status, dan kedaluwarsa berada di bawahnya.
                        </small>
                    </div>
                </div>

                <span class="report-filter-count">
                    <i class="fa-solid fa-sliders"></i>
                    {{ $activeFilterCount }} filter aktif
                </span>
            </header>

            <div class="report-filter-row report-stock-search-row">
                <label class="report-filter-field report-filter-search">
                    <span>Pencarian barang</span>
                    <div class="report-input-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input
                            wire:model.live.debounce.350ms="search"
                            class="form-control"
                            placeholder="Nama, SKU, barcode, atau lokasi rak..."
                        >
                    </div>
                </label>
            </div>

            <div class="report-filter-divider"></div>

            <div class="report-filter-row report-stock-secondary-v2">
                <label class="report-filter-field">
                    <span>Gudang</span>
                    <select
                        wire:model.live="warehouseId"
                        class="form-select"
                        data-no-select2
                    >
                        <option value="">Semua gudang</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="report-filter-field">
                    <span>Kategori barang</span>
                    <select
                        wire:model.live="categoryId"
                        class="form-select"
                        data-no-select2
                    >
                        <option value="">Semua kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="report-filter-field">
                    <span>Status stok</span>
                    <select
                        wire:model.live="stockStatus"
                        class="form-select"
                        data-no-select2
                    >
                        <option value="all">Semua status</option>
                        <option value="healthy">Aman</option>
                        <option value="low">Menipis</option>
                        <option value="out">Habis</option>
                    </select>
                </label>

                <label class="report-expired-toggle {{ $expiredOnly ? 'is-selected' : '' }}">
                    <input
                        wire:model.live="expiredOnly"
                        type="checkbox"
                    >
                    <span class="report-low-stock-icon">
                        <i class="fa-solid fa-calendar-xmark"></i>
                    </span>
                    <span class="report-low-stock-copy">
                        <strong>Ada stok kedaluwarsa</strong>
                        <small>
                            Tampilkan baris dengan batch yang sudah melewati tanggal.
                        </small>
                    </span>
                    <i class="fa-solid fa-circle-check report-toggle-check"></i>
                </label>
            </div>

            <footer class="report-filter-foot">
                <span>
                    <i class="fa-solid fa-scale-balanced"></i>
                    Saldo dan nilai stok menggunakan satuan dasar barang.
                </span>

                <div class="report-filter-foot-actions">
                    <span
                        wire:loading
                        wire:target="search,warehouseId,categoryId,stockStatus,expiredOnly"
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

        <div class="report-value-notice">
            <span><i class="fa-solid fa-shield-halved"></i></span>
            <div>
                <strong>
                    Nilai stok layak jual diperkirakan
                    Rp {{ number_format($summary['sellable_value'] ?? 0, 0, ',', '.') }}
                </strong>
                <small>
                    {{ number_format($summary['expired_rows'] ?? 0) }} baris stok
                    memiliki batch kedaluwarsa. Nilai memakai harga modal rata-rata.
                </small>
            </div>
        </div>

        <div class="report-list-meta">
            <span>
                <i class="fa-solid fa-list"></i>
                <b>{{ number_format($stocks->total()) }}</b> baris stok ditemukan
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
                        <th>Barang</th>
                        <th>Gudang</th>
                        <th>Stok Dasar</th>
                        <th>Ekuivalen</th>
                        <th>Minimum</th>
                        <th>Kedaluwarsa</th>
                        <th>≤ 30 Hari</th>
                        <th>Nilai Modal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $stock)
                        @php
                            $quantity = (float) $stock->quantity;
                            $minimum = (float) $stock->product->minimum_stock;
                            $status = $quantity <= 0
                                ? 'out'
                                : ($quantity <= $minimum ? 'low' : 'healthy');
                            $statusLabel = [
                                'out' => 'Habis',
                                'low' => 'Menipis',
                                'healthy' => 'Aman',
                            ][$status];
                        @endphp
                        <tr>
                            <td>
                                <div class="report-primary-cell">
                                    <strong>{{ $stock->product->name }}</strong>
                                    <small>
                                        {{ $stock->product->sku }} ·
                                        {{ $stock->product->category?->name ?? '-' }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="report-primary-cell">
                                    <strong>{{ $stock->warehouse->name }}</strong>
                                    <small>
                                        {{ $stock->product->rack_location ?: 'Rak belum diatur' }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                <strong>
                                    {{ \App\Support\Number::quantity($quantity) }}
                                    {{ $stock->product->unit?->symbol }}
                                </strong>
                            </td>
                            <td>
                                <div class="report-equivalent-list">
                                    @forelse(
                                        $stock->product->conversionUnits
                                            ->where('is_active', true)
                                        as $conversion
                                    )
                                        <span>
                                            {{ \App\Support\Number::quantity(
                                                $quantity
                                                / (float) $conversion->conversion_factor
                                            ) }}
                                            {{ $conversion->unit->symbol }}
                                        </span>
                                    @empty
                                        <small>—</small>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                {{ \App\Support\Number::quantity($minimum) }}
                                {{ $stock->product->unit?->symbol }}
                            </td>
                            <td>
                                <strong class="{{ (float) $stock->expired_quantity > 0 ? 'text-danger' : '' }}">
                                    {{ \App\Support\Number::quantity($stock->expired_quantity ?? 0) }}
                                </strong>
                            </td>
                            <td>
                                <strong class="{{ (float) $stock->expiring_quantity > 0 ? 'text-warning' : '' }}">
                                    {{ \App\Support\Number::quantity($stock->expiring_quantity ?? 0) }}
                                </strong>
                            </td>
                            <td>
                                <div class="report-primary-cell">
                                    <strong>
                                        Rp {{ number_format(
                                            $quantity * (float) $stock->product->average_cost,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </strong>
                                    <small>
                                        Modal rata-rata
                                        Rp {{ number_format(
                                            $stock->product->average_cost,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                <span class="stock-report-status is-{{ $status }}">
                                    <i class="fa-solid {{ $status === 'healthy'
                                        ? 'fa-circle-check'
                                        : ($status === 'low'
                                            ? 'fa-triangle-exclamation'
                                            : 'fa-circle-xmark') }}"></i>
                                    {{ $statusLabel }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="report-empty">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                    <strong>Stok tidak ditemukan</strong>
                                    <span>Ubah filter gudang, kategori, atau status.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stocks->hasPages())
            <div class="report-pagination">{{ $stocks->links() }}</div>
        @endif
    </section>
</div>
