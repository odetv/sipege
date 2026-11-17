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
        Schema::table('periodes', function (Blueprint $table) {
            $table->boolean('is_penyesuaian')->default(false)->after('tanggal_selesai')->comment('Menandai periode lampau yang disesuaikan sebelum aplikasi digunakan');
            $table->boolean('tampilkan_di_list')->default(true)->after('is_penyesuaian')->comment('Apakah periode ini dimunculkan di dropdown & list periode aplikasi');
            $table->string('keterangan', 255)->nullable()->after('tampilkan_di_list')->comment('Catatan tambahan atau keterangan penyesuaian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periodes', function (Blueprint $table) {
            $table->dropColumn(['is_penyesuaian', 'tampilkan_di_list', 'keterangan']);
        });
    }
};
