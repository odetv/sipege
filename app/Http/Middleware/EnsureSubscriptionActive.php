<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Superadmin selalu memiliki akses penuh tanpa batasan masa aktif
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Rute-rute yang dikecualikan (tetap boleh diakses saat langganan expired)
        $exemptRoutePatterns = [
            'paket-langganan.*',
            'pembayaran.*',
            'onboarding.*',
            'verification.*',
            'password.*',
            'logout',
            'sanctum.*',
        ];

        foreach ($exemptRoutePatterns as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        // Cek apakah masa aktif langganan masih valid
        if (!$user->isSubscriptionActive()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Masa aktif langganan SPPG Anda telah berakhir. Silakan lakukan perpanjangan langganan.',
                    'subscription_expired' => true,
                ], 403);
            }

            return redirect()->route('paket-langganan.index')->with('warning', 'Masa aktif paket langganan SPPG Anda telah berakhir. Silakan pilih paket atau perpanjang sewa untuk melanjutkan operasional.');
        }

        return $next($request);
    }
}

