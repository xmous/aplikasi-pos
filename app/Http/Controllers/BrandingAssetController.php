<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandingAssetController extends Controller
{
    public function __invoke(): BinaryFileResponse|Response
    {
        $store = Store::query()->first();
        $disk = Storage::disk('public');
        $logoPath = $store?->logo_path;

        $headers = [
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if (
            $logoPath
            && $disk->exists($logoPath)
        ) {
            $mimeType = $disk->mimeType($logoPath);

            if ($mimeType) {
                $headers['Content-Type'] = $mimeType;
            }

            return response()->file(
                $disk->path($logoPath),
                $headers
            );
        }

        $defaultLogo = public_path(
            'images/general-affair-logo.png'
        );

        if (is_file($defaultLogo)) {
            return response()->file(
                $defaultLogo,
                $headers
            );
        }

        $storeName = e($store?->name ?: 'POS');
        $initial = e(
            mb_strtoupper(
                mb_substr($store?->name ?: 'P', 0, 1)
            )
        );

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240" viewBox="0 0 240 240">
    <rect width="240" height="240" rx="40" fill="#173d79"/>
    <circle cx="120" cy="96" r="58" fill="#ffbd12"/>
    <text x="120" y="118" text-anchor="middle" font-family="Arial, sans-serif" font-size="62" font-weight="700" fill="#173d79">{$initial}</text>
    <text x="120" y="188" text-anchor="middle" font-family="Arial, sans-serif" font-size="20" font-weight="700" fill="#ffffff">{$storeName}</text>
</svg>
SVG;

        return response(
            $svg,
            200,
            [
                ...$headers,
                'Content-Type' => 'image/svg+xml; charset=UTF-8',
            ]
        );
    }
}
