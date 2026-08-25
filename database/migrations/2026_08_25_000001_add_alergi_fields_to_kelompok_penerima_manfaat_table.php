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
        Schema::table('kelompok_penerima_manfaat', function (Blueprint $table) {
            $table->unsignedInteger('alergi_porsi_kecil')->default(0)->after('total_porsi_besar');
            $table->unsignedInteger('alergi_porsi_besar')->default(0)->after('alergi_porsi_kecil');
            $table->json('keterangan_alergi')->nullable()->after('alergi_porsi_besar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelompok_penerima_manfaat', function (Blueprint $table) {
            $table->dropColumn(['alergi_porsi_kecil', 'alergi_porsi_besar', 'keterangan_alergi']);
        });
    }
};
