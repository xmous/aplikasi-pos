<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionInactivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $timeout = max(5, (int) config('pos-security.session_idle_minutes', 120)) * 60;
        $lastActivity = (int) $request->session()->get('pos_last_activity', time());

        if ((time() - $lastActivity) > $timeout) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Sesi berakhir karena tidak ada aktivitas.',
            ]);
        }

        $request->session()->put('pos_last_activity', time());

        return $next($request);
    }
}
