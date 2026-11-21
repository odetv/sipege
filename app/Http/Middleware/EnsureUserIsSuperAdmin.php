<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSuperAdmin
{
    /**
     * Handle an incoming request.
     * Memastikan pengguna memiliki role superadmin untuk mengakses area Platform Superadmin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->isSuperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses ditolak. Fitur ini hanya untuk Superadmin Platform.'], 403);
            }

            return redirect()->route('dashboard')->with('error', 'Akses ditolak. Fitur pengaturan platform hanya untuk Superadmin.');
        }

        return $next($request);
    }
}

