<?php

namespace Database\Seeders;

use App\Models\RincianPenerimaManfaat;
use Illuminate\Database\Seeder;

class RincianPenerimaManfaatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $rincianRaw = [
            // 1. RA Baitul Mutaallim (ID KPM = 1)
            [1, 'Pelajar', 'Porsi Kecil', 28, 30],
            [1, 'Pendukung (Guru)', 'Porsi Besar', 0, 3],
            [1, 'Pendukung (Tenaga Kependidikan)', 'Porsi Besar', 0, 2],

            // 2. TK Al-Khairiyah Arsyadiyah (ID KPM = 2)
            [2, 'Pelajar', 'Porsi Kecil', 28, 26],
            [2, 'Pendukung (Guru)', 'Porsi Besar', 0, 4],
            [2, 'Pendukung (Tenaga Kependidikan)', 'Porsi Besar', 0, 2],

            // 3. TK Rare Kumara (ID KPM = 3)
            [3, 'Pelajar', 'Porsi Kecil', 7, 7],
            [3, 'Pendukung (Guru)', 'Porsi Besar', 0, 2],

            // 4. SD Negeri 2 Tegallinggah (ID KPM = 4)
            [4, 'Kelas 1', 'Porsi Kecil', 17, 11],
            [4, 'Kelas 2', 'Porsi Kecil', 20, 8],
            [4, 'Kelas 3', 'Porsi Kecil', 12, 11],
            [4, 'Kelas 4', 'Porsi Besar', 18, 16],
            [4, 'Kelas 5', 'Porsi Besar', 19, 15],
            [4, 'Kelas 6', 'Porsi Besar', 13, 23],
            [4, 'Pendukung (Guru)', 'Porsi Besar', 2, 7],
            [4, 'Pendukung (Tenaga Kependidikan)', 'Porsi Besar', 1, 3],

            // 5. MIN 3 Buleleng (ID KPM = 5)
            [5, 'Kelas 1', 'Porsi Kecil', 34, 36],
            [5, 'Kelas 2', 'Porsi Kecil', 25, 48],
            [5, 'Kelas 3', 'Porsi Kecil', 40, 28],
            [5, 'Kelas 4', 'Porsi Besar', 31, 30],
            [5, 'Kelas 5', 'Porsi Besar', 23, 36],
            [5, 'Kelas 6', 'Porsi Besar', 25, 30],
            [5, 'Pendukung (Guru)', 'Porsi Besar', 7, 16],
            [5, 'Pendukung (Tenaga Kependidikan)', 'Porsi Besar', 6, 5],

            // 6. MTs Al-Khairiyah (ID KPM = 6)
            [6, 'Kelas 7', 'Porsi Besar', 24, 23],
            [6, 'Kelas 8', 'Porsi Besar', 18, 18],
            [6, 'Kelas 9', 'Porsi Besar', 35, 23],
            [6, 'Pendukung (Guru)', 'Porsi Besar', 7, 10],
            [6, 'Pendukung (Tenaga Kependidikan)', 'Porsi Besar', 1, 2],

            // 7. MA Syamsul Huda (ID KPM = 7)
            [7, 'Kelas 10', 'Porsi Besar', 11, 18],
            [7, 'Kelas 11', 'Porsi Besar', 10, 20],
            [7, 'Kelas 12', 'Porsi Besar', 16, 11],
            [7, 'Pendukung (Guru)', 'Porsi Besar', 10, 8],
            [7, 'Pendukung (Tenaga Kependidikan)', 'Porsi Besar', 0, 1],

            // 8. RA Ar Rahmah (ID KPM = 8)
            [8, 'Pelajar', 'Porsi Kecil', 18, 20],
            [8, 'Pendukung (Guru)', 'Porsi Besar', 0, 4],
            [8, 'Pendukung (Tenaga Kependidikan)', 'Porsi Besar', 1, 0],

            // 9. TK Catur Kumara (ID KPM = 9)
            [9, 'Pelajar', 'Porsi Kecil', 3, 7],
            [9, 'Pendukung (Guru)', 'Porsi Besar', 0, 1],
            [9, 'Pendukung (Tenaga Kependidikan)', 'Porsi Besar', 0, 1],

            // 10. SD Negeri 1 Tegallinggah (ID KPM = 10)
            [10, 'Kelas 1', 'Porsi Kecil', 12, 6],
            [10, 'Kelas 2', 'Porsi Kecil', 16, 6],
            [10, 'Kelas 3', 'Porsi Kecil', 12, 15],
            [10, 'Kelas 4', 'Porsi Besar', 5, 7],
            [10, 'Kelas 5', 'Porsi Besar', 9, 8],
            [10, 'Kelas 6', 'Porsi Besar', 6, 10],
            [10, 'Pendukung (Guru)', 'Porsi Besar', 5, 3],
            [10, 'Pendukung (Tenaga Kependidikan)', 'Porsi Besar', 2, 1],

            // 11. SD Negeri 4 Tegallinggah (ID KPM = 11)
            [11, 'Kelas 1', 'Porsi Kecil', 7, 4],
            [11, 'Kelas 2', 'Porsi Kecil', 4, 2],
            [11, 'Kelas 3', 'Porsi Kecil', 4, 3],
            [11, 'Kelas 4', 'Porsi Besar', 3, 1],
            [11, 'Kelas 5', 'Porsi Besar', 5, 9],
            [11, 'Kelas 6', 'Porsi Besar', 10, 1],
            [11, 'Pendukung (Guru)', 'Porsi Besar', 4, 3],
            [11, 'Pendukung (Tenaga Kependidikan)', 'Porsi Besar', 1, 1],

            // 12. MIS Abul'Abbas NW (ID KPM = 12)
            [12, 'Kelas 1', 'Porsi Kecil', 11, 7],
            [12, 'Kelas 2', 'Porsi Kecil', 9, 13],
            [12, 'Kelas 3', 'Porsi Kecil', 7, 8],
            [12, 'Kelas 4', 'Porsi Besar', 16, 3],
            [12, 'Kelas 5', 'Porsi Besar', 10, 7],
            [12, 'Kelas 6', 'Porsi Besar', 11, 6],
            [12, 'Pendukung (Guru)', 'Porsi Besar', 6, 9],

            // 13. MTs Abul'Abbas NW (ID KPM = 13)
            [13, 'Kelas 7', 'Porsi Besar', 10, 6],
            [13, 'Kelas 8', 'Porsi Besar', 7, 6],
            [13, 'Kelas 9', 'Porsi Besar', 6, 6],
            [13, 'Pendukung (Guru)', 'Porsi Besar', 6, 3],

            // 14. SD Negeri 1 Selat (ID KPM = 14)
            [14, 'Kelas 1', 'Porsi Kecil', 8, 4],
            [14, 'Kelas 2', 'Porsi Kecil', 13, 6],
            [14, 'Kelas 3', 'Porsi Kecil', 10, 4],
            [14, 'Kelas 4', 'Porsi Besar', 16, 16],
            [14, 'Kelas 5', 'Porsi Besar', 11, 17],
            [14, 'Kelas 6', 'Porsi Besar', 16, 15],
            [14, 'Pendukung (Guru)', 'Porsi Besar', 3, 6],
            [14, 'Pendukung (Tenaga Kependidikan)', 'Porsi Besar', 3, 0],

            // 15. Posyandu Melati Mundukkunci (ID KPM = 15)
            [15, 'Ibu Hamil', 'Porsi Besar', 0, 10],
            [15, 'Ibu Menyusui', 'Porsi Besar', 0, 32],
            [15, 'Balita', 'Porsi Kecil', 60, 26],

            // 16. Posyandu Ratna Tegallinggah Atas (ID KPM = 16)
            [16, 'Ibu Hamil', 'Porsi Besar', 0, 6],
            [16, 'Ibu Menyusui', 'Porsi Besar', 0, 16],
            [16, 'Balita', 'Porsi Kecil', 29, 28],

            // 17. Posyandu Mawar Tegallinggah Bawah (ID KPM = 17)
            [17, 'Ibu Hamil', 'Porsi Besar', 0, 4],
            [17, 'Ibu Menyusui', 'Porsi Besar', 0, 12],
            [17, 'Balita', 'Porsi Kecil', 24, 25],

            // 18. Posyandu Sandat Lebah Pupuan (ID KPM = 18)
            [18, 'Ibu Hamil', 'Porsi Besar', 0, 0],
            [18, 'Ibu Menyusui', 'Porsi Besar', 0, 21],
            [18, 'Balita', 'Porsi Kecil', 17, 16],
        ];

        $batch = [];
        $idCounter = 1;

        foreach ($rincianRaw as $item) {
            [$kpmId, $subKategori, $jenisPorsi, $l, $p] = $item;
            $batch[] = [
                'id' => $idCounter++,
                'kelompok_penerima_manfaat_id' => $kpmId,
                'sub_kategori' => $subKategori,
                'jenis_porsi' => $jenisPorsi,
                'jumlah_laki_laki' => $l,
                'jumlah_perempuan' => $p,
                'total' => $l + $p,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        RincianPenerimaManfaat::insert($batch);
    }
}
