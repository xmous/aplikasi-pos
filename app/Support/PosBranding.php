<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\Store;
use Illuminate\Support\Facades\Storage;

class PosBranding
{
    public static function defaults(): array
    {
        return [
            'brand_kicker' => 'GENERAL AFFAIR · SIGAP & HANDAL',
            'brand_tagline' => 'Sigap & Handal',
            'login_hero_title' => 'Kelola operasional dan stok dalam satu tempat.',
            'login_hero_description' => 'Pantau penjualan, persediaan, pembelian, audit, dan laporan dengan data yang terhubung serta mudah ditindaklanjuti.',
            'login_welcome_title' => 'Selamat datang',
            'login_welcome_description' => 'Masukkan akun Anda untuk melanjutkan ke sistem pengelolaan stok.',
            'login_benefit_1' => 'Data terpusat',
            'login_benefit_2' => 'Pemantauan cepat',
            'login_benefit_3' => 'Akses terkontrol',
            'login_button_text' => 'Masuk ke Sistem',
            'login_security_text' => 'Sesi dan informasi akun Anda dilindungi.',
            'theme_preset' => 'navy_gold',
            'font_scale' => 'small',
            'appearance_mode' => 'light',
            'surface_finish' => 'normal',
        ];
    }

    public static function themePresets(): array
    {
        return [
            'navy_gold' => [
                'label' => 'Ocean Blue',
                'description' => 'Biru retail yang terang dan terpercaya.',
                'navy' => '#0f3a6d',
                'blue' => '#1466b8',
                'blue_2' => '#2b8de0',
                'accent' => '#ffc928',
                'canvas' => '#f3f8ff',
                'surface' => '#ffffff',
                'text' => '#183153',
                'muted' => '#64748b',
                'border' => '#d8e6f4',
                'sidebar_end' => '#14538f',
                'table_head' => '#0f4c81',
                'table_head_text' => '#ffffff',
            ],
            'emerald' => [
                'label' => 'Emerald Retail',
                'description' => 'Hijau segar untuk kas dan penjualan.',
                'navy' => '#0b4b3e',
                'blue' => '#0d8a6a',
                'blue_2' => '#20b486',
                'accent' => '#ffd166',
                'canvas' => '#f2fbf7',
                'surface' => '#ffffff',
                'text' => '#173f37',
                'muted' => '#657d77',
                'border' => '#d4ebe3',
                'sidebar_end' => '#08745a',
                'table_head' => '#08745a',
                'table_head_text' => '#ffffff',
            ],
            'purple' => [
                'label' => 'Royal Purple',
                'description' => 'Ungu modern untuk POS premium.',
                'navy' => '#493178',
                'blue' => '#7551b6',
                'blue_2' => '#9a6ee8',
                'accent' => '#ffd166',
                'canvas' => '#f8f5ff',
                'surface' => '#ffffff',
                'text' => '#3f315d',
                'muted' => '#776c8f',
                'border' => '#e4dcf4',
                'sidebar_end' => '#604497',
                'table_head' => '#5c3b91',
                'table_head_text' => '#ffffff',
            ],
            'teal' => [
                'label' => 'Teal Commerce',
                'description' => 'Cerah, bersih, dan mudah dipantau.',
                'navy' => '#074b59',
                'blue' => '#07869b',
                'blue_2' => '#18b7c9',
                'accent' => '#ffcc4d',
                'canvas' => '#f1fbfc',
                'surface' => '#ffffff',
                'text' => '#163e47',
                'muted' => '#607e85',
                'border' => '#d2e9ed',
                'sidebar_end' => '#066d80',
                'table_head' => '#066d80',
                'table_head_text' => '#ffffff',
            ],
            'slate' => [
                'label' => 'Graphite Orange',
                'description' => 'Netral dengan aksen oranye khas kasir.',
                'navy' => '#334155',
                'blue' => '#52677d',
                'blue_2' => '#708aa6',
                'accent' => '#ff9f43',
                'canvas' => '#f5f7fa',
                'surface' => '#ffffff',
                'text' => '#26384c',
                'muted' => '#6c7c8f',
                'border' => '#dbe2ea',
                'sidebar_end' => '#43566c',
                'table_head' => '#34495e',
                'table_head_text' => '#ffffff',
            ],
            'maroon' => [
                'label' => 'Ruby Red',
                'description' => 'Merah hangat dan tegas untuk transaksi.',
                'navy' => '#7a2438',
                'blue' => '#b63c53',
                'blue_2' => '#e2556c',
                'accent' => '#ffd166',
                'canvas' => '#fff4f6',
                'surface' => '#ffffff',
                'text' => '#54232e',
                'muted' => '#846a70',
                'border' => '#f0d9df',
                'sidebar_end' => '#982f45',
                'table_head' => '#8d2e43',
                'table_head_text' => '#ffffff',
            ],
            'sky' => [
                'label' => 'Sky Blue',
                'description' => 'Biru muda yang ramah dan terang.',
                'navy' => '#125a8b',
                'blue' => '#1d8ed1',
                'blue_2' => '#45b8f4',
                'accent' => '#ffd166',
                'canvas' => '#f2faff',
                'surface' => '#ffffff',
                'text' => '#1d4561',
                'muted' => '#668397',
                'border' => '#d6eaf6',
                'sidebar_end' => '#146b9f',
                'table_head' => '#146b9f',
                'table_head_text' => '#ffffff',
            ],
            'orange' => [
                'label' => 'Tangerine POS',
                'description' => 'Oranye energik untuk toko dan F&B.',
                'navy' => '#8a3d0a',
                'blue' => '#d85a12',
                'blue_2' => '#ff7a1a',
                'accent' => '#ffe08a',
                'canvas' => '#fff8f1',
                'surface' => '#ffffff',
                'text' => '#5a321c',
                'muted' => '#8a725f',
                'border' => '#f0dfd0',
                'sidebar_end' => '#a8450c',
                'table_head' => '#a8450c',
                'table_head_text' => '#ffffff',
            ],
            'indigo' => [
                'label' => 'Indigo Modern',
                'description' => 'Indigo cerah untuk dashboard modern.',
                'navy' => '#293b7a',
                'blue' => '#465bc0',
                'blue_2' => '#6f7de8',
                'accent' => '#ffd166',
                'canvas' => '#f5f6ff',
                'surface' => '#ffffff',
                'text' => '#303b68',
                'muted' => '#70789a',
                'border' => '#dde1f3',
                'sidebar_end' => '#374b96',
                'table_head' => '#344a92',
                'table_head_text' => '#ffffff',
            ],
        ];
    }

