<?php

namespace App\Http\Controllers;

use App\Models\UnitSppg;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the application dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        $kelompokList = [];
        if ($unitSppg) {
            $kelompokList = \App\Models\KelompokPenerimaManfaat::where('unit_sppg_id', $unitSppg->id)
                ->with('rincian')
                ->get();
        }

        return Inertia::render('Dashboard/Index', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
        ]);
    }

    /**
     * Update user profile information (Kepala SPPG).
     * Note: Email cannot be modified by user.
     */
    public function updateUserProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nik' => ['required', 'digits:16', Rule::unique('users', 'nik')->ignore($user->id)],
            'nip' => ['nullable', 'numeric'],
            'nama' => ['required', 'string', 'max:255'],
            'gelar_depan' => ['nullable', 'string', 'max:50'],
            'gelar_belakang' => ['nullable', 'string', 'max:50'],
            'agama' => ['required', 'string', 'max:50'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
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
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus berjumlah tepat 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar di sistem.',
            'nama.required' => 'Nama lengkap wajib diisi.',
            'agama.required' => 'Pilih salah satu agama.',
            'jenis_kelamin.required' => 'Pilih jenis kelamin.',
            'tempat_lahir.required' => 'Tempat lahir wajib diisi.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'jenjang_pendidikan.required' => 'Pilih jenjang pendidikan.',
            'bidang_pendidikan.required' => 'Bidang pendidikan / jurusan wajib diisi.',
            'status_kawin.required' => 'Pilih status perkawinan.',
            'telepon.required' => 'Nomor telepon wajib diisi.',
            'telepon.regex' => 'Format nomor telepon tidak valid. Pastikan diawali format Indonesia (62...).',
            'kode_pos_ktp.digits' => 'Kode pos KTP harus berupa 5 digit angka.',
            'kode_pos_domisili.digits' => 'Kode pos Domisili harus berupa 5 digit angka.',
            'latitude_domisili.required' => 'Titik koordinat domisili wajib ditentukan.',
            'longitude_domisili.required' => 'Titik koordinat domisili wajib ditentukan.',
        ]);

        // Exclude email from mass assignment to ensure it cannot be modified
        unset($validated['email']);

        $user->fill($validated);
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Data Profil Pengguna berhasil diperbarui.');
    }

    /**
     * Update unit SPPG information.
     */
    public function updateUnitSppg(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return redirect()->route('dashboard')->with('error', 'Data Unit SPPG tidak ditemukan.');
        }

        $validated = $request->validate([
            'id_sppg' => ['required', 'string', 'size:8', 'alpha_num', Rule::unique('unit_sppg', 'id_sppg')->ignore($unitSppg->id)],
            'kode_sppg' => ['required', 'string', 'max:100'],
            'nama' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Belum Operasional,Operasional,Suspend Ringan,Suspend Sedang,Suspend Berat,Suspend Permanen'],
            'tanggal_operasional' => ['nullable', 'date'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kabupaten' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'desa_kelurahan' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'kode_pos' => ['required', 'numeric', 'digits:5'],
            'alamat_lengkap' => ['required', 'string'],
        ], [
            'id_sppg.required' => 'ID SPPG wajib diisi.',
            'id_sppg.size' => 'ID SPPG harus tepat 8 digit karakter alfanumerik.',
            'id_sppg.unique' => 'ID SPPG ini sudah digunakan oleh unit lain.',
            'kode_sppg.required' => 'Kode unit SPPG wajib diisi.',
            'nama.required' => 'Nama unit SPPG wajib diisi.',
            'status.required' => 'Status operasional wajib dipilih.',
            'kode_pos.digits' => 'Kode pos Unit SPPG harus berupa 5 digit angka.',
            'latitude.required' => 'Titik koordinat unit SPPG wajib ditentukan.',
            'longitude.required' => 'Titik koordinat unit SPPG wajib ditentukan.',
            'alamat_lengkap.required' => 'Alamat lengkap unit SPPG wajib diisi.',
        ]);

        // Ensure id_sppg is stored in uppercase
        $validated['id_sppg'] = strtoupper($validated['id_sppg']);

        $unitSppg->fill($validated);
        $unitSppg->save();

        return redirect()->route('dashboard')->with('success', 'Data Unit SPPG berhasil diperbarui.');
    }
}
