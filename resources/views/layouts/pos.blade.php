@php
    $posBranding = \App\Support\PosBranding::resolve();
    $posThemeStyle = \App\Support\PosBranding::cssVariables(
        $posBranding['theme']
    );
    $storeName = trim(
        (string) ($posBranding['store_name'] ?? 'POS & Stok')
    );
    $storeTagline = trim(
        (string) ($posBranding['brand_tagline'] ?? '')
    );
    $storeNameIsLong = mb_strlen($storeName) > 22;
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="ga-font-{{ $posBranding['font_scale'] }} ga-appearance-{{ $posBranding['appearance_mode'] }} ga-finish-{{ $posBranding['surface_finish'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'POS & Stok') · {{ $storeName }}</title>
    <link rel="icon" href="{{ $posBranding['logo_url'] }}">
    <link rel="shortcut icon" href="{{ $posBranding['logo_url'] }}">
    <link rel="apple-touch-icon" href="{{ $posBranding['logo_url'] }}">
    <meta name="theme-color" content="{{ $posBranding['theme']['navy'] }}">
    @vite('resources/js/app.js')
    @livewireStyles
    @stack('styles')
</head>
<body
    class="pos-app @yield('body_class')"
    style="{{ $posThemeStyle }}"
    data-brand-kicker="{{ $posBranding['brand_kicker'] }}"
>
@auth
<aside class="pos-sidebar p-3 no-print" data-pos-sidebar>
    <div
        class="pos-brand {{ $storeNameIsLong ? 'is-long' : '' }}"
        title="{{ $storeName }}"
    >
        <img
            src="{{ $posBranding['logo_url'] }}"
            alt="Logo {{ $storeName }}"
        >
        <div class="pos-brand-copy">
            <strong>{{ $storeName }}</strong>
            @if($storeTagline !== '')
                <small>{{ $storeTagline }}</small>
            @endif
        </div>
    </div>
    <nav>
        @can('dashboard.view')
            <a wire:navigate class="pos-nav" href="{{ route('pos.dashboard') }}"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i><span>Dashboard</span></a>
            <a wire:navigate class="pos-nav" href="{{ route('reports.analytics') }}"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i><span>Ringkasan</span></a>
        @endcan
        @can('sales.create')
            <div class="section">Kasir</div>
            <a wire:navigate class="pos-nav" href="{{ route('sales.pos') }}"><i class="fa-solid fa-cash-register" aria-hidden="true"></i><span>POS Penjualan</span></a>
        @endcan
        @can('shifts.manage')<a wire:navigate class="pos-nav" href="{{ route('shifts.index') }}"><i class="fa-solid fa-clock" aria-hidden="true"></i><span>Shift Kasir</span></a>@endcan

        @canany(['products.view','categories.manage','units.manage','suppliers.manage','customers.manage','warehouses.manage','purchases.view'])
            <div class="section">Master & Pembelian</div>
        @endcanany
        @can('products.view')<a wire:navigate class="pos-nav" href="{{ route('products.index') }}"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i><span>Barang & Konversi</span></a>@endcan
        @can('categories.manage')<a wire:navigate class="pos-nav" href="{{ route('master.index','categories') }}"><i class="fa-solid fa-tags" aria-hidden="true"></i><span>Kategori</span></a>@endcan
        @can('units.manage')<a wire:navigate class="pos-nav" href="{{ route('master.index','units') }}"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i><span>Satuan</span></a>@endcan
        @can('suppliers.manage')<a wire:navigate class="pos-nav" href="{{ route('master.index','suppliers') }}"><i class="fa-solid fa-truck-field" aria-hidden="true"></i><span>Supplier</span></a>@endcan
        @can('customers.manage')<a wire:navigate class="pos-nav" href="{{ route('master.index','customers') }}"><i class="fa-solid fa-users" aria-hidden="true"></i><span>Pelanggan</span></a>@endcan
        @can('warehouses.manage')<a wire:navigate class="pos-nav" href="{{ route('master.index','warehouses') }}"><i class="fa-solid fa-warehouse" aria-hidden="true"></i><span>Gudang</span></a>@endcan
        @can('purchases.view')<a wire:navigate class="pos-nav" href="{{ route('purchases.index') }}"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i><span>Pembelian</span></a>@endcan

        @canany(['stock.view','stock.adjust','opname.view','purchase_returns.create'])
            <div class="section">Persediaan</div>
        @endcanany
        @can('stock.view')
            <a wire:navigate class="pos-nav" href="{{ route('stocks.movements') }}"><i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i><span>Mutasi Stok</span></a>
            <a wire:navigate class="pos-nav" href="{{ route('stocks.batches') }}"><i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i><span>Batch & Kedaluwarsa</span></a>
        @endcan
        @can('stock.adjust')<a wire:navigate class="pos-nav" href="{{ route('stocks.adjustment') }}"><i class="fa-solid fa-sliders" aria-hidden="true"></i><span>Penyesuaian Stok</span></a>@endcan
        @can('opname.view')<a wire:navigate class="pos-nav" href="{{ route('opnames.index') }}"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i><span>Stok Opname</span></a>@endcan
        @can('purchase_returns.create')<a wire:navigate class="pos-nav" href="{{ route('purchase-returns.index') }}"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i><span>Retur Pembelian</span></a>@endcan

        @canany(['sale_returns.create','expenses.view','promotions.manage'])<div class="section">Operasional</div>@endcanany
        @can('sale_returns.create')<a wire:navigate class="pos-nav" href="{{ route('sale-returns.index') }}"><i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i><span>Retur Penjualan</span></a>@endcan
        @can('expenses.view')<a wire:navigate class="pos-nav" href="{{ route('expenses.index') }}"><i class="fa-solid fa-wallet" aria-hidden="true"></i><span>Pengeluaran</span></a>@endcan
        @can('promotions.manage')<a wire:navigate class="pos-nav" href="{{ route('promotions.index') }}"><i class="fa-solid fa-percent" aria-hidden="true"></i><span>Promo</span></a>@endcan

        @canany(['reports.sales','reports.stocks','reports.profit','reports.debts','reports.operations'])<div class="section">Laporan</div>@endcanany
        @can('reports.sales')<a wire:navigate class="pos-nav" href="{{ route('reports.sales') }}"><i class="fa-solid fa-chart-line" aria-hidden="true"></i><span>Penjualan</span></a>@endcan
        @can('reports.stocks')<a wire:navigate class="pos-nav" href="{{ route('reports.stocks') }}"><i class="fa-solid fa-cubes" aria-hidden="true"></i><span>Stok</span></a>@endcan
        @can('reports.profit')<a wire:navigate class="pos-nav" href="{{ route('reports.profit') }}"><i class="fa-solid fa-coins" aria-hidden="true"></i><span>Laba Rugi</span></a>@endcan
        @can('reports.debts')<a wire:navigate class="pos-nav" href="{{ route('reports.debts') }}"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i><span>Utang & Piutang</span></a>@endcan
        @can('reports.operations')<a wire:navigate class="pos-nav" href="{{ route('reports.operations') }}"><i class="fa-solid fa-gears" aria-hidden="true"></i><span>Operasional</span></a>@endcan

        @canany(['users.manage','roles.manage','settings.manage','audit.view'])<div class="section">Sistem</div>@endcanany
        @can('users.manage')<a wire:navigate class="pos-nav" href="{{ route('users.index') }}"><i class="fa-solid fa-user-group" aria-hidden="true"></i><span>Pengguna</span></a>@endcan
        @can('roles.manage')<a wire:navigate class="pos-nav" href="{{ route('roles.index') }}"><i class="fa-solid fa-user-lock" aria-hidden="true"></i><span>Role & Permission</span></a>@endcan
        @can('settings.manage')<a wire:navigate class="pos-nav" href="{{ route('settings.edit') }}"><i class="fa-solid fa-gear" aria-hidden="true"></i><span>Pengaturan POS</span></a>@endcan
        @can('audit.view')<a wire:navigate class="pos-nav" href="{{ route('activity.index') }}"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i><span>Activity Log</span></a>@endcan
        <div class="section">Panduan</div>
        <a wire:navigate class="pos-nav" href="{{ route('manual.index') }}"><i class="fa-solid fa-book-open" aria-hidden="true"></i><span>Panduan Pengguna</span></a>
        <div class="section">Akun</div>
        <a wire:navigate class="pos-nav" href="{{ route('account.security') }}">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            <span>
                Profil & Password{{ config('pos-features.two_factor_enabled', false) ? ' & 2FA' : '' }}
            </span>
        </a>
    </nav>
