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
        if (!Schema::hasTable('rekening_banks')) {
            Schema::create('rekening_banks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('unit_sppg_id')->nullable()->constrained('unit_sppg')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('jenis_bank'); // BNI, BRI, Mandiri, BCA, BSI, Bank BPD Bali, dll
                $table->string('nama_bank_lainnya')->nullable();
                $table->string('nama_rekening'); // Nama Pemilik / Atas Nama Rekening
                $table->string('nomor_rekening'); // Nomor Rekening Bank
                $table->string('nomor_virtual_account')->nullable(); // Nomor Virtual Account (VA)
                $table->string('kantor_cabang')->nullable();
                $table->text('keterangan')->nullable();
                $table->boolean('is_utama')->default(false);
                $table->boolean('status_aktif')->default(true);
                $table->timestamps();

                $table->index(['unit_sppg_id', 'status_aktif']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekening_banks');
    }
};

