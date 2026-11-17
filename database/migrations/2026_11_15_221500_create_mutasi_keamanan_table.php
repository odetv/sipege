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
        Schema::create('mutasi_keamanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_sppg_id')->constrained('unit_sppg')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('shift', 50)->default('24 Jam');
            $table->foreignId('petugas_id')->nullable()->constrained('petugas')->nullOnDelete();
            $table->string('petugas_nama')->nullable();
            $table->string('status_keseluruhan', 30)->default('Aman');
            $table->text('catatan_serah_terima')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['unit_sppg_id', 'tanggal']);
        });

        Schema::create('mutasi_keamanan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mutasi_keamanan_id')->constrained('mutasi_keamanan')->cascadeOnDelete();
            $table->string('jam', 10);
            $table->time('waktu_cek')->nullable();
            $table->foreignId('petugas_id')->nullable()->constrained('petugas')->nullOnDelete();
            $table->string('petugas_nama')->nullable();
            $table->string('kondisi', 30)->default('Aman');
            $table->text('uraian_kejadian')->nullable();
            $table->decimal('latitude', 11, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('lokasi_nama')->nullable();
            $table->timestamps();

            $table->index(['mutasi_keamanan_id', 'jam']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_keamanan_detail');
        Schema::dropIfExists('mutasi_keamanan');
    }
};

