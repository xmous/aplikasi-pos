@extends('layouts.pos')
@section('title', 'Pengaturan POS')
@section('body_class', 'pos-settings-page')

@php
    $store = $branding['store'];
    $themeKey = old('theme_preset', $branding['theme_key']);
    $fontScale = old('font_scale', $branding['font_scale']);
    $appearanceMode = old(
        'appearance_mode',
        $branding['appearance_mode']
    );
    $surfaceFinish = old(
        'surface_finish',
        $branding['surface_finish']
    );
    $themesEncoded = base64_encode(json_encode(
        $branding['themes'],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ));
@endphp

@section('content')
<div class="ga-page-head admin-page-head settings-page-head">
    <div class="ga-page-title">
        <span class="ga-eyebrow">
            <i class="fa-solid fa-palette"></i>
            IDENTITAS & TAMPILAN APLIKASI
        </span>
        <h2>Pengaturan POS</h2>
        <p class="mb-0">
            Ubah informasi toko, logo dan icon tab, teks identitas,
            halaman login, ukuran font, 9 tema warna, mode gelap,
            gaya visual, struk, kebijakan persediaan, dan poin pelanggan.
        </p>
    </div>

    <button
        type="submit"
        form="settings-form"
        class="btn btn-warning admin-primary-action"
    >
        <i class="fa-solid fa-floppy-disk"></i>
        Simpan Pengaturan
    </button>
</div>

<form
    method="post"
    action="{{ route('settings.update') }}"
    enctype="multipart/form-data"
    id="settings-form"
    class="settings-form"
    data-brand-settings
    data-themes="{{ $themesEncoded }}"
    data-default-logo="/images/general-affair-logo.png"
