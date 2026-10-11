<?php

namespace App\Livewire;

use App\Services\AuditService;
use App\Support\DashboardAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionManager extends Component
{
    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $name = '';
    public array $selected = [];

    public function mount(): void
    {
        Gate::authorize('roles.manage');
    }

    public function create(): void
    {
        Gate::authorize('roles.manage');

        $this->editingId = null;
        $this->name = '';
        $this->selected = [];
        $this->showModal = true;
        $this->resetValidation();
    }

    public function edit(int $id): void
    {
        Gate::authorize('roles.manage');

        $role = Role::query()
            ->with('permissions')
            ->findOrFail($id);

        abort_if(
            $role->name === 'owner',
            403,
            'Role owner tidak dapat diubah dari antarmuka.'
        );

        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->selected = $role->permissions
            ->pluck('name')
            ->all();
        $this->showModal = true;
        $this->resetValidation();
    }

    public function save(): void
    {
        Gate::authorize('roles.manage');

        $data = $this->validate([
            'name' => [
                'required',
                'alpha_dash',
                'max:50',
                'not_in:owner',
                Rule::unique('roles', 'name')->ignore($this->editingId),
            ],
            'selected' => ['array'],
            'selected.*' => [
                'string',
                'distinct',
                Rule::exists('permissions', 'name')->where(
                    fn ($query) => $query->where('guard_name', 'web')
                ),
            ],
        ], [
            'name.alpha_dash' => 'Nama role hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'name.not_in' => 'Nama owner dicadangkan oleh sistem.',
        ], [
            'name' => 'nama role',
            'selected' => 'permission',
        ]);

        $data['name'] = mb_strtolower(trim($data['name']));
        $wasEditing = $this->editingId !== null;

        DB::transaction(function () use ($data): void {
            $role = $this->editingId
                ? Role::query()
                    ->whereKey($this->editingId)
                    ->lockForUpdate()
                    ->firstOrFail()
                : new Role([
                    'guard_name' => 'web',
                ]);

            abort_if($role->exists && $role->name === 'owner', 403);

            $oldName = $role->name;
            $role->name = $data['name'];
            $role->guard_name = 'web';
            $role->save();
            $role->syncPermissions($data['selected']);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            AuditService::log(
                'role.'.($this->editingId ? 'updated' : 'created'),
                null,
                [
                    'role_id' => $role->id,
                    'old_name' => $oldName,
                    'role' => $role->name,
                    'permissions' => $data['selected'],
                ]
            );
        }, 3);

        $this->showModal = false;
        $this->editingId = null;
        $this->name = '';
        $this->selected = [];

        $this->dispatch(
            'toast',
            message: $wasEditing
                ? 'Role dan permission berhasil diperbarui.'
                : 'Role baru berhasil dibuat.'
        );
    }

    public function toggleGroup(string $group): void
    {
        Gate::authorize('roles.manage');

        $groupPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'like', $group.'.%')
            ->pluck('name')
            ->all();

        $allSelected = collect($groupPermissions)
            ->every(fn (string $permission): bool => in_array(
                $permission,
                $this->selected,
                true
            ));

        if ($allSelected) {
            $this->selected = array_values(array_diff(
                $this->selected,
                $groupPermissions
            ));
            return;
        }

        $this->selected = array_values(array_unique(array_merge(
            $this->selected,
            $groupPermissions
        )));
    }

    public function selectAll(): void
    {
        Gate::authorize('roles.manage');

        $this->selected = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    public function clearPermissions(): void
    {
        Gate::authorize('roles.manage');
        $this->selected = [];
    }

    public function delete(int $id): void
    {
        Gate::authorize('roles.manage');

        DB::transaction(function () use ($id): void {
            $role = Role::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $usersCount = $role->users()->count();

            if ($role->name === 'owner') {
                throw ValidationException::withMessages([
                    'role' => 'Role owner tidak dapat dihapus.',
                ]);
            }

            if ($usersCount > 0) {
                throw ValidationException::withMessages([
                    'role' => 'Role masih digunakan oleh '.$usersCount.' pengguna. Pindahkan role pengguna terlebih dahulu.',
                ]);
            }

            $roleName = $role->name;
            $role->syncPermissions([]);
            $role->delete();

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            AuditService::log('role.deleted', null, [
                'role_id' => $id,
                'role' => $roleName,
            ]);
        }, 3);

        $this->dispatch(
            'toast',
            message: 'Role yang tidak digunakan berhasil dihapus.'
        );
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
        $this->name = '';
        $this->selected = [];
        $this->resetValidation();
    }

    public function render()
    {
        Gate::authorize('roles.manage');

        $permissionQuery = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name');

        $permissions = $permissionQuery->get()->groupBy(
            fn (Permission $permission): string => str($permission->name)
                ->before('.')
                ->toString()
        );

        $roles = Role::query()
            ->with('permissions')
            ->withCount(['permissions', 'users'])
            ->when(
                $this->search !== '',
                function (Builder $query): void {
                    $like = '%'.trim($this->search).'%';
                    $query->where(function (Builder $builder) use ($like): void {
                        $builder
                            ->where('name', 'like', $like)
                            ->orWhereHas('permissions', fn (Builder $permission) => $permission->where('name', 'like', $like));
                    });
                }
            )
            ->orderByRaw("CASE WHEN name = 'owner' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        $roles->each(function (Role $role): void {
            $permissionNames = $role->permissions
                ->pluck('name')
                ->all();

            $access = DashboardAccess::forPermissionNames(
                $permissionNames,
                $role->name === 'owner'
            );

            $role->setAttribute(
                'dashboard_widget_count',
                collect($access)->filter()->count()
            );
        });

        return view('livewire.role-permission-manager', [
            'roles' => $roles,
            'permissionGroups' => $permissions,
            'groupLabels' => $this->groupLabels(),
            'dashboardWidgets' => DashboardAccess::definitions(),
            'summary' => [
                'roles' => Role::query()->count(),
                'permissions' => Permission::query()
                    ->where('guard_name', 'web')
                    ->count(),
                'assigned_users' => DB::table('model_has_roles')
                    ->where('model_type', \App\Models\User::class)
                    ->distinct('model_id')
                    ->count('model_id'),
                'custom_roles' => Role::query()
                    ->where('name', '!=', 'owner')
                    ->count(),
            ],
        ]);
    }

    private function groupLabels(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'sales' => 'Penjualan & Kasir',
            'shifts' => 'Shift Kasir',
            'products' => 'Barang',
            'categories' => 'Kategori',
            'units' => 'Satuan',
            'suppliers' => 'Supplier',
            'customers' => 'Pelanggan',
            'warehouses' => 'Gudang',
            'purchases' => 'Pembelian',
            'stock' => 'Persediaan',
            'opname' => 'Stok Opname',
            'purchase_returns' => 'Retur Pembelian',
            'sale_returns' => 'Retur Penjualan',
            'expenses' => 'Pengeluaran',
            'promotions' => 'Promosi',
            'reports' => 'Laporan',
            'users' => 'Pengguna',
            'roles' => 'Role & Permission',
            'settings' => 'Pengaturan',
            'audit' => 'Audit Log',
        ];
    }
}
