<div class="master-data-page master-data-{{ $type }}">
    @php
        $supportsStatus = (bool) $meta['supports_status'];
        $filterIsActive = $search !== '' || ($supportsStatus && $statusFilter !== 'all');

        $searchPlaceholder = match ($type) {
            'categories' => 'Cari nama atau kode kategori...',
            'units' => 'Cari nama atau simbol satuan...',
            'suppliers' => 'Cari nama, kode, telepon, email, alamat...',
            'customers' => 'Cari member, kode, telepon, email, alamat...',
            'warehouses' => 'Cari nama, kode, atau alamat gudang...',
        };
    @endphp

    <div class="ga-page-head master-page-head">
        <div class="ga-page-title">
            <span class="ga-eyebrow">
                <i class="fa-solid {{ $meta['icon'] }}"></i>
                MASTER DATA
            </span>
            <h2>{{ $meta['title'] }}</h2>
            <p class="mb-0">{{ $meta['description'] }}</p>
        </div>

        <button type="button" wire:click="create" class="btn btn-warning master-add-button">
            <i class="fa-solid fa-plus"></i>
            Tambah {{ $meta['singular'] }}
        </button>
    </div>

    <div class="master-stat-grid">
        @foreach ($stats as $stat)
            <article class="master-stat-card {{ $stat['class'] }}">
                <span><i class="fa-solid {{ $stat['icon'] }}"></i></span>
                <div>
                    <small>{{ $stat['label'] }}</small>
                    <strong>{{ number_format($stat['value']) }}</strong>
                </div>
            </article>
        @endforeach
    </div>

    <section class="master-list-card">
        <div class="master-filter-panel">
            <div class="master-search-field">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" wire:model.live.debounce.350ms="search" class="form-control"
                    placeholder="{{ $searchPlaceholder }}" autocomplete="off">
                <span wire:loading wire:target="search" class="master-search-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
            </div>

            @if ($supportsStatus)
                <select wire:model.live="statusFilter" class="form-select" data-no-select2 aria-label="Filter status">
                    <option value="all">Semua status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            @endif

            @if ($filterIsActive)
                <button type="button" wire:click="clearFilters" class="btn btn-light master-clear-filter">
                    <i class="fa-solid fa-xmark"></i>
                    Bersihkan
                </button>
            @endif
        </div>

        <div class="master-list-meta">
            <span>
                <i class="fa-solid fa-list"></i>
                <b>{{ number_format($rows->total()) }}</b> data ditemukan
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

        <div class="table-responsive master-table-wrap">
            <table class="table master-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ $type === 'customers' ? 'Member / Pelanggan' : $meta['singular'] }}</th>

                        @if ($type === 'categories')
                            <th>Kode</th>
                            <th>Penggunaan</th>
                        @elseif($type === 'units')
                            <th>Simbol</th>
                            <th>Penggunaan</th>
                        @elseif(in_array($type, ['suppliers', 'customers'], true))
                            <th>Kontak</th>
                            <th>{{ $type === 'customers' ? 'Member & Kredit' : 'Pembelian' }}</th>
                        @elseif($type === 'warehouses')
                            <th>Alamat</th>
                            <th>Penggunaan</th>
                        @endif

                        @if ($supportsStatus)
                            <th>Status</th>
                        @endif

                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($rows as $row)
                        <tr wire:key="master-{{ $type }}-{{ $row->id }}">
                            <td>
                                <div class="master-identity">
                                    <span class="master-avatar">
                                        <i class="fa-solid {{ $meta['icon'] }}"></i>
                                    </span>
                                    <div>
                                        <strong>{{ $row->name }}</strong>
                                        <small>
                                            @if ($type !== 'units')
                                                {{ $row->code ?: 'Tanpa kode' }}
                                            @else
                                                Satuan barang
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </td>

                            @if ($type === 'categories')
                                <td>
                                    <span class="master-code-badge">{{ $row->code }}</span>
                                </td>
                                <td>
                                    <div class="master-usage-cell">
                                        <strong>{{ number_format($row->usage_count ?? 0) }}</strong>
                                        <small>barang</small>
                                    </div>
                                </td>
                            @elseif($type === 'units')
                                <td>
                                    <span class="master-symbol-badge">{{ $row->symbol }}</span>
                                </td>
                                <td>
                                    <div class="master-usage-detail">
                                        <span>
                                            <small>Satuan dasar</small>
                                            <b>{{ number_format($row->base_usage_count ?? 0) }}</b>
                                        </span>
                                        <span>
                                            <small>Konversi</small>
                                            <b>{{ number_format($row->conversion_usage_count ?? 0) }}</b>
                                        </span>
                                    </div>
                                </td>
                            @elseif(in_array($type, ['suppliers', 'customers'], true))
                                <td>
                                    <div class="master-contact-cell">
                                        <span>
                                            <i class="fa-solid fa-phone"></i>
                                            {{ $row->phone ?: 'Telepon belum diisi' }}
                                        </span>
                                        <span>
                                            <i class="fa-solid fa-envelope"></i>
                                            {{ $row->email ?: 'Email belum diisi' }}
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    @if ($type === 'customers')
                                        <div class="master-member-cell">
                                            <span>
                                                <small>Poin</small>
                                                <strong>{{ number_format($row->points ?? 0) }}</strong>
                                            </span>
                                            <span>
                                                <small>Limit</small>
                                                <strong>Rp
                                                    {{ number_format($row->credit_limit ?? 0, 0, ',', '.') }}</strong>
                                            </span>
                                            <em>{{ number_format($row->usage_count ?? 0) }} transaksi</em>
                                        </div>
                                    @else
                                        <div class="master-usage-cell">
                                            <strong>{{ number_format($row->usage_count ?? 0) }}</strong>
                                            <small>pembelian</small>
                                        </div>
                                    @endif
                                </td>
                            @elseif($type === 'warehouses')
                                <td>
                                    <div class="master-address-cell">
                                        <i class="fa-solid fa-location-dot"></i>
                                        <span>{{ $row->address ?: 'Alamat belum diisi' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="master-usage-cell">
                                        <strong>{{ number_format($row->usage_count ?? 0) }}</strong>
                                        <small>histori stok/transaksi</small>
                                    </div>
                                </td>
                            @endif

                            @if ($supportsStatus)
                                <td>
                                    <button type="button" wire:click="toggleActive({{ $row->id }})"
                                        wire:loading.attr="disabled" wire:target="toggleActive({{ $row->id }})"
                                        class="master-status-button {{ $row->is_active ? 'is-active' : 'is-inactive' }}"
                                        title="Klik untuk {{ $row->is_active ? 'menonaktifkan' : 'mengaktifkan' }}">
                                        <i
                                            class="fa-solid {{ $row->is_active ? 'fa-circle-check' : 'fa-circle-minus' }}"></i>
                                        {{ $row->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </td>
                            @endif

                            <td class="text-end">
                                <div class="master-action-group">
                                    <button type="button" wire:click="edit({{ $row->id }})"
                                        wire:loading.attr="disabled" wire:target="edit({{ $row->id }})"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Edit {{ strtolower($meta['singular']) }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <button type="button"
                                        data-livewire-confirm="Hapus {{ $row->name }}? Data yang sudah digunakan tidak dapat dihapus."
                                        data-livewire-action="delete" data-livewire-id="{{ $row->id }}"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Hapus {{ strtolower($meta['singular']) }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="master-empty-state">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <strong>Data tidak ditemukan</strong>
                                    <span>Coba ubah kata pencarian atau bersihkan filter.</span>

                                    @if ($filterIsActive)
                                        <button type="button" wire:click="clearFilters"
                                            class="btn btn-sm btn-outline-primary">
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

        @if ($rows->hasPages())
            <div class="master-pagination">
                {{ $rows->links() }}
            </div>
        @endif
    </section>

    @if ($showModal)
        <div class="pos-modal" wire:keydown.escape.window="closeModal">
            <div class="pos-modal-dialog master-modal-dialog" wire:click.stop role="dialog" aria-modal="true">
                <form wire:submit="save">
                    <header class="master-modal-header">
                        <div>
                            <span class="master-modal-icon">
                                <i class="fa-solid {{ $editingId ? 'fa-pen-to-square' : $meta['icon'] }}"></i>
                            </span>
                            <div>
                                <h5 class="mb-0">
                                    {{ $editingId ? 'Edit' : 'Tambah' }} {{ $meta['singular'] }}
                                </h5>
                                <p class="mb-0">
                                    @if ($type === 'customers')
                                        Data ini dapat dipilih sebagai member pada POS.
                                    @elseif($type === 'suppliers')
                                        Data ini digunakan pada transaksi pembelian.
                                    @elseif($type === 'warehouses')
                                        Gudang aktif dapat dipilih pada transaksi stok.
                                    @elseif($type === 'units')
                                        Gunakan simbol singkat seperti pcs, kg, dus, atau pack.
                                    @else
                                        Kategori aktif dapat dipilih pada master barang.
                                    @endif
                                </p>
                            </div>
                        </div>

                        <button type="button" wire:click="closeModal" class="btn-close"
                            aria-label="Tutup"></button>
                    </header>

                    <div class="master-modal-body">
                        <section class="master-form-section">
                            <div class="master-section-heading">
                                <span><i class="fa-solid fa-pen-to-square"></i></span>
                                <div>
                                    <h6>Informasi Utama</h6>
                                    <p>Isi data utama yang akan digunakan pada transaksi dan laporan.</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                @if ($type !== 'units')
                                    <div class="col-md-4">
                                        <label class="form-label">
                                            Kode
                                            @if (in_array($type, ['categories', 'warehouses'], true))
                                                <b>*</b>
                                            @endif
                                        </label>
                                        <input wire:model="form.code" class="form-control text-uppercase"
                                            maxlength="30" autocomplete="off"
                                            placeholder="{{ match ($type) {
                                                'categories' => 'KTG-001',
                                                'suppliers' => 'SUP-001',
                                                'customers' => 'MEM-001',
                                                'warehouses' => 'GDG-001',
                                            } }}">
                                        @error('form.code')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                @endif

                                <div class="{{ $type !== 'units' ? 'col-md-8' : 'col-md-7' }}">
                                    <label class="form-label">Nama <b>*</b></label>
                                    <input wire:model="form.name" class="form-control" maxlength="255"
                                        autocomplete="off"
                                        placeholder="{{ match ($type) {
                                            'categories' => 'Contoh: Frozen Food',
                                            'units' => 'Contoh: Pieces',
                                            'suppliers' => 'Nama perusahaan atau pemasok',
                                            'customers' => 'Nama pelanggan atau member',
                                            'warehouses' => 'Contoh: Gudang Utama',
                                        } }}">
                                    @error('form.name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                @if ($type === 'units')
                                    <div class="col-md-5">
                                        <label class="form-label">Simbol <b>*</b></label>
                                        <input wire:model="form.symbol" class="form-control" maxlength="20"
                                            autocomplete="off" placeholder="pcs">
                                        @error('form.symbol')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                @endif
                            </div>
                        </section>

                        @if (in_array($type, ['suppliers', 'customers'], true))
                            <section class="master-form-section">
                                <div class="master-section-heading">
                                    <span><i class="fa-solid fa-address-book"></i></span>
                                    <div>
                                        <h6>Kontak</h6>
                                        <p>Informasi ini membantu pencarian, komunikasi, dan pencetakan dokumen.</p>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nomor telepon</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                                            <input wire:model="form.phone" class="form-control" maxlength="40"
                                                autocomplete="tel" placeholder="08xxxxxxxxxx">
                                        </div>
                                        @error('form.phone')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                            <input wire:model="form.email" type="email" class="form-control"
                                                maxlength="255" autocomplete="email" placeholder="nama@email.com">
                                        </div>
                                        @error('form.email')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Alamat</label>
                                        <textarea wire:model="form.address" class="form-control" rows="3" maxlength="2000"
                                            placeholder="Alamat lengkap"></textarea>
                                        @error('form.address')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </section>
                        @endif

                        @if ($type === 'warehouses')
                            <section class="master-form-section">
                                <div class="master-section-heading">
                                    <span><i class="fa-solid fa-location-dot"></i></span>
                                    <div>
                                        <h6>Lokasi Gudang</h6>
                                        <p>Alamat akan tampil pada informasi gudang dan dokumen terkait stok.</p>
                                    </div>
                                </div>

                                <label class="form-label">Alamat gudang</label>
                                <textarea wire:model="form.address" class="form-control" rows="3" maxlength="2000"
                                    placeholder="Alamat lengkap gudang"></textarea>
                                @error('form.address')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </section>
                        @endif

                        @if ($type === 'customers')
                            <section class="master-form-section">
                                <div class="master-section-heading">
                                    <span><i class="fa-solid fa-credit-card"></i></span>
                                    <div>
                                        <h6>Member & Kredit</h6>
                                        <p>Poin diperoleh dari transaksi. Limit menentukan batas piutang pelanggan.</p>
                                    </div>
                                </div>

                                <div class="row g-3 align-items-end">
                                    <div class="col-md-7">
                                        <label class="form-label">Limit kredit <b>*</b></label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input wire:model="form.credit_limit" type="number" min="0"
                                                step="1" class="form-control">
                                        </div>
                                        @error('form.credit_limit')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    @if ($editingId)
                                        <div class="col-md-5">
                                            <div class="master-readonly-info">
                                                <small>Poin member</small>
                                                <strong>
                                                    {{ number_format(\App\Models\Customer::query()->find($editingId)?->points ?? 0) }}
                                                </strong>
                                                <span>Poin berubah melalui transaksi.</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </section>
                        @endif

                        @if ($type === 'suppliers')
                            <section class="master-form-section">
                                <div class="master-section-heading">
                                    <span><i class="fa-solid fa-note-sticky"></i></span>
                                    <div>
                                        <h6>Catatan Supplier</h6>
                                        <p>Simpan termin pembayaran, nama sales, atau informasi penting lainnya.</p>
                                    </div>
                                </div>

                                <textarea wire:model="form.notes" class="form-control" rows="3" maxlength="2000"
                                    placeholder="Catatan tambahan"></textarea>
                                @error('form.notes')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </section>
                        @endif

                        @if ($supportsStatus)
                            <section class="master-form-section">
                                <div class="master-section-heading">
                                    <span><i class="fa-solid fa-toggle-on"></i></span>
                                    <div>
                                        <h6>Status Data</h6>
                                        <p>Data nonaktif tetap tersimpan tetapi tidak ditawarkan pada transaksi baru.
                                        </p>
                                    </div>
                                </div>

                                <label class="master-active-option {{ $form['is_active'] ? 'is-selected' : '' }}">
                                    <input wire:model.live="form.is_active" type="checkbox">
                                    <span><i class="fa-solid fa-circle-check"></i></span>
                                    <div>
                                        <strong>{{ $form['is_active'] ? 'Data aktif' : 'Data nonaktif' }}</strong>
                                        <small>
                                            {{ $form['is_active']
                                                ? 'Dapat dipilih pada transaksi dan master terkait.'
                                                : 'Disembunyikan dari pilihan transaksi baru.' }}
                                        </small>
                                    </div>
                                    <i
                                        class="fa-solid {{ $form['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </label>

                                @error('form.is_active')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </section>
                        @endif
                    </div>

                    <footer class="master-modal-footer">
                        <button type="button" wire:click="closeModal" class="btn btn-light">
                            <i class="fa-solid fa-xmark"></i>
                            Batal
                        </button>

                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                            wire:target="save">
                            <span wire:loading.remove wire:target="save">
                                <i class="fa-solid fa-floppy-disk"></i>
                                {{ $editingId ? 'Simpan Perubahan' : 'Simpan ' . $meta['singular'] }}
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
