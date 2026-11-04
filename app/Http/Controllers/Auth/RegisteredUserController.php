<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Tampilkan halaman pendaftaran akun awal (hanya nama, email, password).
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Tangani permintaan registrasi akun awal.
     * Setelah terdaftar, sistem akan memverifikasi email dan paket langganan pengguna terlebih dahulu
     * sebelum pengguna melengkapi profil & data unit SPPG.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email ini sudah terdaftar di sistem.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user = User::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => User::ROLE_GUEST,
            'status_pembayaran' => 'belum_bayar',
            'status_onboarding' => 'belum_mulai',
            'paket_langganan' => User::PAKET_BASIC,
            'nominal_langganan' => 100000,
            'siklus_langganan' => User::SIKLUS_BULANAN,
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Setelah registrasi awal berhasil, arahkan ke verifikasi email terlebih dahulu
        return redirect()->route('verification.notice');
    }
}
