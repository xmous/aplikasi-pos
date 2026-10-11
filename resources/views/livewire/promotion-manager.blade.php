<div class="promotion-manager-page">
    @php
        $filterActive = $search !== ''
            || $status !== 'all'
            || $scopeFilter !== 'all';

        $selectedProductCount = count($productIds);
        $previewValue = (float) ($form['value'] ?? 0);
        $previewMinimum = (float) ($form['minimum_transaction'] ?? 0);
        $previewMaximum = $form['maximum_discount'] ?? null;
    @endphp

    <div class="ga-page-head operations-page-head">
        <div class="ga-page-title">
            <span class="ga-eyebrow">
                <i class="fa-solid fa-tags"></i>
                PROGRAM DISKON
            </span>
            <h2>Promosi</h2>
            <p class="mb-0">
                Atur diskon berdasarkan periode, minimum transaksi,
                dan cakupan produk.
            </p>
        </div>

        <button
            type="button"
            wire:click="create"
            class="btn btn-warning operations-create-button"
        >
            <i class="fa-solid fa-plus"></i>
            Tambah Promo
        </button>
    </div>

    <div class="operations-stat-grid promotion-stat-grid">
        <article>
            <span><i class="fa-solid fa-tags"></i></span>
            <div>
                <small>Total promo</small>
                <strong>{{ number_format($summary['total']) }}</strong>
            </div>
        </article>

        <article class="is-active">
            <span><i class="fa-solid fa-bolt"></i></span>
            <div>
                <small>Sedang aktif</small>
                <strong>{{ number_format($summary['active']) }}</strong>
            </div>
        </article>

        <article class="is-scheduled">
            <span><i class="fa-solid fa-clock"></i></span>
            <div>
                <small>Terjadwal</small>
                <strong>{{ number_format($summary['scheduled']) }}</strong>
            </div>
        </article>

        <article class="is-locked">
            <span><i class="fa-solid fa-lock"></i></span>
            <div>
                <small>Sudah digunakan</small>
                <strong>{{ number_format($summary['locked']) }}</strong>
            </div>
        </article>
    </div>

    <section class="operations-list-card">
        <div class="promotion-filter-panel">
            <div class="operations-search-field">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    wire:model.live.debounce.350ms="search"
                    type="search"
                    class="form-control"
                    placeholder="Cari kode, nama, SKU, atau produk..."
                    autocomplete="off"
                >
                <span
                    wire:loading
                    wire:target="search"
                    class="operations-search-loading"
                >
                    <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
            </div>

            <select
                wire:model.live="status"
                class="form-select"
                data-no-select2
            >
                <option value="all">Semua status</option>
                <option value="active">Sedang aktif</option>
                <option value="scheduled">Terjadwal</option>
                <option value="expired">Kedaluwarsa</option>
                <option value="inactive">Nonaktif</option>
            </select>

            <select
                wire:model.live="scopeFilter"
                class="form-select"
                data-no-select2
            >
                <option value="all">Semua cakupan</option>
                <option value="all_products">Semua produk</option>
                <option value="selected_products">Produk tertentu</option>
            </select>

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

        <div class="operations-list-meta">
            <span>
                <i class="fa-solid fa-list"></i>
                <b>{{ number_format($promotions->total()) }}</b>
                promo ditemukan
            </span>

            <label>
                Tampilkan
                <select
                    wire:model.live="perPage"
                    class="form-select form-select-sm"
                    data-no-select2
                >
                    <option value="15">15</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                </select>
            </label>
        </div>

        <div class="table-responsive operations-table-wrap">
            <table class="table operations-data-table promotion-data-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Promosi</th>
                        <th>Diskon</th>
                        <th>Ketentuan</th>
                        <th>Cakupan</th>
                        <th>Periode</th>
                        <th>Penggunaan</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($promotions as $promotion)
                        @php
                            if (! $promotion->is_active) {
                                $state = [
                                    'label' => 'Nonaktif',
                                    'class' => 'inactive',
                                    'icon' => 'fa-circle-pause',
                                ];
                            } elseif ($promotion->starts_at->isFuture()) {
                                $state = [
                                    'label' => 'Terjadwal',
                                    'class' => 'scheduled',
                                    'icon' => 'fa-clock',
                                ];
                            } elseif ($promotion->ends_at->isPast()) {
                                $state = [
                                    'label' => 'Kedaluwarsa',
                                    'class' => 'expired',
                                    'icon' => 'fa-calendar-xmark',
                                ];
                            } else {
                                $state = [
                                    'label' => 'Aktif',
                                    'class' => 'active',
                                    'icon' => 'fa-bolt',
                                ];
                            }

                            $isLocked = $promotion->sales_count > 0;
                        @endphp

                        <tr wire:key="promotion-{{ $promotion->id }}">
                            <td>
                                <div class="promotion-identity">
                                    <span>
                                        <i class="fa-solid fa-ticket"></i>
                                    </span>
                                    <div>
                                        <strong>{{ $promotion->name }}</strong>
                                        <small>{{ $promotion->code }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="promotion-value-cell">
                                    <strong>
                                        @if($promotion->type === 'percent')
                                            {{ \App\Support\Number::quantity(
                                                $promotion->value,
                                                2
                                            ) }}%
                                        @else
                                            Rp {{ number_format(
                                                $promotion->value,
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        @endif
                                    </strong>
                                    <small>
                                        {{ $promotion->type === 'percent'
                                            ? 'Persentase'
                                            : 'Nominal tetap' }}
                                    </small>
                                </div>
                            </td>

                            <td>
                                <div class="promotion-rules-cell">
                                    <span>
                                        Min.
                                        <b>
                                            Rp {{ number_format(
                                                $promotion->minimum_transaction,
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </b>
                                    </span>
                                    @if(
                                        $promotion->type === 'percent'
                                        && $promotion->maximum_discount !== null
                                    )
                                        <span>
                                            Maks.
                                            <b>
                                                Rp {{ number_format(
                                                    $promotion->maximum_discount,
                                                    0,
                                                    ',',
                                                    '.'
                                                ) }}
                                            </b>
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <span class="promotion-scope-badge">
                                    <i class="fa-solid {{ $promotion->products_count ? 'fa-boxes-stacked' : 'fa-store' }}"></i>
                                    {{ $promotion->products_count
                                        ? $promotion->products_count.' produk'
                                        : 'Semua produk' }}
                                </span>
                            </td>

                            <td>
                                <div class="promotion-period-cell">
                                    <strong>
                                        {{ $promotion->starts_at->format('d/m/Y H:i') }}
                                    </strong>
                                    <small>
                                        s.d.
                                        {{ $promotion->ends_at->format('d/m/Y H:i') }}
                                    </small>
                                </div>
                            </td>

                            <td>
                                @if($isLocked)
                                    <span class="promotion-used-badge">
                                        <i class="fa-solid fa-lock"></i>
                                        {{ number_format($promotion->sales_count) }}
                                        transaksi
                                    </span>
                                @else
                                    <span class="promotion-unused-badge">
                                        Belum digunakan
                                    </span>
                                @endif
                            </td>

                            <td>
                                <button
                                    type="button"
                                    wire:click="toggleActive({{ $promotion->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleActive({{ $promotion->id }})"
                                    class="promotion-state is-{{ $state['class'] }}"
                                    title="Klik untuk {{ $promotion->is_active ? 'menonaktifkan' : 'mengaktifkan' }}"
                                >
                                    <i class="fa-solid {{ $state['icon'] }}"></i>
                                    {{ $state['label'] }}
                                </button>
                            </td>

                            <td class="text-end">
                                <div class="promotion-actions">
                                    @if(! $isLocked)
                                        <button
                                            type="button"
                                            wire:click="edit({{ $promotion->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="edit({{ $promotion->id }})"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Edit promo"
                                        >
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    @endif

                                    <button
                                        type="button"
                                        wire:click="duplicate({{ $promotion->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="duplicate({{ $promotion->id }})"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Duplikasi promo"
                                    >
                                        <i class="fa-solid fa-copy"></i>
                                    </button>

                                    @if(! $isLocked)
                                        <button
                                            type="button"
                                            data-livewire-confirm="Hapus promo {{ $promotion->name }}? Hanya promo yang belum pernah digunakan yang dapat dihapus."
                                            data-livewire-action="delete"
                                            data-livewire-id="{{ $promotion->id }}"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Hapus promo"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @else
                                        <span
                                            class="operations-locked-action"
                                            title="Promo sudah digunakan transaksi"
                                        >
                                            <i class="fa-solid fa-lock"></i>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="operations-empty-state">
                                    <i class="fa-solid fa-tags"></i>
                                    <strong>Promosi tidak ditemukan</strong>
                                    <span>
                                        Tambahkan promo baru atau ubah filter.
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($promotions->hasPages())
            <div class="operations-pagination">
                {{ $promotions->links() }}
            </div>
        @endif
    </section>

    @if($showModal)
        <div class="pos-modal" wire:keydown.escape.window="closeModal">
            <div
                class="pos-modal-dialog promotion-modal-dialog"
                wire:click.stop
                role="dialog"
                aria-modal="true"
            >
                <form wire:submit="save">
                    <header class="operations-modal-header promotion-modal-header">
                        <div>
                            <span>
                                <i class="fa-solid fa-tags"></i>
                            </span>
                            <div>
                                <h5 class="mb-0">
                                    {{ $editingId
                                        ? 'Edit Promosi'
                                        : 'Tambah Promosi' }}
                                </h5>
                                <p class="mb-0">
                                    Atur nilai, periode, dan produk yang menerima
                                    diskon.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            wire:click="closeModal"
                            class="btn-close"
                            aria-label="Tutup"
                        ></button>
                    </header>

                    <div class="operations-modal-body promotion-modal-body">
                        <div class="promotion-form-layout">
                            <div class="promotion-form-main">
                                <section class="operations-form-section">
                                    <div class="operations-section-title">
                                        <span>
                                            <i class="fa-solid fa-ticket"></i>
                                        </span>
                                        <div>
                                            <strong>Identitas Promo</strong>
                                            <small>
                                                Kode ditampilkan pada pilihan
                                                promo kasir.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                Kode <b>*</b>
                                            </label>
                                            <input
                                                wire:model="form.code"
                                                class="form-control text-uppercase"
                                                maxlength="50"
                                                autocomplete="off"
                                                placeholder="HEMAT10"
                                            >
                                            @error('form.code')
                                                <small class="text-danger">
                                                    {{ $message }}
                                                </small>
                                            @enderror
                                        </div>

                                        <div class="col-md-8">
                                            <label class="form-label">
                                                Nama Promo <b>*</b>
                                            </label>
                                            <input
                                                wire:model="form.name"
                                                class="form-control"
                                                maxlength="255"
                                                autocomplete="off"
                                                placeholder="Promo hemat bulanan"
                                            >
                                            @error('form.name')
                                                <small class="text-danger">
                                                    {{ $message }}
                                                </small>
                                            @enderror
                                        </div>
                                    </div>
                                </section>

                                <section class="operations-form-section">
                                    <div class="operations-section-title">
                                        <span>
                                            <i class="fa-solid fa-percent"></i>
                                        </span>
                                        <div>
                                            <strong>Perhitungan Diskon</strong>
                                            <small>
                                                Tentukan persentase atau nominal
                                                tetap.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="promotion-type-options">
                                        <label class="{{ $form['type'] === 'percent' ? 'is-selected' : '' }}">
                                            <input
                                                wire:model.live="form.type"
                                                type="radio"
                                                value="percent"
                                            >
                                            <span>
                                                <i class="fa-solid fa-percent"></i>
                                            </span>
                                            <div>
                                                <strong>Persentase</strong>
                                                <small>
                                                    Potongan berdasarkan nilai
                                                    produk yang memenuhi syarat.
                                                </small>
                                            </div>
                                            <i class="fa-solid fa-circle-check"></i>
                                        </label>

                                        <label class="{{ $form['type'] === 'fixed' ? 'is-selected' : '' }}">
                                            <input
                                                wire:model.live="form.type"
                                                type="radio"
                                                value="fixed"
                                            >
                                            <span>
                                                <i class="fa-solid fa-money-bill"></i>
                                            </span>
                                            <div>
                                                <strong>Nominal Tetap</strong>
                                                <small>
                                                    Potongan rupiah dengan nilai
                                                    yang ditentukan.
                                                </small>
                                            </div>
                                            <i class="fa-solid fa-circle-check"></i>
                                        </label>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                {{ $form['type'] === 'percent'
                                                    ? 'Persentase'
                                                    : 'Nominal Diskon' }}
                                                <b>*</b>
                                            </label>

                                            <div class="input-group">
                                                @if($form['type'] === 'fixed')
                                                    <span class="input-group-text">
                                                        Rp
                                                    </span>
                                                @endif
                                                <input
                                                    wire:model.live.debounce.300ms="form.value"
                                                    type="number"
                                                    min="0.01"
                                                    step="0.01"
                                                    class="form-control"
                                                >
                                                @if($form['type'] === 'percent')
                                                    <span class="input-group-text">
                                                        %
                                                    </span>
                                                @endif
                                            </div>

                                            @error('form.value')
                                                <small class="text-danger">
                                                    {{ $message }}
                                                </small>
                                            @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">
                                                Minimum Transaksi
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    Rp
                                                </span>
                                                <input
                                                    wire:model.live.debounce.300ms="form.minimum_transaction"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    class="form-control"
                                                >
                                            </div>
                                            @error('form.minimum_transaction')
                                                <small class="text-danger">
                                                    {{ $message }}
                                                </small>
                                            @enderror
                                        </div>

                                        @if($form['type'] === 'percent')
                                            <div class="col-md-4">
                                                <label class="form-label">
                                                    Maksimum Diskon
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        Rp
                                                    </span>
                                                    <input
                                                        wire:model.live.debounce.300ms="form.maximum_discount"
                                                        type="number"
                                                        min="0.01"
                                                        step="0.01"
                                                        class="form-control"
                                                        placeholder="Tanpa batas"
                                                    >
                                                </div>
                                                @error('form.maximum_discount')
                                                    <small class="text-danger">
                                                        {{ $message }}
                                                    </small>
                                                @enderror
                                            </div>
                                        @endif
                                    </div>
                                </section>

                                <section class="operations-form-section">
                                    <div class="operations-section-title">
                                        <span>
                                            <i class="fa-solid fa-calendar-days"></i>
                                        </span>
                                        <div>
                                            <strong>Periode & Status</strong>
                                            <small>
                                                Promo hanya dihitung dalam
                                                periode aktif.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                Mulai <b>*</b>
                                            </label>
                                            <input
                                                wire:model="form.starts_at"
                                                type="datetime-local"
                                                class="form-control"
                                            >
                                            @error('form.starts_at')
                                                <small class="text-danger">
                                                    {{ $message }}
                                                </small>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">
                                                Berakhir <b>*</b>
                                            </label>
                                            <input
                                                wire:model="form.ends_at"
                                                type="datetime-local"
                                                class="form-control"
                                            >
                                            @error('form.ends_at')
                                                <small class="text-danger">
                                                    {{ $message }}
                                                </small>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label class="promotion-active-option {{ $form['is_active'] ? 'is-selected' : '' }}">
                                                <input
                                                    wire:model.live="form.is_active"
                                                    type="checkbox"
                                                >
                                                <span>
                                                    <i class="fa-solid fa-toggle-on"></i>
                                                </span>
                                                <div>
                                                    <strong>Aktifkan promo</strong>
                                                    <small>
                                                        Promo tetap mengikuti
                                                        tanggal mulai dan berakhir.
                                                    </small>
                                                </div>
                                                <i class="fa-solid fa-circle-check"></i>
                                            </label>
                                        </div>
                                    </div>
                                </section>

                                <section class="operations-form-section">
                                    <div class="operations-section-title">
                                        <span>
                                            <i class="fa-solid fa-boxes-stacked"></i>
                                        </span>
                                        <div>
                                            <strong>Cakupan Produk</strong>
                                            <small>
                                                Kosongkan pilihan untuk
                                                memberlakukan promo ke semua
                                                produk.
                                            </small>
                                        </div>
                                    </div>

                                    <label class="form-label">
                                        Produk yang Mendapat Promo
                                    </label>
                                    <select
                                        wire:model="productIds"
                                        class="form-select"
                                        multiple
                                        data-placeholder="Pilih produk tertentu"
                                    >
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">
                                                {{ $product->sku }} -
                                                {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('productIds')
                                        <small class="text-danger">
                                            {{ $message }}
                                        </small>
                                    @enderror
                                    @error('productIds.*')
                                        <small class="text-danger">
                                            {{ $message }}
                                        </small>
                                    @enderror
                                </section>
                            </div>

                            <aside class="promotion-preview-card">
                                <span class="promotion-preview-icon">
                                    <i class="fa-solid fa-ticket"></i>
                                </span>

                                <small>Pratinjau Promo</small>

                                <h6>
                                    {{ $form['name'] ?: 'Nama Promosi' }}
                                </h6>

                                <code>
                                    {{ mb_strtoupper(
                                        $form['code'] ?: 'KODEPROMO'
                                    ) }}
                                </code>

                                <strong>
                                    @if($form['type'] === 'percent')
                                        {{ \App\Support\Number::quantity(
                                            $previewValue,
                                            2
                                        ) }}%
                                    @else
                                        Rp {{ number_format(
                                            $previewValue,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    @endif
                                </strong>

                                <p>
                                    Minimum transaksi
                                    <b>
                                        Rp {{ number_format(
                                            $previewMinimum,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </b>
                                </p>

                                @if(
                                    $form['type'] === 'percent'
                                    && $previewMaximum
                                )
                                    <p>
                                        Maksimum diskon
                                        <b>
                                            Rp {{ number_format(
                                                $previewMaximum,
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </b>
                                    </p>
                                @endif

                                <div>
                                    <i class="fa-solid fa-box"></i>
                                    {{ $selectedProductCount
                                        ? $selectedProductCount.' produk dipilih'
                                        : 'Berlaku untuk semua produk' }}
                                </div>

                                <div>
                                    <i class="fa-solid fa-circle {{ $form['is_active'] ? 'text-success' : 'text-secondary' }}"></i>
                                    {{ $form['is_active']
                                        ? 'Status aktif'
                                        : 'Status nonaktif' }}
                                </div>
                            </aside>
                        </div>
                    </div>

                    <footer class="operations-modal-footer">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="btn btn-light"
                        >
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
                                {{ $editingId
                                    ? 'Simpan Perubahan'
                                    : 'Simpan Promosi' }}
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
