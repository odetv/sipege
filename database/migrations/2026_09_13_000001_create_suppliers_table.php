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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_sppg_id')->constrained('unit_sppg')->cascadeOnDelete();
            $table->string('jenis_supplier')->default('UMKM'); // KDMP, Koperasi, Bumdes, Bumdesma, UMKM, Lainnya
            $table->string('nama_usaha');
            $table->string('nama_pemilik')->nullable();
            $table->string('no_telp')->nullable();
            $table->json('komoditas')->nullable(); // Array of strings (e.g. ["Beras", "Sayuran", "Daging Ayam"])
            $table->string('provinsi')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kelurahan')->nullable();
            $table->text('alamat_lengkap')->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
