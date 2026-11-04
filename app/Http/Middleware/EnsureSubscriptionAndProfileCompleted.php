<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionAndProfileCompleted
{
    /**
     * Handle an incoming request.
     * Memastikan pengguna telah menyelesaikan verifikasi paket langganan aplikasi
     * dan melengkapi data profil & unit SPPG sebelum dapat mengakses sistem operasional.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Belum login dibiarkan lewat ke middleware auth standar
        if (!$user) {
            return $next($request);
        }

        // Administrator / Superadmin selalu diizinkan akses penuh tanpa batas
        if ($user->role === 'administrator' || $user->role === 'superadmin') {
            return $next($request);
        }

        // Pengecualian rute dasar otentikasi agar tidak terjadi redirect loop
        if ($request->routeIs(
            'logout',
            'verification.*',
            'password.*',
            'api.wilayah.*'
        )) {
            return $next($request);
        }

        // 1. Cek verifikasi email pengguna
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Pengecualian rute paket langganan setelah email terverifikasi
        if ($request->routeIs('paket-langganan.*', 'pembayaran.*')) {
            return $next($request);
        }

        // 2. Cek status pembayaran paket langganan (Role Member)
        if (!$user->isPaymentVerified()) {
            return redirect()->route('paket-langganan.index');
        }

        // Pengecualian rute onboarding setelah pembayaran valid
        if ($request->routeIs('onboarding.*')) {
            return $next($request);
        }

        // 3. Cek kelengkapan data profil dan Unit SPPG (inisialisasi awal)
        // Pengguna diarahkan ke /onboarding untuk melengkapi data terlebih dahulu
        if (!$user->isProfileCompleted()) {
            return redirect()->route('onboarding.index');
        }

        return $next($request);
    }
}

