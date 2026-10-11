<div class="product-manager-page">
    @php
        $filterIsActive = $search !== ''
            || $categoryFilter !== ''
            || $statusFilter !== 'all'
            || $stockFilter !== 'all';

        $selectedBaseUnit = $units->firstWhere('id', (int) ($form['unit_id'] ?? 0));
        $selectedUnitIds = collect($conversions)
            ->pluck('unit_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();
    @endphp

    <div class="ga-page-head product-page-head">
        <div class="ga-page-title">
            <span class="ga-eyebrow">
                <i class="fa-solid fa-boxes-stacked"></i>
                MASTER PERSEDIAAN
            </span>
            <h2>Barang & Konversi Satuan</h2>
            <p class="mb-0">Kelola identitas barang, harga, stok minimum, dan satuan penjualan dalam satu halaman.</p>
        </div>

        @can('products.create')
            <div class="product-head-actions">
                <button type="button" wire:click="openImportModal" class="btn btn-outline-light">
                    <i class="fa-solid fa-file-excel"></i>
                    Import Excel
                </button>

                <button type="button" wire:click="create" class="btn btn-warning product-add-button">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Barang
                </button>
            </div>
        @endcan
    </div>

    <div class="product-stat-grid">
        <article class="product-stat-card is-total">
            <span><i class="fa-solid fa-boxes-stacked"></i></span>
            <div>
                <small>Total barang</small>
                <strong>{{ number_format($stats['total']) }}</strong>
            </div>
        </article>
        <article class="product-stat-card is-active">
            <span><i class="fa-solid fa-circle-check"></i></span>
            <div>
                <small>Barang aktif</small>
                <strong>{{ number_format($stats['active']) }}</strong>
            </div>
        </article>
        <article class="product-stat-card is-low">
            <span><i class="fa-solid fa-arrow-trend-down"></i></span>
            <div>
                <small>Stok menipis</small>
                <strong>{{ number_format($stats['low']) }}</strong>
            </div>
        </article>
        <article class="product-stat-card is-out">
            <span><i class="fa-solid fa-box-open"></i></span>
            <div>
                <small>Stok habis</small>
                <strong>{{ number_format($stats['out']) }}</strong>
            </div>
        </article>
    </div>

    <section class="product-list-card">
        <div class="product-filter-panel">
            <div class="product-search-field">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    wire:model.live.debounce.350ms="search"
                    type="search"
                    class="form-control"
                    placeholder="Cari nama, SKU, barcode, kategori, rak, atau satuan..."
                    autocomplete="off"
                >
                <span wire:loading wire:target="search" class="product-search-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
            </div>

            <select wire:model.live="categoryFilter" class="form-select" data-no-select2 aria-label="Filter kategori">
                <option value="">Semua kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">
                        {{ $category->name }}{{ $category->is_active ? '' : ' (nonaktif)' }}
                    </option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter" class="form-select" data-no-select2 aria-label="Filter status">
                <option value="all">Semua status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
            </select>

            <select wire:model.live="stockFilter" class="form-select" data-no-select2 aria-label="Filter stok">
                <option value="all">Semua kondisi stok</option>
                <option value="safe">Stok aman</option>
                <option value="low">Stok menipis</option>
                <option value="out">Stok habis</option>
                <option value="untracked">Tanpa pelacakan stok</option>
            </select>

            @if($filterIsActive)
                <button type="button" wire:click="clearFilters" class="btn btn-light product-clear-filter">
                    <i class="fa-solid fa-xmark"></i>
                    Bersihkan
                </button>
            @endif
        </div>

        <div class="product-list-meta">
            <span>
                <i class="fa-solid fa-list"></i>
                <b>{{ number_format($products->total()) }}</b> barang ditemukan
            </span>

            <label>
                Tampilkan
                <select wire:model.live="perPage" class="form-select form-select-sm" data-no-select2>
                    <option value="15">15</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                </select>
            </label>
        </div>

        <div class="table-responsive product-table-wrap">
            <table class="table product-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Kategori & Rak</th>
                        <th>Satuan</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $stock = (float) ($product->total_stock ?? 0);
                            $activeConversions = $product->conversionUnits->where('is_active', true);

                            if (! $product->track_stock) {
                                $stockClass = 'is-untracked';
                                $stockLabel = 'Tidak dilacak';
                                $stockIcon = 'fa-infinity';
                            } elseif ($stock <= 0) {
                                $stockClass = 'is-out';
                                $stockLabel = 'Habis';
                                $stockIcon = 'fa-circle-xmark';
                            } elseif ($stock <= (float) $product->minimum_stock) {
                                $stockClass = 'is-low';
                                $stockLabel = 'Menipis';
                                $stockIcon = 'fa-triangle-exclamation';
                            } else {
                                $stockClass = 'is-safe';
                                $stockLabel = 'Aman';
                                $stockIcon = 'fa-circle-check';
                            }
                        @endphp

                        <tr wire:key="product-{{ $product->id }}">
                            <td>
                                <div class="product-identity">
                                    <span class="product-avatar">
                                        {{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}
                                    </span>
                                    <div>
                                        <strong>{{ $product->name }}</strong>
                                        <small>
                                            <span>{{ $product->sku }}</span>
                                            <i></i>
                                            <span>{{ $product->barcode ?: 'Tanpa barcode' }}</span>
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="product-category-cell">
                                    <span>{{ $product->category?->name ?? 'Tanpa kategori' }}</span>
                                    <small>
                                        <i class="fa-solid fa-location-dot"></i>
                                        {{ $product->rack_location ?: 'Rak belum diatur' }}
                                    </small>
                                </div>
                            </td>

                            <td>
                                <div class="product-unit-cell">
                                    <strong>{{ $product->unit?->symbol ?? '-' }}</strong>
                                    <small>Satuan dasar</small>
                                    @if($activeConversions->isNotEmpty())
                                        <div class="product-conversion-badges">
                                            @foreach($activeConversions->take(2) as $conversion)
                                                <span>
                                                    1 {{ $conversion->unit?->symbol }}
                                                    = {{ \App\Support\Number::quantity($conversion->conversion_factor) }}
                                                    {{ $product->unit?->symbol }}
                                                </span>
                                            @endforeach

                                            @if($activeConversions->count() > 2)
                                                <span>+{{ $activeConversions->count() - 2 }} lainnya</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="product-price-cell">
                                    <span>
                                        <small>Jual</small>
                                        <strong>Rp {{ number_format($product->selling_price, 0, ',', '.') }}</strong>
                                    </span>
                                    <span>
                                        <small>Beli</small>
                                        <b>Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</b>
                                    </span>
                                    @if($product->wholesale_price !== null)
                                        <span class="is-wholesale">
                                            Grosir Rp {{ number_format($product->wholesale_price, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="product-stock-cell {{ $stockClass }}">
                                    <strong>
                                        {{ $product->track_stock
                                            ? \App\Support\Number::quantity($stock).' '.$product->unit?->symbol
                                            : '∞' }}
                                    </strong>
                                    <span>
                                        <i class="fa-solid {{ $stockIcon }}"></i>
                                        {{ $stockLabel }}
                                    </span>
                                    @if($product->track_stock)
                                        <small>Min. {{ \App\Support\Number::quantity($product->minimum_stock) }}</small>
                                    @endif
                                </div>
                            </td>

                            <td>
                                @can('products.update')
                                    <button
                                        type="button"
                                        wire:click="toggleActive({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleActive({{ $product->id }})"
                                        class="product-status-button {{ $product->is_active ? 'is-active' : 'is-inactive' }}"
                                        title="Klik untuk {{ $product->is_active ? 'menonaktifkan' : 'mengaktifkan' }}"
                                    >
                                        <i class="fa-solid {{ $product->is_active ? 'fa-circle-check' : 'fa-circle-minus' }}"></i>
                                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                @else
                                    <span class="product-status-button {{ $product->is_active ? 'is-active' : 'is-inactive' }}">
                                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                @endcan

                                @if($product->has_expiry)
                                    <span class="product-expiry-badge">
                                        <i class="fa-solid fa-calendar-xmark"></i>
                                        Kedaluwarsa
                                    </span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="product-action-group">
                                    @can('products.update')
                                        <button
                                            type="button"
                                            wire:click="edit({{ $product->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="edit({{ $product->id }})"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Edit barang"
                                        >
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    @endcan

                                    @can('products.delete')
                                        <button
                                            type="button"
                                            data-livewire-confirm="Hapus barang {{ $product->name }}? Barang yang sudah mempunyai histori tidak dapat dihapus."
                                            data-livewire-action="delete"
                                            data-livewire-id="{{ $product->id }}"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Hapus barang"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="product-empty-state">
                                    <i class="fa-solid fa-box-open"></i>
                                    <strong>Barang tidak ditemukan</strong>
                                    <span>Coba ubah kata pencarian atau bersihkan filter yang digunakan.</span>
                                    @if($filterIsActive)
                                        <button type="button" wire:click="clearFilters" class="btn btn-sm btn-outline-primary">
                                            Bersihkan Filter
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="product-pagination">
                {{ $products->links() }}
            </div>
        @endif
    </section>

    @if($showImportModal)
        <div class="pos-modal" wire:keydown.escape.window="closeImportModal">
            <div class="pos-modal-dialog product-import-dialog" wire:click.stop role="dialog" aria-modal="true">
                <form wire:submit="importProducts">
                    <header class="product-modal-header product-import-header">
                        <div>
                            <span class="product-modal-icon">
                                <i class="fa-solid fa-file-excel"></i>
                            </span>
                            <div>
                                <h5 class="mb-0">Import Barang dari Excel</h5>
                                <p class="mb-0">Tambah atau perbarui banyak barang sekaligus menggunakan SKU.</p>
                            </div>
                        </div>

                        <button type="button" wire:click="closeImportModal" class="btn-close" aria-label="Tutup"></button>
                    </header>

                    <div class="product-import-body">
                        <div class="product-import-guide">
                            <div>
                                <span><i class="fa-solid fa-download"></i></span>
                                <div>
                                    <strong>1. Unduh template</strong>
                                    <small>Template berisi contoh, petunjuk, kategori, dan satuan yang tersedia.</small>
                                </div>
                            </div>

                            <a href="{{ route('products.import-template') }}" class="btn btn-outline-primary">
                                <i class="fa-solid fa-file-arrow-down"></i>
                                Download Template
                            </a>
                        </div>

                        <label class="product-import-dropzone" for="product-import-file">
                            <input
                                id="product-import-file"
                                wire:model="importFile"
                                type="file"
                                accept=".xlsx,.xls,.csv"
                            >

                            <span class="product-import-dropzone-icon">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </span>

                            <strong>
                                {{ $importFile ? $importFile->getClientOriginalName() : 'Pilih file Excel' }}
                            </strong>

                            <small>
                                XLSX, XLS, atau CSV · maksimal 2 MB · maksimal 1.000 baris
                            </small>

                            @if($importFile)
                                <b>{{ number_format($importFile->getSize() / 1024, 1, ',', '.') }} KB</b>
                            @endif

                            <span wire:loading wire:target="importFile" class="product-import-uploading">
                                <span class="spinner-border spinner-border-sm"></span>
                                Mengunggah file...
                            </span>
                        </label>

                        @error('importFile')
                            <div class="ga-notice ga-notice-danger mb-0">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                {{ $message }}
                            </div>
                        @enderror

                        <label class="product-import-option">
                            <input wire:model="updateExistingProducts" type="checkbox">
                            <span><i class="fa-solid fa-arrows-rotate"></i></span>
                            <div>
                                <strong>Perbarui barang dengan SKU yang sama</strong>
                                <small>Barang lama diperbarui. Histori stok dan transaksi tetap dipertahankan.</small>
                            </div>
                            <i class="fa-solid fa-circle-check"></i>
                        </label>

                        <div class="product-import-rules">
                            <strong><i class="fa-solid fa-circle-info"></i> Kolom wajib</strong>
                            <div>
                                <span>sku</span>
                                <span>nama_barang</span>
                                <span>kategori</span>
                                <span>satuan_dasar</span>
                                <span>harga_beli</span>
                                <span>harga_jual</span>
                            </div>
                            <p>
                                Kategori dapat memakai kode atau nama. Satuan dapat memakai simbol atau nama.
                                Keduanya harus sudah tersedia di master.
                            </p>
                        </div>

                        @if(! empty($importSummary))
                            <div class="product-import-summary">
                                <article>
                                    <small>Baris diproses</small>
                                    <strong>{{ number_format($importSummary['rows'] ?? 0) }}</strong>
                                </article>
                                <article class="is-created">
                                    <small>Barang baru</small>
                                    <strong>{{ number_format($importSummary['created'] ?? 0) }}</strong>
                                </article>
                                <article class="is-updated">
                                    <small>Diperbarui</small>
                                    <strong>{{ number_format($importSummary['updated'] ?? 0) }}</strong>
                                </article>
                                <article class="is-skipped">
                                    <small>Dilewati</small>
                                    <strong>{{ number_format($importSummary['skipped'] ?? 0) }}</strong>
                                </article>
                            </div>
                        @endif

                        @if(! empty($importErrors))
                            <section class="product-import-errors">
                                <header>
                                    <div>
                                        <strong>Baris yang perlu diperiksa</strong>
                                        <small>Maksimal 100 kesalahan ditampilkan.</small>
                                    </div>
                                    <span>{{ count($importErrors) }} masalah</span>
                                </header>

                                <div>
                                    @foreach($importErrors as $error)
                                        <article wire:key="import-error-{{ $loop->index }}">
                                            <b>Baris {{ $error['row'] }}</b>
                                            <span>{{ $error['message'] }}</span>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>

                    <footer class="product-modal-footer">
                        <button type="button" wire:click="closeImportModal" class="btn btn-light">
                            <i class="fa-solid fa-xmark"></i>
                            Tutup
                        </button>

                        <button
                            type="submit"
                            class="btn btn-success"
                            wire:loading.attr="disabled"
                            wire:target="importProducts,importFile"
                            @disabled(! $importFile)
                        >
                            <span wire:loading.remove wire:target="importProducts">
                                <i class="fa-solid fa-file-import"></i>
                                Proses Import
                            </span>
                            <span wire:loading wire:target="importProducts">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Memproses...
                            </span>
                        </button>
                    </footer>
                </form>
            </div>
        </div>
    @endif

    @if($showModal)
        <div class="pos-modal" wire:keydown.escape.window="closeModal">
            <div class="pos-modal-dialog product-modal-dialog" wire:click.stop role="dialog" aria-modal="true">
                <form wire:submit="save">
                    <header class="product-modal-header">
                        <div>
                            <span class="product-modal-icon">
                                <i class="fa-solid {{ $editingId ? 'fa-pen-to-square' : 'fa-box-open' }}"></i>
                            </span>
                            <div>
                                <h5 class="mb-0">{{ $editingId ? 'Edit Barang' : 'Tambah Barang' }}</h5>
                                <p class="mb-0">
                                    {{ $editingId
                                        ? 'Perbarui informasi barang tanpa mengubah histori transaksi.'
                                        : 'Isi identitas, harga, dan pengaturan stok barang.' }}
                                </p>
                            </div>
                        </div>

                        <button type="button" wire:click="closeModal" class="btn-close" aria-label="Tutup"></button>
                    </header>

                    <div class="product-modal-body">
                        <section class="product-form-section">
                            <div class="product-section-heading">
                                <span><i class="fa-solid fa-barcode"></i></span>
                                <div>
                                    <h6>Identitas Barang</h6>
                                    <p>Kode dan informasi utama yang digunakan saat pencarian atau scan.</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">SKU <b>*</b></label>
                                    <input
                                        wire:model="form.sku"
                                        class="form-control"
                                        maxlength="60"
                                        autocomplete="off"
                                        placeholder="BRG-001"
                                    >
                                    @error('form.sku')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">Barcode dasar</label>
                                    <input
                                        wire:model="form.barcode"
                                        class="form-control"
                                        maxlength="100"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        placeholder="Scan atau ketik barcode"
                                    >
                                    @error('form.barcode')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>

                                <div class="col-lg-6">
                                    <label class="form-label">Nama barang <b>*</b></label>
                                    <input
                                        wire:model="form.name"
                                        class="form-control"
                                        maxlength="255"
                                        autocomplete="off"
                                        placeholder="Nama barang yang tampil di POS"
                                    >
                                    @error('form.name')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>

                                <div class="col-lg-4">
                                    <label class="form-label">Kategori <b>*</b></label>
                                    <select wire:model="form.product_category_id" class="form-select" data-no-select2>
                                        <option value="">Pilih kategori</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">
                                                {{ $category->name }}{{ $category->is_active ? '' : ' (nonaktif)' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('form.product_category_id')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>

                                <div class="col-lg-4">
                                    <label class="form-label">Satuan dasar stok <b>*</b></label>
                                    <select
                                        wire:model.live="form.unit_id"
                                        class="form-select"
                                        data-no-select2
                                        @disabled($baseUnitLocked)
                                    >
                                        <option value="">Pilih satuan dasar</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}">
                                                {{ $unit->name }} ({{ $unit->symbol }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($baseUnitLocked)
                                        <small class="product-field-warning">
                                            <i class="fa-solid fa-lock"></i>
                                            Terkunci karena barang sudah memiliki histori.
                                        </small>
                                    @endif
                                    @error('form.unit_id')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>

                                <div class="col-lg-4">
                                    <label class="form-label">Lokasi rak</label>
                                    <input
                                        wire:model="form.rack_location"
                                        class="form-control"
                                        maxlength="100"
                                        autocomplete="off"
                                        placeholder="Contoh: Rak A-02"
                                    >
                                    @error('form.rack_location')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>
                            </div>
                        </section>

                        <section class="product-form-section">
                            <div class="product-section-heading">
                                <span><i class="fa-solid fa-tags"></i></span>
                                <div>
                                    <h6>Harga & Batas Stok</h6>
                                    <p>Harga di bawah ini menggunakan satuan dasar.</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-lg-4 col-md-6">
                                    <label class="form-label">Harga beli dasar <b>*</b></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input wire:model="form.purchase_price" type="number" min="0" step="0.01" class="form-control">
                                    </div>
                                    @error('form.purchase_price')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>

                                <div class="col-lg-4 col-md-6">
                                    <label class="form-label">Harga jual dasar <b>*</b></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input wire:model="form.selling_price" type="number" min="0" step="0.01" class="form-control">
                                    </div>
                                    @error('form.selling_price')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>

                                <div class="col-lg-4 col-md-6">
                                    <label class="form-label">Stok minimum <b>*</b></label>
                                    <div class="input-group">
                                        <input wire:model="form.minimum_stock" type="number" min="0" step="0.001" class="form-control">
                                        <span class="input-group-text">{{ $selectedBaseUnit?->symbol ?: 'dasar' }}</span>
                                    </div>
                                    @error('form.minimum_stock')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>

                                <div class="col-lg-4 col-md-6">
                                    <label class="form-label">Harga grosir</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input wire:model="form.wholesale_price" type="number" min="0" step="0.01" class="form-control" placeholder="Opsional">
                                    </div>
                                    @error('form.wholesale_price')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>

                                <div class="col-lg-4 col-md-6">
                                    <label class="form-label">Minimal pembelian grosir</label>
                                    <div class="input-group">
                                        <input wire:model="form.wholesale_min_qty" type="number" min="0.001" step="0.001" class="form-control" placeholder="Opsional">
                                        <span class="input-group-text">{{ $selectedBaseUnit?->symbol ?: 'dasar' }}</span>
                                    </div>
                                    @error('form.wholesale_min_qty')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>
                            </div>
                        </section>

                        <section class="product-form-section">
                            <div class="product-section-heading">
                                <span><i class="fa-solid fa-sliders"></i></span>
                                <div>
                                    <h6>Pengaturan Barang</h6>
                                    <p>Atur perilaku stok, kedaluwarsa, dan ketersediaan barang.</p>
                                </div>
                            </div>

                            <div class="product-option-grid">
                                <label class="product-option-card {{ $form['track_stock'] ? 'is-selected' : '' }}">
                                    <input wire:model.live="form.track_stock" type="checkbox">
                                    <span><i class="fa-solid fa-boxes-stacked"></i></span>
                                    <div>
                                        <strong>Kelola stok</strong>
                                        <small>Penjualan dan pembelian memengaruhi stok.</small>
                                    </div>
                                    <i class="fa-solid fa-circle-check"></i>
                                </label>

                                <label class="product-option-card {{ $form['has_expiry'] ? 'is-selected' : '' }} {{ ! $form['track_stock'] ? 'is-disabled' : '' }}">
                                    <input
                                        wire:model.live="form.has_expiry"
                                        type="checkbox"
                                        @disabled(! $form['track_stock'])
                                    >
                                    <span><i class="fa-solid fa-calendar-xmark"></i></span>
                                    <div>
                                        <strong>Punya kedaluwarsa</strong>
                                        <small>Batch dan tanggal kedaluwarsa wajib dicatat.</small>
                                    </div>
                                    <i class="fa-solid fa-circle-check"></i>
                                </label>

                                <label class="product-option-card {{ $form['is_active'] ? 'is-selected' : '' }}">
                                    <input wire:model.live="form.is_active" type="checkbox">
                                    <span><i class="fa-solid fa-toggle-on"></i></span>
                                    <div>
                                        <strong>Barang aktif</strong>
                                        <small>Dapat dipilih pada pembelian dan POS.</small>
                                    </div>
                                    <i class="fa-solid fa-circle-check"></i>
                                </label>
                            </div>

                            @error('form.has_expiry')<small class="text-danger d-block mt-2">{{ $message }}</small>@enderror
                        </section>

                        <section class="product-form-section product-conversion-section">
                            <div class="product-section-heading is-action">
                                <span><i class="fa-solid fa-right-left"></i></span>
                                <div>
                                    <h6>Konversi Satuan</h6>
                                    <p>
                                        Contoh: 1 dus = 24 {{ $selectedBaseUnit?->symbol ?: 'satuan dasar' }}.
                                        Harga kosong akan dihitung otomatis dari faktor konversi.
                                    </p>
                                </div>

                                <button type="button" wire:click="addConversion" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-plus"></i>
                                    Tambah Konversi
                                </button>
                            </div>

                            <div class="product-conversion-list">
                                @forelse($conversions as $index => $row)
                                    @php
                                        $conversionUnit = $units->firstWhere('id', (int) ($row['unit_id'] ?? 0));
                                        $factor = (float) ($row['conversion_factor'] ?? 0);
                                        $autoPurchase = (float) ($form['purchase_price'] ?? 0) * $factor;
                                        $autoSelling = (float) ($form['selling_price'] ?? 0) * $factor;
                                    @endphp

                                    <article class="product-conversion-card" wire:key="conversion-{{ $row['_key'] }}">
                                        <header>
                                            <div>
                                                <span>{{ $index + 1 }}</span>
                                                <div>
                                                    <strong>{{ $conversionUnit?->name ?: 'Konversi baru' }}</strong>
                                                    <small>
                                                        @if($conversionUnit && $selectedBaseUnit && $factor > 0)
                                                            1 {{ $conversionUnit->symbol }}
                                                            = {{ \App\Support\Number::quantity($factor) }}
                                                            {{ $selectedBaseUnit->symbol }}
                                                        @else
                                                            Pilih satuan dan isi faktor konversi.
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>

                                            <button
                                                type="button"
                                                wire:click="removeConversion({{ $index }})"
                                                class="product-conversion-remove"
                                                title="Hapus konversi"
                                                aria-label="Hapus konversi"
                                            >
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </header>

                                        <div class="row g-3">
                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-label">Satuan <b>*</b></label>
                                                <select wire:model.live="conversions.{{ $index }}.unit_id" class="form-select" data-no-select2>
                                                    <option value="">Pilih satuan</option>
                                                    @foreach($units as $unit)
                                                        @php
                                                            $usedElsewhere = in_array((int) $unit->id, $selectedUnitIds, true)
                                                                && (int) ($row['unit_id'] ?? 0) !== (int) $unit->id;
                                                        @endphp
                                                        <option
                                                            value="{{ $unit->id }}"
                                                            @disabled(
                                                                (int) $unit->id === (int) ($form['unit_id'] ?? 0)
                                                                || $usedElsewhere
                                                            )
                                                        >
                                                            {{ $unit->name }} ({{ $unit->symbol }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error("conversions.$index.unit_id")<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-label">Faktor ke dasar <b>*</b></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">1 {{ $conversionUnit?->symbol ?: '?' }} =</span>
                                                    <input
                                                        wire:model.live.debounce.300ms="conversions.{{ $index }}.conversion_factor"
                                                        type="number"
                                                        min="0.000001"
                                                        step="0.000001"
                                                        class="form-control"
                                                    >
                                                    <span class="input-group-text">{{ $selectedBaseUnit?->symbol ?: 'dasar' }}</span>
                                                </div>
                                                @error("conversions.$index.conversion_factor")<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-label">Barcode</label>
                                                <input
                                                    wire:model="conversions.{{ $index }}.barcode"
                                                    class="form-control"
                                                    maxlength="100"
                                                    inputmode="numeric"
                                                    placeholder="Opsional"
                                                >
                                                @error("conversions.$index.barcode")<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-label">Status</label>
                                                <label class="product-conversion-toggle">
                                                    <input wire:model.live="conversions.{{ $index }}.is_active" type="checkbox">
                                                    <span></span>
                                                    <b>{{ ($row['is_active'] ?? false) ? 'Aktif' : 'Nonaktif' }}</b>
                                                </label>
                                                @error("conversions.$index.is_active")<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Harga beli satuan</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input
                                                        wire:model="conversions.{{ $index }}.purchase_price"
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        class="form-control"
                                                        placeholder="{{ number_format($autoPurchase, 0, ',', '.') }}"
                                                    >
                                                </div>
                                                <small class="product-auto-price">
                                                    Otomatis: Rp {{ number_format($autoPurchase, 0, ',', '.') }}
                                                </small>
                                                @error("conversions.$index.purchase_price")<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Harga jual satuan</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input
                                                        wire:model="conversions.{{ $index }}.selling_price"
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        class="form-control"
                                                        placeholder="{{ number_format($autoSelling, 0, ',', '.') }}"
                                                    >
                                                </div>
                                                <small class="product-auto-price">
                                                    Otomatis: Rp {{ number_format($autoSelling, 0, ',', '.') }}
                                                </small>
                                                @error("conversions.$index.selling_price")<small class="text-danger">{{ $message }}</small>@enderror
                                            </div>
                                        </div>
                                    </article>
                                @empty
                                    <div class="product-conversion-empty">
                                        <i class="fa-solid fa-scale-balanced"></i>
                                        <strong>Belum ada konversi satuan</strong>
                                        <span>
                                            Barang tetap dapat digunakan dengan
                                            {{ $selectedBaseUnit?->symbol ?: 'satuan dasar' }}.
                                        </span>
                                    </div>
                                @endforelse
                            </div>

                            @error('conversions')
                                <div class="ga-notice ga-notice-danger mt-3 mb-0">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </section>
                    </div>

                    <footer class="product-modal-footer">
                        <button type="button" wire:click="closeModal" class="btn btn-light">
                            <i class="fa-solid fa-xmark"></i>
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="btn btn-primary"
                            wire:loading.attr="disabled"
                            wire:target="save"
                        >
                            <span wire:loading.remove wire:target="save">
                                <i class="fa-solid fa-floppy-disk"></i>
                                {{ $editingId ? 'Simpan Perubahan' : 'Simpan Barang' }}
                            </span>
                            <span wire:loading wire:target="save">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Menyimpan...
                            </span>
                        </button>
                    </footer>
                </form>
            </div>
        </div>
    @endif
</div>
