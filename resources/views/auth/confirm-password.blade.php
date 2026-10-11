@extends('layouts.auth')
@section('title', 'Konfirmasi Password')
@section('content')
<h2 class="h5 mb-2">Konfirmasi password</h2>
<p class="text-muted">Tindakan ini sensitif. Masukkan kembali password akun.</p>
<form method="post" action="{{ route('password.confirm') }}">@csrf<div class="mb-4"><label class="form-label">Password</label><input name="password" type="password" class="form-control" autocomplete="current-password" required autofocus></div><button class="btn btn-primary w-100">Konfirmasi</button></form>
@endsection
