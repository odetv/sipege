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
        Schema::create('distribusi_penerima_manfaat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelompok_id')->constrained('kelompok_penerima_manfaat')->cascadeOnDelete();
            $table->foreignId('unit_sppg_id')->constrained('unit_sppg')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('status', 10)->default('T'); // T: Terkirim, P: Proses/Jadwal, L: Libur
            $table->integer('porsi_kecil')->default(0);
            $table->integer('porsi_besar')->default(0);
            $table->integer('total_porsi')->default(0);
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->unique(['kelompok_id', 'tanggal']);
            $table->index(['unit_sppg_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribusi_penerima_manfaat');
    }
};
