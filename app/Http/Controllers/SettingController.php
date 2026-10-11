<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Store;
use App\Services\AuditService;
use App\Support\PosBranding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            'branding' => PosBranding::resolve(),
            'settings' => Setting::query()->pluck('value', 'key'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $themeKeys = array_keys(PosBranding::themePresets());
        $fontScaleKeys = array_keys(PosBranding::fontScales());
        $appearanceModeKeys = array_keys(PosBranding::appearanceModes());
        $surfaceFinishKeys = array_keys(PosBranding::surfaceFinishes());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'receipt_policy_text' => [
                'nullable',
                'string',
                'max:255',
            ],
            'logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
                'dimensions:max_width=1600,max_height=1600',
            ],
            'remove_logo' => ['nullable', 'boolean'],
            'brand_kicker' => ['required', 'string', 'max:100'],
            'brand_tagline' => ['required', 'string', 'max:100'],
            'login_hero_title' => ['required', 'string', 'max:180'],
            'login_hero_description' => ['required', 'string', 'max:500'],
            'login_welcome_title' => ['required', 'string', 'max:100'],
            'login_welcome_description' => ['required', 'string', 'max:300'],
            'login_benefit_1' => ['required', 'string', 'max:60'],
            'login_benefit_2' => ['required', 'string', 'max:60'],
            'login_benefit_3' => ['required', 'string', 'max:60'],
            'login_button_text' => ['required', 'string', 'max:60'],
            'login_security_text' => ['required', 'string', 'max:150'],
            'theme_preset' => ['required', Rule::in($themeKeys)],
            'font_scale' => ['required', Rule::in($fontScaleKeys)],
            'appearance_mode' => [
                'required',
                Rule::in($appearanceModeKeys),
            ],
            'surface_finish' => [
                'required',
                Rule::in($surfaceFinishKeys),
            ],
            'expiry_warning_days' => ['required', 'integer', 'min:1', 'max:365'],
            'allow_negative_stock' => ['nullable', 'boolean'],
            'loyalty_enabled' => ['nullable', 'boolean'],
            'loyalty_earn_amount' => [
                'required',
                'integer',
                'min:100',
                'max:1000000000',
            ],
            'loyalty_redeem_value' => [
                'required',
                'integer',
                'min:1',
                'max:10000000',
            ],
            'loyalty_max_redeem_percent' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
        ], [
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
            'logo.dimensions' => 'Ukuran gambar logo maksimal 1600 × 1600 piksel.',
            'theme_preset.in' => 'Tema yang dipilih tidak tersedia.',
            'font_scale.in' => 'Ukuran font yang dipilih tidak tersedia.',
            'appearance_mode.in' => 'Mode tampilan yang dipilih tidak tersedia.',
            'surface_finish.in' => 'Gaya visual yang dipilih tidak tersedia.',
            'loyalty_earn_amount.min' => 'Nilai belanja per poin minimal Rp100.',
            'loyalty_redeem_value.min' => 'Nilai tukar poin minimal Rp1.',
            'loyalty_max_redeem_percent.max' => 'Batas penukaran poin maksimal 100%.',
        ]);

        $store = Store::query()->firstOrFail();
        $oldLogoPath = $store->logo_path;
        $newLogoPath = null;

        if ($request->hasFile('logo')) {
            $newLogoPath = $request->file('logo')->store(
                'branding',
                'public'
            );
        }

        try {
            DB::transaction(function () use (
                $request,
                $data,
                $store,
                $newLogoPath
            ): void {
                $logoPath = $store->logo_path;
    
                if ($request->boolean('remove_logo')) {
                    $logoPath = null;
                }
    
                if ($newLogoPath) {
                    $logoPath = $newLogoPath;
                }
    
                $store->update([
                    'name' => trim($data['name']),
                    'phone' => $data['phone'] !== null
                        ? trim($data['phone'])
                        : null,
                    'email' => $data['email'] !== null
                        ? mb_strtolower(trim($data['email']))
                        : null,
                    'address' => $data['address'] !== null
                        ? trim($data['address'])
                        : null,
                    'receipt_footer' => $data['receipt_footer'] !== null
                        ? trim($data['receipt_footer'])
                        : null,
                    'receipt_policy_text' => $data[
                        'receipt_policy_text'
                    ] !== null
                        ? trim($data['receipt_policy_text'])
                        : null,
                    'logo_path' => $logoPath,
                ]);
    
                $settingValues = [
                    'brand_kicker' => trim($data['brand_kicker']),
                    'brand_tagline' => trim($data['brand_tagline']),
                    'login_hero_title' => trim($data['login_hero_title']),
                    'login_hero_description' => trim($data['login_hero_description']),
                    'login_welcome_title' => trim($data['login_welcome_title']),
                    'login_welcome_description' => trim($data['login_welcome_description']),
                    'login_benefit_1' => trim($data['login_benefit_1']),
                    'login_benefit_2' => trim($data['login_benefit_2']),
                    'login_benefit_3' => trim($data['login_benefit_3']),
                    'login_button_text' => trim($data['login_button_text']),
                    'login_security_text' => trim($data['login_security_text']),
                    'theme_preset' => $data['theme_preset'],
                    'font_scale' => $data['font_scale'],
                    'appearance_mode' => $data['appearance_mode'],
                    'surface_finish' => $data['surface_finish'],
                    'expiry_warning_days' => (string) $data['expiry_warning_days'],
                    'allow_negative_stock' => $request->boolean(
                        'allow_negative_stock'
                    ) ? '1' : '0',
                    'loyalty_enabled' => $request->boolean(
                        'loyalty_enabled'
                    ) ? '1' : '0',
                    'loyalty_earn_amount' => (string) $data[
                        'loyalty_earn_amount'
                    ],
                    'loyalty_redeem_value' => (string) $data[
                        'loyalty_redeem_value'
                    ],
                    'loyalty_max_redeem_percent' => (string) $data[
                        'loyalty_max_redeem_percent'
                    ],
                ];
    
                foreach ($settingValues as $key => $value) {
                    Setting::updateOrCreate(
                        ['key' => $key],
                        ['value' => $value]
                    );
                }
    
                AuditService::log('settings.updated', $store, [
                    'theme_preset' => $data['theme_preset'],
                    'font_scale' => $data['font_scale'],
                    'appearance_mode' => $data['appearance_mode'],
                    'surface_finish' => $data['surface_finish'],
                    'brand_kicker' => $settingValues['brand_kicker'],
                    'brand_tagline' => $settingValues['brand_tagline'],
                    'login_texts_updated' => true,
                    'receipt_texts_updated' => true,
                    'logo_changed' => $newLogoPath !== null
                        || $request->boolean('remove_logo'),
                    'expiry_warning_days' => $data['expiry_warning_days'],
                    'allow_negative_stock' => $request->boolean(
                        'allow_negative_stock'
                    ),
                    'loyalty_enabled' => $request->boolean(
                        'loyalty_enabled'
                    ),
                    'loyalty_earn_amount' => $data[
                        'loyalty_earn_amount'
                    ],
                    'loyalty_redeem_value' => $data[
                        'loyalty_redeem_value'
                    ],
                    'loyalty_max_redeem_percent' => $data[
                        'loyalty_max_redeem_percent'
                    ],
                ]);
            }, 3);
        } catch (\Throwable $exception) {
            if (
                $newLogoPath
                && Storage::disk('public')->exists($newLogoPath)
            ) {
                Storage::disk('public')->delete($newLogoPath);
            }

            throw $exception;
        }

        if (
            ($newLogoPath || $request->boolean('remove_logo'))
            && $oldLogoPath
            && $oldLogoPath !== $newLogoPath
            && Storage::disk('public')->exists($oldLogoPath)
        ) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        return back()->with(
            'success',
            'Pengaturan POS, tampilan, stok, dan poin pelanggan berhasil diperbarui.'
        );
    }
}
