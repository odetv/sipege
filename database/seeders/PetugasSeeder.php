<?php

namespace Database\Seeders;

use App\Models\Petugas;
use App\Models\UnitSppg;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PetugasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unitSppg = UnitSppg::where('id_sppg', 'QQCV0LUG')->first() ?: UnitSppg::first();
        $unitSppgId = $unitSppg ? $unitSppg->id : 1;

        $jsonPath = database_path('data/petugas.json');
        if (! File::exists($jsonPath)) {
            $this->command->error("File data petugas tidak ditemukan di: {$jsonPath}");
            return;
        }

        $petugasList = json_decode(File::get($jsonPath), true);
        $niks = [];

        foreach ($petugasList as $item) {
            $niks[] = $item['nik'];
            Petugas::updateOrCreate(
                ['nik' => $item['nik']],
                [
                    'unit_sppg_id' => $unitSppgId,
                    'nama' => $item['nama'],
                    'jenis_kelamin' => $item['jenis_kelamin'],
                    'tempat_lahir' => $item['tempat_lahir'],
                    'tanggal_lahir' => $item['tanggal_lahir'],
                    'alamat' => $item['alamat'],
                    'no_telp' => $item['no_telp'],
                    'email' => $item['email'],
                    'jabatan' => $item['jabatan'],
                    'jenis_bank' => $item['jenis_bank'] ?? 'BNI',
                    'nomor_rekening' => $item['nomor_rekening'] ?? null,
                    'jam_kerja' => $item['jam_kerja'],
                    'gaji_harian_bgn' => $item['gaji_harian_bgn'],
                    'bonus_harian_mitra' => $item['bonus_harian_mitra'],
                    'iuran_bpjs_tk' => $item['iuran_bpjs_tk'],
                    'status' => $item['status'] ?? 'Aktif',
                    'keterangan' => $item['keterangan'] ?? null,
                ]
            );
        }

        // Hapus petugas yang tidak ada lagi di file master jika ada
        Petugas::where('unit_sppg_id', $unitSppgId)
            ->whereNotIn('nik', $niks)
            ->delete();

        $this->command->info('Berhasil menyinkronkan ' . count($petugasList) . ' data petugas.');
    }
}