</aside>
<div class="pos-backdrop" data-pos-backdrop></div>
@endauth

<main class="@auth pos-content @endauth">
    @auth
    <div class="pos-topbar no-print sticky-top">
        <button
            type="button"
            class="pos-menu-toggle"
            data-pos-toggle
            aria-label="Buka atau sembunyikan menu"
        >
            <i class="fa-solid fa-bars"></i>
        </button>
        <div
            class="pos-topbar-brand {{ $storeNameIsLong ? 'is-long' : '' }}"
            title="{{ $storeName }}"
        >
            <img
                src="{{ $posBranding['logo_url'] }}"
                alt=""
                aria-hidden="true"
            >
            <div>
                @if($storeTagline !== '')
                    <small>{{ $storeTagline }}</small>
                @endif
                <strong>{{ $storeName }}</strong>
            </div>
        </div>
        <div class="ms-auto dropdown">
            <button
                type="button"
                class="pos-user-trigger"
                data-pos-user-toggle
                aria-expanded="false"
            >
                <span class="pos-user-copy">
                    <strong>{{ auth()->user()->name }}</strong>
                    <span>
                        <b>{{ auth()->user()->getRoleNames()->join(', ') ?: 'Pengguna' }}</b>
                        <small>{{ auth()->user()->email }}</small>
                    </span>
                </span>
                <img
                    src="{{ $posBranding['logo_url'] }}"
                    alt="Menu akun"
                >
            </button>
            <div
                class="dropdown-menu dropdown-menu-end pos-user-menu"
                data-pos-user-menu
            >
                <div class="pos-user-menu-head">
                    <img
                        src="{{ $posBranding['logo_url'] }}"
                        alt="Logo {{ $storeName }}"
                    >
                    <div>
                        <strong>{{ auth()->user()->name }}</strong>
                        <b>{{ auth()->user()->getRoleNames()->join(', ') ?: 'Pengguna' }}</b>
                        <small>{{ auth()->user()->email }}</small>
                    </div>
                </div>
                <a
                    wire:navigate
                    class="dropdown-item"
                    href="{{ route('account.security') }}"
                >
                    <i class="fa-solid fa-user-shield"></i>
                    Profil &amp; Keamanan
                </a>
                <form
                    method="post"
                    action="{{ route('logout') }}"
                    data-confirm="Yakin ingin keluar dari aplikasi?"
                >
                    @csrf
                    <button class="dropdown-item text-danger">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endauth

    <div class="p-4 pos-page">
        <div
            data-flash-message
            data-success="{{ session('success') }}"
            data-warning="{{ session('warning') }}"
            data-error='@json($errors->all())'
        ></div>
        @yield('content')
    </div>
</main>

@livewireScripts
@stack('scripts')
</body>
</html>
