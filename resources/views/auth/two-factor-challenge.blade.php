@extends('layouts.auth')
@section('title', 'Verifikasi Dua Langkah')
@section('content')
<h2 class="h5 mb-2">Verifikasi dua langkah</h2>
<p class="text-muted" data-two-factor-description>Masukkan kode 6 digit dari aplikasi authenticator.</p>
<form method="post" action="{{ route('two-factor.login') }}">@csrf
    <div class="mb-4" data-auth-code><label class="form-label">Kode authenticator</label><input name="code" inputmode="numeric" autocomplete="one-time-code" class="form-control form-control-lg text-center" maxlength="6" autofocus></div>
    <div class="mb-4 d-none" data-recovery-code><label class="form-label">Recovery code</label><input name="recovery_code" autocomplete="one-time-code" class="form-control"></div>
    <button class="btn btn-primary w-100">Verifikasi</button>
</form>
<button type="button" class="btn btn-link w-100 mt-2" data-two-factor-switch>Gunakan recovery code</button>
@endsection
