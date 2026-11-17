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
        Schema::create('mutasi_keamanan_izin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mutasi_keamanan_id')->constrained('mutasi_keamanan')->onDelete('cascade');
            $table->foreignId('petugas_id')->nullable()->constrained('petugas')->nullOnDelete();
            $table->string('petugas_nama');
            $table->text('keperluan');
            $table->date('tanggal_keluar');
            $table->string('waktu_keluar', 20);
            $table->string('lokasi_awal')->default('Pos Penjagaan SPPG');
            $table->decimal('lat_awal', 10, 7)->nullable();
            $table->decimal('lng_awal', 10, 7)->nullable();
            $table->string('lokasi_tujuan');
            $table->decimal('lat_tujuan', 10, 7)->nullable();
            $table->decimal('lng_tujuan', 10, 7)->nullable();
            $table->date('tanggal_kembali')->nullable();
            $table->string('waktu_kembali', 20)->nullable();
            $table->string('lokasi_kembali')->nullable()->default('Pos Penjagaan SPPG');
            $table->decimal('lat_kembali', 10, 7)->nullable();
            $table->decimal('lng_kembali', 10, 7)->nullable();
            $table->string('status', 30)->default('Sedang Keluar');
            $table->text('laporan_kegiatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_keamanan_izin');
    }
};

