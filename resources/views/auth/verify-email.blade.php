@extends('layouts.auth')
@section('title', 'Verifikasi Email')
@section('content')
<h2 class="h5 mb-2">Verifikasi email</h2>
<p class="text-muted">Buka tautan yang kami kirim ke emailmu sebelum mengakses POS.</p>
@if(session('status') === 'verification-link-sent')<div data-flash-message data-success="Tautan verifikasi baru sudah dikirim." data-error="[]"></div>@endif
<form method="post" action="{{ route('verification.send') }}">@csrf<button class="btn btn-primary w-100">Kirim ulang verifikasi</button></form>
<form method="post" action="{{ route('logout') }}" class="mt-2">@csrf<button class="btn btn-outline-secondary w-100">Keluar</button></form>
@endsection
