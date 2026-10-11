@extends('layouts.pos')
@section('title', 'Profil & Keamanan')
@section('body_class', 'pos-account-security-page')

@section('content')
@php
    $initial = mb_strtoupper(mb_substr($user->name, 0, 1));
    $roleNames = $roles->isNotEmpty()
        ? $roles->join(', ')
        : 'Pengguna';
    $passwordAge = $user->password_changed_at
        ? $user->password_changed_at->diffForHumans()
        : 'Belum tercatat';
    $lastLogin = $user->last_login_at
        ? $user->last_login_at->format('d/m/Y H:i')
        : 'Belum tercatat';
    $twoFactorFeatureEnabled = (bool) config(
        'pos-features.two_factor_enabled',
        false
    );
    $twoFactorEnabled = $twoFactorFeatureEnabled
        && (bool) $user->two_factor_secret;
    $twoFactorConfirmed = (bool) $user->two_factor_confirmed_at;
    $recoveryCodes = $twoFactorConfirmed
        ? collect($user->recoveryCodes() ?? [])
        : collect();
@endphp

<div class="ga-page-head account-security-head">
    <div class="ga-page-title">
        <span class="ga-eyebrow">
            <i class="fa-solid fa-user-shield"></i>
            PUSAT KEAMANAN AKUN
        </span>
        <h2>Profil & Keamanan</h2>
        <p class="mb-0">
            Kelola identitas akun, password, keamanan sesi perangkat
            @if($twoFactorFeatureEnabled)
                , dan autentikasi dua faktor
            @endif
            .
        </p>
    </div>

    <div class="account-security-identity">
        <span>{{ $initial }}</span>
        <div>
            <strong>{{ $user->name }}</strong>
            <small>{{ $roleNames }}</small>
        </div>
    </div>
</div>

<div class="account-security-stat-grid {{ $twoFactorFeatureEnabled ? '' : 'is-without-two-factor' }}">
    <article class="{{ $user->email_verified_at ? 'is-success' : 'is-warning' }}">
        <span>
            <i class="fa-solid {{ $user->email_verified_at ? 'fa-envelope-circle-check' : 'fa-envelope-open-text' }}"></i>
        </span>
        <div>
            <small>Status Email</small>
            <strong>
                {{ $user->email_verified_at
                    ? 'Terverifikasi'
                    : 'Belum diverifikasi' }}
            </strong>
        </div>
    </article>

    @if($twoFactorFeatureEnabled)
    <article class="{{ $twoFactorConfirmed ? 'is-success' : ($twoFactorEnabled ? 'is-warning' : 'is-muted') }}">
        <span>
            <i class="fa-solid fa-shield-halved"></i>
        </span>
        <div>
            <small>Autentikasi 2FA</small>
            <strong>
                {{ $twoFactorConfirmed
                    ? 'Aktif'
                    : ($twoFactorEnabled ? 'Menunggu konfirmasi' : 'Nonaktif') }}
            </strong>
        </div>
    </article>
    @endif

    <article>
        <span>
            <i class="fa-solid fa-key"></i>
        </span>
        <div>
            <small>Password Terakhir Diubah</small>
            <strong>{{ $passwordAge }}</strong>
        </div>
    </article>

    <article>
        <span>
            <i class="fa-solid fa-clock-rotate-left"></i>
        </span>
        <div>
            <small>Login Terakhir</small>
            <strong>{{ $lastLogin }}</strong>
        </div>
    </article>
</div>

