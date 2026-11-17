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
        Schema::table('mutasi_keamanan_izin', function (Blueprint $table) {
            $table->date('tanggal_izin')->nullable()->after('keperluan');
            $table->string('waktu_izin', 20)->nullable()->after('tanggal_izin');
            $table->string('lokasi_izin')->nullable()->default('Pos Penjagaan SPPG')->after('waktu_izin');
            $table->decimal('lat_izin', 10, 7)->nullable()->after('lokasi_izin');
            $table->decimal('lng_izin', 10, 7)->nullable()->after('lat_izin');

            $table->string('waktu_sampai_tujuan', 20)->nullable()->after('lng_tujuan');

            $table->integer('durasi_keluar_menit')->nullable()->after('lng_kembali');
            $table->integer('durasi_total_menit')->nullable()->after('durasi_keluar_menit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mutasi_keamanan_izin', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_izin',
                'waktu_izin',
                'lokasi_izin',
                'lat_izin',
                'lng_izin',
                'waktu_sampai_tujuan',
                'durasi_keluar_menit',
                'durasi_total_menit',
            ]);
        });
    }
};