    public static function fontScales(): array
    {
        return [
            'small' => [
                'label' => 'Kecil',
                'description' => 'Ukuran saat ini, paling banyak memuat data.',
                'root_size' => '16px',
                'preview' => 'Aa',
            ],
            'medium' => [
                'label' => 'Sedang',
                'description' => 'Lebih nyaman untuk pemakaian harian.',
                'root_size' => '17.5px',
                'preview' => 'Aa',
            ],
            'large' => [
                'label' => 'Besar',
                'description' => 'Lebih mudah dibaca pada monitor besar.',
                'root_size' => '19px',
                'preview' => 'Aa',
            ],
        ];
    }

    public static function appearanceModes(): array
    {
        return [
            'light' => [
                'label' => 'Terang',
                'description' => 'Tampilan cerah untuk ruangan terang.',
                'icon' => 'fa-sun',
            ],
            'dark' => [
                'label' => 'Gelap',
                'description' => 'Nyaman digunakan pada malam hari.',
                'icon' => 'fa-moon',
            ],
            'system' => [
                'label' => 'Ikuti Sistem',
                'description' => 'Mengikuti mode Windows atau browser.',
                'icon' => 'fa-circle-half-stroke',
            ],
        ];
    }

    public static function surfaceFinishes(): array
    {
        return [
            'normal' => [
                'label' => 'Biasa',
                'description' => 'Gradient dan bayangan seimbang untuk pemakaian harian.',
                'icon' => 'fa-layer-group',
            ],
            'matte' => [
                'label' => 'Matte',
                'description' => 'Permukaan lebih tenang dengan bayangan dan kilau minimal.',
                'icon' => 'fa-minus',
            ],
            'glossy' => [
                'label' => 'Glossy',
                'description' => 'Warna, kedalaman, dan highlight dibuat lebih menonjol.',
                'icon' => 'fa-wand-magic-sparkles',
            ],
        ];
    }

