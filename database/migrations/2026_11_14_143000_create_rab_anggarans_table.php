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
        Schema::create('rab_anggarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_sppg_id')->constrained('unit_sppg')->cascadeOnDelete();
            $table->integer('tahun');
            $table->integer('bulan'); // 1 - 12
            $table->integer('hari_efektif')->default(20);
            $table->json('rincian_porsi')->nullable();
            $table->json('rincian_operasional')->nullable();
            $table->decimal('total_biaya_per_hari', 18, 2)->default(0);
            $table->decimal('total_biaya_2mingguan', 18, 2)->default(0);
            $table->decimal('total_biaya_per_bulan', 18, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->string('kepala_sppg_nama')->nullable();
            $table->string('pengawas_keuangan_nama')->nullable();
            $table->string('pengawas_gizi_nama')->nullable();
            $table->string('nama_yayasan')->nullable();
            $table->string('nomor_rekening_yayasan')->nullable();
            $table->string('npwp')->nullable();
            $table->timestamps();

            $table->unique(['unit_sppg_id', 'tahun', 'bulan'], 'rab_unit_tahun_bulan_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rab_anggarans');
    }
};

