<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeriodeSeeder extends Seeder
{
    /**
     * Seed 9 Periode Operasional SPPG.
     * Status (aktif/selesai/akan_datang) dihitung otomatis dari tanggal.
     */
    public function run(): void
    {
        $periodes = [
            // nomor | tanggal_mulai | tanggal_selesai
            [1, '2026-05-04', '2026-05-16'],
            [2, '2026-05-18', '2026-05-30'],
            [3, '2026-06-01', '2026-06-14'],
            [4, '2026-06-15', '2026-06-28'],
            [5, '2026-06-29', '2026-07-12'],
            [6, '2026-07-13', '2026-07-26'],
            [7, '2026-07-27', '2026-08-09'],
            [8, '2026-08-10', '2026-08-23'],
            [9, '2026-08-24', '2026-09-06'],
        ];

        foreach ($periodes as [$nomor, $mulai, $selesai]) {
            DB::table('periodes')->updateOrInsert(
                ['nomor_periode' => $nomor],
                [
                    'tanggal_mulai'   => $mulai,
                    'tanggal_selesai' => $selesai,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]
            );
        }
    }
}
