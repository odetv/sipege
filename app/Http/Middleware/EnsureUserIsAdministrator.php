<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdministrator
{
    /**
     * Handle an incoming request.
     * Memastikan pengguna memiliki role administrator untuk mengakses area Admin Panel.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->role !== User::ROLE_ADMINISTRATOR && $user->role !== 'superadmin') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses ditolak. Area ini hanya untuk Administrator.'], 403);
            }

            return redirect()->route('dashboard')->with('error', 'Akses ditolak. Anda tidak memiliki izin untuk membuka Admin Panel.');
        }

        return $next($request);
    }
}

