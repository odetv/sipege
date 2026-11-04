<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UnitSppg;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /**
     * Tampilkan halaman onboarding kelengkapan data profil Kepala SPPG & Unit SPPG.
     * Hanya dapat diakses jika pembayaran sewa aplikasi sudah terverifikasi valid.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // 1. Cek apakah status pembayaran paket langganan sudah valid (Role Member)
        if (!$user->isPaymentVerified()) {
            return redirect()->route('paket-langganan.index')->with('warning', 'Silakan selesaikan pemilihan paket langganan terlebih dahulu.');
        }

        // 2. Jika profil sudah lengkap, arahkan langsung ke dashboard
        if ($user->isProfileCompleted()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Onboarding', [
            'user' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->email,
                'role' => $user->role,
                'status_pembayaran' => $user->status_pembayaran,
                'status_onboarding' => $user->status_onboarding,
            ],
        ]);
    }

    /**
     * Simpan kelengkapan data profil Kepala SPPG dan Data Unit Operasional SPPG.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user->isPaymentVerified()) {
            return redirect()->route('paket-langganan.index')->with('warning', 'Status pembayaran paket langganan belum terverifikasi.');
        }

        $request->validate([
            // Data Profil Pengguna (Kepala SPPG)
            'nik' => ['required', 'numeric', 'digits:16', 'unique:users,nik,' . $user->id],
            'nip' => ['nullable', 'numeric'],
            'nama' => ['required', 'string', 'max:255'],
            'gelar_depan' => ['nullable', 'string', 'max:50'],
            'gelar_belakang' => ['nullable', 'string', 'max:50'],
            'agama' => ['required', 'string', 'max:50'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tempat_lahir' => ['required', 'regex:/^[a-zA-Z\s\.\,\-]+$/u', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],
            'jenjang_pendidikan' => ['required', 'string', 'max:50'],
            'bidang_pendidikan' => ['required', 'string', 'max:100'],
            'status_kawin' => ['required', 'in:Belum Menikah,Menikah,Janda,Duda'],
            'provinsi_ktp' => ['required', 'string', 'max:100'],
            'kabupaten_ktp' => ['required', 'string', 'max:100'],
            'kecamatan_ktp' => ['required', 'string', 'max:100'],
            'desa_kelurahan_ktp' => ['required', 'string', 'max:100'],
            'kode_pos_ktp' => ['required', 'numeric', 'digits:5'],
            'alamat_lengkap_ktp' => ['required', 'string'],
            'provinsi_domisili' => ['required', 'string', 'max:100'],
            'kabupaten_domisili' => ['required', 'string', 'max:100'],
            'kecamatan_domisili' => ['required', 'string', 'max:100'],
            'desa_kelurahan_domisili' => ['required', 'string', 'max:100'],
            'kode_pos_domisili' => ['required', 'numeric', 'digits:5'],
            'alamat_lengkap_domisili' => ['required', 'string'],
            'latitude_domisili' => ['required', 'numeric'],
            'longitude_domisili' => ['required', 'numeric'],
            'telepon' => ['required', 'string', 'regex:/^62[0-9]{8,15}$/'],

            // Data Unit SPPG
            'id_sppg' => ['required', 'string', 'size:8', 'alpha_num', 'unique:unit_sppg,id_sppg'],
            'kode_sppg' => ['required', 'string', 'max:100'],
            'unit_nama' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Belum Operasional,Operasional,Suspend Ringan,Suspend Sedang,Suspend Berat,Suspend Permanen'],
            'tanggal_operasional' => ['nullable', 'date'],
            'unit_provinsi' => ['required', 'string', 'max:100'],
            'unit_kabupaten' => ['required', 'string', 'max:100'],
            'unit_kecamatan' => ['required', 'string', 'max:100'],
            'unit_desa_kelurahan' => ['required', 'string', 'max:100'],
            'unit_latitude' => ['required', 'numeric'],
            'unit_longitude' => ['required', 'numeric'],
            'kode_pos' => ['required', 'numeric'],
            'alamat_lengkap' => ['required', 'string'],
        ], [
            'nik.digits' => 'NIK harus berjumlah tepat 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar di sistem.',
            'telepon.regex' => 'Format nomor telepon tidak valid. Pastikan diawali dengan format Indonesia (62...).',
            'id_sppg.size' => 'ID SPPG harus tepat 8 digit karakter alfanumerik.',
            'id_sppg.unique' => 'ID SPPG ini sudah digunakan.',
            'tempat_lahir.regex' => 'Tempat lahir hanya boleh berupa huruf.',
        ]);

        DB::transaction(function () use ($request, $user) {
            $user->update([
                'nik' => $request->nik,
                'nip' => $request->nip,
                'nama' => $request->nama,
                'gelar_depan' => $request->gelar_depan,
                'gelar_belakang' => $request->gelar_belakang,
                'agama' => $request->agama,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tempat_lahir' => $request->tempat_lahir,
                'tanggal_lahir' => $request->tanggal_lahir,
                'jenjang_pendidikan' => $request->jenjang_pendidikan,
                'bidang_pendidikan' => $request->bidang_pendidikan,
                'status_kawin' => $request->status_kawin,
                'provinsi_ktp' => $request->provinsi_ktp,
                'kabupaten_ktp' => $request->kabupaten_ktp,
                'kecamatan_ktp' => $request->kecamatan_ktp,
                'desa_kelurahan_ktp' => $request->desa_kelurahan_ktp,
                'kode_pos_ktp' => $request->kode_pos_ktp ?? $request->kode_pos,
                'alamat_lengkap_ktp' => $request->alamat_lengkap_ktp,
                'provinsi_domisili' => $request->provinsi_domisili,
                'kabupaten_domisili' => $request->kabupaten_domisili,
                'kecamatan_domisili' => $request->kecamatan_domisili,
                'desa_kelurahan_domisili' => $request->desa_kelurahan_domisili,
                'kode_pos_domisili' => $request->kode_pos_domisili ?? $request->kode_pos,
                'alamat_lengkap_domisili' => $request->alamat_lengkap_domisili,
                'latitude_domisili' => $request->latitude_domisili,
                'longitude_domisili' => $request->longitude_domisili,
                'telepon' => $request->telepon,
                'status_onboarding' => 'selesai',
            ]);

            UnitSppg::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'id_sppg' => strtoupper($request->id_sppg),
                    'kode_sppg' => $request->kode_sppg,
                    'nama' => $request->unit_nama,
                    'status' => $request->status,
                    'tanggal_operasional' => $request->tanggal_operasional,
                    'provinsi' => $request->unit_provinsi,
                    'kabupaten' => $request->unit_kabupaten,
                    'kecamatan' => $request->unit_kecamatan,
                    'desa_kelurahan' => $request->unit_desa_kelurahan,
                    'latitude' => $request->unit_latitude,
                    'longitude' => $request->unit_longitude,
                    'kode_pos' => $request->kode_pos,
                    'alamat_lengkap' => $request->alamat_lengkap,
                    'photo' => null,
                ]
            );
        });

        return back()
            ->with('success', 'Selamat! Data telah berhasil di inisialisasi dan siap untuk digunakan.')
            ->with('initialization_success', true);
    }
}

