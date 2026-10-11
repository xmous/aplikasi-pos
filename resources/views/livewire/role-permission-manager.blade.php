<div class="admin-role-page">
    @php
        $actionLabels = [
            'view' => 'Lihat',
            'create' => 'Tambah',
            'update' => 'Ubah',
            'delete' => 'Hapus',
            'manage' => 'Kelola',
            'payment' => 'Pembayaran',
            'adjust' => 'Penyesuaian',
            'approve' => 'Persetujuan',
            'export' => 'Ekspor',
            'discount' => 'Diskon',
        ];
    @endphp

    <div class="ga-page-head admin-page-head">
        <div class="ga-page-title">
            <span class="ga-eyebrow">
                <i class="fa-solid fa-user-lock"></i>
                KONTROL HAK AKSES
            </span>
            <h2>Role & Permission</h2>
            <p class="mb-0">
                Susun hak akses berdasarkan tugas pengguna tanpa membuka
                menu atau tindakan yang tidak diperlukan.
            </p>
        </div>

        <button
            type="button"
            wire:click="create"
            class="btn btn-warning admin-primary-action"
        >
            <i class="fa-solid fa-plus"></i>
            Tambah Role
        </button>
    </div>

    @error('role')
        <div class="admin-inline-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ $message }}</span>
        </div>
    @enderror

    <div class="admin-stat-grid">
        <article>
            <span><i class="fa-solid fa-user-tag"></i></span>
            <div>
                <small>Total role</small>
                <strong>{{ number_format($summary['roles']) }}</strong>
            </div>
        </article>
        <article class="is-secure">
            <span><i class="fa-solid fa-key"></i></span>
            <div>
                <small>Permission tersedia</small>
                <strong>{{ number_format($summary['permissions']) }}</strong>
            </div>
        </article>
        <article class="is-active">
            <span><i class="fa-solid fa-users-gear"></i></span>
            <div>
                <small>Pengguna punya role</small>
                <strong>{{ number_format($summary['assigned_users']) }}</strong>
            </div>
        </article>
        <article class="is-warning">
            <span><i class="fa-solid fa-layer-group"></i></span>
            <div>
                <small>Role yang dapat dikelola</small>
                <strong>{{ number_format($summary['custom_roles']) }}</strong>
            </div>
        </article>
    </div>

    <section class="admin-role-toolbar">
        <div class="admin-search-field">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
                wire:model.live.debounce.350ms="search"
                type="search"
                class="form-control"
                placeholder="Cari role atau permission..."
                autocomplete="off"
            >
            <span wire:loading wire:target="search">
                <i class="fa-solid fa-spinner fa-spin"></i>
            </span>
        </div>

        <span>
            <i class="fa-solid fa-shield-halved"></i>
            Role <b>Owner</b> dikunci oleh sistem.
        </span>
    </section>

    <div class="admin-dashboard-permission-note">
        <span><i class="fa-solid fa-table-columns"></i></span>
        <div>
            <strong>Dashboard mengikuti permission modul</strong>
            <small>
                Role harus memiliki <code>dashboard.view</code>. Panel di
                dashboard kemudian muncul otomatis berdasarkan permission
                Penjualan, Stok, Laporan, Pengguna, dan modul lainnya.
            </small>
        </div>
    </div>

    <div class="admin-role-grid">
        @forelse($roles as $role)
            @php
                $isOwner = $role->name === 'owner';
                $shownPermissions = $role->permissions->take(10);
            @endphp

            <article class="admin-role-card {{ $isOwner ? 'is-owner' : '' }}" wire:key="role-card-{{ $role->id }}">
                <header>
                    <span>
                        <i class="fa-solid {{ $isOwner ? 'fa-crown' : 'fa-user-shield' }}"></i>
                    </span>
                    <div>
                        <strong>
                            {{ str($role->name)->replace('_', ' ')->title() }}
                        </strong>
                        <small>
                            {{ number_format($role->users_count) }} pengguna
                            · {{ number_format($role->permissions_count) }} permission
                        </small>
                    </div>

                    @if($isOwner)
                        <em><i class="fa-solid fa-lock"></i> Sistem</em>
                    @endif
                </header>

                <div class="admin-role-permissions">
                    @forelse($shownPermissions as $permission)
                        @php
                            $group = str($permission->name)->before('.')->toString();
                            $action = str($permission->name)->after('.')->toString();
                        @endphp
                        <span title="{{ $permission->name }}">
                            {{ $groupLabels[$group] ?? str($group)->replace('_', ' ')->title() }}:
                            <b>{{ $actionLabels[$action] ?? str($action)->replace('_', ' ')->title() }}</b>
                        </span>
                    @empty
                        <div class="admin-role-empty-permission">
                            Belum ada permission.
                        </div>
                    @endforelse

                    @if($role->permissions_count > 10)
                        <span class="is-more">
                            +{{ number_format($role->permissions_count - 10) }} lainnya
                        </span>
                    @endif
                </div>

                <div class="admin-role-dashboard-count">
                    <i class="fa-solid fa-table-columns"></i>
                    <span>
                        Dashboard:
                        <b>
                            {{ number_format(
                                $role->dashboard_widget_count
                            ) }}
                            panel
                        </b>
                    </span>
                </div>

                <footer>
                    @if($isOwner)
                        <span>
                            Owner selalu mempunyai akses penuh dan tidak dapat diubah.
                        </span>
                    @else
                        <button
                            type="button"
                            wire:click="edit({{ $role->id }})"
                            wire:loading.attr="disabled"
                            wire:target="edit({{ $role->id }})"
                            class="btn btn-sm btn-outline-primary"
                        >
                            <i class="fa-solid fa-pen-to-square"></i>
                            Edit
                        </button>

                        @if($role->users_count === 0)
                            <button
                                type="button"
                                data-livewire-confirm="Hapus role {{ $role->name }}? Role ini belum digunakan oleh pengguna."
                                data-livewire-action="delete"
                                data-livewire-id="{{ $role->id }}"
                                class="btn btn-sm btn-outline-danger"
                            >
                                <i class="fa-solid fa-trash"></i>
                                Hapus
                            </button>
                        @else
                            <span class="admin-role-in-use">
                                <i class="fa-solid fa-users"></i>
                                Sedang digunakan
                            </span>
                        @endif
                    @endif
                </footer>
            </article>
        @empty
            <div class="admin-empty-state admin-role-empty">
                <i class="fa-solid fa-user-lock"></i>
                <strong>Role tidak ditemukan</strong>
                <span>Ubah kata pencarian atau buat role baru.</span>
            </div>
        @endforelse
    </div>

    @if($showModal)
        <div class="pos-modal" wire:keydown.escape.window="closeModal">
            <div
                class="pos-modal-dialog admin-role-modal"
                wire:click.stop
                role="dialog"
                aria-modal="true"
            >
                <form wire:submit="save">
                    <header class="admin-modal-header">
                        <div>
                            <span><i class="fa-solid fa-user-lock"></i></span>
                            <div>
                                <h5 class="mb-0">
                                    {{ $editingId ? 'Edit Role' : 'Tambah Role' }}
                                </h5>
                                <p class="mb-0">
                                    Pilih hanya permission yang dibutuhkan oleh tugas role.
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

                    <div class="admin-modal-body admin-role-modal-body">
                        <section class="admin-form-section">
                            <div class="admin-section-title">
                                <span><i class="fa-solid fa-id-badge"></i></span>
                                <div>
                                    <strong>Identitas Role</strong>
                                    <small>
                                        Gunakan nama singkat tanpa spasi, misalnya supervisor_toko.
                                    </small>
                                </div>
                            </div>

                            <label class="form-label">Nama Role <b>*</b></label>
                            <input
                                wire:model="name"
                                class="form-control"
                                maxlength="50"
                                autocomplete="off"
                                placeholder="supervisor_toko"
                            >
                            @error('name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </section>

                        @php
                            $dashboardBaseEnabled = in_array(
                                'dashboard.view',
                                $selected,
                                true
                            );
                        @endphp

                        <section class="admin-dashboard-preview-section">
                            <header>
                                <div>
                                    <span>
                                        <i class="fa-solid fa-table-columns"></i>
                                    </span>
                                    <div>
                                        <strong>Pratinjau Dashboard Role</strong>
                                        <small>
                                            Panel aktif mengikuti permission
                                            modul yang dipilih di bawah.
                                        </small>
                                    </div>
                                </div>

                                <em class="{{ $dashboardBaseEnabled ? 'is-enabled' : 'is-disabled' }}">
                                    <i class="fa-solid {{ $dashboardBaseEnabled ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                                    {{ $dashboardBaseEnabled
                                        ? 'Dashboard dapat dibuka'
                                        : 'dashboard.view belum dipilih' }}
                                </em>
                            </header>

                            <div class="admin-dashboard-preview-grid">
                                @foreach($dashboardWidgets as $widgetKey => $widget)
                                    @php
                                        $widgetVisible = $dashboardBaseEnabled
                                            && \App\Support\DashboardAccess::allowsPermissions(
                                                $selected,
                                                $widgetKey
                                            );
                                    @endphp

                                    <article class="{{ $widgetVisible ? 'is-visible' : 'is-hidden' }}">
                                        <span>
                                            <i class="fa-solid {{ $widget['icon'] }}"></i>
                                        </span>
                                        <div>
                                            <strong>{{ $widget['label'] }}</strong>
                                            <small>{{ $widget['description'] }}</small>
                                            <code>
                                                {{ implode(
                                                    ' / ',
                                                    $widget['permissions']
                                                ) }}
                                            </code>
                                        </div>
                                        <i class="fa-solid {{ $widgetVisible ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                                    </article>
                                @endforeach
                            </div>

                            <p>
                                <i class="fa-solid fa-circle-info"></i>
                                Pratinjau ini tidak memberi akses baru.
                                Centang permission modul pada daftar berikut
                                untuk menampilkan panel sekaligus membuka
                                modul terkait.
                            </p>
                        </section>

                        <section class="admin-permission-section">
                            <header>
                                <div>
                                    <strong>Daftar Permission</strong>
                                    <small>
                                        {{ number_format(count($selected)) }} permission dipilih
                                    </small>
                                </div>
                                <div>
                                    <button
                                        type="button"
                                        wire:click="selectAll"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        <i class="fa-solid fa-check-double"></i>
                                        Pilih Semua
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="clearPermissions"
                                        class="btn btn-sm btn-light"
                                    >
                                        <i class="fa-solid fa-eraser"></i>
                                        Kosongkan
                                    </button>
                                </div>
                            </header>

                            @error('selected')
                                <small class="text-danger d-block mb-2">{{ $message }}</small>
                            @enderror

                            <div class="admin-permission-grid">
                                @foreach($permissionGroups as $group => $permissions)
                                    @php
                                        $groupPermissionNames = $permissions->pluck('name')->all();
                                        $selectedInGroup = count(array_intersect($selected, $groupPermissionNames));
                                        $allSelected = $selectedInGroup === count($groupPermissionNames);
                                    @endphp

                                    <article class="admin-permission-group {{ $allSelected ? 'is-complete' : '' }}">
                                        <header>
                                            <div>
                                                <span>
                                                    <i class="fa-solid fa-folder-tree"></i>
                                                </span>
                                                <div>
                                                    <strong>
                                                        {{ $groupLabels[$group] ?? str($group)->replace('_', ' ')->title() }}
                                                    </strong>
                                                    <small>
                                                        {{ $selectedInGroup }}/{{ $permissions->count() }} dipilih
                                                    </small>
                                                </div>
                                            </div>

                                            <button
                                                type="button"
                                                wire:click="toggleGroup('{{ $group }}')"
                                            >
                                                {{ $allSelected ? 'Kosongkan' : 'Pilih semua' }}
                                            </button>
                                        </header>

                                        <div>
                                            @foreach($permissions as $permission)
                                                @php
                                                    $action = str($permission->name)->after('.')->toString();
                                                @endphp
                                                <label>
                                                    <input
                                                        wire:model="selected"
                                                        value="{{ $permission->name }}"
                                                        type="checkbox"
                                                        class="form-check-input"
                                                    >
                                                    <span>
                                                        <strong>
                                                            {{ $actionLabels[$action] ?? str($action)->replace('_', ' ')->title() }}
                                                        </strong>
                                                        <small>{{ $permission->name }}</small>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
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
                                Simpan Role
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
