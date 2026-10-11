@php
    $authBranding = \App\Support\PosBranding::resolve();
@endphp
@extends('layouts.auth')
@section('title', 'Masuk')
@section('content')
<div class="auth-heading auth-login-heading">
    <h2>{{ $authBranding['login_welcome_title'] }}</h2>
    <p>{{ $authBranding['login_welcome_description'] }}</p>
</div>
<form method="post" action="{{ route('login') }}" autocomplete="on" class="auth-login-form">
    @csrf
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <div class="auth-input"><i class="fa-regular fa-envelope"></i><input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control form-control-lg" placeholder="nama@perusahaan.com" autocomplete="username" inputmode="email" required autofocus></div>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <div class="auth-input">
            <i class="fa-solid fa-lock"></i><input id="password" name="password" type="password" class="form-control form-control-lg" placeholder="Masukkan password" autocomplete="current-password" required data-password-input>
            <button type="button" class="auth-password-toggle" data-password-toggle aria-label="Tampilkan password"><i class="fa-regular fa-eye"></i></button>
        </div>
    </div>
    <div class="auth-login-options">
        <div class="form-check">
            <input
                id="remember"
                name="remember"
                type="checkbox"
                class="form-check-input"
            >
            <label for="remember" class="form-check-label">
                Ingat saya
            </label>
        </div>

        <a
            href="{{ route('password.request') }}"
            class="auth-forgot-link"
        >
            Lupa password?
        </a>
    </div>
    <button class="btn btn-primary btn-lg w-100 auth-submit">
        {{ $authBranding['login_button_text'] }}
        <i class="fa-solid fa-arrow-right"></i>
    </button>

    <div class="auth-login-note">
        <i class="fa-solid fa-circle-info"></i>
        <span>
            Gunakan akun dan role yang diberikan oleh pengelola toko.
        </span>
    </div>
</form>
@endsection
