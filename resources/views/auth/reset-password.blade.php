@extends('layouts.auth')
@section('title', 'Password Baru')
@section('content')
<h2 class="h5 mb-3">Buat password baru</h2>
<form method="post" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
    <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" value="{{ old('email', $request->email) }}" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Password baru</label><input name="password" type="password" class="form-control" autocomplete="new-password" required><small class="text-muted">Minimal 12 karakter, huruf besar/kecil, angka, dan simbol.</small></div>
    <div class="mb-4"><label class="form-label">Konfirmasi password</label><input name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required></div>
    <button class="btn btn-primary w-100">Simpan password</button>
</form>
@endsection
