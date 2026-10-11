<div class="admin-user-page">
    @php
        $filterActive = $search !== ''
            || $roleFilter !== ''
            || $statusFilter !== 'all'
            || $verificationFilter !== 'all'
            || (
                $twoFactorFeatureEnabled
                && $twoFactorFilter !== 'all'
            );
        $ownerCanCalibrateReceipt = auth()->user()->hasRole('owner');
        $tableColumnCount = 7
            + ($twoFactorFeatureEnabled ? 1 : 0)
            + ($ownerCanCalibrateReceipt ? 1 : 0);
    @endphp

    <div class="ga-page-head admin-page-head">
        <div class="ga-page-title">
            <span class="ga-eyebrow">
                <i class="fa-solid fa-user-shield"></i>
                AKUN & KEAMANAN AKSES
            </span>
            <h2>Pengguna</h2>
            <p class="mb-0">
                Kelola akun, role, verifikasi email
                @if($twoFactorFeatureEnabled)
                    , status 2FA
                @endif
                , akses masuk, dan kalibrasi printer tiap pegawai.
            </p>
        </div>

        <button
            type="button"
            wire:click="create"
            class="btn btn-warning admin-primary-action"
        >
            <i class="fa-solid fa-user-plus"></i>
            Tambah Pengguna
        </button>
    </div>

    @error('user')
        <div class="admin-inline-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ $message }}</span>
        </div>
    @enderror

    <div class="admin-stat-grid">
        <article>
            <span><i class="fa-solid fa-users"></i></span>
            <div>
                <small>Total pengguna</small>
                <strong>{{ number_format($summary['total']) }}</strong>
            </div>
        </article>
        <article class="is-active">
            <span><i class="fa-solid fa-user-check"></i></span>
            <div>
                <small>Akun aktif</small>
                <strong>{{ number_format($summary['active']) }}</strong>
            </div>
        </article>
        <article class="is-warning">
            <span><i class="fa-solid fa-envelope-circle-check"></i></span>
            <div>
                <small>Belum verifikasi email</small>
                <strong>{{ number_format($summary['unverified']) }}</strong>
            </div>
        </article>
        @if($twoFactorFeatureEnabled)
            <article class="is-secure">
                <span>
                    <i class="fa-solid fa-mobile-screen-button"></i>
                </span>
                <div>
                    <small>2FA aktif</small>
                    <strong>
                        {{ number_format($summary['two_factor']) }}
                    </strong>
                </div>
            </article>
        @else
            <article class="is-secure">
                <span><i class="fa-solid fa-user-tag"></i></span>
                <div>
                    <small>Role tersedia</small>
                    <strong>{{ number_format($allRoles->count()) }}</strong>
                </div>
            </article>
        @endif
    </div>

    <section class="admin-list-card">
        <div class="admin-user-filters {{ $twoFactorFeatureEnabled ? '' : 'is-without-two-factor' }}">
            <div class="admin-search-field">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    wire:model.live.debounce.350ms="search"
                    type="search"
                    class="form-control"
                    placeholder="Cari nama, email, atau role..."
                    autocomplete="off"
                >
                <span wire:loading wire:target="search">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
            </div>

            <select
                wire:model.live="roleFilter"
                class="form-select"
                data-no-select2
            >
                <option value="">Semua role</option>
                @foreach($allRoles as $role)
                    <option value="{{ $role->name }}">
                        {{ str($role->name)->replace('_', ' ')->title() }}
                    </option>
                @endforeach
            </select>

            <select
                wire:model.live="statusFilter"
                class="form-select"
                data-no-select2
            >
                <option value="all">Semua status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
            </select>

            <select
                wire:model.live="verificationFilter"
                class="form-select"
                data-no-select2
            >
                <option value="all">Semua verifikasi</option>
                <option value="verified">Email terverifikasi</option>
                <option value="unverified">Belum verifikasi</option>
            </select>

            @if($twoFactorFeatureEnabled)
            <select
                wire:model.live="twoFactorFilter"
                class="form-select"
                data-no-select2
            >
                <option value="all">Semua status 2FA</option>
                <option value="enabled">2FA aktif</option>
                <option value="disabled">2FA belum aktif</option>
            </select>

            @endif

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

        <div class="admin-list-meta">
            <span>
                <i class="fa-solid fa-list"></i>
                <b>{{ number_format($users->total()) }}</b>
                pengguna ditemukan
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

        <div class="table-responsive admin-table-wrap">
            <table class="table admin-data-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Role</th>
                        <th>Verifikasi Email</th>
                        @if($twoFactorFeatureEnabled)
                            <th>2FA</th>
                        @endif
                        <th>Login Terakhir</th>
                        <th>Password</th>
                        @if($ownerCanCalibrateReceipt)
                            <th>Printer Struk</th>
                        @endif
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $roleName = $user->getRoleNames()->first() ?: 'Tanpa role';
                            $isSelf = (int) $user->id === (int) auth()->id();
                        @endphp
                        <tr wire:key="user-row-{{ $user->id }}">
                            <td>
                                <div class="admin-user-identity">
                                    <span>
                                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </span>
                                    <div>
                                        <strong>
                                            {{ $user->name }}
                                            @if($isSelf)
                                                <em>Kamu</em>
                                            @endif
                                        </strong>
                                        <small>{{ $user->email }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="admin-role-badge is-{{ $roleName === 'owner' ? 'owner' : 'regular' }}">
                                    <i class="fa-solid {{ $roleName === 'owner' ? 'fa-crown' : 'fa-user-tag' }}"></i>
                                    {{ str($roleName)->replace('_', ' ')->title() }}
                                </span>
                            </td>

                            <td>
                                @if($user->email_verified_at)
                                    <span class="admin-security-status is-success">
                                        <i class="fa-solid fa-circle-check"></i>
                                        Terverifikasi
                                    </span>
                                    <small class="admin-status-date">
                                        {{ $user->email_verified_at->format('d/m/Y H:i') }}
                                    </small>
                                @else
                                    <span class="admin-security-status is-warning">
                                        <i class="fa-solid fa-clock"></i>
                                        Belum verifikasi
                                    </span>
                                    @if($user->is_active)
                                        <button
                                            type="button"
                                            wire:click="resendVerification({{ $user->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="resendVerification({{ $user->id }})"
                                            class="admin-text-action"
                                        >
                                            Kirim ulang
                                        </button>
                                    @endif
                                @endif
                            </td>

                            @if($twoFactorFeatureEnabled)
                            <td>
                                <span class="admin-security-status {{ $user->two_factor_confirmed_at ? 'is-success' : 'is-muted' }}">
                                    <i class="fa-solid {{ $user->two_factor_confirmed_at ? 'fa-shield-halved' : 'fa-shield' }}"></i>
                                    {{ $user->two_factor_confirmed_at ? 'Aktif' : 'Belum aktif' }}
                                </span>
                            </td>
                            @endif

                            <td>
                                <div class="admin-date-cell">
                                    <strong>
                                        {{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Belum pernah' }}
                                    </strong>
                                    <small>
                                        Akun dibuat {{ $user->created_at->format('d/m/Y') }}
                                    </small>
                                </div>
                            </td>

                            <td>
                                <div class="admin-date-cell">
                                    <strong>
                                        {{ $user->password_changed_at?->format('d/m/Y') ?? 'Belum tercatat' }}
                                    </strong>
                                    <small>Perubahan terakhir</small>
                                </div>
                            </td>

                            @if($ownerCanCalibrateReceipt)
                                <td>
                                    @php
                                        $receiptPosition = $user
                                            ->preferredReceiptAlignment();
                                        $receiptPaperSize = $user
                                            ->preferredReceiptPaperSize();
                                        $receiptPositionMeta = match (
                                            $receiptPosition
                                        ) {
                                            'left' => [
                                                'icon' => 'fa-arrow-left',
                                                'label' => 'Ke kiri',
                                            ],
                                            'right' => [
                                                'icon' => 'fa-arrow-right',
                                                'label' => 'Ke kanan',
                                            ],
                                            default => [
                                                'icon' => 'fa-align-center',
                                                'label' => 'Tengah',
                                            ],
                                        };
                                    @endphp
                                    <div class="admin-receipt-printer-meta">
                                        <span class="admin-receipt-size">
                                            <i class="fa-solid fa-ruler-horizontal"></i>
                                            {{ $receiptPaperSize }} mm
                                        </span>
                                        <span class="admin-receipt-position is-{{ $receiptPosition }}">
                                            <i class="fa-solid {{
                                                $receiptPositionMeta['icon']
                                            }}"></i>
                                            {{ $receiptPositionMeta['label'] }}
                                        </span>
                                    </div>
                                    <small class="admin-status-date">
                                        Default printer pegawai
                                    </small>
                                </td>
                            @endif

                            <td>
                                <button
                                    type="button"
                                    wire:click="toggleActive({{ $user->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleActive({{ $user->id }})"
                                    class="admin-account-state {{ $user->is_active ? 'is-active' : 'is-inactive' }}"
                                    @disabled($isSelf)
                                    title="{{ $isSelf ? 'Status akun sendiri diubah melalui form edit dengan perlindungan sistem' : 'Klik untuk mengubah status' }}"
                                >
                                    <i class="fa-solid {{ $user->is_active ? 'fa-circle-check' : 'fa-circle-pause' }}"></i>
                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>

                            <td class="text-end">
                                <button
                                    type="button"
                                    wire:click="edit({{ $user->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="edit({{ $user->id }})"
                                    class="btn btn-sm btn-outline-primary admin-icon-button"
                                    title="Edit pengguna"
                                >
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tableColumnCount }}">
                                <div class="admin-empty-state">
                                    <i class="fa-solid fa-user-group"></i>
                                    <strong>Pengguna tidak ditemukan</strong>
                                    <span>
                                        Tambahkan pengguna baru atau ubah filter pencarian.
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="admin-pagination">
                {{ $users->links() }}
            </div>
        @endif
    </section>

    <div class="admin-record-policy">
        <i class="fa-solid fa-circle-info"></i>
        <span>
            Akun tidak dihapus agar histori transaksi dan audit tetap mempunyai
            penanggung jawab. Gunakan status <b>Nonaktif</b> untuk mencabut akses.
        </span>
    </div>

    @if($showModal)
        <div class="pos-modal" wire:keydown.escape.window="closeModal">
            <div
                class="pos-modal-dialog admin-user-modal"
                wire:click.stop
                role="dialog"
                aria-modal="true"
            >
                <form wire:submit="save">
                    <header class="admin-modal-header">
                        <div>
                            <span><i class="fa-solid fa-user-shield"></i></span>
                            <div>
                                <h5 class="mb-0">
                                    {{ $editingId ? 'Edit Pengguna' : 'Tambah Pengguna' }}
                                </h5>
                                <p class="mb-0">
                                    Atur identitas, role, password, dan status akses.
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

                    <div class="admin-modal-body">
                        <section class="admin-form-section">
                            <div class="admin-section-title">
                                <span><i class="fa-solid fa-address-card"></i></span>
                                <div>
                                    <strong>Identitas Akun</strong>
                                    <small>Email baru wajib diverifikasi sebelum login.</small>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap <b>*</b></label>
                                    <input
                                        wire:model="form.name"
                                        class="form-control"
                                        maxlength="255"
                                        autocomplete="name"
                                        placeholder="Nama pengguna"
                                    >
                                    @error('form.name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Email <b>*</b></label>
                                    <input
                                        wire:model="form.email"
                                        type="email"
                                        class="form-control"
                                        maxlength="255"
                                        autocomplete="email"
                                        placeholder="nama@perusahaan.com"
                                    >
                                    @error('form.email')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </section>

                        <section class="admin-form-section">
                            <div class="admin-section-title">
                                <span><i class="fa-solid fa-key"></i></span>
                                <div>
                                    <strong>Role & Password</strong>
                                    <small>
                                        Role menentukan menu dan tindakan yang dapat digunakan.
                                    </small>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Role <b>*</b></label>
                                    <select
                                        wire:model="form.role"
                                        class="form-select"
                                        data-no-select2
                                    >
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}">
                                                {{ str($role->name)->replace('_', ' ')->title() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('form.role')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Password
                                        @if(! $editingId)<b>*</b>@endif
                                    </label>
                                    <input
                                        wire:model="form.password"
                                        type="password"
                                        class="form-control"
                                        autocomplete="new-password"
                                        placeholder="{{ $editingId ? 'Kosongkan jika tidak diganti' : 'Buat password kuat' }}"
                                    >
                                    @error('form.password')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="admin-password-rules">
                                <i class="fa-solid fa-shield-halved"></i>
                                <span>
                                    Minimal 12 karakter, huruf besar-kecil, angka,
                                    simbol, dan tidak pernah bocor pada basis data publik.
                                </span>
                            </div>
                        </section>

                        @if($ownerCanCalibrateReceipt)
                            <section class="admin-form-section admin-receipt-calibration">
                                <div class="admin-section-title">
                                    <span>
                                        <i class="fa-solid fa-print"></i>
                                    </span>
                                    <div>
                                        <strong>Kalibrasi Printer Pegawai</strong>
                                        <small>
                                            Struk mengikuti kasir yang membuat transaksi,
                                            bukan akun yang sedang membuka halaman struk.
                                        </small>
                                    </div>
                                </div>

                                <div class="admin-receipt-setting-label">
                                    <i class="fa-solid fa-ruler-horizontal"></i>
                                    <div>
                                        <strong>Ukuran kertas default</strong>
                                        <small>Masih bisa diganti satu kali dari halaman cetak.</small>
                                    </div>
                                </div>

                                <div class="admin-receipt-paper-options">
                                    <label class="{{
                                        (int) ($form['receipt_paper_size'] ?? 58) === 58
                                            ? 'is-selected'
                                            : ''
                                    }}">
                                        <input
                                            type="radio"
                                            wire:model.live="form.receipt_paper_size"
                                            value="58"
                                        >
                                        <span>58</span>
                                        <div>
                                            <strong>58 mm</strong>
                                            <small>Default. Cocok untuk POS-58 dan printer mini.</small>
                                        </div>
                                        <i class="fa-solid fa-circle-check"></i>
                                    </label>

                                    <label class="{{
                                        (int) ($form['receipt_paper_size'] ?? 58) === 80
                                            ? 'is-selected'
                                            : ''
                                    }}">
                                        <input
                                            type="radio"
                                            wire:model.live="form.receipt_paper_size"
                                            value="80"
                                        >
                                        <span>80</span>
                                        <div>
                                            <strong>80 mm</strong>
                                            <small>Lebih lebar dan nyaman untuk struk yang panjang.</small>
                                        </div>
                                        <i class="fa-solid fa-circle-check"></i>
                                    </label>
                                </div>

                                @error('form.receipt_paper_size')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                                <div class="admin-receipt-setting-label is-alignment">
                                    <i class="fa-solid fa-arrows-left-right"></i>
                                    <div>
                                        <strong>Posisi isi struk</strong>
                                        <small>Kalibrasikan bila sisi kiri atau kanan terpotong.</small>
                                    </div>
                                </div>

                                <div class="admin-receipt-alignment-options">
                                    <label class="{{
                                        ($form['receipt_alignment'] ?? 'center') === 'left'
                                            ? 'is-selected'
                                            : ''
                                    }}">
                                        <input type="radio" wire:model.live="form.receipt_alignment" value="left">
                                        <span><i class="fa-solid fa-arrow-left"></i></span>
                                        <div>
                                            <strong>Menjorok ke kiri</strong>
                                            <small>Gunakan saat nominal sisi kanan sering terpotong oleh print head.</small>
                                        </div>
                                        <i class="fa-solid fa-circle-check"></i>
                                    </label>

                                    <label class="{{
                                        ($form['receipt_alignment'] ?? 'center') === 'center'
                                            ? 'is-selected'
                                            : ''
                                    }}">
                                        <input type="radio" wire:model.live="form.receipt_alignment" value="center">
                                        <span><i class="fa-solid fa-align-center"></i></span>
                                        <div>
                                            <strong>Posisi tengah</strong>
                                            <small>Default. Margin kiri dan kanan seimbang untuk printer yang normal.</small>
                                        </div>
                                        <i class="fa-solid fa-circle-check"></i>
                                    </label>

                                    <label class="{{
                                        ($form['receipt_alignment'] ?? 'center') === 'right'
                                            ? 'is-selected'
                                            : ''
                                    }}">
                                        <input type="radio" wire:model.live="form.receipt_alignment" value="right">
                                        <span><i class="fa-solid fa-arrow-right"></i></span>
                                        <div>
                                            <strong>Menjorok ke kanan</strong>
                                            <small>Gunakan saat area cetak bergeser ke kanan atau sisi kiri terpotong.</small>
                                        </div>
                                        <i class="fa-solid fa-circle-check"></i>
                                    </label>
                                </div>

                                @error('form.receipt_alignment')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                                <div class="admin-receipt-calibration-note">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span>
                                        Pegawai baru otomatis memakai kertas 58 mm
                                        dengan posisi tengah. Keduanya tetap bisa diganti
                                        satu kali dari halaman cetak.
                                    </span>
                                </div>
                            </section>
                        @endif

                        <label class="admin-active-option {{ $form['is_active'] ? 'is-selected' : '' }}">
                            <input
                                wire:model.live="form.is_active"
                                type="checkbox"
                            >
                            <span><i class="fa-solid fa-power-off"></i></span>
                            <div>
                                <strong>Akun aktif</strong>
                                <small>
                                    Pengguna dapat login setelah email terverifikasi.
                                </small>
                            </div>
                            <i class="fa-solid fa-circle-check"></i>
                        </label>
                    </div>

                    <footer class="admin-modal-footer">
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
                                Simpan Pengguna
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
