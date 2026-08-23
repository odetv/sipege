<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UnitSppg;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1 User (Kepala SPPG)
        $user = User::create([
            'nik' => '5108011505900001',
            'nip' => '199005152015031001',
            'nama' => 'I Wayan Pratama',
            'gelar_depan' => 'Ir.',
            'gelar_belakang' => 'S.T., M.T.',
            'agama' => 'Hindu',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Buleleng',
            'tanggal_lahir' => '1990-05-15',
            'jenjang_pendidikan' => 'S-I',
            'bidang_pendidikan' => 'Teknik Elektro / Energi',
            'status_kawin' => 'Menikah',
            'provinsi_ktp' => 'Bali',
            'kabupaten_ktp' => 'Buleleng',
            'kecamatan_ktp' => 'Sukasada',
            'desa_kelurahan_ktp' => 'Tegallinggah',
            'kode_pos_ktp' => '81161',
            'alamat_lengkap_ktp' => 'Banjar Dinas Tegallinggah, RT/RW 001/002',
            'provinsi_domisili' => 'Bali',
            'kabupaten_domisili' => 'Buleleng',
            'kecamatan_domisili' => 'Sukasada',
            'desa_kelurahan_domisili' => 'Tegallinggah',
            'kode_pos_domisili' => '81161',
            'alamat_lengkap_domisili' => 'Banjar Dinas Tegallinggah, RT/RW 001/002',
            'latitude_domisili' => -8.1568224,
            'longitude_domisili' => 115.0972345,
            'telepon' => '6285239182736',
            'email' => 'admin@sppg.id',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'photo' => null,
            'role' => 'administrator',
        ]);

        // 1 Unit SPPG yang terkait
        UnitSppg::create([
            'user_id' => $user->id,
            'id_sppg' => 'SPPG8899',
            'kode_sppg' => 'UNIT-BALI-01',
            'nama' => 'SPPG Sukasada Utama',
            'status' => 'Operasional',
            'tanggal_operasional' => '2023-01-10',
            'provinsi' => 'Bali',
            'kabupaten' => 'Buleleng',
            'kecamatan' => 'Sukasada',
            'desa_kelurahan' => 'Tegallinggah',
            'latitude' => -8.1571200,
            'longitude' => 115.0984000,
            'kode_pos' => '81161',
            'alamat_lengkap' => 'Jl. Raya Singaraja - Bedugul No. 88, Tegallinggah, Sukasada, Buleleng, Bali',
            'photo' => null,
        ]);
    }
}
