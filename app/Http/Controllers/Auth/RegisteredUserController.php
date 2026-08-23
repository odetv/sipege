<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UnitSppg;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            // Profil Pengguna (Kepala SPPG)
            'nik' => ['required', 'numeric', 'digits:16', 'unique:users,nik'],
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],

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

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
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
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'photo' => null,
                'role' => 'guest', // default role
            ]);

            UnitSppg::create([
                'user_id' => $user->id,
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
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
