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
        Schema::create('absensi_petugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petugas_id')->constrained('petugas')->cascadeOnDelete();
            $table->foreignId('unit_sppg_id')->constrained('unit_sppg')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('status', 10)->default('H'); // H, I, S, TK
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->unique(['petugas_id', 'tanggal']);
            $table->index(['unit_sppg_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi_petugas');
    }
};
