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
        if (!Schema::hasTable('transaksi_keuangans')) {
            Schema::create('transaksi_keuangans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('unit_sppg_id')->nullable()->constrained('unit_sppg')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('nomor_transaksi')->index();
                $table->date('tanggal')->index();
                $table->string('kategori'); // Bahan Baku, Operasional, Insentif Fasilitas
                $table->string('nama_transaksi');
                $table->decimal('nominal', 15, 2)->default(0);
                $table->string('status')->default('Disetujui'); // Disetujui, Ditolak
                $table->boolean('perlu_npo')->default(true);
                $table->string('vendor')->nullable();
                $table->text('keterangan')->nullable();
                $table->string('metode_bayar')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_keuangans');
    }
};
