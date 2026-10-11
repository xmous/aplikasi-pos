<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'sales.view', 'sales.create', 'sales.payment', 'sales.discount', 'sale_returns.create',
            'shifts.manage',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'categories.manage', 'units.manage', 'suppliers.manage', 'customers.manage', 'warehouses.manage',
            'purchases.view', 'purchases.create', 'purchases.payment', 'purchase_returns.create',
            'stock.view', 'stock.adjust',
            'opname.view', 'opname.create', 'opname.approve',
            'expenses.view', 'expenses.create', 'expenses.update', 'promotions.manage',
            'reports.sales', 'reports.stocks', 'reports.profit', 'reports.debts', 'reports.operations', 'reports.export',
            'users.manage', 'roles.manage', 'settings.manage', 'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $owner = Role::findOrCreate('owner', 'web');
        $admin = Role::findOrCreate('admin', 'web');
        $warehouse = Role::findOrCreate('warehouse', 'web');
        $cashier = Role::findOrCreate('cashier', 'web');

        $owner->syncPermissions($permissions);
        $admin->syncPermissions(array_values(array_diff($permissions, ['roles.manage'])));
        $warehouse->syncPermissions([
            'dashboard.view', 'products.view', 'products.create', 'products.update',
            'categories.manage', 'units.manage', 'suppliers.manage', 'customers.manage', 'warehouses.manage',
            'purchases.view', 'purchases.create', 'purchases.payment', 'purchase_returns.create',
            'stock.view', 'stock.adjust', 'opname.view', 'opname.create',
            'reports.stocks', 'reports.operations',
        ]);
        $cashier->syncPermissions([
            'dashboard.view', 'sales.view', 'sales.create', 'sales.payment', 'shifts.manage',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
