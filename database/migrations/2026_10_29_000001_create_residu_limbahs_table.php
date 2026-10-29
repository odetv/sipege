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
        Schema::create('residu_limbahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_sppg_id')->nullable()->constrained('unit_sppg')->cascadeOnDelete();
            $table->date('tanggal')->index();
            $table->string('nama_limbah');
            $table->decimal('jumlah', 10, 2); // Jumlah (tidak hanya bilangan bulat)
            $table->enum('satuan', ['Kg', 'L'])->default('Kg');
            $table->enum('jenis_limbah', ['Organik', 'Anorganik'])->default('Organik');
            $table->enum('sumber_limbah', ['Persiapan', 'Pengolahan', 'Ompreng', 'Lainnya'])->default('Persiapan');
            $table->enum('metode_penanganan', ['Donasi', 'Kompos', 'Daur Ulang', 'Pembuangan'])->default('Pembuangan');
            $table->enum('status_berbahaya', ['Tidak Berbahaya', 'Berbahaya'])->default('Tidak Berbahaya');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residu_limbahs');
    }
};
