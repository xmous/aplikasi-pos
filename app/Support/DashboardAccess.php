<?php

namespace App\Support;

use App\Models\User;

class DashboardAccess
{
    public static function definitions(): array
    {
        return [
            'cashier' => [
                'label' => 'Kasir & Shift',
                'description' => 'Status shift pribadi, kas sistem, dan tombol menuju kasir.',
                'icon' => 'fa-cash-register',
                'permissions' => ['sales.create', 'shifts.manage'],
            ],
            'sales' => [
                'label' => 'Ringkasan Penjualan',
                'description' => 'Omzet, tren, dan transaksi terbaru. Data seluruh kasir memerlukan permission reports.sales.',
                'icon' => 'fa-chart-column',
                'permissions' => ['sales.view', 'sales.create', 'reports.sales'],
            ],
            'profit' => [
                'label' => 'Laba Hari Ini',
                'description' => 'Laba kotor, pengeluaran, laba bersih, dan margin.',
                'icon' => 'fa-chart-line',
                'permissions' => ['reports.profit'],
            ],
            'stock' => [
                'label' => 'Persediaan',
                'description' => 'Nilai stok, stok habis/menipis, gudang, dan kedaluwarsa.',
                'icon' => 'fa-boxes-stacked',
                'permissions' => ['stock.view', 'reports.stocks'],
            ],
            'purchases' => [
                'label' => 'Pembelian',
                'description' => 'Pembelian hari ini, retur, dan transaksi pembelian terbaru.',
                'icon' => 'fa-cart-shopping',
                'permissions' => ['purchases.view'],
            ],
            'debts' => [
                'label' => 'Utang & Piutang',
                'description' => 'Saldo utang/piutang serta tagihan yang terlambat.',
                'icon' => 'fa-file-invoice-dollar',
                'permissions' => [
                    'reports.debts',
                    'sales.payment',
                    'purchases.payment',
                ],
            ],
            'expenses' => [
                'label' => 'Pengeluaran',
                'description' => 'Biaya hari ini, bulan berjalan, dan kategori terbesar.',
                'icon' => 'fa-money-bill-transfer',
                'permissions' => ['expenses.view', 'reports.operations'],
            ],
            'promotions' => [
                'label' => 'Promosi',
                'description' => 'Promo aktif dan promo yang akan segera dimulai.',
                'icon' => 'fa-tags',
                'permissions' => ['promotions.manage'],
            ],
            'opname' => [
                'label' => 'Stok Opname',
                'description' => 'Draft dan opname yang menunggu persetujuan.',
                'icon' => 'fa-clipboard-check',
                'permissions' => ['opname.view'],
            ],
            'users' => [
                'label' => 'Pengguna',
                'description' => config(
                    'pos-features.two_factor_enabled',
                    false
                )
                    ? 'Jumlah akun aktif, belum terverifikasi, dan 2FA.'
                    : 'Jumlah akun aktif, nonaktif, dan belum terverifikasi.',
                'icon' => 'fa-users-gear',
                'permissions' => ['users.manage'],
            ],
            'activity' => [
                'label' => 'Aktivitas Sistem',
                'description' => 'Aktivitas terbaru pengguna dan proses otomatis.',
                'icon' => 'fa-clock-rotate-left',
                'permissions' => ['audit.view'],
            ],
        ];
    }

    public static function forUser(User $user): array
    {
        $hasDashboard = $user->can('dashboard.view');

        return collect(self::definitions())
            ->mapWithKeys(function (array $definition, string $key) use (
                $user,
                $hasDashboard
            ): array {
                $allowed = $hasDashboard
                    && collect($definition['permissions'])
                        ->contains(
                            fn (string $permission): bool => $user->can(
                                $permission
                            )
                        );

                return [$key => $allowed];
            })
            ->all();
    }

    public static function forPermissionNames(
        array $permissions,
        bool $owner = false
    ): array {
        if ($owner) {
            return collect(self::definitions())
                ->mapWithKeys(fn (array $definition, string $key): array => [
                    $key => true,
                ])
                ->all();
        }

        if (! in_array('dashboard.view', $permissions, true)) {
            return collect(self::definitions())
                ->mapWithKeys(fn (array $definition, string $key): array => [
                    $key => false,
                ])
                ->all();
        }

        return collect(self::definitions())
            ->mapWithKeys(function (array $definition, string $key) use (
                $permissions
            ): array {
                return [
                    $key => count(array_intersect(
                        $definition['permissions'],
                        $permissions
                    )) > 0,
                ];
            })
            ->all();
    }

    public static function allowsPermissions(
        array $permissions,
        string $widget
    ): bool {
        return (bool) (
            self::forPermissionNames($permissions)[$widget]
            ?? false
        );
    }
}
