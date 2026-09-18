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
        Schema::create('survei_harga_pasars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_sppg_id')->nullable()->constrained('unit_sppg')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('no_dokumen')->nullable();
            $table->string('revisi')->default('00');
            $table->date('tanggal_berlaku')->nullable();
            $table->date('tanggal_survei');
            $table->string('hari_survei')->nullable();
            $table->string('lokasi_survei');
            $table->string('petugas_survei')->nullable();
            $table->string('mengetahui_nama')->nullable();
            $table->string('status')->default('Selesai'); // Draft, Selesai, Disetujui
            $table->json('items')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survei_harga_pasars');
    }
};
