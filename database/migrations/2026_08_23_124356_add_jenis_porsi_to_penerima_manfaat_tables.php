<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rincian_penerima_manfaat', function (Blueprint $table) {
            $table->string('jenis_porsi')->default('Porsi Besar')->after('sub_kategori'); // Porsi Kecil, Porsi Besar
        });

        Schema::table('kelompok_penerima_manfaat', function (Blueprint $table) {
            $table->unsignedInteger('total_porsi_kecil')->default(0)->after('total_perempuan');
            $table->unsignedInteger('total_porsi_besar')->default(0)->after('total_porsi_kecil');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rincian_penerima_manfaat', function (Blueprint $table) {
            $table->dropColumn('jenis_porsi');
        });

        Schema::table('kelompok_penerima_manfaat', function (Blueprint $table) {
            $table->dropColumn(['total_porsi_kecil', 'total_porsi_besar']);
        });
    }
};
