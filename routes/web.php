<?php

use App\Http\Controllers\AccountSecurityController;
use App\Http\Controllers\BrandingAssetController;
use Illuminate\Support\Facades\Route;

Route::get(
    '/branding/logo',
    BrandingAssetController::class
)->name('branding.logo');

Route::get('/', function () {
    return auth()->check() ? redirect()->route('pos.dashboard') : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified', 'active', 'session.idle'])->group(function () {
    Route::get('/account/security', [AccountSecurityController::class, 'show'])->name('account.security');
    Route::put('/account/profile', [AccountSecurityController::class, 'updateProfile'])->middleware('throttle:pos-write')->name('account.profile.update');
    Route::put('/account/password', [AccountSecurityController::class, 'updatePassword'])->middleware(['password.confirm', 'throttle:6,1'])->name('account.password.update');
});

require __DIR__.'/pos.php';
