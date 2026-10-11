<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $vite = app()->isLocal() ? ' http://localhost:5173 http://127.0.0.1:5173' : '';
        $viteConnect = app()->isLocal() ? ' ws://localhost:5173 ws://127.0.0.1:5173 http://localhost:5173 http://127.0.0.1:5173' : '';
        $upgradeInsecureRequests = app()->environment('production') ? '; upgrade-insecure-requests' : '';

        $csp = "default-src 'self'; "
            ."base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; "
            ."img-src 'self' data: blob:; font-src 'self' data:; "
            ."style-src 'self' 'unsafe-inline'{$vite}; "
            ."script-src 'self'{$vite}; "
            ."connect-src 'self'{$viteConnect}{$upgradeInsecureRequests}";

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        if ($request->isSecure() && app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
