<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UnitSppg;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PenggunaController extends Controller
{
    /**
     * Tampilkan halaman monitoring & manajemen data pengguna untuk Administrator.
     */
    public function index(Request $request): Response
    {
        $query = User::with('unitSppg');

        // Pencarian (Nama, Email, NIK, Telepon, ID SPPG, Nama Unit)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('telepon', 'like', "%{$search}%")
                  ->orWhereHas('unitSppg', function ($uq) use ($search) {
                      $uq->where('id_sppg', 'like', "%{$search}%")
                         ->orWhere('nama', 'like', "%{$search}%")
                         ->orWhere('kode_sppg', 'like', "%{$search}%");
                  });
            });
        }

        // Filter Role
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        // Filter Paket Langganan
        if ($paket = $request->input('paket')) {
            $query->where('paket_langganan', $paket);
        }

        // Filter Status Pembayaran
        if ($statusBayar = $request->input('status_pembayaran')) {
            $query->where('status_pembayaran', $statusBayar);
        }

        // Filter Status Onboarding
        if ($statusOnboarding = $request->input('status_onboarding')) {
            $query->where('status_onboarding', $statusOnboarding);
        }

        // Filter Verifikasi Email
        if ($emailVerified = $request->input('email_verified')) {
            if ($emailVerified === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($emailVerified === 'unverified') {
                $query->whereNull('email_verified_at');
            }
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['id', 'nama', 'email', 'role', 'paket_langganan', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $users = $query->paginate(15)->withQueryString()->through(function ($u) {
            return [
                'id' => $u->id,
                'nama' => $u->nama,
                'nama_lengkap' => $u->nama_lengkap,
                'gelar_depan' => $u->gelar_depan,
                'gelar_belakang' => $u->gelar_belakang,
                'email' => $u->email,
                'email_verified_at' => $u->email_verified_at ? $u->email_verified_at->format('d M Y, H:i') : null,
                'is_email_verified' => $u->hasVerifiedEmail(),
                'role' => $u->role,
                'status_pembayaran' => $u->status_pembayaran,
                'paket_langganan' => $u->paket_langganan ?? 'basic',
                'siklus_langganan' => $u->siklus_langganan ?? 'bulanan',
                'nominal_langganan' => (float) $u->nominal_langganan,
                'tgl_pembayaran' => $u->tgl_pembayaran ? $u->tgl_pembayaran->format('d M Y, H:i') : null,
                'catatan_pembayaran' => $u->catatan_pembayaran,
                'status_onboarding' => $u->status_onboarding,
                'is_onboarding_completed' => $u->isProfileCompleted(),
                'nik' => $u->nik,
                'nip' => $u->nip,
                'agama' => $u->agama,
                'jenis_kelamin' => $u->jenis_kelamin,
                'tempat_lahir' => $u->tempat_lahir,
                'tanggal_lahir' => $u->tanggal_lahir ? $u->tanggal_lahir->format('d M Y') : null,
                'jenjang_pendidikan' => $u->jenjang_pendidikan,
                'bidang_pendidikan' => $u->bidang_pendidikan,
                'status_kawin' => $u->status_kawin,
                'telepon' => $u->telepon,
                'alamat_lengkap_ktp' => $u->alamat_lengkap_ktp,
                'alamat_lengkap_domisili' => $u->alamat_lengkap_domisili,
                'provinsi_domisili' => $u->provinsi_domisili,
                'kabupaten_domisili' => $u->kabupaten_domisili,
                'latitude_domisili' => $u->latitude_domisili,
                'longitude_domisili' => $u->longitude_domisili,
                'created_at' => $u->created_at ? $u->created_at->format('d M Y, H:i') : null,
                'unit_sppg' => $u->unitSppg ? [
                    'id' => $u->unitSppg->id,
                    'id_sppg' => $u->unitSppg->id_sppg,
                    'kode_sppg' => $u->unitSppg->kode_sppg,
                    'nama' => $u->unitSppg->nama,
                    'status' => $u->unitSppg->status,
                    'tanggal_operasional' => $u->unitSppg->tanggal_operasional ? $u->unitSppg->tanggal_operasional->format('d M Y') : null,
                    'provinsi' => $u->unitSppg->provinsi,
                    'kabupaten' => $u->unitSppg->kabupaten,
                    'kecamatan' => $u->unitSppg->kecamatan,
                    'desa_kelurahan' => $u->unitSppg->desa_kelurahan,
                    'kode_pos' => $u->unitSppg->kode_pos,
                    'alamat_lengkap' => $u->unitSppg->alamat_lengkap,
                    'latitude' => $u->unitSppg->latitude,
                    'longitude' => $u->unitSppg->longitude,
                ] : null,
            ];
        });

        // Ringkasan Statistik Monitoring
        $stats = [
            'total_users' => User::count(),
            'total_administrators' => User::where('role', User::ROLE_ADMINISTRATOR)->count(),
            'total_members' => User::where('role', User::ROLE_MEMBER)->count(),
            'total_guests' => User::where('role', User::ROLE_GUEST)->count(),
            'total_verified_emails' => User::whereNotNull('email_verified_at')->count(),
            'total_unverified_emails' => User::whereNull('email_verified_at')->count(),
            'total_onboarded' => User::where('status_onboarding', 'selesai')->count(),
            'total_pending_onboarding' => User::where('status_onboarding', '!=', 'selesai')->count(),
            'paket_free' => User::where('paket_langganan', User::PAKET_FREE)->count(),
            'paket_basic' => User::where('paket_langganan', User::PAKET_BASIC)->count(),
            'paket_standart' => User::where('paket_langganan', User::PAKET_STANDART)->count(),
            'paket_pro' => User::where('paket_langganan', User::PAKET_PRO)->count(),
        ];

        return Inertia::render('Admin/Pengguna/Index', [
            'users' => $users,
            'stats' => $stats,
            'filters' => [
                'search' => $request->input('search', ''),
                'role' => $request->input('role', ''),
                'paket' => $request->input('paket', ''),
                'status_pembayaran' => $request->input('status_pembayaran', ''),
                'status_onboarding' => $request->input('status_onboarding', ''),
                'email_verified' => $request->input('email_verified', ''),
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
            'daftar_paket' => User::DAFTAR_PAKET,
        ]);
    }

    /**
     * Ubah role dan status akun pengguna secara manual oleh Administrator.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        // Mencegah administrator mengedit akun dirinya sendiri di panel pengguna
        if ($currentUser->id === $user->id) {
            return back()->with('error', 'Anda tidak dapat mengedit akun Anda sendiri melalui panel ini.');
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:administrator,member,guest'],
            'paket_langganan' => ['nullable', 'string', 'in:free,basic,standart,pro'],
            'status_pembayaran' => ['nullable', 'string', 'in:lunas,belum_bayar,menunggu_pembayaran'],
            'status_onboarding' => ['nullable', 'string', 'in:selesai,menunggu_data,belum_mulai'],
        ]);

        $user->update($validated);

        return back()->with('success', "Status dan role pengguna {$user->nama} ({$user->email}) berhasil diperbarui.");
    }

    /**
     * Verifikasi email pengguna secara manual oleh Administrator.
     */
    public function verifyEmailManual(Request $request, User $user): RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return back()->with('info', 'Email pengguna ini sudah terverifikasi sebelumnya.');
        }

        $user->markEmailAsVerified();

        return back()->with('success', "Email pengguna {$user->email} berhasil ditandai sebagai terverifikasi.");
    }

    /**
     * Hapus akun pengguna oleh Administrator (dengan proteksi akun sendiri).
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $nama = $user->nama;
        $email = $user->email;

        // Hapus unit SPPG jika ada
        if ($user->unitSppg) {
            $user->unitSppg->delete();
        }

        $user->delete();

        return back()->with('success', "Akun pengguna {$nama} ({$email}) berhasil dihapus dari sistem.");
    }
}

