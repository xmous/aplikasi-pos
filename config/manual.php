<?php

$manual = [
    'title' => 'Panduan Pengguna POS & Stok',
    'categories' => [
        'Owner',
        'Mulai',
        'Kasir',
        'Master',
        'Pembelian',
        'Persediaan',
        'Operasional',
        'Laporan',
        'Sistem',
        'Akun',
        'Bantuan',
    ],
    'sections' => [
        [
            'slug' => 'setup-awal-owner',
            'category' => 'Owner',
            'title' => 'Checklist Owner: Persiapan Pertama Kali',
            'badge' => 'MULAI DARI SINI',
            'icon' => 'fa-list-check',
            'color' => 'gold',
            'roles' => [
                'owner',
            ],
            'permissions' => [],
            'route' => 'settings.edit',
            'route_label' => 'Buka Pengaturan POS',
            'summary' => 'Urutan persiapan dari instalasi kosong sampai toko siap menerima transaksi sungguhan.',
            'prerequisites' => [
                [
                    'text' => 'Login menggunakan akun dengan role owner.',
                    'actions' => [
                        [
                            'route' => 'account.security',
                            'label' => 'Buka Keamanan Akun',
                            'icon' => 'fa-shield-halved',
                            'tone' => 'info',
                        ],
                    ],
                ],
                [
                    'text' => 'Database dan aplikasi sudah berjalan tanpa error.',
                    'actions' => [
                        [
                            'manual_slug' => 'troubleshooting',
                            'label' => 'Buka Troubleshooting',
                            'icon' => 'fa-screwdriver-wrench',
                            'tone' => 'secondary',
                        ],
                    ],
                ],
                [
                    'text' => 'Siapkan data toko, petugas, kategori, satuan, gudang, supplier, barang, harga, serta stok awal.',
                    'actions' => [
                        [
                            'route' => 'users.index',
                            'label' => 'Buka Pengguna',
                            'icon' => 'fa-users',
                            'tone' => 'primary',
                            'permissions' => [
                                'users.manage',
                            ],
                        ],
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Kategori',
                            'icon' => 'fa-tags',
                            'tone' => 'warning',
                            'route_params' => [
                                'type' => 'categories',
                            ],
                            'permissions' => [
                                'categories.manage',
                            ],
                        ],
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Satuan',
                            'icon' => 'fa-scale-balanced',
                            'tone' => 'info',
                            'route_params' => [
                                'type' => 'units',
                            ],
                            'permissions' => [
                                'units.manage',
                            ],
                        ],
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Gudang',
                            'icon' => 'fa-warehouse',
                            'tone' => 'primary',
                            'route_params' => [
                                'type' => 'warehouses',
                            ],
                            'permissions' => [
                                'warehouses.manage',
                            ],
                        ],
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Supplier',
                            'icon' => 'fa-truck',
                            'tone' => 'info',
                            'route_params' => [
                                'type' => 'suppliers',
                            ],
                            'permissions' => [
                                'suppliers.manage',
                            ],
                        ],
                        [
                            'route' => 'products.index',
                            'label' => 'Buka Barang & Konversi',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'purple',
                            'permissions' => [
                                'products.view',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Gunakan data percobaan dahulu sebelum memasukkan transaksi operasional sebenarnya.',
                    'actions' => [
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                        [
                            'route' => 'purchases.create',
                            'label' => 'Tambah Pembelian',
                            'icon' => 'fa-cart-plus',
                            'tone' => 'success',
                            'permissions' => [
                                'purchases.create',
                            ],
                        ],
                    ],
                ],
            ],
            'steps' => [
                [
                    'text' => 'Amankan akun owner. Buka Profil, Password & 2FA, ganti password awal, aktifkan autentikasi dua faktor, dan simpan recovery code di tempat aman.',
                    'actions' => [
                        [
                            'route' => 'account.security',
                            'label' => 'Ganti Password',
                            'icon' => 'fa-key',
                            'tone' => 'warning',
                            'fragment' => 'account-password',
                        ],
                        [
                            'route' => 'account.security',
                            'label' => 'Aktifkan 2FA',
                            'icon' => 'fa-mobile-screen-button',
                            'tone' => 'info',
                            'fragment' => 'account-2fa',
                        ],
                    ],
                ],
                [
                    'text' => 'Rancang role petugas. Buka Role & Permission, lalu siapkan role sesuai kebutuhan seperti admin, kasir, gudang, pembelian, supervisor, atau auditor. Berikan permission minimum sesuai tugas.',
                    'actions' => [
                        [
                            'route' => 'roles.index',
                            'label' => 'Buka Role & Permission',
                            'icon' => 'fa-user-shield',
                            'tone' => 'purple',
                            'permissions' => [
                                'roles.manage',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Buat akun petugas. Buka Pengguna, tambahkan akun satu per satu, pilih role, aktifkan akun, lalu minta setiap petugas mengganti password awal.',
                    'actions' => [
                        [
                            'route' => 'users.index',
                            'label' => 'Buka Pengguna',
                            'icon' => 'fa-users',
                            'tone' => 'primary',
                            'permissions' => [
                                'users.manage',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Lengkapi Pengaturan POS. Isi nama toko, alamat, telepon, email, logo, slogan, teks login, footer struk, ukuran font, tema, dan mode tampilan.',
                    'actions' => [
                        [
                            'route' => 'settings.edit',
                            'label' => 'Identitas Toko',
                            'icon' => 'fa-store',
                            'tone' => 'primary',
                            'permissions' => [
                                'settings.manage',
                            ],
                            'fragment' => 'settings-identitas',
                        ],
                        [
                            'route' => 'settings.edit',
                            'label' => 'Teks Aplikasi',
                            'icon' => 'fa-font',
                            'tone' => 'purple',
                            'permissions' => [
                                'settings.manage',
                            ],
                            'fragment' => 'settings-teks',
                        ],
                        [
                            'route' => 'settings.edit',
                            'label' => 'Halaman Login',
                            'icon' => 'fa-right-to-bracket',
                            'tone' => 'info',
                            'permissions' => [
                                'settings.manage',
                            ],
                            'fragment' => 'settings-login',
                        ],
                        [
                            'route' => 'settings.edit',
                            'label' => 'Tampilan',
                            'icon' => 'fa-display',
                            'tone' => 'primary',
                            'permissions' => [
                                'settings.manage',
                            ],
                            'fragment' => 'settings-tampilan',
                        ],
                        [
                            'route' => 'settings.edit',
                            'label' => 'Tema',
                            'icon' => 'fa-swatchbook',
                            'tone' => 'purple',
                            'permissions' => [
                                'settings.manage',
                            ],
                            'fragment' => 'settings-tema',
                        ],
                        [
                            'route' => 'settings.edit',
                            'label' => 'Struk',
                            'icon' => 'fa-receipt',
                            'tone' => 'secondary',
                            'permissions' => [
                                'settings.manage',
                            ],
                            'fragment' => 'settings-struk',
                        ],
                    ],
                ],
                [
                    'text' => 'Tentukan kebijakan operasional. Atur peringatan kedaluwarsa, stok minus, poin pelanggan, nilai perolehan poin, nilai tukar poin, dan batas maksimal penukaran.',
                    'actions' => [
                        [
                            'route' => 'settings.edit',
                            'label' => 'Poin Pelanggan',
                            'icon' => 'fa-coins',
                            'tone' => 'purple',
                            'permissions' => [
                                'settings.manage',
                            ],
                            'fragment' => 'settings-poin',
                        ],
                        [
                            'route' => 'settings.edit',
                            'label' => 'Kebijakan Persediaan',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'warning',
                            'permissions' => [
                                'settings.manage',
                            ],
                            'fragment' => 'settings-persediaan',
                        ],
                    ],
                ],
                [
                    'text' => 'Buat kategori barang terlebih dahulu, misalnya Frozen Food, Minuman, Sembako, Perawatan, atau Umum. Gunakan nama dan kode yang konsisten.',
                    'actions' => [
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Kategori',
                            'icon' => 'fa-tags',
                            'tone' => 'warning',
                            'route_params' => [
                                'type' => 'categories',
                            ],
                            'permissions' => [
                                'categories.manage',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Buat satuan dasar dan satuan penjualan, misalnya pcs, pack, dus, botol, kilogram, atau liter. Gunakan satuan terkecil sebagai satuan dasar stok.',
                    'actions' => [
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Satuan',
                            'icon' => 'fa-scale-balanced',
                            'tone' => 'info',
                            'route_params' => [
                                'type' => 'units',
                            ],
                            'permissions' => [
                                'units.manage',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Buat gudang atau lokasi penyimpanan. Minimal siapkan Gudang Utama. Tambahkan gudang lain hanya bila stok benar-benar dipisahkan secara fisik.',
                    'actions' => [
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Gudang',
                            'icon' => 'fa-warehouse',
                            'tone' => 'primary',
                            'route_params' => [
                                'type' => 'warehouses',
                            ],
                            'permissions' => [
                                'warehouses.manage',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Tambahkan supplier. Isi nama, kode, telepon, alamat, termin, dan limit bila data tersebut digunakan untuk pembelian atau utang.',
                    'actions' => [
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Supplier',
                            'icon' => 'fa-truck',
                            'tone' => 'info',
                            'route_params' => [
                                'type' => 'suppliers',
                            ],
                            'permissions' => [
                                'suppliers.manage',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Tambahkan pelanggan/member bila diperlukan. Isi nama sebenarnya agar tampil jelas pada transaksi dan struk. Nomor telepon tidak dicetak pada struk.',
                    'actions' => [
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Pelanggan',
                            'icon' => 'fa-users',
                            'tone' => 'success',
                            'route_params' => [
                                'type' => 'customers',
                            ],
                            'permissions' => [
                                'customers.manage',
                                'sales.create',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Tambahkan barang dan konversi satuan. Isi SKU, barcode, kategori, satuan dasar, harga beli, harga jual, stok minimum, rak, status stok, serta kedaluwarsa. Gunakan Import Excel bila jumlah barang banyak.',
                    'actions' => [
                        [
                            'route' => 'products.index',
                            'label' => 'Buka Barang & Konversi',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'purple',
                            'permissions' => [
                                'products.view',
                            ],
                        ],
                        [
                            'route' => 'products.import-template',
                            'label' => 'Unduh Template Excel',
                            'icon' => 'fa-file-excel',
                            'tone' => 'success',
                            'permissions' => [
                                'products.create',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Masukkan stok awal hanya satu kali. Gunakan Penyesuaian Stok dengan alasan Stok Awal untuk persediaan yang sudah ada sebelum aplikasi digunakan. Isi batch dan tanggal kedaluwarsa bila diwajibkan.',
                    'actions' => [
                        [
                            'route' => 'stocks.adjustment',
                            'label' => 'Buka Penyesuaian Stok',
                            'icon' => 'fa-sliders',
                            'tone' => 'warning',
                            'permissions' => [
                                'stock.adjust',
                            ],
                        ],
                        [
                            'route' => 'stocks.batches',
                            'label' => 'Buka Batch & Kedaluwarsa',
                            'icon' => 'fa-calendar-xmark',
                            'tone' => 'warning',
                            'permissions' => [
                                'stock.view',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Catat pembelian pertama untuk barang yang benar-benar baru diterima setelah aplikasi digunakan. Jangan memasukkan stok yang sama melalui Stok Awal dan Pembelian karena stok akan terhitung dua kali.',
                    'actions' => [
                        [
                            'route' => 'purchases.create',
                            'label' => 'Tambah Pembelian',
                            'icon' => 'fa-cart-plus',
                            'tone' => 'success',
                            'permissions' => [
                                'purchases.create',
                            ],
                        ],
                        [
                            'route' => 'purchases.index',
                            'label' => 'Buka Pembelian',
                            'icon' => 'fa-cart-shopping',
                            'tone' => 'primary',
                            'permissions' => [
                                'purchases.view',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Buat promo bila diperlukan, lalu periksa periode, jenis diskon, barang yang berlaku, minimum belanja, dan status aktif.',
                    'actions' => [
                        [
                            'route' => 'promotions.index',
                            'label' => 'Buka Promo',
                            'icon' => 'fa-percent',
                            'tone' => 'purple',
                            'permissions' => [
                                'promotions.manage',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Uji alur kasir. Buka shift dengan saldo awal percobaan, lakukan penjualan tunai dan non-tunai, pilih member, gunakan promo/poin, lalu periksa total dan kembalian.',
                    'actions' => [
                        [
                            'route' => 'shifts.index',
                            'label' => 'Buka Shift Kasir',
                            'icon' => 'fa-clock',
                            'tone' => 'warning',
                            'permissions' => [
                                'shifts.manage',
                            ],
                        ],
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Uji cetak struk. Buka detail penjualan, pilih kertas 58 mm atau 80 mm, periksa nama toko, nama member, barang, total, pembayaran, poin, dan footer struk.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'manual_slug' => 'struk',
                            'label' => 'Buka Panduan Struk',
                            'icon' => 'fa-receipt',
                            'tone' => 'secondary',
                        ],
                    ],
                ],
                [
                    'text' => 'Uji koreksi transaksi. Coba retur penjualan, retur pembelian, penyesuaian stok, dan stok opname menggunakan data percobaan.',
                    'actions' => [
                        [
                            'route' => 'sale-returns.index',
                            'label' => 'Buka Retur Penjualan',
                            'icon' => 'fa-rotate-left',
                            'tone' => 'danger',
                            'permissions' => [
                                'sale_returns.create',
                            ],
                        ],
                        [
                            'route' => 'purchase-returns.index',
                            'label' => 'Buka Retur Pembelian',
                            'icon' => 'fa-rotate-left',
                            'tone' => 'danger',
                            'permissions' => [
                                'purchase_returns.create',
                            ],
                        ],
                        [
                            'route' => 'stocks.adjustment',
                            'label' => 'Buka Penyesuaian Stok',
                            'icon' => 'fa-sliders',
                            'tone' => 'warning',
                            'permissions' => [
                                'stock.adjust',
                            ],
                        ],
                        [
                            'route' => 'opnames.index',
                            'label' => 'Buka Stok Opname',
                            'icon' => 'fa-clipboard-check',
                            'tone' => 'info',
                            'permissions' => [
                                'opname.view',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Periksa seluruh laporan. Cocokkan penjualan, stok, laba rugi, utang-piutang, dan operasional dengan transaksi percobaan.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'reports.stocks',
                            'label' => 'Buka Laporan Stok',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'info',
                            'permissions' => [
                                'reports.stocks',
                            ],
                        ],
                        [
                            'route' => 'reports.profit',
                            'label' => 'Buka Laba Rugi',
                            'icon' => 'fa-coins',
                            'tone' => 'success',
                            'permissions' => [
                                'reports.profit',
                            ],
                        ],
                        [
                            'route' => 'reports.debts',
                            'label' => 'Buka Utang & Piutang',
                            'icon' => 'fa-file-invoice-dollar',
                            'tone' => 'warning',
                            'permissions' => [
                                'reports.debts',
                            ],
                        ],
                        [
                            'route' => 'reports.operations',
                            'label' => 'Buka Laporan Operasional',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'reports.operations',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Hapus atau tandai data percobaan sesuai kebijakan sebelum mulai operasional. Pastikan saldo awal, harga, stok, batch, dan utang-piutang sudah benar.',
                    'actions' => [
                        [
                            'route' => 'stocks.movements',
                            'label' => 'Buka Mutasi Stok',
                            'icon' => 'fa-arrow-right-arrow-left',
                            'tone' => 'primary',
                            'permissions' => [
                                'stock.view',
                            ],
                        ],
                        [
                            'route' => 'stocks.batches',
                            'label' => 'Buka Batch & Kedaluwarsa',
                            'icon' => 'fa-calendar-xmark',
                            'tone' => 'warning',
                            'permissions' => [
                                'stock.view',
                            ],
                        ],
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'activity.index',
                            'label' => 'Buka Activity Log',
                            'icon' => 'fa-clock-rotate-left',
                            'tone' => 'secondary',
                            'permissions' => [
                                'audit.view',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Lakukan backup database, catat akun owner dan recovery code secara aman, lalu mulai operasional dengan petugas menggunakan akun masing-masing.',
                    'actions' => [
                        [
                            'route' => 'settings.edit',
                            'label' => 'Buka Pengaturan POS',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'settings.manage',
                            ],
                        ],
                        [
                            'manual_slug' => 'troubleshooting',
                            'label' => 'Buka Troubleshooting',
                            'icon' => 'fa-screwdriver-wrench',
                            'tone' => 'secondary',
                        ],
                    ],
                ],
            ],
            'tips' => [
                [
                    'text' => 'Mulai dengan tiga sampai lima barang contoh untuk menguji alur sebelum mengimpor seluruh master barang.',
                    'actions' => [
                        [
                            'route' => 'products.index',
                            'label' => 'Buka Barang & Konversi',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'purple',
                            'permissions' => [
                                'products.view',
                            ],
                        ],
                        [
                            'route' => 'products.import-template',
                            'label' => 'Unduh Template Excel',
                            'icon' => 'fa-file-excel',
                            'tone' => 'success',
                            'permissions' => [
                                'products.create',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Uji setiap role menggunakan akun petugas, bukan hanya melalui akun owner.',
                    'actions' => [
                        [
                            'route' => 'roles.index',
                            'label' => 'Buka Role & Permission',
                            'icon' => 'fa-user-shield',
                            'tone' => 'purple',
                            'permissions' => [
                                'roles.manage',
                            ],
                        ],
                        [
                            'route' => 'users.index',
                            'label' => 'Buka Pengguna',
                            'icon' => 'fa-users',
                            'tone' => 'primary',
                            'permissions' => [
                                'users.manage',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Gunakan kode singkat dan konsisten untuk kategori, satuan, gudang, supplier, pelanggan, serta SKU barang.',
                    'actions' => [
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Kategori',
                            'icon' => 'fa-tags',
                            'tone' => 'warning',
                            'route_params' => [
                                'type' => 'categories',
                            ],
                            'permissions' => [
                                'categories.manage',
                            ],
                        ],
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Satuan',
                            'icon' => 'fa-scale-balanced',
                            'tone' => 'info',
                            'route_params' => [
                                'type' => 'units',
                            ],
                            'permissions' => [
                                'units.manage',
                            ],
                        ],
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Gudang',
                            'icon' => 'fa-warehouse',
                            'tone' => 'primary',
                            'route_params' => [
                                'type' => 'warehouses',
                            ],
                            'permissions' => [
                                'warehouses.manage',
                            ],
                        ],
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Supplier',
                            'icon' => 'fa-truck',
                            'tone' => 'info',
                            'route_params' => [
                                'type' => 'suppliers',
                            ],
                            'permissions' => [
                                'suppliers.manage',
                            ],
                        ],
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Pelanggan',
                            'icon' => 'fa-users',
                            'tone' => 'success',
                            'route_params' => [
                                'type' => 'customers',
                            ],
                            'permissions' => [
                                'customers.manage',
                                'sales.create',
                            ],
                        ],
                        [
                            'route' => 'products.index',
                            'label' => 'Buka Barang & Konversi',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'purple',
                            'permissions' => [
                                'products.view',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Simpan hasil backup sebelum memasukkan stok awal dan sebelum mulai transaksi sungguhan.',
                    'actions' => [
                        [
                            'route' => 'stocks.adjustment',
                            'label' => 'Buka Penyesuaian Stok',
                            'icon' => 'fa-sliders',
                            'tone' => 'warning',
                            'permissions' => [
                                'stock.adjust',
                            ],
                        ],
                        [
                            'manual_slug' => 'troubleshooting',
                            'label' => 'Buka Troubleshooting',
                            'icon' => 'fa-screwdriver-wrench',
                            'tone' => 'secondary',
                        ],
                    ],
                ],
                [
                    'text' => 'Buat checklist fisik dan beri tanda selesai pada setiap tahap agar tidak ada master penting yang terlewat.',
                    'actions' => [
                        [
                            'manual_slug' => 'setup-awal-owner',
                            'label' => 'Kembali ke Checklist Owner',
                            'icon' => 'fa-list-check',
                            'tone' => 'secondary',
                        ],
                    ],
                ],
            ],
            'warnings' => [
                [
                    'text' => 'Jangan memberikan seluruh permission kepada kasir atau petugas gudang hanya agar menu terlihat lengkap.',
                    'actions' => [
                        [
                            'route' => 'roles.index',
                            'label' => 'Buka Role & Permission',
                            'icon' => 'fa-user-shield',
                            'tone' => 'purple',
                            'permissions' => [
                                'roles.manage',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Jangan mengaktifkan stok minus kecuali kebijakan toko memang mengizinkannya.',
                    'actions' => [
                        [
                            'route' => 'settings.edit',
                            'label' => 'Buka Pengaturan POS',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'settings.manage',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Jangan memasukkan stok yang sama melalui Penyesuaian Stok dan Pembelian.',
                    'actions' => [
                        [
                            'route' => 'stocks.adjustment',
                            'label' => 'Buka Penyesuaian Stok',
                            'icon' => 'fa-sliders',
                            'tone' => 'warning',
                            'permissions' => [
                                'stock.adjust',
                            ],
                        ],
                        [
                            'route' => 'purchases.create',
                            'label' => 'Tambah Pembelian',
                            'icon' => 'fa-cart-plus',
                            'tone' => 'success',
                            'permissions' => [
                                'purchases.create',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Jangan mulai transaksi sungguhan sebelum printer, metode pembayaran, struk, poin, retur, dan laporan diuji.',
                    'actions' => [
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                        [
                            'manual_slug' => 'struk',
                            'label' => 'Buka Panduan Struk',
                            'icon' => 'fa-receipt',
                            'tone' => 'secondary',
                        ],
                        [
                            'route' => 'sale-returns.index',
                            'label' => 'Buka Retur Penjualan',
                            'icon' => 'fa-rotate-left',
                            'tone' => 'danger',
                            'permissions' => [
                                'sale_returns.create',
                            ],
                        ],
                        [
                            'route' => 'reports.operations',
                            'label' => 'Buka Laporan Operasional',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'reports.operations',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Jangan mengganti APP_KEY pada aplikasi yang sudah digunakan dan jangan menjalankan migrate:fresh pada database operasional.',
                    'actions' => [
                        [
                            'manual_slug' => 'troubleshooting',
                            'label' => 'Buka Troubleshooting',
                            'icon' => 'fa-screwdriver-wrench',
                            'tone' => 'secondary',
                        ],
                    ],
                    'context' => 'Terminal / server',
                ],
            ],
            'keywords' => [
                'owner',
                'pertama kali',
                'setup awal',
                'mulai dari mana',
                'checklist',
                'akun',
                'role',
                'pengaturan',
                'kategori',
                'satuan',
                'gudang',
                'supplier',
                'pelanggan',
                'barang',
                'stok awal',
                'go live',
            ],
            'journey' => [
                [
                    'label' => 'Akun & Akses',
                    'icon' => 'fa-user-shield',
                    'target' => 'manual-setup-awal-owner-steps-1',
                    'range' => 'Langkah 1–3',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Pengaturan',
                    'icon' => 'fa-gears',
                    'target' => 'manual-setup-awal-owner-steps-4',
                    'range' => 'Langkah 4–5',
                    'tone' => 'purple',
                ],
                [
                    'label' => 'Master Data',
                    'icon' => 'fa-database',
                    'target' => 'manual-setup-awal-owner-steps-6',
                    'range' => 'Langkah 6–11',
                    'tone' => 'gold',
                ],
                [
                    'label' => 'Stok & Pembelian',
                    'icon' => 'fa-boxes-stacked',
                    'target' => 'manual-setup-awal-owner-steps-12',
                    'range' => 'Langkah 12–14',
                    'tone' => 'orange',
                ],
                [
                    'label' => 'Uji Operasional',
                    'icon' => 'fa-vial-circle-check',
                    'target' => 'manual-setup-awal-owner-steps-15',
                    'range' => 'Langkah 15–18',
                    'tone' => 'green',
                ],
                [
                    'label' => 'Siap Digunakan',
                    'icon' => 'fa-flag-checkered',
                    'target' => 'manual-setup-awal-owner-steps-19',
                    'range' => 'Langkah 19–20',
                    'tone' => 'teal',
                ],
            ],
        ],
        [
            'slug' => 'mulai',
            'category' => 'Mulai',
            'title' => 'Mulai Menggunakan Aplikasi',
            'icon' => 'fa-rocket',
            'color' => 'blue',
            'permissions' => [],
            'route' => 'pos.dashboard',
            'summary' => 'Kenali alur masuk, menu, hak akses, mode tampilan, dan kebiasaan penggunaan yang aman.',
            'prerequisites' => [
                'Akun sudah aktif.',
                'Email sudah diverifikasi bila verifikasi email diaktifkan.',
                'Gunakan browser modern seperti Chrome atau Edge.',
            ],
            'steps' => [
                'Buka alamat aplikasi yang diberikan administrator.',
                'Masukkan email dan password, lalu tekan Masuk ke Sistem.',
                'Sesudah login, perhatikan menu sidebar. Menu yang tampil mengikuti permission akun.',
                'Gunakan tombol menu pada bagian atas untuk menyembunyikan atau menampilkan sidebar.',
                [
                    'text' => 'Buka Profil, Password & 2FA untuk mengganti identitas akun dan mengaktifkan autentikasi dua faktor.',
                    'actions' => [
                        [
                            'route' => 'account.security',
                            'label' => 'Buka Keamanan Akun',
                            'icon' => 'fa-shield-halved',
                            'tone' => 'info',
                        ],
                    ],
                ],
                'Logout setelah selesai bekerja, terutama pada komputer kasir bersama.',
            ],
            'tips' => [
                [
                    'text' => 'Gunakan satu akun untuk satu petugas agar Activity Log dapat dipertanggungjawabkan.',
                    'actions' => [
                        [
                            'route' => 'activity.index',
                            'label' => 'Buka Activity Log',
                            'icon' => 'fa-clock-rotate-left',
                            'tone' => 'secondary',
                            'permissions' => [
                                'audit.view',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Jangan membagikan password atau kode pemulihan 2FA.',
                    'actions' => [
                        [
                            'route' => 'account.security',
                            'label' => 'Buka Keamanan Akun',
                            'icon' => 'fa-shield-halved',
                            'tone' => 'info',
                        ],
                    ],
                ],
            ],
            'warnings' => [
                'Jangan mengubah tanggal dan jam komputer ketika transaksi sedang berjalan karena nomor, shift, dan laporan bergantung pada waktu sistem.',
            ],
            'keywords' => [
                'login',
                'sidebar',
                'akun',
                'password',
                '2fa',
                'hak akses',
            ],
        ],
        [
            'slug' => 'dashboard',
            'category' => 'Mulai',
            'title' => 'Dashboard',
            'icon' => 'fa-chart-pie',
            'color' => 'indigo',
            'permissions' => [
                'dashboard.view',
            ],
            'route' => 'pos.dashboard',
            'summary' => 'Melihat ringkasan penjualan, persediaan, pembelian, piutang, utang, pengeluaran, promo, dan prioritas hari ini.',
            'prerequisites' => [
                'Permission dashboard.view.',
            ],
            'steps' => [
                'Buka Dashboard dari sidebar.',
                'Baca kartu ringkasan yang tersedia. Kartu hanya muncul bila akun mempunyai permission modul terkait.',
                'Periksa bagian Prioritas Hari Ini untuk shift yang belum dibuka, stok menipis, batch kedaluwarsa, atau opname tertunda.',
                [
                    'text' => 'Gunakan tombol aksi cepat untuk menuju kasir, pembelian, penyesuaian, opname, pengeluaran, atau barang.',
                    'actions' => [
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                        [
                            'route' => 'purchases.create',
                            'label' => 'Tambah Pembelian',
                            'icon' => 'fa-cart-plus',
                            'tone' => 'success',
                            'permissions' => [
                                'purchases.create',
                            ],
                        ],
                        [
                            'route' => 'stocks.adjustment',
                            'label' => 'Buka Penyesuaian Stok',
                            'icon' => 'fa-sliders',
                            'tone' => 'warning',
                            'permissions' => [
                                'stock.adjust',
                            ],
                        ],
                        [
                            'route' => 'opnames.create',
                            'label' => 'Buat Stok Opname',
                            'icon' => 'fa-clipboard-list',
                            'tone' => 'success',
                            'permissions' => [
                                'opname.create',
                            ],
                        ],
                        [
                            'route' => 'expenses.index',
                            'label' => 'Buka Pengeluaran',
                            'icon' => 'fa-money-bill-transfer',
                            'tone' => 'danger',
                            'permissions' => [
                                'expenses.view',
                            ],
                        ],
                        [
                            'route' => 'products.index',
                            'label' => 'Buka Barang & Konversi',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'purple',
                            'permissions' => [
                                'products.view',
                            ],
                        ],
                    ],
                ],
                'Gunakan tabel transaksi terbaru dan distribusi persediaan untuk pemeriksaan cepat.',
            ],
            'tips' => [
                [
                    'text' => 'Dashboard bukan pengganti laporan. Gunakan menu Laporan untuk periode dan filter yang lebih rinci.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'reports.stocks',
                            'label' => 'Buka Laporan Stok',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'info',
                            'permissions' => [
                                'reports.stocks',
                            ],
                        ],
                        [
                            'route' => 'reports.profit',
                            'label' => 'Buka Laba Rugi',
                            'icon' => 'fa-coins',
                            'tone' => 'success',
                            'permissions' => [
                                'reports.profit',
                            ],
                        ],
                        [
                            'route' => 'reports.debts',
                            'label' => 'Buka Utang & Piutang',
                            'icon' => 'fa-file-invoice-dollar',
                            'tone' => 'warning',
                            'permissions' => [
                                'reports.debts',
                            ],
                        ],
                        [
                            'route' => 'reports.operations',
                            'label' => 'Buka Laporan Operasional',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'reports.operations',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Pemilik dapat mengatur permission role untuk menentukan panel dashboard yang terlihat.',
                    'actions' => [
                        [
                            'route' => 'roles.index',
                            'label' => 'Buka Role & Permission',
                            'icon' => 'fa-user-shield',
                            'tone' => 'purple',
                            'permissions' => [
                                'roles.manage',
                            ],
                        ],
                    ],
                ],
            ],
            'warnings' => [],
            'keywords' => [
                'dashboard',
                'ringkasan',
                'prioritas',
                'kpi',
                'grafik',
            ],
        ],
        [
            'slug' => 'shift-kasir',
            'category' => 'Kasir',
            'title' => 'Membuka dan Menutup Shift Kasir',
            'icon' => 'fa-clock',
            'color' => 'gold',
            'permissions' => [
                'shifts.manage',
            ],
            'route' => 'shifts.index',
            'summary' => 'Mencatat saldo awal, aktivitas kas, saldo fisik, dan selisih pada setiap periode kerja kasir.',
            'prerequisites' => [
                'Permission shifts.manage.',
                'Tidak ada shift aktif lain untuk akun yang sama.',
            ],
            'steps' => [
                'Buka Shift Kasir.',
                'Masukkan saldo awal fisik yang tersedia di laci kas.',
                'Isi catatan pembukaan bila diperlukan, lalu tekan Buka Shift Sekarang.',
                [
                    'text' => 'Lakukan transaksi penjualan, pembayaran piutang, pengeluaran, dan refund selama shift aktif.',
                    'actions' => [
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                        [
                            'route' => 'reports.debts',
                            'label' => 'Buka Utang & Piutang',
                            'icon' => 'fa-file-invoice-dollar',
                            'tone' => 'warning',
                            'permissions' => [
                                'reports.debts',
                            ],
                        ],
                        [
                            'route' => 'expenses.index',
                            'label' => 'Buka Pengeluaran',
                            'icon' => 'fa-money-bill-transfer',
                            'tone' => 'danger',
                            'permissions' => [
                                'expenses.view',
                            ],
                        ],
                    ],
                ],
                'Pada akhir kerja, buka kembali Shift Kasir dan masukkan kas fisik yang dihitung.',
                'Periksa kas sistem, kas fisik, dan selisih. Isi catatan penutupan bila ada perbedaan.',
                'Tekan Tutup Shift dan pastikan notifikasi berhasil muncul.',
            ],
            'tips' => [
                'Hitung pecahan uang sebelum membuka dan menutup shift.',
                'Pisahkan uang pribadi dari laci kas.',
            ],
            'warnings' => [
                [
                    'text' => 'POS dapat dikunci untuk transaksi tunai ketika tidak ada shift aktif.',
                    'actions' => [
                        [
                            'route' => 'shifts.index',
                            'label' => 'Buka Shift Kasir',
                            'icon' => 'fa-clock',
                            'tone' => 'warning',
                            'permissions' => [
                                'shifts.manage',
                            ],
                        ],
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Selisih kas tidak boleh diperbaiki dengan menghapus transaksi. Gunakan catatan dan proses koreksi yang tersedia.',
                    'actions' => [
                        [
                            'route' => 'activity.index',
                            'label' => 'Buka Activity Log',
                            'icon' => 'fa-clock-rotate-left',
                            'tone' => 'secondary',
                            'permissions' => [
                                'audit.view',
                            ],
                        ],
                    ],
                ],
            ],
            'keywords' => [
                'shift',
                'saldo awal',
                'kas fisik',
                'selisih',
                'tutup kasir',
            ],
        ],
        [
            'slug' => 'pos-penjualan',
            'category' => 'Kasir',
            'title' => 'POS Penjualan',
            'icon' => 'fa-cash-register',
            'color' => 'green',
            'permissions' => [
                'sales.create',
            ],
            'route' => 'sales.pos',
            'summary' => 'Memilih barang, pelanggan, promo, poin, metode pembayaran, dan menyimpan transaksi penjualan.',
            'prerequisites' => [
                'Permission sales.create.',
                'Shift aktif untuk pembayaran tunai.',
                'Barang aktif dan memiliki stok bila stok negatif tidak diizinkan.',
            ],
            'steps' => [
                'Buka POS Penjualan.',
                'Cari barang melalui nama, SKU, barcode, atau kategori. Tekan F2 untuk fokus ke pencarian.',
                'Klik barang untuk memasukkannya ke keranjang.',
                'Periksa satuan, jumlah, diskon item, subtotal, dan stok pada setiap baris keranjang.',
                [
                    'text' => 'Pilih member bila transaksi perlu mencatat histori, piutang, atau poin. Gunakan tombol Hapus untuk kembali ke Pelanggan Umum.',
                    'actions' => [
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Pelanggan',
                            'icon' => 'fa-users',
                            'tone' => 'success',
                            'route_params' => [
                                'type' => 'customers',
                            ],
                            'permissions' => [
                                'customers.manage',
                                'sales.create',
                            ],
                        ],
                    ],
                ],
                'Pilih promo yang berlaku. Total akan dihitung ulang saat promo diganti atau dihapus.',
                'Masukkan poin yang akan digunakan atau tekan Pakai Maks. bila fitur poin aktif.',
                'Tambahkan satu atau beberapa metode pembayaran. Referensi wajib untuk metode non-tunai bila kebijakan toko memerlukannya.',
                'Tekan Bayar/Periksa Transaksi, lalu cek daftar barang, total, pembayaran, kembalian, piutang, dan poin.',
                [
                    'text' => 'Tekan Simpan & Lihat Struk setelah semua angka benar.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'manual_slug' => 'struk',
                            'label' => 'Buka Panduan Struk',
                            'icon' => 'fa-receipt',
                            'tone' => 'secondary',
                        ],
                    ],
                ],
            ],
            'tips' => [
                'Gunakan barcode scanner dalam mode keyboard untuk transaksi cepat.',
                [
                    'text' => 'Pilih pelanggan sebelum memakai poin atau membuat piutang.',
                    'actions' => [
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Pelanggan',
                            'icon' => 'fa-users',
                            'tone' => 'success',
                            'route_params' => [
                                'type' => 'customers',
                            ],
                            'permissions' => [
                                'customers.manage',
                                'sales.create',
                            ],
                        ],
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
                'Kembalian hanya dihitung dari pembayaran yang diterapkan pada transaksi.',
            ],
            'warnings' => [
                'Jangan menutup atau menyegarkan halaman saat penyimpanan sedang berjalan.',
                [
                    'text' => 'Transaksi yang sudah final dikoreksi melalui retur, bukan diedit atau dihapus.',
                    'actions' => [
                        [
                            'route' => 'sale-returns.index',
                            'label' => 'Buka Retur Penjualan',
                            'icon' => 'fa-rotate-left',
                            'tone' => 'danger',
                            'permissions' => [
                                'sale_returns.create',
                            ],
                        ],
                    ],
                ],
            ],
            'keywords' => [
                'kasir',
                'penjualan',
                'keranjang',
                'barcode',
                'pembayaran',
                'kembalian',
                'promo',
                'poin',
            ],
        ],
        [
            'slug' => 'struk',
            'category' => 'Kasir',
            'title' => 'Mencetak Struk Thermal',
            'icon' => 'fa-receipt',
            'color' => 'teal',
            'permissions' => [
                'sales.view',
            ],
            'route' => null,
            'summary' => 'Mencetak struk 58 mm atau 80 mm, mengunduh TXT, dan memastikan informasi transaksi terbaca.',
            'prerequisites' => [
                'Permission sales.view.',
                'Printer thermal dan driver Windows sudah terpasang.',
            ],
            'steps' => [
                [
                    'text' => 'Buka detail penjualan dari transaksi yang baru disimpan atau laporan penjualan.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Pilih ukuran 58 mm atau 80 mm. Pratinjau kertas akan menyesuaikan.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Tekan Cetak Struk.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Pada dialog printer, pilih printer thermal, ukuran kertas yang sesuai, margin None/Tidak Ada, skala 100%, dan nonaktifkan Headers and footers.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Gunakan Unduh TXT bila pencetakan dilakukan melalui aplikasi bridge atau ESC/POS.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
            ],
            'tips' => [
                [
                    'text' => 'Struk member menampilkan nama yang tersimpan di master tanpa nomor telepon untuk menjaga privasi.',
                    'actions' => [
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Pelanggan',
                            'icon' => 'fa-users',
                            'tone' => 'success',
                            'route_params' => [
                                'type' => 'customers',
                            ],
                            'permissions' => [
                                'customers.manage',
                                'sales.create',
                            ],
                        ],
                    ],
                ],
                'Nama barang panjang akan turun ke baris berikutnya.',
            ],
            'warnings' => [
                [
                    'text' => 'Pilihan ukuran kertas pada dialog printer berasal dari driver Windows. Tambahkan form 80 mm pada Printing Preferences bila belum tersedia.',
                    'actions' => [
                        [
                            'manual_slug' => 'troubleshooting',
                            'label' => 'Buka Troubleshooting',
                            'icon' => 'fa-screwdriver-wrench',
                            'tone' => 'secondary',
                        ],
                    ],
                    'context' => 'Driver printer Windows',
                ],
            ],
            'keywords' => [
                'struk',
                'thermal',
                '58mm',
                '80mm',
                'txt',
                'printer',
            ],
        ],
        [
            'slug' => 'barang',
            'category' => 'Master',
            'title' => 'Barang dan Konversi Satuan',
            'icon' => 'fa-boxes-stacked',
            'color' => 'purple',
            'permissions' => [
                'products.view',
            ],
            'route' => 'products.index',
            'summary' => 'Mengelola SKU, barcode, harga, stok minimum, status barang, kedaluwarsa, dan konversi satuan.',
            'prerequisites' => [
                'Permission products.view; permission products.create/update/delete untuk aksi terkait.',
                [
                    'text' => 'Kategori dan satuan sudah tersedia.',
                    'actions' => [
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Kategori',
                            'icon' => 'fa-tags',
                            'tone' => 'warning',
                            'route_params' => [
                                'type' => 'categories',
                            ],
                            'permissions' => [
                                'categories.manage',
                            ],
                        ],
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Satuan',
                            'icon' => 'fa-scale-balanced',
                            'tone' => 'info',
                            'route_params' => [
                                'type' => 'units',
                            ],
                            'permissions' => [
                                'units.manage',
                            ],
                        ],
                    ],
                ],
            ],
            'steps' => [
                'Buka Barang & Konversi.',
                'Gunakan pencarian dan filter kategori, status, serta kondisi stok.',
                'Tekan Tambah Barang untuk membuat barang baru.',
                'Isi SKU unik, nama barang, kategori, satuan dasar, harga beli, harga jual, stok minimum, dan lokasi rak.',
                'Aktifkan Kelola stok bila barang memengaruhi persediaan dan aktifkan Punya kedaluwarsa bila batch wajib dicatat.',
                'Tambahkan konversi untuk satuan seperti dus, pack, atau renteng. Isi faktor dan harga per satuan.',
                [
                    'text' => 'Simpan, lalu uji pencarian barang pada POS.',
                    'actions' => [
                        [
                            'route' => 'products.index',
                            'label' => 'Buka Barang & Konversi',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'purple',
                            'permissions' => [
                                'products.view',
                            ],
                        ],
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Gunakan Import Excel untuk penambahan massal berdasarkan template yang disediakan.',
                    'actions' => [
                        [
                            'route' => 'products.index',
                            'label' => 'Buka Barang & Konversi',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'purple',
                            'permissions' => [
                                'products.view',
                            ],
                        ],
                        [
                            'route' => 'products.import-template',
                            'label' => 'Unduh Template Excel',
                            'icon' => 'fa-file-excel',
                            'tone' => 'success',
                            'permissions' => [
                                'products.create',
                            ],
                        ],
                    ],
                ],
            ],
            'tips' => [
                [
                    'text' => 'Satuan dasar sebaiknya satuan terkecil yang dihitung pada stok.',
                    'actions' => [
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Satuan',
                            'icon' => 'fa-scale-balanced',
                            'tone' => 'info',
                            'route_params' => [
                                'type' => 'units',
                            ],
                            'permissions' => [
                                'units.manage',
                            ],
                        ],
                    ],
                ],
                'Barcode setiap barang dan konversi harus unik.',
                'Barang yang sudah mempunyai transaksi tidak boleh bebas mengganti satuan dasar.',
            ],
            'warnings' => [
                [
                    'text' => 'Barang dengan histori transaksi atau stok tidak dapat dihapus sembarangan. Nonaktifkan barang bila sudah tidak dijual.',
                    'actions' => [
                        [
                            'route' => 'products.index',
                            'label' => 'Buka Barang & Konversi',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'purple',
                            'permissions' => [
                                'products.view',
                            ],
                        ],
                        [
                            'route' => 'stocks.movements',
                            'label' => 'Buka Mutasi Stok',
                            'icon' => 'fa-arrow-right-arrow-left',
                            'tone' => 'primary',
                            'permissions' => [
                                'stock.view',
                            ],
                        ],
                    ],
                ],
            ],
            'keywords' => [
                'barang',
                'produk',
                'sku',
                'barcode',
                'konversi',
                'satuan',
                'harga',
                'import excel',
            ],
        ],
        [
            'slug' => 'kategori',
            'category' => 'Master',
            'title' => 'Kategori Barang',
            'icon' => 'fa-tags',
            'color' => 'blue',
            'permissions' => [
                'categories.manage',
            ],
            'route' => 'master.index',
            'route_params' => [
                'type' => 'categories',
            ],
            'summary' => 'Mengelompokkan barang untuk pencarian, filter, dan laporan.',
            'prerequisites' => [
                'Permission categories.manage.',
            ],
            'steps' => [
                'Buka Kategori.',
                'Tekan Tambah Kategori.',
                'Isi nama dan kode kategori.',
                'Simpan dan pastikan kategori aktif.',
                'Gunakan edit untuk memperbaiki nama atau kode.',
            ],
            'tips' => [
                'Gunakan nama singkat dan konsisten seperti Frozen Food, Minuman, atau ATK.',
            ],
            'warnings' => [
                'Kategori yang sudah dipakai barang sebaiknya tidak dihapus; nonaktifkan bila tidak digunakan lagi.',
            ],
            'keywords' => [
                'kategori',
                'kelompok barang',
                'kode kategori',
            ],
        ],
        [
            'slug' => 'satuan',
            'category' => 'Master',
            'title' => 'Satuan',
            'icon' => 'fa-scale-balanced',
            'color' => 'teal',
            'permissions' => [
                'units.manage',
            ],
            'route' => 'master.index',
            'route_params' => [
                'type' => 'units',
            ],
            'summary' => 'Menyediakan satuan dasar dan satuan konversi yang dipakai barang.',
            'prerequisites' => [
                'Permission units.manage.',
            ],
            'steps' => [
                'Buka Satuan.',
                'Tekan Tambah Satuan.',
                'Isi nama, simbol, dan keterangan.',
                'Simpan satuan.',
                'Gunakan simbol yang konsisten pada barang dan transaksi.',
            ],
            'tips' => [
                'Contoh simbol: pcs, kg, dus, pack, botol.',
                'Hindari membuat dua satuan dengan arti yang sama.',
            ],
            'warnings' => [
                'Satuan yang sudah dipakai barang atau transaksi tidak boleh dihapus tanpa pemeriksaan relasi.',
            ],
            'keywords' => [
                'satuan',
                'pcs',
                'dus',
                'pack',
                'simbol',
            ],
        ],
        [
            'slug' => 'supplier',
            'category' => 'Master',
            'title' => 'Supplier',
            'icon' => 'fa-truck-field',
            'color' => 'orange',
            'permissions' => [
                'suppliers.manage',
            ],
            'route' => 'master.index',
            'route_params' => [
                'type' => 'suppliers',
            ],
            'summary' => 'Menyimpan identitas pemasok untuk pembelian, utang, dan laporan.',
            'prerequisites' => [
                'Permission suppliers.manage.',
            ],
            'steps' => [
                'Buka Supplier.',
                'Tekan Tambah Supplier.',
                'Isi nama, kode, telepon, email, alamat, dan catatan.',
                'Simpan supplier.',
                [
                    'text' => 'Gunakan supplier tersebut pada transaksi pembelian.',
                    'actions' => [
                        [
                            'route' => 'purchases.create',
                            'label' => 'Tambah Pembelian',
                            'icon' => 'fa-cart-plus',
                            'tone' => 'success',
                            'permissions' => [
                                'purchases.create',
                            ],
                        ],
                        [
                            'route' => 'purchases.index',
                            'label' => 'Buka Pembelian',
                            'icon' => 'fa-cart-shopping',
                            'tone' => 'primary',
                            'permissions' => [
                                'purchases.view',
                            ],
                        ],
                    ],
                ],
            ],
            'tips' => [
                'Gunakan satu kode unik per supplier.',
                [
                    'text' => 'Isi kontak penagihan agar utang mudah ditindaklanjuti.',
                    'actions' => [
                        [
                            'route' => 'reports.debts',
                            'label' => 'Buka Utang & Piutang',
                            'icon' => 'fa-file-invoice-dollar',
                            'tone' => 'warning',
                            'permissions' => [
                                'reports.debts',
                            ],
                        ],
                    ],
                ],
            ],
            'warnings' => [
                'Supplier yang sudah dipakai pembelian sebaiknya dinonaktifkan, bukan dihapus.',
            ],
            'keywords' => [
                'supplier',
                'pemasok',
                'kontak',
                'utang',
            ],
        ],
        [
            'slug' => 'pelanggan',
            'category' => 'Master',
            'title' => 'Pelanggan, Member, Kredit, dan Poin',
            'icon' => 'fa-users',
            'color' => 'green',
            'permissions' => [
                'customers.manage',
                'sales.create',
            ],
            'route' => 'master.index',
            'route_params' => [
                'type' => 'customers',
            ],
            'summary' => 'Mengelola identitas pelanggan, saldo poin, limit kredit, dan penggunaan member pada POS.',
            'prerequisites' => [
                'Permission customers.manage untuk mengelola master; sales.create untuk memilih pelanggan pada POS.',
            ],
            'steps' => [
                'Buka Pelanggan untuk menambah atau mengubah data member.',
                'Isi nama yang benar karena nama ini tampil pada struk member.',
                'Isi telepon, email, alamat, limit kredit, dan status aktif sesuai kebutuhan.',
                [
                    'text' => 'Pada POS, pilih pelanggan sebelum memakai poin atau membuat piutang.',
                    'actions' => [
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Pelanggan',
                            'icon' => 'fa-users',
                            'tone' => 'success',
                            'route_params' => [
                                'type' => 'customers',
                            ],
                            'permissions' => [
                                'customers.manage',
                                'sales.create',
                            ],
                        ],
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Gunakan tombol Hapus pada POS bila pelanggan salah dipilih.',
                    'actions' => [
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Poin baru diperoleh setelah transaksi lunas. Poin yang dipakai menjadi potongan sesuai Pengaturan POS.',
                    'actions' => [
                        [
                            'route' => 'settings.edit',
                            'label' => 'Buka Pengaturan POS',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'settings.manage',
                            ],
                        ],
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
            ],
            'tips' => [
                'Nomor telepon membantu pencarian member tetapi tidak dicetak pada struk.',
                'Saldo poin awal dapat dipakai untuk migrasi member lama.',
            ],
            'warnings' => [
                [
                    'text' => 'Jangan membuat transaksi piutang untuk Pelanggan Umum karena penagihan tidak dapat ditelusuri ke orang tertentu.',
                    'actions' => [
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Pelanggan',
                            'icon' => 'fa-users',
                            'tone' => 'success',
                            'route_params' => [
                                'type' => 'customers',
                            ],
                            'permissions' => [
                                'customers.manage',
                                'sales.create',
                            ],
                        ],
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
            ],
            'keywords' => [
                'pelanggan',
                'member',
                'poin',
                'kredit',
                'limit',
                'piutang',
            ],
        ],
        [
            'slug' => 'gudang',
            'category' => 'Master',
            'title' => 'Gudang',
            'icon' => 'fa-warehouse',
            'color' => 'indigo',
            'permissions' => [
                'warehouses.manage',
            ],
            'route' => 'master.index',
            'route_params' => [
                'type' => 'warehouses',
            ],
            'summary' => 'Menentukan lokasi persediaan untuk pembelian, penjualan, mutasi, dan laporan.',
            'prerequisites' => [
                'Permission warehouses.manage.',
            ],
            'steps' => [
                'Buka Gudang.',
                'Tambah gudang dan isi nama, kode, serta alamat/keterangan.',
                'Tentukan gudang utama bila diperlukan.',
                [
                    'text' => 'Gunakan gudang yang sesuai pada transaksi dan koreksi stok.',
                    'actions' => [
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Gudang',
                            'icon' => 'fa-warehouse',
                            'tone' => 'primary',
                            'route_params' => [
                                'type' => 'warehouses',
                            ],
                            'permissions' => [
                                'warehouses.manage',
                            ],
                        ],
                        [
                            'route' => 'stocks.movements',
                            'label' => 'Buka Mutasi Stok',
                            'icon' => 'fa-arrow-right-arrow-left',
                            'tone' => 'primary',
                            'permissions' => [
                                'stock.view',
                            ],
                        ],
                    ],
                ],
            ],
            'tips' => [
                'Buat gudang terpisah hanya bila stok memang harus dibedakan secara operasional.',
            ],
            'warnings' => [
                [
                    'text' => 'Jangan menggabungkan saldo dua gudang hanya dengan mengubah nama. Gunakan proses mutasi atau penyesuaian yang dapat diaudit.',
                    'actions' => [
                        [
                            'route' => 'stocks.movements',
                            'label' => 'Buka Mutasi Stok',
                            'icon' => 'fa-arrow-right-arrow-left',
                            'tone' => 'primary',
                            'permissions' => [
                                'stock.view',
                            ],
                        ],
                        [
                            'route' => 'stocks.adjustment',
                            'label' => 'Buka Penyesuaian Stok',
                            'icon' => 'fa-sliders',
                            'tone' => 'warning',
                            'permissions' => [
                                'stock.adjust',
                            ],
                        ],
                    ],
                ],
            ],
            'keywords' => [
                'gudang',
                'lokasi stok',
                'warehouse',
            ],
        ],
        [
            'slug' => 'pembelian',
            'category' => 'Pembelian',
            'title' => 'Pembelian Barang',
            'icon' => 'fa-cart-shopping',
            'color' => 'blue',
            'permissions' => [
                'purchases.view',
            ],
            'route' => 'purchases.index',
            'summary' => 'Mencatat barang masuk, batch, harga beli, pembayaran, dan utang supplier.',
            'prerequisites' => [
                'Permission purchases.view; purchases.create untuk membuat transaksi.',
                [
                    'text' => 'Supplier, gudang, barang, dan satuan sudah tersedia.',
                    'actions' => [
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Supplier',
                            'icon' => 'fa-truck',
                            'tone' => 'info',
                            'route_params' => [
                                'type' => 'suppliers',
                            ],
                            'permissions' => [
                                'suppliers.manage',
                            ],
                        ],
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Gudang',
                            'icon' => 'fa-warehouse',
                            'tone' => 'primary',
                            'route_params' => [
                                'type' => 'warehouses',
                            ],
                            'permissions' => [
                                'warehouses.manage',
                            ],
                        ],
                        [
                            'route' => 'products.index',
                            'label' => 'Buka Barang & Konversi',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'purple',
                            'permissions' => [
                                'products.view',
                            ],
                        ],
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Satuan',
                            'icon' => 'fa-scale-balanced',
                            'tone' => 'info',
                            'route_params' => [
                                'type' => 'units',
                            ],
                            'permissions' => [
                                'units.manage',
                            ],
                        ],
                    ],
                ],
            ],
            'steps' => [
                [
                    'text' => 'Buka Pembelian dan tekan Tambah Pembelian.',
                    'actions' => [
                        [
                            'route' => 'purchases.index',
                            'label' => 'Buka Pembelian',
                            'icon' => 'fa-cart-shopping',
                            'tone' => 'primary',
                            'permissions' => [
                                'purchases.view',
                            ],
                        ],
                        [
                            'route' => 'purchases.create',
                            'label' => 'Tambah Pembelian',
                            'icon' => 'fa-cart-plus',
                            'tone' => 'success',
                            'permissions' => [
                                'purchases.create',
                            ],
                        ],
                    ],
                ],
                'Pilih supplier, gudang, tanggal pembelian, invoice, dan jatuh tempo.',
                'Tambahkan baris barang, pilih satuan, jumlah, harga per satuan, diskon, batch, dan kedaluwarsa bila diwajibkan.',
                'Isi catatan, diskon transaksi, biaya tambahan, bayar awal, dan metode pembayaran.',
                'Periksa ringkasan dan simpan transaksi.',
                [
                    'text' => 'Buka detail pembelian untuk mencetak, melihat utang, atau menambah pembayaran.',
                    'actions' => [
                        [
                            'route' => 'purchases.index',
                            'label' => 'Buka Pembelian',
                            'icon' => 'fa-cart-shopping',
                            'tone' => 'primary',
                            'permissions' => [
                                'purchases.view',
                            ],
                        ],
                        [
                            'route' => 'reports.debts',
                            'label' => 'Buka Utang & Piutang',
                            'icon' => 'fa-file-invoice-dollar',
                            'tone' => 'warning',
                            'permissions' => [
                                'reports.debts',
                            ],
                        ],
                    ],
                ],
            ],
            'tips' => [
                'Harga pembelian otomatis dikonversi ke satuan dasar untuk pembaruan stok dan biaya rata-rata.',
                'Gunakan nomor invoice supplier untuk rekonsiliasi.',
            ],
            'warnings' => [
                [
                    'text' => 'Pembelian yang sudah diposting memengaruhi stok dan tidak diedit atau dihapus. Koreksi barang melalui Retur Pembelian.',
                    'actions' => [
                        [
                            'route' => 'purchase-returns.index',
                            'label' => 'Buka Retur Pembelian',
                            'icon' => 'fa-rotate-left',
                            'tone' => 'danger',
                            'permissions' => [
                                'purchase_returns.create',
                            ],
                        ],
                    ],
                ],
            ],
            'keywords' => [
                'pembelian',
                'barang masuk',
                'supplier',
                'batch',
                'utang',
                'harga beli',
            ],
        ],
        [
            'slug' => 'retur-pembelian',
            'category' => 'Pembelian',
            'title' => 'Retur Pembelian',
            'icon' => 'fa-rotate-left',
            'color' => 'red',
            'permissions' => [
                'purchase_returns.create',
            ],
            'route' => 'purchase-returns.index',
            'summary' => 'Mengembalikan barang kepada supplier dan menyesuaikan stok serta utang.',
            'prerequisites' => [
                'Permission purchase_returns.create.',
                'Transaksi pembelian tersedia dan kuantitas retur tidak melebihi sisa yang dapat diretur.',
            ],
            'steps' => [
                [
                    'text' => 'Buka Retur Pembelian atau tekan Buat Retur dari detail pembelian.',
                    'actions' => [
                        [
                            'route' => 'purchases.index',
                            'label' => 'Buka Pembelian',
                            'icon' => 'fa-cart-shopping',
                            'tone' => 'primary',
                            'permissions' => [
                                'purchases.view',
                            ],
                        ],
                        [
                            'route' => 'purchase-returns.index',
                            'label' => 'Buka Retur Pembelian',
                            'icon' => 'fa-rotate-left',
                            'tone' => 'danger',
                            'permissions' => [
                                'purchase_returns.create',
                            ],
                        ],
                    ],
                ],
                'Pilih barang dan jumlah yang diretur.',
                'Isi alasan, metode penyelesaian, dan catatan.',
                'Periksa nilai retur dan simpan.',
                'Pastikan stok dan utang supplier berubah sesuai hasil retur.',
            ],
            'tips' => [
                'Catat kondisi barang dan nomor dokumen retur supplier pada catatan.',
            ],
            'warnings' => [
                [
                    'text' => 'Jangan menggunakan penyesuaian stok untuk menggantikan retur pembelian karena utang supplier tidak ikut terkoreksi.',
                    'actions' => [
                        [
                            'route' => 'purchase-returns.index',
                            'label' => 'Buka Retur Pembelian',
                            'icon' => 'fa-rotate-left',
                            'tone' => 'danger',
                            'permissions' => [
                                'purchase_returns.create',
                            ],
                        ],
                        [
                            'route' => 'stocks.adjustment',
                            'label' => 'Buka Penyesuaian Stok',
                            'icon' => 'fa-sliders',
                            'tone' => 'warning',
                            'permissions' => [
                                'stock.adjust',
                            ],
                        ],
                    ],
                ],
            ],
            'keywords' => [
                'retur pembelian',
                'kembali supplier',
                'stok keluar',
                'utang',
            ],
        ],
        [
            'slug' => 'mutasi-batch',
            'category' => 'Persediaan',
            'title' => 'Mutasi Stok dan Batch Kedaluwarsa',
            'icon' => 'fa-arrow-right-arrow-left',
            'color' => 'teal',
            'permissions' => [
                'stock.view',
            ],
            'route' => 'stocks.movements',
            'summary' => 'Menelusuri semua perubahan stok dan memantau batch serta tanggal kedaluwarsa.',
            'prerequisites' => [
                'Permission stock.view.',
            ],
            'steps' => [
                'Buka Mutasi Stok untuk melihat barang masuk dan keluar.',
                'Gunakan filter barang, gudang, sumber, arah mutasi, dan periode.',
                'Klik sumber transaksi bila tersedia untuk membuka dokumen asal.',
                [
                    'text' => 'Buka Batch & Kedaluwarsa untuk melihat saldo per batch dan sisa hari.',
                    'actions' => [
                        [
                            'route' => 'stocks.movements',
                            'label' => 'Buka Mutasi Stok',
                            'icon' => 'fa-arrow-right-arrow-left',
                            'tone' => 'primary',
                            'permissions' => [
                                'stock.view',
                            ],
                        ],
                        [
                            'route' => 'stocks.batches',
                            'label' => 'Buka Batch & Kedaluwarsa',
                            'icon' => 'fa-calendar-xmark',
                            'tone' => 'warning',
                            'permissions' => [
                                'stock.view',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Prioritaskan batch yang akan segera kedaluwarsa.',
                    'actions' => [
                        [
                            'route' => 'stocks.batches',
                            'label' => 'Buka Batch & Kedaluwarsa',
                            'icon' => 'fa-calendar-xmark',
                            'tone' => 'warning',
                            'permissions' => [
                                'stock.view',
                            ],
                        ],
                    ],
                ],
            ],
            'tips' => [
                'Mutasi stok adalah jejak audit utama. Gunakan catatan yang jelas pada setiap koreksi manual.',
                [
                    'text' => 'Lakukan penjualan berdasarkan kebijakan FEFO bila barang mempunyai kedaluwarsa.',
                    'actions' => [
                        [
                            'route' => 'stocks.batches',
                            'label' => 'Buka Batch & Kedaluwarsa',
                            'icon' => 'fa-calendar-xmark',
                            'tone' => 'warning',
                            'permissions' => [
                                'stock.view',
                            ],
                        ],
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
            ],
            'warnings' => [
                'Jangan mengubah database stok secara langsung karena saldo, batch, dan histori dapat tidak sinkron.',
            ],
            'keywords' => [
                'mutasi stok',
                'batch',
                'kedaluwarsa',
                'fefo',
                'histori',
            ],
        ],
        [
            'slug' => 'penyesuaian',
            'category' => 'Persediaan',
            'title' => 'Penyesuaian Stok',
            'icon' => 'fa-sliders',
            'color' => 'orange',
            'permissions' => [
                'stock.adjust',
            ],
            'route' => 'stocks.adjustment',
            'summary' => 'Menambah atau mengurangi saldo untuk stok awal, rusak, hilang, kedaluwarsa, atau koreksi pemeriksaan.',
            'prerequisites' => [
                'Permission stock.adjust.',
                'Gudang dan barang sudah dipilih.',
            ],
            'steps' => [
                'Buka Penyesuaian Stok.',
                'Pilih gudang dan barang.',
                'Pilih Tambah Stok atau Kurangi Stok.',
                'Masukkan jumlah dalam satuan dasar.',
                'Isi batch dan kedaluwarsa bila barang membutuhkannya.',
                'Pilih alasan dan isi catatan yang dapat diaudit.',
                'Periksa saldo sebelum dan sesudah, lalu simpan.',
            ],
            'tips' => [
                'Gunakan alasan yang paling spesifik.',
                'Lampirkan referensi pemeriksaan pada catatan bila proses organisasi memerlukannya.',
            ],
            'warnings' => [
                [
                    'text' => 'Pembelian, penjualan, retur, dan opname sudah membuat mutasi otomatis. Jangan mencatat ulang melalui menu ini.',
                    'actions' => [
                        [
                            'route' => 'purchases.index',
                            'label' => 'Buka Pembelian',
                            'icon' => 'fa-cart-shopping',
                            'tone' => 'primary',
                            'permissions' => [
                                'purchases.view',
                            ],
                        ],
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'purchase-returns.index',
                            'label' => 'Buka Retur Pembelian',
                            'icon' => 'fa-rotate-left',
                            'tone' => 'danger',
                            'permissions' => [
                                'purchase_returns.create',
                            ],
                        ],
                        [
                            'route' => 'sale-returns.index',
                            'label' => 'Buka Retur Penjualan',
                            'icon' => 'fa-rotate-left',
                            'tone' => 'danger',
                            'permissions' => [
                                'sale_returns.create',
                            ],
                        ],
                        [
                            'route' => 'opnames.index',
                            'label' => 'Buka Stok Opname',
                            'icon' => 'fa-clipboard-check',
                            'tone' => 'info',
                            'permissions' => [
                                'opname.view',
                            ],
                        ],
                        [
                            'route' => 'stocks.movements',
                            'label' => 'Buka Mutasi Stok',
                            'icon' => 'fa-arrow-right-arrow-left',
                            'tone' => 'primary',
                            'permissions' => [
                                'stock.view',
                            ],
                        ],
                    ],
                ],
            ],
            'keywords' => [
                'penyesuaian stok',
                'stok awal',
                'rusak',
                'hilang',
                'koreksi',
            ],
        ],
        [
            'slug' => 'opname',
            'category' => 'Persediaan',
            'title' => 'Stok Opname',
            'icon' => 'fa-clipboard-check',
            'color' => 'green',
            'permissions' => [
                'opname.view',
            ],
            'route' => 'opnames.index',
            'summary' => 'Menghitung stok fisik, membandingkan dengan sistem, dan memproses koreksi melalui persetujuan.',
            'prerequisites' => [
                'Permission opname.view; opname.create untuk membuat dan opname.approve untuk menyetujui.',
            ],
            'steps' => [
                [
                    'text' => 'Buka Stok Opname dan tekan Buat Opname.',
                    'actions' => [
                        [
                            'route' => 'opnames.index',
                            'label' => 'Buka Stok Opname',
                            'icon' => 'fa-clipboard-check',
                            'tone' => 'info',
                            'permissions' => [
                                'opname.view',
                            ],
                        ],
                        [
                            'route' => 'opnames.create',
                            'label' => 'Buat Stok Opname',
                            'icon' => 'fa-clipboard-list',
                            'tone' => 'success',
                            'permissions' => [
                                'opname.create',
                            ],
                        ],
                    ],
                ],
                'Pilih gudang dan ruang lingkup barang.',
                'Masukkan jumlah fisik setiap barang atau batch.',
                'Simpan draft selama proses penghitungan.',
                'Periksa selisih, lalu submit opname.',
                'Pengguna dengan permission opname.approve memeriksa dan menyetujui.',
                [
                    'text' => 'Sesudah disetujui, sistem membuat penyesuaian stok otomatis.',
                    'actions' => [
                        [
                            'route' => 'opnames.index',
                            'label' => 'Buka Stok Opname',
                            'icon' => 'fa-clipboard-check',
                            'tone' => 'info',
                            'permissions' => [
                                'opname.view',
                            ],
                        ],
                        [
                            'route' => 'stocks.movements',
                            'label' => 'Buka Mutasi Stok',
                            'icon' => 'fa-arrow-right-arrow-left',
                            'tone' => 'primary',
                            'permissions' => [
                                'stock.view',
                            ],
                        ],
                    ],
                ],
            ],
            'tips' => [
                'Hentikan atau kendalikan transaksi gudang selama penghitungan agar selisih tidak berubah.',
                'Gunakan dua petugas untuk barang bernilai tinggi.',
            ],
            'warnings' => [
                [
                    'text' => 'Jangan membuat penyesuaian manual untuk selisih yang sama sebelum opname disetujui.',
                    'actions' => [
                        [
                            'route' => 'opnames.index',
                            'label' => 'Buka Stok Opname',
                            'icon' => 'fa-clipboard-check',
                            'tone' => 'info',
                            'permissions' => [
                                'opname.view',
                            ],
                        ],
                        [
                            'route' => 'stocks.adjustment',
                            'label' => 'Buka Penyesuaian Stok',
                            'icon' => 'fa-sliders',
                            'tone' => 'warning',
                            'permissions' => [
                                'stock.adjust',
                            ],
                        ],
                    ],
                ],
            ],
            'keywords' => [
                'opname',
                'stok fisik',
                'selisih',
                'persetujuan',
            ],
        ],
        [
            'slug' => 'retur-penjualan',
            'category' => 'Operasional',
            'title' => 'Retur Penjualan',
            'icon' => 'fa-arrow-rotate-left',
            'color' => 'red',
            'permissions' => [
                'sale_returns.create',
            ],
            'route' => 'sale-returns.index',
            'summary' => 'Menerima pengembalian pelanggan, mengembalikan stok, dan menyesuaikan pembayaran serta poin.',
            'prerequisites' => [
                'Permission sale_returns.create.',
                'Transaksi penjualan tersedia dan kuantitas belum seluruhnya diretur.',
            ],
            'steps' => [
                [
                    'text' => 'Buka detail penjualan lalu tekan Buat Retur atau buka daftar Retur Penjualan.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'sale-returns.index',
                            'label' => 'Buka Retur Penjualan',
                            'icon' => 'fa-rotate-left',
                            'tone' => 'danger',
                            'permissions' => [
                                'sale_returns.create',
                            ],
                        ],
                    ],
                ],
                'Pilih item dan jumlah yang dikembalikan.',
                'Pilih kondisi barang dan apakah stok layak dikembalikan.',
                'Isi metode refund atau penyesuaian piutang.',
                'Periksa total retur dan simpan.',
                [
                    'text' => 'Pastikan stok, pembayaran/piutang, dan poin pelanggan sudah disesuaikan.',
                    'actions' => [
                        [
                            'route' => 'sale-returns.index',
                            'label' => 'Buka Retur Penjualan',
                            'icon' => 'fa-rotate-left',
                            'tone' => 'danger',
                            'permissions' => [
                                'sale_returns.create',
                            ],
                        ],
                        [
                            'route' => 'reports.stocks',
                            'label' => 'Buka Laporan Stok',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'info',
                            'permissions' => [
                                'reports.stocks',
                            ],
                        ],
                        [
                            'route' => 'reports.debts',
                            'label' => 'Buka Utang & Piutang',
                            'icon' => 'fa-file-invoice-dollar',
                            'tone' => 'warning',
                            'permissions' => [
                                'reports.debts',
                            ],
                        ],
                        [
                            'route' => 'master.index',
                            'label' => 'Buka Pelanggan',
                            'icon' => 'fa-users',
                            'tone' => 'success',
                            'route_params' => [
                                'type' => 'customers',
                            ],
                            'permissions' => [
                                'customers.manage',
                                'sales.create',
                            ],
                        ],
                    ],
                ],
            ],
            'tips' => [
                'Gunakan alasan retur yang konsisten untuk analisis kualitas dan pelayanan.',
                'Poin yang dipakai dikembalikan secara proporsional; poin yang diperoleh dapat dikurangi.',
            ],
            'warnings' => [
                [
                    'text' => 'Jangan menghapus transaksi penjualan untuk menangani retur.',
                    'actions' => [
                        [
                            'route' => 'sale-returns.index',
                            'label' => 'Buka Retur Penjualan',
                            'icon' => 'fa-rotate-left',
                            'tone' => 'danger',
                            'permissions' => [
                                'sale_returns.create',
                            ],
                        ],
                    ],
                ],
            ],
            'keywords' => [
                'retur penjualan',
                'refund',
                'barang kembali',
                'poin',
            ],
        ],
        [
            'slug' => 'pengeluaran',
            'category' => 'Operasional',
            'title' => 'Pengeluaran Operasional',
            'icon' => 'fa-wallet',
            'color' => 'red',
            'permissions' => [
                'expenses.view',
            ],
            'route' => 'expenses.index',
            'summary' => 'Mencatat biaya operasional dan dampaknya pada kas serta laporan laba rugi.',
            'prerequisites' => [
                'Permission expenses.view; expenses.create/update untuk mengubah data.',
            ],
            'steps' => [
                'Buka Pengeluaran.',
                'Tekan Tambah Pengeluaran.',
                'Isi tanggal, kategori, nominal, metode pembayaran, penerima, dan catatan.',
                'Simpan pengeluaran.',
                [
                    'text' => 'Gunakan filter untuk memeriksa biaya berdasarkan periode dan kategori.',
                    'actions' => [
                        [
                            'route' => 'expenses.index',
                            'label' => 'Buka Pengeluaran',
                            'icon' => 'fa-money-bill-transfer',
                            'tone' => 'danger',
                            'permissions' => [
                                'expenses.view',
                            ],
                        ],
                        [
                            'route' => 'reports.operations',
                            'label' => 'Buka Laporan Operasional',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'reports.operations',
                            ],
                        ],
                    ],
                ],
            ],
            'tips' => [
                'Gunakan kategori yang konsisten seperti listrik, transportasi, konsumsi, atau perawatan.',
                'Simpan bukti transaksi di luar sistem bila aplikasi belum menyimpan lampiran.',
            ],
            'warnings' => [
                [
                    'text' => 'Pengeluaran tunai selama shift memengaruhi rekonsiliasi kas.',
                    'actions' => [
                        [
                            'route' => 'expenses.index',
                            'label' => 'Buka Pengeluaran',
                            'icon' => 'fa-money-bill-transfer',
                            'tone' => 'danger',
                            'permissions' => [
                                'expenses.view',
                            ],
                        ],
                        [
                            'route' => 'shifts.index',
                            'label' => 'Buka Shift Kasir',
                            'icon' => 'fa-clock',
                            'tone' => 'warning',
                            'permissions' => [
                                'shifts.manage',
                            ],
                        ],
                    ],
                ],
            ],
            'keywords' => [
                'pengeluaran',
                'biaya',
                'operasional',
                'kas',
                'laba rugi',
            ],
        ],
        [
            'slug' => 'promo',
            'category' => 'Operasional',
            'title' => 'Promo',
            'icon' => 'fa-percent',
            'color' => 'purple',
            'permissions' => [
                'promotions.manage',
            ],
            'route' => 'promotions.index',
            'summary' => 'Membuat diskon nominal atau persentase dengan periode, batas, dan target barang.',
            'prerequisites' => [
                'Permission promotions.manage.',
            ],
            'steps' => [
                'Buka Promo dan tekan Tambah Promo.',
                'Isi nama, kode, tipe diskon, dan nilai diskon.',
                'Atur tanggal mulai dan selesai.',
                'Atur minimum transaksi, maksimum diskon, atau target barang/kategori bila tersedia.',
                'Aktifkan promo dan simpan.',
                [
                    'text' => 'Uji promo pada POS dan pastikan total berubah ketika promo diganti.',
                    'actions' => [
                        [
                            'route' => 'promotions.index',
                            'label' => 'Buka Promo',
                            'icon' => 'fa-percent',
                            'tone' => 'purple',
                            'permissions' => [
                                'promotions.manage',
                            ],
                        ],
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
            ],
            'tips' => [
                'Gunakan nama promo yang mudah dikenali kasir.',
                'Pastikan periode promo menggunakan zona waktu Asia/Jakarta.',
            ],
            'warnings' => [
                'Hindari kombinasi promo yang menyebabkan harga negatif atau diskon berlebihan.',
            ],
            'keywords' => [
                'promo',
                'diskon',
                'voucher',
                'periode',
                'minimum transaksi',
            ],
        ],
        [
            'slug' => 'laporan',
            'category' => 'Laporan',
            'title' => 'Laporan Penjualan, Stok, Laba Rugi, Utang Piutang, dan Operasional',
            'icon' => 'fa-chart-line',
            'color' => 'indigo',
            'permissions' => [
                'reports.sales',
                'reports.stocks',
                'reports.profit',
                'reports.debts',
                'reports.operations',
            ],
            'route' => null,
            'summary' => 'Memfilter data, membaca indikator, dan mengekspor laporan PDF atau Excel sesuai permission.',
            'prerequisites' => [
                'Salah satu permission laporan.',
                'Permission reports.export untuk ekspor.',
            ],
            'steps' => [
                [
                    'text' => 'Pilih jenis laporan dari sidebar.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'reports.stocks',
                            'label' => 'Buka Laporan Stok',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'info',
                            'permissions' => [
                                'reports.stocks',
                            ],
                        ],
                        [
                            'route' => 'reports.profit',
                            'label' => 'Buka Laba Rugi',
                            'icon' => 'fa-coins',
                            'tone' => 'success',
                            'permissions' => [
                                'reports.profit',
                            ],
                        ],
                        [
                            'route' => 'reports.debts',
                            'label' => 'Buka Utang & Piutang',
                            'icon' => 'fa-file-invoice-dollar',
                            'tone' => 'warning',
                            'permissions' => [
                                'reports.debts',
                            ],
                        ],
                        [
                            'route' => 'reports.operations',
                            'label' => 'Buka Laporan Operasional',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'reports.operations',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Atur periode tanggal dan filter tambahan seperti status, metode, kasir, gudang, atau sumber.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'reports.stocks',
                            'label' => 'Buka Laporan Stok',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'info',
                            'permissions' => [
                                'reports.stocks',
                            ],
                        ],
                        [
                            'route' => 'reports.profit',
                            'label' => 'Buka Laba Rugi',
                            'icon' => 'fa-coins',
                            'tone' => 'success',
                            'permissions' => [
                                'reports.profit',
                            ],
                        ],
                        [
                            'route' => 'reports.debts',
                            'label' => 'Buka Utang & Piutang',
                            'icon' => 'fa-file-invoice-dollar',
                            'tone' => 'warning',
                            'permissions' => [
                                'reports.debts',
                            ],
                        ],
                        [
                            'route' => 'reports.operations',
                            'label' => 'Buka Laporan Operasional',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'reports.operations',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Baca kartu ringkasan sebelum melihat rincian tabel.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'reports.stocks',
                            'label' => 'Buka Laporan Stok',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'info',
                            'permissions' => [
                                'reports.stocks',
                            ],
                        ],
                        [
                            'route' => 'reports.profit',
                            'label' => 'Buka Laba Rugi',
                            'icon' => 'fa-coins',
                            'tone' => 'success',
                            'permissions' => [
                                'reports.profit',
                            ],
                        ],
                        [
                            'route' => 'reports.debts',
                            'label' => 'Buka Utang & Piutang',
                            'icon' => 'fa-file-invoice-dollar',
                            'tone' => 'warning',
                            'permissions' => [
                                'reports.debts',
                            ],
                        ],
                        [
                            'route' => 'reports.operations',
                            'label' => 'Buka Laporan Operasional',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'reports.operations',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Gunakan jumlah baris untuk mengatur kepadatan data.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'reports.stocks',
                            'label' => 'Buka Laporan Stok',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'info',
                            'permissions' => [
                                'reports.stocks',
                            ],
                        ],
                        [
                            'route' => 'reports.profit',
                            'label' => 'Buka Laba Rugi',
                            'icon' => 'fa-coins',
                            'tone' => 'success',
                            'permissions' => [
                                'reports.profit',
                            ],
                        ],
                        [
                            'route' => 'reports.debts',
                            'label' => 'Buka Utang & Piutang',
                            'icon' => 'fa-file-invoice-dollar',
                            'tone' => 'warning',
                            'permissions' => [
                                'reports.debts',
                            ],
                        ],
                        [
                            'route' => 'reports.operations',
                            'label' => 'Buka Laporan Operasional',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'reports.operations',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Tekan Excel atau PDF bila akun memiliki permission ekspor.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'reports.stocks',
                            'label' => 'Buka Laporan Stok',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'info',
                            'permissions' => [
                                'reports.stocks',
                            ],
                        ],
                        [
                            'route' => 'reports.profit',
                            'label' => 'Buka Laba Rugi',
                            'icon' => 'fa-coins',
                            'tone' => 'success',
                            'permissions' => [
                                'reports.profit',
                            ],
                        ],
                        [
                            'route' => 'reports.debts',
                            'label' => 'Buka Utang & Piutang',
                            'icon' => 'fa-file-invoice-dollar',
                            'tone' => 'warning',
                            'permissions' => [
                                'reports.debts',
                            ],
                        ],
                        [
                            'route' => 'reports.operations',
                            'label' => 'Buka Laporan Operasional',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'reports.operations',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Cocokkan laporan dengan shift, transaksi, dan bukti eksternal bila melakukan rekonsiliasi.',
                    'actions' => [
                        [
                            'route' => 'shifts.index',
                            'label' => 'Buka Shift Kasir',
                            'icon' => 'fa-clock',
                            'tone' => 'warning',
                            'permissions' => [
                                'shifts.manage',
                            ],
                        ],
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'reports.operations',
                            'label' => 'Buka Laporan Operasional',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'reports.operations',
                            ],
                        ],
                        [
                            'route' => 'activity.index',
                            'label' => 'Buka Activity Log',
                            'icon' => 'fa-clock-rotate-left',
                            'tone' => 'secondary',
                            'permissions' => [
                                'audit.view',
                            ],
                        ],
                    ],
                ],
            ],
            'tips' => [
                [
                    'text' => 'Laporan Penjualan memakai nilai setelah retur.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Laba rugi adalah estimasi berbasis biaya yang tersimpan di sistem.',
                    'actions' => [
                        [
                            'route' => 'reports.profit',
                            'label' => 'Buka Laba Rugi',
                            'icon' => 'fa-coins',
                            'tone' => 'success',
                            'permissions' => [
                                'reports.profit',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Laporan Operasional mempunyai tab berwarna untuk pembelian, mutasi, kedaluwarsa, shift, pengeluaran, retur, dan opname.',
                    'actions' => [
                        [
                            'route' => 'reports.operations',
                            'label' => 'Buka Laporan Operasional',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'reports.operations',
                            ],
                        ],
                    ],
                ],
            ],
            'warnings' => [
                [
                    'text' => 'Jangan menyimpulkan selisih hanya dari satu laporan. Periksa periode, status transaksi, retur, dan filter aktif.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'reports.stocks',
                            'label' => 'Buka Laporan Stok',
                            'icon' => 'fa-boxes-stacked',
                            'tone' => 'info',
                            'permissions' => [
                                'reports.stocks',
                            ],
                        ],
                        [
                            'route' => 'reports.profit',
                            'label' => 'Buka Laba Rugi',
                            'icon' => 'fa-coins',
                            'tone' => 'success',
                            'permissions' => [
                                'reports.profit',
                            ],
                        ],
                        [
                            'route' => 'reports.debts',
                            'label' => 'Buka Utang & Piutang',
                            'icon' => 'fa-file-invoice-dollar',
                            'tone' => 'warning',
                            'permissions' => [
                                'reports.debts',
                            ],
                        ],
                        [
                            'route' => 'reports.operations',
                            'label' => 'Buka Laporan Operasional',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'reports.operations',
                            ],
                        ],
                    ],
                ],
            ],
            'keywords' => [
                'laporan',
                'penjualan',
                'stok',
                'laba rugi',
                'utang',
                'piutang',
                'excel',
                'pdf',
            ],
        ],
        [
            'slug' => 'pengguna',
            'category' => 'Sistem',
            'title' => 'Pengguna',
            'icon' => 'fa-user-group',
            'color' => 'blue',
            'permissions' => [
                'users.manage',
            ],
            'route' => 'users.index',
            'summary' => 'Membuat akun, menetapkan role, mengaktifkan akun, dan memantau keamanan pengguna.',
            'prerequisites' => [
                'Permission users.manage.',
            ],
            'steps' => [
                'Buka Pengguna.',
                'Tekan Tambah Pengguna.',
                'Isi nama, email, password awal, role, dan status aktif.',
                'Simpan akun.',
                [
                    'text' => 'Minta pengguna mengganti password dan mengaktifkan 2FA.',
                    'actions' => [
                        [
                            'route' => 'users.index',
                            'label' => 'Buka Pengguna',
                            'icon' => 'fa-users',
                            'tone' => 'primary',
                            'permissions' => [
                                'users.manage',
                            ],
                        ],
                        [
                            'route' => 'account.security',
                            'label' => 'Buka Keamanan Akun',
                            'icon' => 'fa-shield-halved',
                            'tone' => 'info',
                        ],
                    ],
                ],
                'Nonaktifkan akun segera ketika petugas tidak lagi berwenang.',
            ],
            'tips' => [
                'Gunakan email unik untuk setiap akun.',
                [
                    'text' => 'Hindari akun bersama agar Activity Log tetap jelas.',
                    'actions' => [
                        [
                            'route' => 'activity.index',
                            'label' => 'Buka Activity Log',
                            'icon' => 'fa-clock-rotate-left',
                            'tone' => 'secondary',
                            'permissions' => [
                                'audit.view',
                            ],
                        ],
                    ],
                ],
            ],
            'warnings' => [
                'Jangan menghapus akun yang sudah mempunyai histori transaksi; nonaktifkan akun.',
            ],
            'keywords' => [
                'pengguna',
                'akun',
                'role',
                'aktif',
                '2fa',
            ],
        ],
        [
            'slug' => 'role-permission',
            'category' => 'Sistem',
            'title' => 'Role dan Permission',
            'icon' => 'fa-user-lock',
            'color' => 'purple',
            'permissions' => [
                'roles.manage',
            ],
            'route' => 'roles.index',
            'summary' => 'Menentukan menu, tindakan, laporan, dan panel dashboard yang dapat digunakan setiap role.',
            'prerequisites' => [
                'Permission roles.manage.',
            ],
            'steps' => [
                'Buka Role & Permission.',
                'Tambah role baru atau edit role yang tersedia.',
                'Pilih hanya permission yang diperlukan oleh tugas role.',
                'Periksa Pratinjau Dashboard Role untuk melihat panel yang akan tampil.',
                'Simpan role.',
                [
                    'text' => 'Uji menggunakan akun non-administrator sebelum digunakan operasional.',
                    'actions' => [
                        [
                            'route' => 'roles.index',
                            'label' => 'Buka Role & Permission',
                            'icon' => 'fa-user-shield',
                            'tone' => 'purple',
                            'permissions' => [
                                'roles.manage',
                            ],
                        ],
                        [
                            'route' => 'users.index',
                            'label' => 'Buka Pengguna',
                            'icon' => 'fa-users',
                            'tone' => 'primary',
                            'permissions' => [
                                'users.manage',
                            ],
                        ],
                    ],
                ],
            ],
            'tips' => [
                'Terapkan prinsip least privilege: berikan akses minimum yang diperlukan.',
                'Pisahkan permission melihat, membuat, membayar, menyetujui, dan mengekspor.',
            ],
            'warnings' => [
                [
                    'text' => 'Perubahan permission langsung memengaruhi menu dan akses. Jangan mencabut akses saat pengguna sedang menyimpan transaksi.',
                    'actions' => [
                        [
                            'route' => 'roles.index',
                            'label' => 'Buka Role & Permission',
                            'icon' => 'fa-user-shield',
                            'tone' => 'purple',
                            'permissions' => [
                                'roles.manage',
                            ],
                        ],
                        [
                            'route' => 'activity.index',
                            'label' => 'Buka Activity Log',
                            'icon' => 'fa-clock-rotate-left',
                            'tone' => 'secondary',
                            'permissions' => [
                                'audit.view',
                            ],
                        ],
                    ],
                ],
            ],
            'keywords' => [
                'role',
                'permission',
                'hak akses',
                'dashboard',
                'least privilege',
            ],
        ],
        [
            'slug' => 'pengaturan',
            'category' => 'Sistem',
            'title' => 'Pengaturan POS',
            'icon' => 'fa-gear',
            'color' => 'gold',
            'permissions' => [
                'settings.manage',
            ],
            'route' => 'settings.edit',
            'summary' => 'Mengatur identitas toko, logo, halaman login, struk, stok, poin, ukuran font, tema, mode gelap, dan gaya visual.',
            'prerequisites' => [
                'Permission settings.manage.',
            ],
            'steps' => [
                'Buka Pengaturan POS.',
                'Atur nama toko, telepon, email, alamat, logo, kicker, slogan, dan teks halaman login.',
                'Atur footer struk dan peringatan kedaluwarsa.',
                'Aktifkan stok minus hanya bila kebijakan toko benar-benar mengizinkan.',
                'Atur poin pelanggan: aktif/nonaktif, nominal per 1 poin, nilai tukar, dan batas maksimal penukaran.',
                'Pilih ukuran font kecil, sedang, atau besar.',
                'Pilih tema warna, mode terang/gelap, lalu tentukan gaya visual Biasa, Matte, atau Glossy.',
                'Simpan seluruh pengaturan dan lakukan refresh browser.',
            ],
            'tips' => [
                'Gunakan logo rasio persegi agar tampil baik di sidebar dan halaman login.',
                [
                    'text' => 'Gunakan APP_URL yang sesuai agar URL logo dan aset benar.',
                    'actions' => [
                        [
                            'manual_slug' => 'troubleshooting',
                            'label' => 'Buka Troubleshooting',
                            'icon' => 'fa-screwdriver-wrench',
                            'tone' => 'secondary',
                        ],
                    ],
                    'context' => '.env / server',
                ],
            ],
            'warnings' => [
                'Perubahan stok minus dan poin berdampak pada transaksi baru. Dokumentasikan kebijakan sebelum mengaktifkannya.',
            ],
            'keywords' => [
                'pengaturan',
                'logo',
                'tema',
                'dark mode',
                'font',
                'struk',
                'poin',
                'stok minus',
                'matte',
                'glossy',
                'gaya visual',
            ],
        ],
        [
            'slug' => 'activity-log',
            'category' => 'Sistem',
            'title' => 'Activity Log',
            'icon' => 'fa-clipboard-list',
            'color' => 'teal',
            'permissions' => [
                'audit.view',
            ],
            'route' => 'activity.index',
            'summary' => 'Menelusuri aktivitas pengguna, subjek, perubahan properti, IP, perangkat, dan waktu kejadian.',
            'prerequisites' => [
                'Permission audit.view.',
            ],
            'steps' => [
                'Buka Activity Log.',
                'Gunakan pencarian untuk aksi, pengguna, email, IP, subjek, atau ID.',
                'Gunakan filter modul, aksi, pengguna, dan tanggal.',
                'Buka rincian perubahan bila tersedia.',
                'Cetak atau simpan hasil pemeriksaan sesuai kebutuhan audit.',
            ],
            'tips' => [
                [
                    'text' => 'Gunakan Activity Log sebagai pendukung, lalu cocokkan dengan dokumen transaksi terkait.',
                    'actions' => [
                        [
                            'route' => 'reports.sales',
                            'label' => 'Buka Laporan Penjualan',
                            'icon' => 'fa-chart-line',
                            'tone' => 'primary',
                            'permissions' => [
                                'reports.sales',
                            ],
                        ],
                        [
                            'route' => 'purchases.index',
                            'label' => 'Buka Pembelian',
                            'icon' => 'fa-cart-shopping',
                            'tone' => 'primary',
                            'permissions' => [
                                'purchases.view',
                            ],
                        ],
                        [
                            'route' => 'stocks.movements',
                            'label' => 'Buka Mutasi Stok',
                            'icon' => 'fa-arrow-right-arrow-left',
                            'tone' => 'primary',
                            'permissions' => [
                                'stock.view',
                            ],
                        ],
                    ],
                ],
                'Pertahankan waktu server yang benar.',
            ],
            'warnings' => [
                'Activity Log tidak menggantikan backup database dan log server.',
            ],
            'keywords' => [
                'activity log',
                'audit',
                'ip',
                'perangkat',
                'perubahan',
            ],
        ],
        [
            'slug' => 'akun-keamanan',
            'category' => 'Akun',
            'title' => 'Profil, Password, dan 2FA',
            'icon' => 'fa-shield-halved',
            'color' => 'green',
            'permissions' => [],
            'route' => 'account.security',
            'summary' => 'Mengubah profil, password, dan autentikasi dua faktor untuk melindungi akun.',
            'prerequisites' => [
                'Sudah login.',
            ],
            'steps' => [
                'Buka Profil, Password & 2FA.',
                'Perbarui nama dan email bila diizinkan.',
                'Ganti password dengan kombinasi panjang dan unik.',
                'Aktifkan 2FA, pindai QR menggunakan aplikasi authenticator, lalu konfirmasi kode.',
                'Simpan recovery codes di tempat aman.',
                'Gunakan logout perangkat lain bila akun diduga digunakan tanpa izin.',
            ],
            'tips' => [
                'Gunakan password manager.',
                'Simpan recovery code di luar komputer kasir.',
            ],
            'warnings' => [
                'Jangan memotret QR 2FA atau recovery code menggunakan perangkat yang dapat diakses orang lain.',
            ],
            'keywords' => [
                'profil',
                'password',
                '2fa',
                'recovery code',
                'keamanan',
            ],
        ],
        [
            'slug' => 'troubleshooting',
            'category' => 'Bantuan',
            'title' => 'Masalah Umum dan Penyelesaian',
            'icon' => 'fa-screwdriver-wrench',
            'color' => 'orange',
            'permissions' => [],
            'route' => null,
            'summary' => 'Langkah pemeriksaan ketika tampilan, aset, storage, database, printer, atau transaksi tidak berjalan sesuai harapan.',
            'prerequisites' => [],
            'steps' => [
                [
                    'text' => 'Tampilan lama setelah patch: jalankan npm run build, php artisan optimize:clear, lalu Ctrl+F5.',
                    'actions' => [
                        [
                            'manual_slug' => 'troubleshooting',
                            'label' => 'Buka Troubleshooting',
                            'icon' => 'fa-screwdriver-wrench',
                            'tone' => 'secondary',
                        ],
                    ],
                    'context' => 'Terminal + browser',
                ],
                [
                    'text' => 'Logo 404 saat artisan serve: pastikan APP_URL=http://127.0.0.1:8000 dan php artisan storage:link sudah dijalankan.',
                    'actions' => [
                        [
                            'route' => 'settings.edit',
                            'label' => 'Buka Pengaturan POS',
                            'icon' => 'fa-gears',
                            'tone' => 'purple',
                            'permissions' => [
                                'settings.manage',
                            ],
                        ],
                        [
                            'manual_slug' => 'troubleshooting',
                            'label' => 'Buka Troubleshooting',
                            'icon' => 'fa-screwdriver-wrench',
                            'tone' => 'secondary',
                        ],
                    ],
                    'context' => '.env + terminal',
                ],
                [
                    'text' => 'Perubahan .env tidak terbaca: jalankan php artisan config:clear atau optimize:clear, lalu restart server.',
                    'actions' => [
                        [
                            'manual_slug' => 'troubleshooting',
                            'label' => 'Buka Troubleshooting',
                            'icon' => 'fa-screwdriver-wrench',
                            'tone' => 'secondary',
                        ],
                    ],
                    'context' => 'Terminal / server',
                ],
                [
                    'text' => 'Tombol tidak bekerja: buka Console browser dan periksa error CSP atau JavaScript.',
                    'actions' => [
                        [
                            'manual_slug' => 'troubleshooting',
                            'label' => 'Buka Troubleshooting',
                            'icon' => 'fa-screwdriver-wrench',
                            'tone' => 'secondary',
                        ],
                    ],
                    'context' => 'Console browser',
                ],
                [
                    'text' => 'Migration belum masuk: jalankan php artisan migrate dan php artisan migrate:status.',
                    'actions' => [
                        [
                            'manual_slug' => 'troubleshooting',
                            'label' => 'Buka Troubleshooting',
                            'icon' => 'fa-screwdriver-wrench',
                            'tone' => 'secondary',
                        ],
                    ],
                    'context' => 'Terminal / migration',
                ],
                [
                    'text' => 'Transaksi tidak dapat disimpan: periksa shift, stok, permission, nilai pembayaran, dan pesan validasi.',
                    'actions' => [
                        [
                            'route' => 'shifts.index',
                            'label' => 'Buka Shift Kasir',
                            'icon' => 'fa-clock',
                            'tone' => 'warning',
                            'permissions' => [
                                'shifts.manage',
                            ],
                        ],
                        [
                            'route' => 'stocks.movements',
                            'label' => 'Buka Mutasi Stok',
                            'icon' => 'fa-arrow-right-arrow-left',
                            'tone' => 'primary',
                            'permissions' => [
                                'stock.view',
                            ],
                        ],
                        [
                            'route' => 'roles.index',
                            'label' => 'Buka Role & Permission',
                            'icon' => 'fa-user-shield',
                            'tone' => 'purple',
                            'permissions' => [
                                'roles.manage',
                            ],
                        ],
                        [
                            'route' => 'sales.pos',
                            'label' => 'Buka POS Penjualan',
                            'icon' => 'fa-cash-register',
                            'tone' => 'success',
                            'permissions' => [
                                'sales.create',
                            ],
                        ],
                    ],
                ],
                [
                    'text' => 'Printer 80 mm tidak tersedia: tambahkan ukuran kertas pada driver printer Windows.',
                    'actions' => [
                        [
                            'manual_slug' => 'struk',
                            'label' => 'Buka Panduan Struk',
                            'icon' => 'fa-receipt',
                            'tone' => 'secondary',
                        ],
                    ],
                    'context' => 'Driver printer Windows',
                ],
                [
                    'text' => 'Queue tidak berjalan: jalankan php artisan queue:work untuk pekerjaan yang menggunakan antrean.',
                    'actions' => [
                        [
                            'manual_slug' => 'troubleshooting',
                            'label' => 'Buka Troubleshooting',
                            'icon' => 'fa-screwdriver-wrench',
                            'tone' => 'secondary',
                        ],
                    ],
                    'context' => 'Terminal / queue',
                ],
            ],
            'tips' => [
                'Catat pesan error lengkap, URL halaman, waktu kejadian, dan langkah sebelum error.',
                [
                    'text' => 'Backup database sebelum perbaikan besar.',
                    'actions' => [
                        [
                            'manual_slug' => 'troubleshooting',
                            'label' => 'Buka Troubleshooting',
                            'icon' => 'fa-screwdriver-wrench',
                            'tone' => 'secondary',
                        ],
                    ],
                    'context' => 'Backup database',
                ],
            ],
            'warnings' => [
                [
                    'text' => 'Jangan menjalankan migrate:fresh pada database produksi karena menghapus seluruh tabel.',
                    'actions' => [
                        [
                            'manual_slug' => 'troubleshooting',
                            'label' => 'Buka Troubleshooting',
                            'icon' => 'fa-screwdriver-wrench',
                            'tone' => 'secondary',
                        ],
                    ],
                    'context' => 'Terminal / database',
                ],
                [
                    'text' => 'Jangan mengganti APP_KEY pada aplikasi yang sudah digunakan.',
                    'actions' => [
                        [
                            'manual_slug' => 'troubleshooting',
                            'label' => 'Buka Troubleshooting',
                            'icon' => 'fa-screwdriver-wrench',
                            'tone' => 'secondary',
                        ],
                    ],
                    'context' => '.env / enkripsi',
                ],
            ],
            'keywords' => [
                'error',
                'build',
                'cache',
                'storage link',
                'migration',
                'printer',
                'queue',
            ],
        ],
    ],
];


if (! env('POS_TWO_FACTOR_ENABLED', false)) {
    foreach ($manual['sections'] as &$section) {
        if (($section['slug'] ?? '') === 'setup-awal-owner') {
            $section['steps'][0]['text'] =
                'Amankan akun owner. Buka Profil & Password, ganti '
                .'password awal, dan pastikan nama serta email akun benar.';

            $section['steps'][0]['actions'] = array_values(
                array_filter(
                    $section['steps'][0]['actions'] ?? [],
                    static fn (array $action): bool =>
                        ($action['fragment'] ?? null) !== 'account-2fa'
                )
            );

            $section['steps'][19]['text'] =
                'Lakukan backup database, catat akun owner secara aman, '
                .'lalu mulai operasional dengan petugas memakai akun '
                .'masing-masing.';
        }

        if (($section['slug'] ?? '') === 'mulai') {
            $section['steps'][4]['text'] =
                'Buka Profil & Password untuk mengganti identitas akun '
                .'dan password.';

            $section['tips'] = array_values(array_filter(
                $section['tips'] ?? [],
                static function (array|string $item): bool {
                    $text = is_array($item)
                        ? ($item['text'] ?? '')
                        : $item;

                    return ! str_contains(
                        mb_strtolower($text),
                        '2fa'
                    );
                }
            ));

            $section['keywords'] = array_values(array_filter(
                $section['keywords'] ?? [],
                static fn (string $keyword): bool =>
                    mb_strtolower($keyword) !== '2fa'
            ));
        }

        if (($section['slug'] ?? '') === 'pengguna') {
            $section['steps'][4]['text'] =
                'Minta pengguna mengganti password awal setelah login '
                .'pertama.';

            $section['keywords'] = array_values(array_filter(
                $section['keywords'] ?? [],
                static fn (string $keyword): bool =>
                    mb_strtolower($keyword) !== '2fa'
            ));
        }

        if (($section['slug'] ?? '') === 'akun-keamanan') {
            $section['title'] = 'Profil & Password';
            $section['summary'] =
                'Mengelola identitas akun, email, dan password pengguna '
                .'yang sedang login.';

            $section['steps'] = array_slice(
                $section['steps'] ?? [],
                0,
                3
            );

            $section['tips'] = [
                'Gunakan password berbeda untuk setiap akun.',
                'Jangan membagikan akun kepada pengguna lain.',
            ];

            $section['warnings'] = [
                'Perubahan email mengharuskan verifikasi ulang.',
                'Mengganti password akan mengeluarkan sesi perangkat lain.',
            ];

            $section['keywords'] = [
                'profil',
                'password',
                'email',
                'keamanan akun',
            ];
        }
    }

    unset($section);
}

return $manual;
