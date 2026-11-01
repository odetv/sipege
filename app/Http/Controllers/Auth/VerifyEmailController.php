<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Tandai alamat email pengguna sebagai terverifikasi.
     * Dapat diakses baik pengguna sudah login maupun baru mengklik tautan dari inbox email.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = User::find($request->route('id'));

        if (!$user) {
            return redirect()->route('login')->with('error', 'Pengguna tidak ditemukan.');
        }

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return redirect()->route('login')->with('error', 'Tautan verifikasi tidak valid atau telah kedaluwarsa.');
        }

        if ($user->hasVerifiedEmail()) {
            if (Auth::check()) {
                return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
            }
            return redirect()->route('login')->with('status', 'Email Anda sudah terverifikasi sebelumnya. Silakan masuk.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Jika belum login, otomatis login pengguna tersebut
        if (!Auth::check()) {
            Auth::login($user);
        }

        return redirect()->intended(route('dashboard', absolute: false) . '?verified=1')->with('verified', true);
    }
}