    public static function resolve(): array
    {
        $store = Store::query()->first();
        $settings = Setting::query()
            ->whereIn('key', array_keys(self::defaults()))
            ->pluck('value', 'key')
            ->all();

        $values = array_merge(self::defaults(), $settings);
        $themes = self::themePresets();
        $fontScales = self::fontScales();
        $appearanceModes = self::appearanceModes();
        $surfaceFinishes = self::surfaceFinishes();

        $themeKey = array_key_exists($values['theme_preset'], $themes)
            ? $values['theme_preset']
            : self::defaults()['theme_preset'];

        $fontScale = array_key_exists($values['font_scale'], $fontScales)
            ? $values['font_scale']
            : self::defaults()['font_scale'];

        $appearanceMode = array_key_exists(
            $values['appearance_mode'],
            $appearanceModes
        )
            ? $values['appearance_mode']
            : self::defaults()['appearance_mode'];

        $surfaceFinish = array_key_exists(
            $values['surface_finish'],
            $surfaceFinishes
        )
            ? $values['surface_finish']
            : self::defaults()['surface_finish'];

        $hasCustomLogo = (bool) (
            $store?->logo_path
            && Storage::disk('public')->exists($store->logo_path)
        );

        $logoVersion = $store?->updated_at?->timestamp ?: 1;
        $logoUrl = route(
            'branding.logo',
            ['v' => $logoVersion],
            false
        );

        return [
            'store' => $store,
            'settings' => $values,
            'themes' => $themes,
            'theme_key' => $themeKey,
            'theme' => $themes[$themeKey],
            'font_scales' => $fontScales,
            'font_scale' => $fontScale,
            'root_font_size' => $fontScales[$fontScale]['root_size'],
            'appearance_modes' => $appearanceModes,
            'appearance_mode' => $appearanceMode,
            'surface_finishes' => $surfaceFinishes,
            'surface_finish' => $surfaceFinish,
            'logo_url' => $logoUrl,
            'has_custom_logo' => $hasCustomLogo,
            'store_name' => $store?->name ?: 'POS & Stok',
            'brand_kicker' => $values['brand_kicker'],
            'brand_tagline' => $values['brand_tagline'],
            'login_hero_title' => $values['login_hero_title'],
            'login_hero_description' => $values['login_hero_description'],
            'login_welcome_title' => $values['login_welcome_title'],
            'login_welcome_description' => $values['login_welcome_description'],
            'login_benefit_1' => $values['login_benefit_1'],
            'login_benefit_2' => $values['login_benefit_2'],
            'login_benefit_3' => $values['login_benefit_3'],
            'login_button_text' => $values['login_button_text'],
            'login_security_text' => $values['login_security_text'],
        ];
    }

    public static function cssVariables(array $theme): string
    {
        return collect([
            '--ga-navy' => $theme['navy'],
            '--ga-blue' => $theme['blue'],
            '--ga-blue-2' => $theme['blue_2'],
            '--ga-gold' => $theme['accent'],
            '--ga-canvas' => $theme['canvas'],
            '--ga-surface' => $theme['surface'],
            '--ga-text' => $theme['text'],
            '--ga-muted' => $theme['muted'],
            '--ga-border' => $theme['border'],
            '--ga-sidebar-end' => $theme['sidebar_end'],
            '--ga-table-head' => $theme['table_head'],
            '--ga-table-head-text' => $theme['table_head_text'],
        ])->map(
            fn (string $value, string $key): string => $key.':'.$value
        )->implode(';');
    }
}