>
    @csrf
    @method('PUT')

    <section class="settings-brand-preview">
        <div class="settings-preview-sidebar">
            <div class="settings-preview-brand">
                <img
                    src="{{ $branding['logo_url'] }}"
                    alt="Logo {{ $branding['store_name'] }}"
                    data-brand-preview-logo
                >
                <div>
                    <strong data-brand-preview-name>
                        {{ old('name', $branding['store_name']) }}
                    </strong>
                    <small data-brand-preview-tagline>
                        {{ old('brand_tagline', $branding['brand_tagline']) }}
                    </small>
                </div>
            </div>
        </div>

        <div class="settings-preview-header">
            <span data-brand-preview-kicker>
                <i class="fa-solid fa-bolt"></i>
                {{ old('brand_kicker', $branding['brand_kicker']) }}
            </span>
            <strong>POS Kasir</strong>
            <small>Pratinjau identitas dan tema aplikasi.</small>
        </div>
    </section>

    <div class="settings-layout">
        <div class="settings-main-column">
            <section id="settings-identitas" class="settings-card ga-anchor-section">
                <header>
                    <span><i class="fa-solid fa-store"></i></span>
                    <div>
                        <strong>Identitas Toko</strong>
                        <small>
                            Digunakan pada sidebar, dokumen, struk, dan laporan.
                        </small>
                    </div>
                </header>

                <div class="row g-3">
                    <div class="col-lg-5">
                        <label class="form-label">Logo Aplikasi</label>
                        <div class="settings-logo-editor">
                            <div class="settings-logo-frame">
                                <img
                                    src="{{ $branding['logo_url'] }}"
                                    alt="Logo saat ini"
                                    data-brand-logo-editor
                                >
                            </div>
                            <div>
                                <label class="btn btn-outline-primary settings-logo-button">
                                    <i class="fa-solid fa-upload"></i>
                                    Pilih Logo
                                    <input
                                        name="logo"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        class="d-none"
                                        data-brand-logo-input
                                    >
                                </label>
                                <small>
                                    PNG, JPG, atau WEBP. Maksimal 2 MB dan 1600 × 1600 px.
                                    Logo yang sama juga digunakan sebagai icon tab browser.
                                </small>

                                @if($store?->logo_path)
                                    <input type="hidden" name="remove_logo" value="0">
                                    <label class="settings-remove-logo">
                                        <input
                                            name="remove_logo"
                                            value="1"
                                            type="checkbox"
                                            class="form-check-input"
                                            data-brand-remove-logo
                                        >
                                        <span>Hapus logo khusus dan gunakan logo bawaan</span>
                                    </label>
                                @endif

                                @error('logo')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nama Toko / Aplikasi <b>*</b></label>
                                <input
                                    name="name"
                                    value="{{ old('name', $store?->name) }}"
                                    class="form-control"
                                    maxlength="255"
                                    required
                                    data-brand-name-input
                                    placeholder="Nama toko atau unit kerja"
                                >
                                @error('name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Telepon</label>
                                <input
                                    name="phone"
                                    value="{{ old('phone', $store?->phone) }}"
                                    class="form-control"
                                    maxlength="40"
                                    placeholder="Nomor telepon"
                                >
                                @error('phone')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input
                                    name="email"
                                    type="email"
                                    value="{{ old('email', $store?->email) }}"
                                    class="form-control"
                                    maxlength="255"
                                    placeholder="email@perusahaan.com"
                                >
                                @error('email')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea
                                    name="address"
                                    rows="3"
                                    maxlength="2000"
                                    class="form-control"
                                    placeholder="Alamat toko atau kantor"
                                >{{ old('address', $store?->address) }}</textarea>
                                @error('address')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="settings-teks" class="settings-card ga-anchor-section">
                <header>
                    <span><i class="fa-solid fa-font"></i></span>
                    <div>
                        <strong>Teks Identitas Aplikasi</strong>
                        <small>
                            Menggantikan tulisan GENERAL AFFAIR dan Sigap & Handal.
                        </small>
                    </div>
                </header>

                <div class="row g-3">
                    <div class="col-lg-7">
                        <label class="form-label">Teks Header / Kicker <b>*</b></label>
                        <input
                            name="brand_kicker"
                            value="{{ old('brand_kicker', $branding['brand_kicker']) }}"
                            class="form-control text-uppercase"
                            maxlength="100"
                            required
                            data-brand-kicker-input
                            placeholder="GENERAL AFFAIR · SIGAP & HANDAL"
                        >
                        <small class="settings-field-help">
                            Tampil di atas judul POS Kasir dan judul halaman yang dibuat otomatis.
                        </small>
                        @error('brand_kicker')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-lg-5">
                        <label class="form-label">Slogan Sidebar <b>*</b></label>
                        <input
                            name="brand_tagline"
                            value="{{ old('brand_tagline', $branding['brand_tagline']) }}"
                            class="form-control"
                            maxlength="100"
                            required
                            data-brand-tagline-input
                            placeholder="Sigap & Handal"
                        >
                        <small class="settings-field-help">
                            Tampil di bawah nama toko pada sidebar dan halaman login.
                        </small>
                        @error('brand_tagline')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </section>


            <section id="settings-login" class="settings-card ga-anchor-section">
                <header>
                    <span><i class="fa-solid fa-right-to-bracket"></i></span>
                    <div>
                        <strong>Teks Halaman Login</strong>
                        <small>
                            Mengatur judul, deskripsi, keunggulan, tombol,
                            dan keterangan keamanan pada halaman masuk.
                        </small>
                    </div>
                </header>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">
                            Judul Utama Sisi Kiri <b>*</b>
                        </label>
                        <input
                            name="login_hero_title"
                            value="{{ old(
                                'login_hero_title',
                                $branding['login_hero_title']
                            ) }}"
                            class="form-control"
                            maxlength="180"
                            required
                            placeholder="Kelola operasional dan stok dalam satu tempat."
                        >
                        @error('login_hero_title')
                            <small class="text-danger d-block">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">
                            Deskripsi Sisi Kiri <b>*</b>
                        </label>
                        <textarea
                            name="login_hero_description"
                            rows="3"
                            maxlength="500"
                            class="form-control"
                            required
                            placeholder="Penjelasan singkat manfaat aplikasi"
                        >{{ old(
                            'login_hero_description',
                            $branding['login_hero_description']
                        ) }}</textarea>
                        @error('login_hero_description')
                            <small class="text-danger d-block">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Keunggulan 1 <b>*</b>
                        </label>
                        <input
                            name="login_benefit_1"
                            value="{{ old(
                                'login_benefit_1',
                                $branding['login_benefit_1']
                            ) }}"
                            class="form-control"
                            maxlength="60"
                            required
                        >
                        @error('login_benefit_1')
                            <small class="text-danger d-block">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Keunggulan 2 <b>*</b>
                        </label>
                        <input
                            name="login_benefit_2"
                            value="{{ old(
                                'login_benefit_2',
                                $branding['login_benefit_2']
                            ) }}"
                            class="form-control"
                            maxlength="60"
                            required
                        >
                        @error('login_benefit_2')
                            <small class="text-danger d-block">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Keunggulan 3 <b>*</b>
                        </label>
                        <input
                            name="login_benefit_3"
                            value="{{ old(
                                'login_benefit_3',
                                $branding['login_benefit_3']
                            ) }}"
                            class="form-control"
                            maxlength="60"
                            required
                        >
                        @error('login_benefit_3')
                            <small class="text-danger d-block">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">
                            Judul Form Login <b>*</b>
                        </label>
                        <input
                            name="login_welcome_title"
                            value="{{ old(
                                'login_welcome_title',
                                $branding['login_welcome_title']
                            ) }}"
                            class="form-control"
                            maxlength="100"
                            required
                            placeholder="Selamat datang"
                        >
                        @error('login_welcome_title')
                            <small class="text-danger d-block">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>

                    <div class="col-md-7">
                        <label class="form-label">
                            Deskripsi Form Login <b>*</b>
                        </label>
                        <textarea
                            name="login_welcome_description"
                            rows="2"
                            maxlength="300"
                            class="form-control"
                            required
                        >{{ old(
                            'login_welcome_description',
                            $branding['login_welcome_description']
                        ) }}</textarea>
                        @error('login_welcome_description')
                            <small class="text-danger d-block">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">
                            Teks Tombol Login <b>*</b>
                        </label>
                        <input
                            name="login_button_text"
                            value="{{ old(
                                'login_button_text',
                                $branding['login_button_text']
                            ) }}"
                            class="form-control"
                            maxlength="60"
                            required
                            placeholder="Masuk ke Sistem"
                        >
                        @error('login_button_text')
                            <small class="text-danger d-block">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>

                    <div class="col-md-7">
                        <label class="form-label">
                            Teks Keamanan <b>*</b>
                        </label>
                        <input
                            name="login_security_text"
                            value="{{ old(
                                'login_security_text',
                                $branding['login_security_text']
                            ) }}"
                            class="form-control"
                            maxlength="150"
                            required
                            placeholder="Sesi dan informasi akun Anda dilindungi."
                        >
                        @error('login_security_text')
                            <small class="text-danger d-block">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>
                </div>
            </section>


            <section id="settings-tampilan" class="settings-card ga-anchor-section">
                <header>
                    <span><i class="fa-solid fa-display"></i></span>
                    <div>
                        <strong>Ukuran Teks & Mode Tampilan</strong>
                        <small>
                            Pengaturan diterapkan ke login, sidebar, form,
                            dashboard, tabel, dan seluruh halaman POS.
                        </small>
                    </div>
                </header>

                <div class="settings-display-block">
                    <label class="form-label">Ukuran Font</label>
                    <div class="settings-font-grid">
                        @foreach($branding['font_scales'] as $key => $scale)
                            <label
                                class="settings-font-option {{ $fontScale === $key ? 'is-selected' : '' }}"
                            >
                                <input
                                    name="font_scale"
                                    value="{{ $key }}"
                                    type="radio"
                                    @checked($fontScale === $key)
                                    data-font-scale-option
                                >
                                <span class="is-{{ $key }}">
                                    {{ $scale['preview'] }}
                                </span>
                                <div>
                                    <strong>{{ $scale['label'] }}</strong>
                                    <small>{{ $scale['description'] }}</small>
                                </div>
                                <i class="fa-solid fa-circle-check"></i>
                            </label>
                        @endforeach
                    </div>
                    @error('font_scale')
                        <small class="text-danger d-block mt-2">
                            {{ $message }}
                        </small>
                    @enderror
                </div>

                <div class="settings-display-block mt-3">
                    <label class="form-label">Mode Tampilan</label>
                    <div class="settings-appearance-grid">
                        @foreach($branding['appearance_modes'] as $key => $mode)
                            <label
                                class="settings-appearance-option {{ $appearanceMode === $key ? 'is-selected' : '' }}"
                            >
                                <input
                                    name="appearance_mode"
                                    value="{{ $key }}"
                                    type="radio"
                                    @checked($appearanceMode === $key)
                                    data-appearance-option
                                >
                                <span>
                                    <i class="fa-solid {{ $mode['icon'] }}"></i>
                                </span>
                                <div>
                                    <strong>{{ $mode['label'] }}</strong>
                                    <small>{{ $mode['description'] }}</small>
                                </div>
                                <i class="fa-solid fa-circle-check"></i>
                            </label>
                        @endforeach
                    </div>
                    @error('appearance_mode')
                        <small class="text-danger d-block mt-2">
                            {{ $message }}
                        </small>
                    @enderror
                </div>

                <div class="settings-display-block mt-3">
                    <label class="form-label">Gaya Visual</label>
                    <div class="settings-finish-grid">
                        @foreach($branding['surface_finishes'] as $key => $finish)
                            <label
                                class="settings-finish-option is-{{ $key }} {{ $surfaceFinish === $key ? 'is-selected' : '' }}"
                            >
                                <input
                                    name="surface_finish"
                                    value="{{ $key }}"
                                    type="radio"
                                    @checked($surfaceFinish === $key)
                                    data-finish-option
                                >
                                <span class="settings-finish-preview">
                                    <i class="fa-solid {{ $finish['icon'] }}"></i>
                                    <b></b>
                                    <em></em>
                                </span>
                                <div>
                                    <strong>{{ $finish['label'] }}</strong>
                                    <small>{{ $finish['description'] }}</small>
                                </div>
                                <i class="fa-solid fa-circle-check"></i>
                            </label>
                        @endforeach
                    </div>
                    @error('surface_finish')
                        <small class="text-danger d-block mt-2">
                            {{ $message }}
                        </small>
                    @enderror
                </div>
            </section>

            <section id="settings-tema" class="settings-card ga-anchor-section">
                <header>
                    <span><i class="fa-solid fa-swatchbook"></i></span>
                    <div>
                        <strong>9 Tema Warna POS</strong>
                        <small>
                            Palet lebih terang. Warna tema tetap dipakai pada
                            mode gelap sebagai warna identitas dan aksen.
                        </small>
                    </div>
                </header>

                <div class="settings-theme-grid">
                    @foreach($branding['themes'] as $key => $theme)
                        <label
                            class="settings-theme-option {{ $themeKey === $key ? 'is-selected' : '' }}"
                            style="--theme-navy:{{ $theme['navy'] }};--theme-blue:{{ $theme['blue'] }};--theme-blue-2:{{ $theme['blue_2'] }};--theme-accent:{{ $theme['accent'] }};--theme-canvas:{{ $theme['canvas'] }};--theme-table:{{ $theme['table_head'] }}"
                        >
                            <input
                                name="theme_preset"
                                value="{{ $key }}"
                                type="radio"
                                @checked($themeKey === $key)
                                data-theme-option
                            >
                            <span class="settings-theme-swatch">
                                <i></i><i></i><i></i><i></i>
                            </span>
                            <div>
                                <strong>{{ $theme['label'] }}</strong>
                                <small>{{ $theme['description'] }}</small>
                            </div>
                            <i class="fa-solid fa-circle-check"></i>
                        </label>
                    @endforeach
                </div>

                @error('theme_preset')
                    <small class="text-danger d-block mt-2">{{ $message }}</small>
                @enderror
            </section>
        </div>

        <aside class="settings-side-column">
            <section id="settings-struk" class="settings-card ga-anchor-section">
                <header>
                    <span><i class="fa-solid fa-receipt"></i></span>
                    <div>
                        <strong>Struk Penjualan</strong>
                        <small>Teks penutup pada struk pelanggan.</small>
                    </div>
                </header>

                <div class="settings-receipt-text-stack">
                    <div>
                        <label class="form-label">
                            Ucapan Terima Kasih
                        </label>
                        <textarea
                            name="receipt_footer"
                            rows="3"
                            maxlength="255"
                            class="form-control"
                            placeholder="Terima kasih sudah berbelanja."
                        >{{ old(
                            'receipt_footer',
                            $store?->receipt_footer
                        ) }}</textarea>
                        @error('receipt_footer')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">
                            Keterangan di Bawah Struk
                        </label>
                        <textarea
                            name="receipt_policy_text"
                            rows="3"
                            maxlength="255"
                            class="form-control"
                            placeholder="Barang yang sudah dibeli mengikuti ketentuan retur toko."
                        >{{ old(
                            'receipt_policy_text',
                            $store?->receipt_policy_text
                                ?? 'Barang yang sudah dibeli mengikuti ketentuan retur toko.'
                        ) }}</textarea>
                        @error('receipt_policy_text')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror
                        <small class="settings-field-help">
                            Tampil tepat di bawah ucapan terima kasih pada
                            struk 58 mm, 80 mm, dan file TXT.
                        </small>
                    </div>
                </div>
            </section>


            <section
                id="settings-poin"
                class="settings-card settings-loyalty-card ga-anchor-section"
                data-loyalty-settings
            >
                <header>
                    <span><i class="fa-solid fa-coins"></i></span>
                    <div>
                        <strong>Poin Pelanggan</strong>
                        <small>
                            Atur perolehan dan penukaran poin member di POS.
                        </small>
                    </div>
                </header>

                <input
                    type="hidden"
                    name="loyalty_enabled"
                    value="0"
                >

                <label class="settings-loyalty-toggle">
                    <input
                        type="checkbox"
                        name="loyalty_enabled"
                        value="1"
                        class="form-check-input"
                        data-loyalty-enabled
                        @checked(old(
                            'loyalty_enabled',
                            ($settings['loyalty_enabled'] ?? '1') === '1'
                        ))
                    >
                    <span>
                        <strong>Aktifkan poin pelanggan</strong>
                        <small>
                            Saat nonaktif, poin tidak diperoleh dan tidak
                            dapat digunakan pada transaksi baru.
                        </small>
                    </span>
                    <i class="fa-solid fa-circle-check"></i>
                </label>

                <div class="settings-loyalty-fields">
                    <div class="settings-loyalty-field">
                        <div class="settings-loyalty-field-head">
                            <span>
                                <i class="fa-solid fa-arrow-trend-up"></i>
                            </span>
                            <div>
                                <label class="form-label">
                                    Belanja untuk 1 poin
                                </label>
                                <small>
                                    Nominal minimum untuk memperoleh satu poin.
                                </small>
                            </div>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input
                                name="loyalty_earn_amount"
                                type="number"
                                min="100"
                                max="1000000000"
                                step="100"
                                value="{{ old(
                                    'loyalty_earn_amount',
                                    $settings['loyalty_earn_amount']
                                        ?? 10000
                                ) }}"
                                class="form-control"
                                data-loyalty-earn
                                required
                            >
                        </div>
                        @error('loyalty_earn_amount')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>

                    <div class="settings-loyalty-field">
                        <div class="settings-loyalty-field-head">
                            <span>
                                <i class="fa-solid fa-ticket"></i>
                            </span>
                            <div>
                                <label class="form-label">
                                    Nilai tukar 1 poin
                                </label>
                                <small>
                                    Potongan rupiah untuk setiap poin.
                                </small>
                            </div>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input
                                name="loyalty_redeem_value"
                                type="number"
                                min="1"
                                max="10000000"
                                value="{{ old(
                                    'loyalty_redeem_value',
                                    $settings['loyalty_redeem_value']
                                        ?? 100
                                ) }}"
                                class="form-control"
                                data-loyalty-value
                                required
                            >
                        </div>
                        @error('loyalty_redeem_value')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>

                    <div class="settings-loyalty-field">
                        <div class="settings-loyalty-field-head">
                            <span>
                                <i class="fa-solid fa-shield-halved"></i>
                            </span>
                            <div>
                                <label class="form-label">
                                    Maksimal penukaran
                                </label>
                                <small>
                                    Batas potongan poin dari total transaksi.
                                </small>
                            </div>
                        </div>
                        <div class="input-group">
                            <input
                                name="loyalty_max_redeem_percent"
                                type="number"
                                min="1"
                                max="100"
                                value="{{ old(
                                    'loyalty_max_redeem_percent',
                                    $settings[
                                        'loyalty_max_redeem_percent'
                                    ] ?? 20
                                ) }}"
                                class="form-control"
                                data-loyalty-percent
                                required
                            >
                            <span class="input-group-text">
                                % total
                            </span>
                        </div>
                        @error('loyalty_max_redeem_percent')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>
                </div>

                <div class="settings-loyalty-preview">
                    <span>
                        <i class="fa-solid fa-arrow-trend-up"></i>
                        <b data-loyalty-earn-preview>
                            Belanja Rp10.000 = 1 poin
                        </b>
                    </span>
                    <span>
                        <i class="fa-solid fa-ticket"></i>
                        <b data-loyalty-value-preview>
                            1 poin = Rp100
                        </b>
                    </span>
                    <span>
                        <i class="fa-solid fa-shield-halved"></i>
                        <b data-loyalty-limit-preview>
                            Maksimal 20% dari transaksi
                        </b>
                    </span>
                </div>

                <div class="settings-loyalty-rule">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>
                        Poin baru diberikan setelah transaksi lunas.
                        Retur mengurangi poin yang diperoleh dan
                        mengembalikan poin yang digunakan secara
                        proporsional.
                    </span>
                </div>
            </section>

            <section id="settings-persediaan" class="settings-card ga-anchor-section">
                <header>
                    <span><i class="fa-solid fa-boxes-stacked"></i></span>
                    <div>
                        <strong>Persediaan</strong>
                        <small>Peringatan dan kebijakan stok.</small>
                    </div>
                </header>

                <label class="form-label">Peringatan Kedaluwarsa</label>
                <div class="input-group">
                    <input
                        name="expiry_warning_days"
                        type="number"
                        min="1"
                        max="365"
                        value="{{ old(
                            'expiry_warning_days',
                            $settings['expiry_warning_days'] ?? 30
                        ) }}"
                        class="form-control"
                        required
                    >
                    <span class="input-group-text">hari</span>
                </div>
                @error('expiry_warning_days')
                    <small class="text-danger">{{ $message }}</small>
                @enderror

                <input type="hidden" name="allow_negative_stock" value="0">
                <label class="settings-danger-option">
                    <input
                        type="checkbox"
                        name="allow_negative_stock"
                        value="1"
                        class="form-check-input"
                        @checked(old(
                            'allow_negative_stock',
                            ($settings['allow_negative_stock'] ?? '0') === '1'
                        ))
                    >
                    <span>
                        <strong>Izinkan stok minus</strong>
                        <small>
                            Tidak direkomendasikan karena dapat menyamarkan selisih fisik.
                        </small>
                    </span>
                </label>
            </section>

            <div class="settings-storage-note">
                <i class="fa-solid fa-link"></i>
                <div>
                    <strong>Logo dan icon tab</strong>
                    <span>
                        Logo ditampilkan melalui route aplikasi dan juga
                        digunakan sebagai favicon/icon tab pada halaman login,
                        dashboard, dan seluruh halaman POS. Tetap bekerja pada
                        <code>php artisan serve</code> tanpa bergantung pada
                        <code>APP_URL</code> atau
                        <code>php artisan storage:link</code>.
                    </span>
                </div>
            </div>
        </aside>
    </div>

    <footer class="settings-form-footer">
        <a href="{{ route('pos.dashboard') }}" class="btn btn-light">
            <i class="fa-solid fa-arrow-left"></i>
            Kembali
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk"></i>
            Simpan Semua Pengaturan
        </button>
    </footer>
</form>
@endsection
