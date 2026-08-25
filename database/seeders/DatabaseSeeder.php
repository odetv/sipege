<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 0. Reset semua data & Reset Auto-Increment ID / Sequence kembali ke 1
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('TRUNCATE TABLE rincian_penerima_manfaat, kelompok_penerima_manfaat, unit_sppg, users RESTART IDENTITY CASCADE;');
        } elseif ($driver === 'mysql') {
            Schema::disableForeignKeyConstraints();
            DB::table('rincian_penerima_manfaat')->truncate();
            DB::table('kelompok_penerima_manfaat')->truncate();
            DB::table('unit_sppg')->truncate();
            DB::table('users')->truncate();
            Schema::enableForeignKeyConstraints();
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            DB::table('rincian_penerima_manfaat')->delete();
            DB::table('kelompok_penerima_manfaat')->delete();
            DB::table('unit_sppg')->delete();
            DB::table('users')->delete();
            DB::statement('DELETE FROM sqlite_sequence WHERE name IN ("rincian_penerima_manfaat", "kelompok_penerima_manfaat", "unit_sppg", "users");');
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        // 1. Seeder User (Kepala SPPG) -> ID: 1
        $this->call(UserSeeder::class);

        // 2. Seeder Unit SPPG (QQCV0LUG) -> ID: 1, User ID: 1
        $this->call(UnitSppgSeeder::class);

        // 3. Seeder Kelompok Penerima Manfaat (14 Sekolah & 4 Posyandu) -> ID: 1..18, Unit SPPG ID: 1
        $this->call(KelompokPenerimaManfaatSeeder::class);

        // 4. Seeder Rincian Penerima Manfaat (87 Klasifikasi Sub Kategori) -> ID: 1..87, KPM ID: 1..18
        $this->call(RincianPenerimaManfaatSeeder::class);
    }
}
