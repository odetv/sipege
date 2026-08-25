<?php

namespace Database\Seeders;

use App\Models\UnitSppg;
use App\Models\User;
use Illuminate\Database\Seeder;

class UnitSppgSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'gelgel.abdiutama@gmail.com')->first() ?: User::first();

        UnitSppg::create([
            'id' => 1,
            'user_id' => $user ? $user->id : 1,
            'id_sppg' => 'QQCV0LUG',
            'kode_sppg' => '51.08.05.2013.03',
            'nama' => 'SPPG Buleleng Sukasada Tegallinggah',
            'status' => 'Operasional',
            'tanggal_operasional' => '2026-05-04',
            'provinsi' => 'Bali',
            'kabupaten' => 'Buleleng',
            'kecamatan' => 'Sukasada',
            'desa_kelurahan' => 'Tegallinggah',
            'latitude' => -8.1634360,
            'longitude' => 115.0771640,
            'kode_pos' => '81161',
            'alamat_lengkap' => 'Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali',
            'photo' => null,
        ]);
    }
}
