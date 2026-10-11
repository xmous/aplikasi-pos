<div class="expense-manager-page">
    @php
        $filterActive = $search !== ''
            || $methodFilter !== 'all'
            || $categoryFilter !== ''
            || $from !== now()->startOfMonth()->format('Y-m-d')
            || $to !== now()->format('Y-m-d');

        $categoryPresets = [
            'Listrik',
            'Air',
            'Internet',
            'Transportasi',
            'Perlengkapan',
            'Perawatan',
            'Konsumsi',
            'Lain-lain',
        ];
    @endphp

    <div class="ga-page-head operations-page-head">
        <div class="ga-page-title">
            <span class="ga-eyebrow">
                <i class="fa-solid fa-money-bill-transfer"></i>
                BIAYA OPERASIONAL
            </span>
            <h2>Pengeluaran</h2>
            <p class="mb-0">
                Catat biaya operasional dan sinkronkan pengeluaran tunai
                dengan kas shift aktif.
            </p>
        </div>

        @can('expenses.create')
            <button
                type="button"
                wire:click="create"
                class="btn btn-warning operations-create-button"
            >
                <i class="fa-solid fa-plus"></i>
                Tambah Pengeluaran
            </button>
        @endcan
    </div>

    <div class="operations-stat-grid">
        <article>
            <span><i class="fa-solid fa-receipt"></i></span>
            <div>
                <small>Jumlah transaksi</small>
                <strong>{{ number_format($summary['count']) }}</strong>
            </div>
        </article>

        <article class="is-total">
            <span><i class="fa-solid fa-coins"></i></span>
            <div>
                <small>Total periode</small>
                <strong>
                    Rp {{ number_format($summary['total'], 0, ',', '.') }}
                </strong>
            </div>
        </article>

        <article class="is-cash">
            <span><i class="fa-solid fa-money-bill-wave"></i></span>
            <div>
                <small>Dibayar tunai</small>
                <strong>
                    Rp {{ number_format($summary['cash'], 0, ',', '.') }}
                </strong>
            </div>
        </article>

        <article class="is-non-cash">
            <span><i class="fa-solid fa-credit-card"></i></span>
            <div>
                <small>Non-tunai</small>
                <strong>
                    Rp {{ number_format($summary['non_cash'], 0, ',', '.') }}
                </strong>
            </div>
        </article>
    </div>

    <section class="operations-list-card">
        <div class="expense-filter-panel">
            <div class="operations-search-field">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    wire:model.live.debounce.350ms="search"
                    type="search"
                    class="form-control"
                    placeholder="Cari kategori, catatan, metode, atau petugas..."
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
                wire:model.live="categoryFilter"
                class="form-select"
                data-no-select2
            >
                <option value="">Semua kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}">{{ $category }}</option>
                @endforeach
            </select>

            <select
                wire:model.live="methodFilter"
                class="form-select"
                data-no-select2
            >
                <option value="all">Semua metode</option>
                @foreach(config('pos.payment_methods') as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>

            <input
                wire:model.live="from"
                type="date"
                class="form-control"
                aria-label="Tanggal awal"
            >

            <input
                wire:model.live="to"
                type="date"
                class="form-control"
                aria-label="Tanggal akhir"
            >

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
                <b>{{ number_format($expenses->total()) }}</b>
                pengeluaran ditemukan
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

        <div class="table-responsive operations-table-wrap">
            <table class="table operations-data-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Metode</th>
                        <th>Catatan</th>
                        <th>Petugas</th>
                        <th>Shift Kasir</th>
                        <th class="text-end">Nominal</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr wire:key="expense-{{ $expense->id }}">
                            <td>
                                <div class="operation-date-cell">
                                    <strong>
                                        {{ $expense->expense_date->format('d/m/Y') }}
                                    </strong>
                                    <small>
                                        Dicatat
                                        {{ $expense->created_at->format('d/m/Y H:i') }}
                                    </small>
                                </div>
                            </td>

                            <td>
                                <span class="expense-category-badge">
                                    <i class="fa-solid fa-folder-open"></i>
                                    {{ $expense->category }}
                                </span>
                            </td>

                            <td>
                                <span class="expense-method is-{{ $expense->payment_method === 'cash' ? 'cash' : 'non-cash' }}">
                                    <i class="fa-solid {{ $expense->payment_method === 'cash' ? 'fa-money-bill-wave' : 'fa-credit-card' }}"></i>
                                    {{ config(
                                        'pos.payment_methods.'.$expense->payment_method,
                                        $expense->payment_method
                                    ) }}
                                </span>
                            </td>

                            <td>
                                <span
                                    class="operations-note"
                                    title="{{ $expense->notes }}"
                                >
                                    {{ $expense->notes }}
                                </span>
                            </td>

                            <td>
                                <div class="operations-user-cell">
                                    <i class="fa-solid fa-user"></i>
                                    <span>
                                        {{ $expense->creator?->name ?? '-' }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                @if($expense->payment_method === 'cash')
                                    <div class="expense-shift-cell">
                                        <strong>
                                            {{ $expense->cashierShift?->status === 'open'
                                                ? 'Shift Aktif'
                                                : 'Shift Ditutup' }}
                                        </strong>
                                        <small>
                                            {{ $expense->cashierShift?->opened_at?->format('d/m/Y H:i') ?? '-' }}
                                        </small>
                                    </div>
                                @else
                                    <span class="text-muted">Tidak melalui kas</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <strong class="expense-amount">
                                    Rp {{ number_format(
                                        $expense->amount,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </strong>
                            </td>

                            <td class="text-end">
                                @can('expenses.update')
                                    @if($expense->can_edit)
                                        <button
                                            type="button"
                                            wire:click="edit({{ $expense->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="edit({{ $expense->id }})"
                                            class="btn btn-sm btn-outline-primary operations-icon-button"
                                            title="Edit pengeluaran"
                                        >
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    @else
                                        <span
                                            class="operations-locked-action"
                                            title="Shift asal sudah ditutup"
                                        >
                                            <i class="fa-solid fa-lock"></i>
                                        </span>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="operations-empty-state">
                                    <i class="fa-solid fa-receipt"></i>
                                    <strong>Pengeluaran tidak ditemukan</strong>
                                    <span>
                                        Belum ada biaya pada periode ini atau
                                        filter terlalu spesifik.
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
            <div class="operations-pagination">
                {{ $expenses->links() }}
            </div>
        @endif
    </section>

    @if($showModal)
        <div class="pos-modal" wire:keydown.escape.window="closeModal">
            <div
                class="pos-modal-dialog operations-modal-dialog"
                wire:click.stop
                role="dialog"
                aria-modal="true"
            >
                <form wire:submit="save">
                    <header class="operations-modal-header">
                        <div>
                            <span>
                                <i class="fa-solid fa-money-bill-transfer"></i>
                            </span>
                            <div>
                                <h5 class="mb-0">
                                    {{ $editingId
                                        ? 'Edit Pengeluaran'
                                        : 'Tambah Pengeluaran' }}
                                </h5>
                                <p class="mb-0">
                                    Pengeluaran tunai langsung mengurangi kas
                                    shift yang digunakan.
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

                    <div class="operations-modal-body">
                        <section class="operations-form-section">
                            <div class="operations-section-title">
                                <span>
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                </span>
                                <div>
                                    <strong>Informasi Biaya</strong>
                                    <small>
                                        Lengkapi tanggal, kategori, dan nominal.
                                    </small>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">
                                        Tanggal <b>*</b>
                                    </label>
                                    <input
                                        wire:model="form.expense_date"
                                        type="date"
                                        max="{{ today()->format('Y-m-d') }}"
                                        class="form-control"
                                    >
                                    @error('form.expense_date')
                                        <small class="text-danger">
                                            {{ $message }}
                                        </small>
                                    @enderror
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label">
                                        Kategori <b>*</b>
                                    </label>
                                    <input
                                        wire:model="form.category"
                                        class="form-control"
                                        maxlength="100"
                                        autocomplete="off"
                                        placeholder="Contoh: Listrik atau Perlengkapan"
                                    >
                                    @error('form.category')
                                        <small class="text-danger">
                                            {{ $message }}
                                        </small>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <div class="expense-category-presets">
                                        @foreach($categoryPresets as $preset)
                                            <button
                                                type="button"
                                                wire:click="$set('form.category', '{{ $preset }}')"
                                                class="{{ $form['category'] === $preset ? 'is-selected' : '' }}"
                                            >
                                                {{ $preset }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Nominal <b>*</b>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input
                                            wire:model.live.debounce.300ms="form.amount"
                                            type="number"
                                            min="1"
                                            step="0.01"
                                            class="form-control"
                                        >
                                    </div>
                                    @error('form.amount')
                                        <small class="text-danger">
                                            {{ $message }}
                                        </small>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Metode Pembayaran <b>*</b>
                                    </label>
                                    <select
                                        wire:model.live="form.payment_method"
                                        class="form-select"
                                        data-no-select2
                                    >
                                        @foreach(config('pos.payment_methods') as $key => $label)
                                            <option value="{{ $key }}">
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('form.payment_method')
                                        <small class="text-danger">
                                            {{ $message }}
                                        </small>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">
                                        Catatan atau Alasan <b>*</b>
                                    </label>
                                    <textarea
                                        wire:model="form.notes"
                                        rows="4"
                                        minlength="3"
                                        maxlength="1000"
                                        class="form-control"
                                        placeholder="Jelaskan untuk apa pengeluaran digunakan..."
                                    ></textarea>
                                    @error('form.notes')
                                        <small class="text-danger">
                                            {{ $message }}
                                        </small>
                                    @enderror
                                </div>
                            </div>
                        </section>

                        <section class="expense-payment-preview {{ $form['payment_method'] === 'cash' ? 'is-cash' : 'is-non-cash' }}">
                            <span>
                                <i class="fa-solid {{ $form['payment_method'] === 'cash' ? 'fa-cash-register' : 'fa-building-columns' }}"></i>
                            </span>
                            <div>
                                @if($form['payment_method'] === 'cash')
                                    <strong>Pengeluaran dari kas shift</strong>
                                    @if($openShift)
                                        <small>
                                            Kas sistem tersedia:
                                            <b>
                                                Rp {{ number_format(
                                                    $openShift->expected_cash,
                                                    0,
                                                    ',',
                                                    '.'
                                                ) }}
                                            </b>
                                        </small>
                                    @else
                                        <small class="text-danger">
                                            Belum ada shift kasir aktif.
                                        </small>
                                    @endif
                                @else
                                    <strong>Pengeluaran non-tunai</strong>
                                    <small>
                                        Tidak memengaruhi saldo kas shift.
                                    </small>
                                @endif
                            </div>
                        </section>

                        @if($editingId)
                            <div class="operations-edit-notice">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>
                                    Data lama akan dibalik lebih dahulu, lalu
                                    nilai baru diterapkan dan dicatat pada
                                    audit log.
                                </span>
                            </div>
                        @endif
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
                                    : 'Simpan Pengeluaran' }}
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
