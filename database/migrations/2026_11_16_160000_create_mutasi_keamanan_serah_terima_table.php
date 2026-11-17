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
        Schema::create('mutasi_keamanan_serah_terima', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mutasi_keamanan_id')->constrained('mutasi_keamanan')->cascadeOnDelete();
            $table->foreignId('petugas_id')->nullable()->constrained('petugas')->nullOnDelete();
            $table->string('petugas_nama')->nullable();
            $table->string('jenis', 30); // 'Masuk Jaga' | 'Lepas Jaga'
            $table->date('tanggal');
            $table->string('waktu', 30); // e.g. '08:00:00' or '08.00 WITA'
            $table->text('catatan')->nullable();
            $table->decimal('latitude', 11, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('lokasi_nama')->nullable();
            $table->timestamps();

            $table->index(['mutasi_keamanan_id', 'petugas_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_keamanan_serah_terima');
    }
};

