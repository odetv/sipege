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
        Schema::create('reseps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('unit_sppg_id')->constrained('unit_sppg')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama_resep');
            $table->string('tipe')->default('sub_menu'); // 'sub_menu' atau 'paket_menu'
            $table->string('kategori')->nullable(); // 'Makanan Pokok', 'Lauk Hewani', 'Lauk Nabati', 'Sayuran', 'Buah', 'Paket Lengkap', dll
            $table->string('asal_menu')->nullable(); // Nama menu sumber saat disimpan
            $table->text('deskripsi')->nullable();
            $table->json('data_resep'); // Detail bahan baku, gramasi PK/PB, nutrisi, dll
            $table->timestamps();

            $table->index(['unit_sppg_id', 'tipe']);
            $table->index(['unit_sppg_id', 'kategori']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseps');
    }
};
