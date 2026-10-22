<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('rincian_penerima_manfaat', 'waktu_batas_makan')) {
            Schema::table('rincian_penerima_manfaat', function (Blueprint $table) {
                $table->string('waktu_batas_makan', 10)->default('10:00')->after('jenis_porsi');
            });
        }

        // Set semua default menjadi 10:00 jika masih kosong
        DB::table('rincian_penerima_manfaat')
            ->whereNull('waktu_batas_makan')
            ->orWhere('waktu_batas_makan', '')
            ->update(['waktu_batas_makan' => '10:00']);

        // Khusus MIN 3 (MIN 3 Buleleng) Kelas 4, 5, dan 6 set batasnya jam 14:00
        $min3Ids = DB::table('kelompok_penerima_manfaat')
            ->where('nama_kelompok', 'ILIKE', '%MIN 3%')
            ->pluck('id');

        if ($min3Ids->isNotEmpty()) {
            DB::table('rincian_penerima_manfaat')
                ->whereIn('kelompok_penerima_manfaat_id', $min3Ids)
                ->where(function ($query) {
                    $query->where('sub_kategori', 'ILIKE', '%Kelas 4%')
                        ->orWhere('sub_kategori', 'ILIKE', '%Kelas 5%')
                        ->orWhere('sub_kategori', 'ILIKE', '%Kelas 6%');
                })
                ->update(['waktu_batas_makan' => '14:00']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('rincian_penerima_manfaat', 'waktu_batas_makan')) {
            Schema::table('rincian_penerima_manfaat', function (Blueprint $table) {
                $table->dropColumn('waktu_batas_makan');
            });
        }
    }
};
