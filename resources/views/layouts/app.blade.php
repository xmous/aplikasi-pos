@php
    $appBranding = \App\Support\PosBranding::resolve();
    $appThemeStyle = \App\Support\PosBranding::cssVariables(
        $appBranding['theme']
    );
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="ga-font-{{ $appBranding['font_scale'] }} ga-appearance-{{ $appBranding['appearance_mode'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'POS & Stok' }} · {{ $appBranding['store_name'] }}</title>
    <link rel="icon" href="{{ $appBranding['logo_url'] }}">
    <link rel="shortcut icon" href="{{ $appBranding['logo_url'] }}">
    <link rel="apple-touch-icon" href="{{ $appBranding['logo_url'] }}">
    <meta name="theme-color" content="{{ $appBranding['theme']['navy'] }}">
    @vite('resources/js/app.js')
    @livewireStyles
</head>
<body style="{{ $appThemeStyle }}">
    <main class="container-fluid p-3 p-lg-4">
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
