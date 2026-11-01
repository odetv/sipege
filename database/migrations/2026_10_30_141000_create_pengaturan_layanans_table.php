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
        Schema::create('pengaturan_layanans', function (Blueprint $table) {
            $table->id();
            $table->string('kategori', 50)->unique(); // 'storage', 'mail'
            $table->string('driver', 50)->default('local'); // 'local', 'r2', 'log', 'smtp'
            $table->json('konfigurasi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('status_koneksi', 30)->default('belum_dites'); // belum_dites, terhubung, gagal
            $table->timestamp('last_tested_at')->nullable();
            $table->text('pesan_koneksi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_layanans');
    }
};

