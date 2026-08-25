<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'id' => 1,
            'nik' => '5108052607020004',
            'nip' => '200207262026211008',
            'nama' => 'I Gede Gelgel Abdiutama',
            'gelar_depan' => null,
            'gelar_belakang' => 'S.Kom.',
            'agama' => 'Hindu',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Buleleng',
            'tanggal_lahir' => '2002-07-26',
            'jenjang_pendidikan' => 'S-I',
            'bidang_pendidikan' => 'Ilmu Komputer',
            'status_kawin' => 'Belum Menikah',
            'provinsi_ktp' => 'Bali',
            'kabupaten_ktp' => 'Buleleng',
            'kecamatan_ktp' => 'Sukasada',
            'desa_kelurahan_ktp' => 'Sukasada',
            'kode_pos_ktp' => '81161',
            'alamat_lengkap_ktp' => 'Lingkungan Lumbanan, Kel. Sukasada, Kec. Sukasada, Kab. Buleleng, Bali',
            'provinsi_domisili' => 'Bali',
            'kabupaten_domisili' => 'Buleleng',
            'kecamatan_domisili' => 'Sukasada',
            'desa_kelurahan_domisili' => 'Sukasada',
            'kode_pos_domisili' => '81161',
            'alamat_lengkap_domisili' => 'Lingkungan Lumbanan, Kel. Sukasada, Kec. Sukasada, Kab. Buleleng, Bali',
            'latitude_domisili' => -8.16056370112416,
            'longitude_domisili' => 115.11315832651155,
            'telepon' => '6285739683673',
            'email' => 'admin@sipege.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Password@123'),
            'photo' => null,
            'role' => 'administrator',
        ]);
    }
}
