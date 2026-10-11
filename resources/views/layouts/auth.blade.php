@php
    /*
     * View turunan Blade dirender sebelum layout. Gunakan nilai yang sudah
     * disiapkan halaman login, lalu fallback untuk halaman auth lainnya.
     */
    $authBranding = $authBranding
        ?? \App\Support\PosBranding::resolve();
    $authThemeStyle = \App\Support\PosBranding::cssVariables(
        $authBranding['theme']
    );
@endphp
<!doctype html>
<html lang="id"
    class="ga-font-{{ $authBranding['font_scale'] }} ga-appearance-{{ $authBranding['appearance_mode'] }} ga-finish-{{ $authBranding['surface_finish'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Masuk') · {{ $authBranding['store_name'] }}</title>
    <link rel="icon" href="{{ $authBranding['logo_url'] }}">
    <link rel="shortcut icon" href="{{ $authBranding['logo_url'] }}">
    <link rel="apple-touch-icon" href="{{ $authBranding['logo_url'] }}">
    <meta name="theme-color" content="{{ $authBranding['theme']['navy'] }}">
    @vite('resources/js/app.js')
</head>
<body class="auth-body" style="{{ $authThemeStyle }}">
<main class="auth-shell">
    <section class="auth-showcase">
        <div class="auth-grid-pattern"></div>
        <div class="auth-orb auth-orb-top"></div>
        <div class="auth-orb auth-orb-bottom"></div>
        <div class="auth-showcase-content">
            <span class="auth-brand-mark">
                <img
                    src="{{ $authBranding['logo_url'] }}"
                    alt="Logo {{ $authBranding['store_name'] }}"
                >
            </span>
            <span class="auth-kicker">
                {{ $authBranding['brand_kicker'] }}
            </span>
            <h1>{{ $authBranding['login_hero_title'] }}</h1>
            <p>{{ $authBranding['login_hero_description'] }}</p>
            <div class="auth-benefits">
                <span>
                    <i class="fa-solid fa-circle"></i>
                    {{ $authBranding['login_benefit_1'] }}
                </span>
                <span>
                    <i class="fa-solid fa-circle"></i>
                    {{ $authBranding['login_benefit_2'] }}
                </span>
                <span>
                    <i class="fa-solid fa-circle"></i>
                    {{ $authBranding['login_benefit_3'] }}
                </span>
            </div>
        </div>
    </section>
    <section class="auth-form-panel">
        <div class="auth-form-wrap">
            <div
                data-flash-message
                data-success="{{ session('status') === 'verification-link-sent' ? '' : session('status') }}"
                data-error='@json($errors->all())'
            ></div>
            @yield('content')
            <div class="auth-secure">
                <i class="fa-solid fa-shield-halved"></i>
                {{ $authBranding['login_security_text'] }}
            </div>
        </div>
        <small class="auth-copyright">
            © {{ date('Y') }} {{ $authBranding['store_name'] }}
        </small>
    </section>
</main>
</body>
</html>
