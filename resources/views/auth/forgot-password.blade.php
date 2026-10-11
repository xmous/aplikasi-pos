@extends('layouts.auth')
@section('title', 'Lupa Password')
@section('content')
<h2 class="h5 mb-2">Reset password</h2>
<p class="text-muted">Masukkan email akun. Tautan reset akan dikirim melalui email.</p>
<form method="post" action="{{ route('password.email') }}">
    @csrf
    <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" value="{{ old('email') }}" class="form-control form-control-lg" required autofocus></div>
    <button class="btn btn-primary w-100">Kirim tautan reset</button>
</form>
<a href="{{ route('login') }}" class="btn btn-link w-100 mt-2">Kembali ke login</a>
@endsection