<div class="account-security-layout">
    <form
        method="post"
        action="{{ route('account.profile.update') }}"
        id="account-profile"
        class="account-security-card ga-anchor-section"
    >
        @csrf
        @method('PUT')

        <header>
            <span><i class="fa-solid fa-address-card"></i></span>
            <div>
                <strong>Informasi Profil</strong>
                <small>
                    Nama digunakan pada transaksi, laporan, dan activity log.
                </small>
            </div>
        </header>

        <div class="account-profile-summary">
            <span>{{ $initial }}</span>
            <div>
                <strong>{{ $user->name }}</strong>
                <small>{{ $user->email }}</small>
                <div>
                    @forelse($roles as $role)
                        <b>{{ $role }}</b>
                    @empty
                        <b>Pengguna</b>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="account-security-card-body">
            <div class="mb-3">
                <label class="form-label" for="account-name">
                    Nama Lengkap <b>*</b>
                </label>
                <div class="account-input">
                    <i class="fa-solid fa-user"></i>
                    <input
                        id="account-name"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        class="form-control"
                        maxlength="255"
                        autocomplete="name"
                        required
                    >
                </div>
                @error('name', 'profile')
                    <small class="text-danger d-block mt-1">
                        {{ $message }}
                    </small>
                @enderror
            </div>

            <div>
                <label class="form-label" for="account-email">
                    Email <b>*</b>
                </label>
                <div class="account-input">
                    <i class="fa-solid fa-envelope"></i>
                    <input
                        id="account-email"
                        name="email"
                        type="email"
                        value="{{ old('email', $user->email) }}"
                        class="form-control"
                        maxlength="255"
                        autocomplete="email"
                        required
                    >
                </div>
                @error('email', 'profile')
                    <small class="text-danger d-block mt-1">
                        {{ $message }}
                    </small>
                @enderror
            </div>

            <div class="account-inline-notice is-info">
                <i class="fa-solid fa-circle-info"></i>
                <span>
                    Mengubah email akan membatalkan status verifikasi lama.
                    Sistem mengirim tautan verifikasi ke alamat yang baru.
                </span>
            </div>
        </div>

        <footer>
            <button class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                Simpan Profil
            </button>
        </footer>
    </form>

    <form
        method="post"
        action="{{ route('account.password.update') }}"
        id="account-password"
        class="account-security-card ga-anchor-section"
    >
        @csrf
        @method('PUT')

        <header>
            <span><i class="fa-solid fa-key"></i></span>
            <div>
                <strong>Ganti Password</strong>
                <small>
                    Perangkat lain akan dikeluarkan setelah password berhasil
                    diubah.
                </small>
            </div>
        </header>

        <div class="account-security-card-body">
            <div class="mb-3">
                <label class="form-label" for="current-password">
                    Password Saat Ini <b>*</b>
                </label>
                <div class="input-group account-password-input">
                    <span class="input-group-text">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input
                        id="current-password"
                        name="current_password"
                        type="password"
                        class="form-control"
                        autocomplete="current-password"
                        data-password-input
                        required
                    >
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-password-toggle
                        aria-label="Lihat password saat ini"
                    >
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
                @error('current_password', 'password')
                    <small class="text-danger d-block mt-1">
                        {{ $message }}
                    </small>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="new-password">
                    Password Baru <b>*</b>
                </label>
                <div class="input-group account-password-input">
                    <span class="input-group-text">
                        <i class="fa-solid fa-shield-halved"></i>
                    </span>
                    <input
                        id="new-password"
                        name="password"
                        type="password"
                        class="form-control"
                        autocomplete="new-password"
                        data-password-input
                        required
                    >
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-password-toggle
                        aria-label="Lihat password baru"
                    >
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
                @error('password', 'password')
                    <small class="text-danger d-block mt-1">
                        {{ $message }}
                    </small>
                @enderror
            </div>

            <div>
                <label class="form-label" for="password-confirmation">
                    Konfirmasi Password Baru <b>*</b>
                </label>
                <div class="input-group account-password-input">
                    <span class="input-group-text">
                        <i class="fa-solid fa-check"></i>
                    </span>
                    <input
                        id="password-confirmation"
                        name="password_confirmation"
                        type="password"
                        class="form-control"
                        autocomplete="new-password"
                        data-password-input
                        required
                    >
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-password-toggle
                        aria-label="Lihat konfirmasi password"
                    >
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="account-password-rules">
                <strong>Password harus memenuhi:</strong>
                <div>
                    <span><i class="fa-solid fa-check"></i> Minimal 12 karakter</span>
                    <span><i class="fa-solid fa-check"></i> Huruf besar dan kecil</span>
                    <span><i class="fa-solid fa-check"></i> Angka dan simbol</span>
                    <span><i class="fa-solid fa-check"></i> Bukan password yang pernah bocor</span>
                </div>
            </div>
        </div>

        <footer>
            <button class="btn btn-primary">
                <i class="fa-solid fa-key"></i>
                Perbarui Password
            </button>
        </footer>
    </form>

    @if($twoFactorFeatureEnabled)
    <section
        id="account-2fa"
        class="account-security-card account-two-factor-card ga-anchor-section"
    >
        <header>
            <span><i class="fa-solid fa-mobile-screen-button"></i></span>
            <div>
                <strong>Autentikasi Dua Faktor (2FA)</strong>
                <small>
                    Tambahkan kode authenticator setelah password pada saat
                    login.
                </small>
            </div>

            <span class="account-two-factor-status {{ $twoFactorConfirmed ? 'is-active' : ($twoFactorEnabled ? 'is-pending' : 'is-inactive') }}">
                <i class="fa-solid {{ $twoFactorConfirmed ? 'fa-circle-check' : ($twoFactorEnabled ? 'fa-hourglass-half' : 'fa-circle-pause') }}"></i>
                {{ $twoFactorConfirmed
                    ? 'Aktif'
                    : ($twoFactorEnabled ? 'Belum Dikonfirmasi' : 'Nonaktif') }}
            </span>
        </header>

        <div class="account-security-card-body">
            @if(! $twoFactorEnabled)
                <div class="account-two-factor-intro">
                    <div>
                        <span><i class="fa-solid fa-shield-halved"></i></span>
                        <div>
                            <strong>Lindungi akun dari password yang bocor</strong>
                            <p>
                                Setelah aktif, login membutuhkan kode enam digit
                                dari aplikasi authenticator pada ponsel.
                            </p>
                        </div>
                    </div>

                    <div class="account-security-benefits">
                        <span>
                            <i class="fa-solid fa-user-lock"></i>
                            Mencegah login hanya dengan password
                        </span>
                        <span>
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            Recovery code untuk keadaan darurat
                        </span>
                        <span>
                            <i class="fa-solid fa-shield"></i>
                            Direkomendasikan untuk akun owner dan admin
                        </span>
                    </div>

                    <form
                        method="post"
                        action="{{ route('two-factor.enable') }}"
                    >
                        @csrf
                        <button class="btn btn-success">
                            <i class="fa-solid fa-shield-halved"></i>
                            Aktifkan 2FA
                        </button>
                    </form>
                </div>
            @elseif(! $twoFactorConfirmed)
                <div class="account-inline-notice is-warning">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>
                        2FA belum aktif. Selesaikan pemindaian dan konfirmasi
                        kode sebelum meninggalkan halaman ini.
                    </span>
                </div>

                <div class="account-two-factor-setup">
                    <div class="account-qr-panel">
                        <div class="account-qr-code">
                            {!! $user->twoFactorQrCodeSvg() !!}
                        </div>
                        <strong>Pindai QR Code</strong>
                        <small>
                            Gunakan Google Authenticator, Microsoft
                            Authenticator, Authy, atau aplikasi TOTP lain.
                        </small>
                    </div>

                    <div class="account-two-factor-steps">
                        <ol>
                            <li>
                                Buka aplikasi authenticator pada ponsel.
                            </li>
                            <li>
                                Pilih tambah akun, lalu pindai QR code.
                            </li>
                            <li>
                                Masukkan kode enam digit yang muncul.
                            </li>
                        </ol>

                        <form
                            method="post"
                            action="{{ route('two-factor.confirm') }}"
                            class="account-two-factor-confirm"
                        >
                            @csrf
                            <label for="two-factor-code">
                                Kode Authenticator
                            </label>
                            <input
                                id="two-factor-code"
                                name="code"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="6"
                                autocomplete="one-time-code"
                                class="form-control"
                                placeholder="000000"
                                required
                            >
                            @error('code')
                                <small class="text-danger d-block">
                                    {{ $message }}
                                </small>
                            @enderror
                            <button class="btn btn-primary">
                                <i class="fa-solid fa-circle-check"></i>
                                Konfirmasi & Aktifkan
                            </button>
                        </form>

                        <form
                            method="post"
                            action="{{ route('two-factor.disable') }}"
                            data-confirm="Batalkan proses aktivasi 2FA?"
                        >
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger">
                                <i class="fa-solid fa-xmark"></i>
                                Batalkan Aktivasi
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="account-inline-notice is-success">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>
                        2FA aktif. Simpan recovery codes di tempat yang terpisah
                        dari perangkat authenticator.
                    </span>
                </div>

                <div class="account-recovery-layout">
                    <div>
                        <div class="account-recovery-heading">
                            <div>
                                <strong>Recovery Codes</strong>
                                <small>
                                    Setiap kode hanya dapat digunakan satu kali.
                                </small>
                            </div>
                            <span>
                                {{ number_format($recoveryCodes->count()) }}
                                kode
                            </span>
                        </div>

                        <div class="account-recovery-codes">
                            @forelse($recoveryCodes as $code)
                                <code>{{ $code }}</code>
                            @empty
                                <span>
                                    Recovery codes belum tersedia. Buat ulang
                                    untuk menghasilkan kode baru.
                                </span>
                            @endforelse
                        </div>
                    </div>

                    <aside class="account-recovery-actions">
                        <strong>Kelola 2FA</strong>
                        <p>
                            Membuat ulang recovery codes akan membatalkan semua
                            kode yang lama.
                        </p>

                        <form
                            method="post"
                            action="{{ route('two-factor.recovery-codes') }}"
                            data-confirm="Buat ulang recovery codes? Semua kode lama akan dibatalkan."
                        >
                            @csrf
                            <button class="btn btn-outline-primary w-100">
                                <i class="fa-solid fa-rotate"></i>
                                Buat Ulang Recovery Codes
                            </button>
                        </form>

                        <form
                            method="post"
                            action="{{ route('two-factor.disable') }}"
                            data-confirm="Nonaktifkan 2FA? Keamanan akun akan berkurang."
                        >
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger w-100">
                                <i class="fa-solid fa-shield"></i>
                                Nonaktifkan 2FA
                            </button>
                        </form>
                    </aside>
                </div>
            @endif
        </div>
    </section>
    @endif
</div>
@endsection
