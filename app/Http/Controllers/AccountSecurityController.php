<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountSecurityController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('account.security', [
            'user' => $user,
            'roles' => $user->getRoleNames(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validateWithBag('profile', [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ], [], [
            'name' => 'nama',
            'email' => 'email',
        ]);

        $name = trim($data['name']);
        $email = mb_strtolower(trim($data['email']));
        $emailChanged = $email !== mb_strtolower((string) $user->email);
        $nameChanged = $name !== (string) $user->name;

        if (! $emailChanged && ! $nameChanged) {
            return back()->with('success', 'Tidak ada perubahan profil yang perlu disimpan.');
        }

        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => $emailChanged
                ? null
                : $user->email_verified_at,
        ])->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        AuditService::log('account.profile.updated', $user, [
            'name_changed' => $nameChanged,
            'email_changed' => $emailChanged,
        ]);

        return back()->with(
            'success',
            $emailChanged
                ? 'Profil diperbarui. Tautan verifikasi sudah dikirim ke email baru.'
                : 'Profil berhasil diperbarui.'
        );
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('password', [
            'current_password' => [
                'required',
                'current_password:web',
            ],
            'password' => [
                'required',
                'string',
                'different:current_password',
                'confirmed',
                'max:255',
                Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ], [
            'password.different' => 'Password baru tidak boleh sama dengan password saat ini.',
        ], [
            'current_password' => 'password saat ini',
            'password' => 'password baru',
        ]);

        $user = $request->user();

        /*
         * Keluarkan sesi perangkat lain sebelum password lama tidak lagi valid.
         * Sesi perangkat yang sedang dipakai tetap dipertahankan.
         */
        Auth::logoutOtherDevices($data['current_password']);

        $user->forceFill([
            'password' => $data['password'],
            'password_changed_at' => now(),
        ])->save();

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        AuditService::log('account.password.updated', $user, [
            'other_sessions_logged_out' => true,
        ]);

        return back()->with(
            'success',
            'Password berhasil diperbarui. Sesi pada perangkat lain sudah dikeluarkan.'
        );
    }
}
