<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaketLanggananController extends Controller
{
    /**
     * Tampilkan halaman pilihan paket langganan aplikasi.
     * Jika paket langganan sudah aktif / terverifikasi valid, pengguna diarahkan ke onboarding.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Jika status langganan sudah valid, arahkan ke onboarding
        if ($user->isPaymentVerified()) {
            if ($user->isProfileCompleted()) {
                return redirect()->route('dashboard');
            }
            return redirect()->route('onboarding.index');
        }

        $paketAktif = $user->paket_langganan ?? User::PAKET_BASIC;
        $siklusAktif = $user->siklus_langganan ?? User::SIKLUS_BULANAN;
        $daftarPaket = User::DAFTAR_PAKET;
        $infoPaket = $daftarPaket[$paketAktif] ?? $daftarPaket[User::PAKET_BASIC];

        // Hitung nominal dan periode berdasarkan paket dan siklus
        if ($paketAktif === User::PAKET_FREE) {
            $siklusAktif = User::SIKLUS_TRIAL_3_HARI;
            $nominal = 0;
            $durasiText = '3 Hari Operasional (Uji Coba Trial)';
        } elseif ($siklusAktif === User::SIKLUS_TAHUNAN) {
            $nominal = (float) $infoPaket['harga_tahunan'];
            $durasiText = '1 Tahun (12 Bulan Operasional)';
        } else {
            $siklusAktif = User::SIKLUS_BULANAN;
            $nominal = (float) $infoPaket['harga_bulanan'];
            $durasiText = '1 Bulan (30 Hari Operasional)';
        }

        $nomorInvoice = 'INV-SUB-' . date('Ym') . '-' . str_pad($user->id, 4, '0', STR_PAD_LEFT);

        return Inertia::render('Auth/PaketLangganan', [
            'user' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->email,
                'role' => $user->role,
                'status_pembayaran' => $user->status_pembayaran,
                'status_onboarding' => $user->status_onboarding,
                'paket_langganan' => $paketAktif,
                'siklus_langganan' => $siklusAktif,
                'nominal_langganan' => $nominal,
                'catatan_pembayaran' => $user->catatan_pembayaran,
                'tgl_pembayaran' => $user->tgl_pembayaran ? $user->tgl_pembayaran->format('Y-m-d H:i:s') : null,
                'created_at' => $user->created_at ? $user->created_at->format('d M Y, H:i') : null,
            ],
            'daftar_paket' => $daftarPaket,
            'invoice' => [
                'nomor' => $nomorInvoice,
                'tanggal' => date('d F Y'),
                'jatuh_tempo' => date('d F Y', strtotime('+3 days')),
                'item' => $paketAktif === User::PAKET_FREE
                    ? 'Paket Free Trial 3 Hari (Bila Disetujui) - Sistem Pengelolaan SPPG (SIPEGE)'
                    : 'Paket Langganan ' . $infoPaket['nama'] . ' (' . ucfirst($siklusAktif) . ') - SIPEGE',
                'periode' => $durasiText,
                'subtotal' => $nominal,
                'pajak' => 0,
                'total' => $nominal,
                'is_free' => $paketAktif === User::PAKET_FREE,
            ],
            'rekening' => [
                [
                    'bank' => 'BCA',
                    'nomor' => '8730-1234-5678',
                    'atas_nama' => 'PENGELOLA APLIKASI SIPEGE',
                    'logo' => 'BCA',
                ],
                [
                    'bank' => 'Mandiri',
                    'nomor' => '137-00-9876543-2',
                    'atas_nama' => 'PENGELOLA APLIKASI SIPEGE',
                    'logo' => 'MANDIRI',
                ],
                [
                    'bank' => 'BRI',
                    'nomor' => '0206-01-002345-50-8',
                    'atas_nama' => 'PENGELOLA APLIKASI SIPEGE',
                    'logo' => 'BRI',
                ],
            ],
        ]);
    }

    /**
     * Ubah pilihan paket langganan dan siklus penagihan (bulanan / tahunan).
     */
    public function updatePaket(Request $request): RedirectResponse
    {
        $request->validate([
            'paket_langganan' => ['required', 'string', 'in:free,basic,standart,pro'],
            'siklus_langganan' => ['nullable', 'string', 'in:bulanan,tahunan,3_hari'],
        ]);

        $user = $request->user();
        $paket = $request->paket_langganan;
        $infoPaket = User::DAFTAR_PAKET[$paket];

        if ($paket === User::PAKET_FREE) {
            $siklus = User::SIKLUS_TRIAL_3_HARI;
            $nominal = 0;
        } else {
            $siklus = $request->siklus_langganan === User::SIKLUS_TAHUNAN ? User::SIKLUS_TAHUNAN : User::SIKLUS_BULANAN;
            $nominal = $siklus === User::SIKLUS_TAHUNAN ? $infoPaket['harga_tahunan'] : $infoPaket['harga_bulanan'];
        }

        $user->update([
            'paket_langganan' => $paket,
            'siklus_langganan' => $siklus,
            'nominal_langganan' => $nominal,
        ]);

        return back()->with('success', 'Paket langganan diperbarui ke ' . $infoPaket['nama'] . ' (' . ucfirst($siklus) . ').');
    }

    /**
     * Ajukan persetujuan paket Free 3 Hari ke Administrator.
     */
    public function ajukanFree(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'paket_langganan' => User::PAKET_FREE,
            'siklus_langganan' => User::SIKLUS_TRIAL_3_HARI,
            'nominal_langganan' => 0,
            'catatan_pembayaran' => 'Pengajuan Paket Free 3 Hari berhasil dikirim. Menunggu persetujuan admin.',
        ]);

        return back()->with('info', 'Pengajuan Paket Free 3 Hari berhasil dikirim. Akses akan dibuka setelah disetujui admin.');
    }

    /**
     * Fitur simulasi pembayaran paket langganan valid / approval free (Dev/Demo Mode).
     * Mengubah role menjadi 'member', status pembayaran 'lunas', dan mengarahkan ke onboarding.
     */
    public function simulasiBayar(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isFree = $user->paket_langganan === User::PAKET_FREE;

        $catatan = $isFree
            ? 'Pengajuan Paket Free 3 Hari telah disetujui admin.'
            : 'Pembayaran paket langganan telah berhasil diverifikasi valid.';

        $user->update([
            'role' => User::ROLE_MEMBER, // Ketika sudah membayar/disetujui baru dapat akses role member!
            'status_pembayaran' => 'lunas',
            'tgl_pembayaran' => now(),
            'status_onboarding' => 'menunggu_data',
            'catatan_pembayaran' => $catatan,
        ]);

        $msg = $isFree
            ? 'Paket Free 3 Hari Anda telah disetujui admin! Role Anda kini adalah Member Aktif. Silakan lanjutkan inisialisasi data profil dan unit SPPG.'
            : 'Pembayaran paket langganan berhasil diverifikasi! Role Anda kini adalah Member Aktif. Silakan lanjutkan inisialisasi data profil dan unit SPPG.';

        return redirect()->route('onboarding.index')->with('success', $msg);
    }

    /**
     * Konfirmasi pengiriman bukti pembayaran paket langganan oleh pengguna.
     */
    public function konfirmasi(Request $request): RedirectResponse
    {
        $request->validate([
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $user->update([
            'catatan_pembayaran' => $request->catatan ?? 'Menunggu konfirmasi verifikasi admin.',
        ]);

        return back()->with('info', 'Konfirmasi pembayaran paket langganan telah dicatat. Admin kami akan segera melakukan verifikasi.');
    }
}
