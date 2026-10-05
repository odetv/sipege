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
        Schema::create('petugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_sppg_id')->constrained('unit_sppg')->cascadeOnDelete();
            $table->string('nama');
            $table->string('nik', 20)->unique();
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->text('alamat');
            $table->string('no_telp', 30);
            $table->string('email');
            $table->string('jabatan');
            $table->string('jam_kerja');
            $table->bigInteger('gaji_harian_bgn')->default(0);
            $table->bigInteger('bonus_harian_mitra')->default(0);
            $table->bigInteger('iuran_bpjs_tk')->default(0);
            $table->string('status', 30)->default('Aktif');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['unit_sppg_id', 'jabatan']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petugas');
    }
};
