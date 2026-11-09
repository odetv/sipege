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
        if (!Schema::hasTable('keuangan_saldo_awals')) {
            Schema::create('keuangan_saldo_awals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('unit_sppg_id')->nullable()->constrained('unit_sppg')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('tanggal');
                $table->decimal('nominal', 15, 2)->default(0);
                $table->text('keterangan')->nullable();
                $table->boolean('is_locked')->default(true);
                $table->timestamps();

                $table->unique('unit_sppg_id');
            });
        }

        if (!Schema::hasTable('keuangan_penyesuaians')) {
            Schema::create('keuangan_penyesuaians', function (Blueprint $table) {
                $table->id();
                $table->foreignId('unit_sppg_id')->nullable()->constrained('unit_sppg')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('nomor_penyesuaian')->index();
                $table->date('tanggal')->index();
                $table->string('arah'); // 'masuk' atau 'keluar'
                $table->string('jenis'); // Bahan Baku, Operasional, Insentif Fasilitas, Dana Bantuan Pemerintah, Pengembalian Dana
                $table->decimal('jumlah', 15, 2)->default(0);
                $table->text('alasan');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keuangan_penyesuaians');
        Schema::dropIfExists('keuangan_saldo_awals');
    }
};
