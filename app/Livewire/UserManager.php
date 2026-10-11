<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UserManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';
    public string $statusFilter = 'all';
    public string $verificationFilter = 'all';
    public string $twoFactorFilter = 'all';
    public bool $twoFactorFeatureEnabled = false;
    public int $perPage = 15;

    public bool $showModal = false;
    public ?int $editingId = null;
    public array $form = [];

    public function mount(): void
    {
        Gate::authorize('users.manage');
        $this->twoFactorFeatureEnabled = (bool) config(
            'pos-features.two_factor_enabled',
            false
        );
        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedVerificationFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTwoFactorFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, [15, 30, 50], true)) {
            $this->perPage = 15;
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->statusFilter = 'all';
        $this->verificationFilter = 'all';
        $this->twoFactorFilter = 'all';
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('users.manage');

        $this->editingId = null;
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize('users.manage');

        $user = User::query()->with('roles')->findOrFail($id);

        abort_if(
            $user->hasRole('owner')
                && ! auth()->user()->hasRole('owner'),
            403
        );

        $this->editingId = $user->id;
        $this->form = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'role' => $user->getRoleNames()->first()
                ?? $this->defaultRole(),
            'receipt_alignment' => $user
                ->preferredReceiptAlignment(),
            'receipt_paper_size' => $user
                ->preferredReceiptPaperSize(),
            'is_active' => (bool) $user->is_active,
        ];

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        Gate::authorize('users.manage');

        $this->form['name'] = trim(
            (string) ($this->form['name'] ?? '')
        );
        $this->form['email'] = mb_strtolower(trim(
            (string) ($this->form['email'] ?? '')
        ));

        $password = Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised();

        $passwordRules = $this->editingId
            ? ['nullable', $password]
            : ['required', $password];

        $allowedRoles = $this->allowedRoles()->pluck('name')->all();

        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'form.password' => $passwordRules,
            'form.role' => ['required', Rule::in($allowedRoles)],
            'form.receipt_alignment' => [
                'required',
                Rule::in([
                    User::RECEIPT_ALIGNMENT_LEFT,
                    User::RECEIPT_ALIGNMENT_CENTER,
                    User::RECEIPT_ALIGNMENT_RIGHT,
                ]),
            ],
            'form.receipt_paper_size' => [
                'required',
                'integer',
                Rule::in([
                    User::RECEIPT_PAPER_SIZE_58,
                    User::RECEIPT_PAPER_SIZE_80,
                ]),
            ],
            'form.is_active' => ['boolean'],
        ], [
            'form.email.required' => 'Email wajib diisi.',
            'form.email.email' => 'Format email tidak valid.',
            'form.email.unique' => 'Email sudah digunakan pengguna lain.',
            'form.password.required' => 'Password wajib diisi untuk pengguna baru.',
            'form.receipt_alignment.required' => 'Pilih posisi struk thermal.',
            'form.receipt_alignment.in' => 'Pilihan posisi struk thermal tidak valid.',
            'form.receipt_paper_size.required' => 'Pilih ukuran kertas struk default.',
            'form.receipt_paper_size.in' => 'Ukuran kertas struk harus 58 mm atau 80 mm.',
        ], [
            'form.name' => 'nama',
            'form.email' => 'email',
            'form.password' => 'password',
            'form.role' => 'role',
            'form.receipt_alignment' => 'posisi struk thermal',
            'form.receipt_paper_size' => 'ukuran kertas struk',
            'form.is_active' => 'status akun',
        ])['form'];

        $data['name'] = trim($data['name']);
        $data['email'] = mb_strtolower(trim($data['email']));

        $actor = auth()->user();
        $wasEditing = $this->editingId !== null;
        $verificationRequired = false;
        $savedUser = null;

        DB::transaction(function () use (
            $data,
            $actor,
            &$verificationRequired,
            &$savedUser
        ): void {
            $target = $this->editingId
                ? User::query()
                    ->with('roles')
                    ->whereKey($this->editingId)
                    ->lockForUpdate()
                    ->firstOrFail()
                : new User();

            abort_if(
                $target->exists
                    && $target->hasRole('owner')
                    && ! $actor->hasRole('owner'),
                403
            );

            if (
                $target->exists
                && (int) $target->id === (int) $actor->id
                && ! $data['is_active']
            ) {
                throw ValidationException::withMessages([
                    'form.is_active' => 'Kamu tidak dapat menonaktifkan akun sendiri.',
                ]);
            }

            if ($target->exists && $target->hasRole('owner')) {
                $activeOwners = User::query()
                    ->role('owner')
                    ->where('is_active', true)
                    ->count();

                $removingLastOwner = (
                    $data['role'] !== 'owner'
                    || ! $data['is_active']
                ) && $target->is_active && $activeOwners <= 1;

                if ($removingLastOwner) {
                    throw ValidationException::withMessages([
                        'form.role' => 'Minimal satu akun owner aktif harus tetap tersedia.',
                    ]);
                }
            }

            $wasNew = ! $target->exists;
            $emailChanged = $target->exists
                && mb_strtolower((string) $target->email) !== $data['email'];

            $verificationRequired = $wasNew || $emailChanged;

            $receiptAlignment = $actor->hasRole('owner')
                ? $data['receipt_alignment']
                : (
                    $target->exists
                        ? $target->preferredReceiptAlignment()
                        : User::RECEIPT_ALIGNMENT_CENTER
                );

            $receiptPaperSize = $actor->hasRole('owner')
                ? (int) $data['receipt_paper_size']
                : (
                    $target->exists
                        ? $target->preferredReceiptPaperSize()
                        : User::RECEIPT_PAPER_SIZE_58
                );

            $target->name = $data['name'];
            $target->email = $data['email'];
            $target->receipt_alignment = $receiptAlignment;
            $target->receipt_paper_size = $receiptPaperSize;
            $target->is_active = $data['is_active'];

            if ($verificationRequired) {
                $target->email_verified_at = null;
            }

            if ($data['password'] !== '') {
                $target->password = $data['password'];
                $target->password_changed_at = now();
            }

            $target->save();
            $target->syncRoles([$data['role']]);

            AuditService::log(
                'user.'.($this->editingId ? 'updated' : 'created'),
                $target,
                [
                    'role' => $data['role'],
                    'active' => $data['is_active'],
                    'receipt_alignment' => $receiptAlignment,
                    'receipt_paper_size' => $receiptPaperSize,
                    'verification_required' => $verificationRequired,
                    'password_changed' => $data['password'] !== '',
                ]
            );

            $savedUser = $target;
        }, 3);

        $mailWarning = false;

        if ($verificationRequired && $savedUser?->is_active) {
            try {
                $savedUser->sendEmailVerificationNotification();
            } catch (\Throwable $exception) {
                report($exception);
                $mailWarning = true;
            }
        }

        $this->showModal = false;
        $this->editingId = null;
        $this->resetForm();

        if ($mailWarning) {
            $this->dispatch(
                'alert',
                type: 'warning',
                title: 'Pengguna tersimpan',
                message: 'Data berhasil disimpan, tetapi email verifikasi belum terkirim. Periksa konfigurasi email aplikasi.'
            );
            return;
        }

        $this->dispatch(
            'toast',
            message: $wasEditing
                ? 'Data pengguna berhasil diperbarui.'
                : 'Pengguna berhasil dibuat.'
        );
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('users.manage');

        DB::transaction(function () use ($id): void {
            $user = User::query()
                ->with('roles')
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $actor = auth()->user();

            abort_if(
                $user->hasRole('owner')
                    && ! $actor->hasRole('owner'),
                403
            );

            if ((int) $user->id === (int) $actor->id) {
                throw ValidationException::withMessages([
                    'user' => 'Kamu tidak dapat mengubah status akun sendiri dari daftar pengguna.',
                ]);
            }

            if ($user->hasRole('owner') && $user->is_active) {
                $activeOwners = User::query()
                    ->role('owner')
                    ->where('is_active', true)
                    ->count();

                if ($activeOwners <= 1) {
                    throw ValidationException::withMessages([
                        'user' => 'Owner aktif terakhir tidak dapat dinonaktifkan.',
                    ]);
                }
            }

            $user->update([
                'is_active' => ! $user->is_active,
            ]);

            AuditService::log('user.status_updated', $user, [
                'is_active' => $user->is_active,
            ]);
        }, 3);

        $this->dispatch(
            'toast',
            message: 'Status akun berhasil diperbarui.'
        );
    }

    public function resendVerification(int $id): void
    {
        Gate::authorize('users.manage');

        $user = User::query()->findOrFail($id);

        if ($user->hasVerifiedEmail()) {
            $this->dispatch(
                'alert',
                type: 'info',
                title: 'Email sudah terverifikasi',
                message: 'Tidak perlu mengirim ulang tautan verifikasi.'
            );
            return;
        }

        if (! $user->is_active) {
            $this->dispatch(
                'alert',
                type: 'warning',
                title: 'Akun nonaktif',
                message: 'Aktifkan akun terlebih dahulu sebelum mengirim email verifikasi.'
            );
            return;
        }

        try {
            $user->sendEmailVerificationNotification();

            AuditService::log('user.verification_resent', $user);

            $this->dispatch(
                'toast',
                message: 'Email verifikasi berhasil dikirim ulang.'
            );
        } catch (\Throwable $exception) {
            report($exception);

            $this->dispatch(
                'alert',
                type: 'warning',
                title: 'Email belum terkirim',
                message: 'Periksa konfigurasi mail aplikasi lalu coba kembali.'
            );
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
        $this->resetForm();
    }

    public function render()
    {
        Gate::authorize('users.manage');

        $query = User::query()->with('roles');

        if ($this->search !== '') {
            $terms = preg_split('/\s+/', trim($this->search)) ?: [];

            foreach (array_filter($terms) as $term) {
                $like = '%'.$term.'%';

                $query->where(function (Builder $builder) use ($like): void {
                    $builder
                        ->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhereHas('roles', fn (Builder $role) => $role->where('name', 'like', $like));
                });
            }
        }

        $query
            ->when(
                $this->roleFilter !== '',
                fn (Builder $builder) => $builder->role($this->roleFilter)
            )
            ->when(
                $this->statusFilter === 'active',
                fn (Builder $builder) => $builder->where('is_active', true)
            )
            ->when(
                $this->statusFilter === 'inactive',
                fn (Builder $builder) => $builder->where('is_active', false)
            )
            ->when(
                $this->verificationFilter === 'verified',
                fn (Builder $builder) => $builder->whereNotNull('email_verified_at')
            )
            ->when(
                $this->verificationFilter === 'unverified',
                fn (Builder $builder) => $builder->whereNull('email_verified_at')
            )
            ->when(
                $this->twoFactorFeatureEnabled
                    && $this->twoFactorFilter === 'enabled',
                fn (Builder $builder) => $builder->whereNotNull('two_factor_confirmed_at')
            )
            ->when(
                $this->twoFactorFeatureEnabled
                    && $this->twoFactorFilter === 'disabled',
                fn (Builder $builder) => $builder->whereNull('two_factor_confirmed_at')
            );

        $users = $query
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(
                in_array($this->perPage, [15, 30, 50], true)
                    ? $this->perPage
                    : 15
            );

        $base = User::query();

        return view('livewire.user-manager', [
            'users' => $users,
            'roles' => $this->allowedRoles(),
            'allRoles' => Role::query()->orderBy('name')->get(),
            'summary' => [
                'total' => (clone $base)->count(),
                'active' => (clone $base)->where('is_active', true)->count(),
                'unverified' => (clone $base)->whereNull('email_verified_at')->count(),
                'two_factor' => $this->twoFactorFeatureEnabled
                    ? (clone $base)
                        ->whereNotNull('two_factor_confirmed_at')
                        ->count()
                    : 0,
            ],
        ]);
    }

    private function allowedRoles()
    {
        return Role::query()
            ->when(
                ! auth()->user()->hasRole('owner'),
                fn ($query) => $query->where('name', '!=', 'owner')
            )
            ->orderBy('name')
            ->get();
    }

    private function defaultRole(): string
    {
        $roles = $this->allowedRoles();

        return $roles->firstWhere('name', 'cashier')?->name
            ?: $roles->first()?->name
            ?: 'cashier';
    }

    private function resetForm(): void
    {
        $this->form = [
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => $this->defaultRole(),
            'receipt_alignment' => User::RECEIPT_ALIGNMENT_CENTER,
            'receipt_paper_size' => User::RECEIPT_PAPER_SIZE_58,
            'is_active' => true,
        ];

        $this->resetValidation();
    }
}
