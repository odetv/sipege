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
        Schema::create('shifting_petugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_sppg_id')->constrained('unit_sppg')->cascadeOnDelete();
            $table->foreignId('petugas_id')->constrained('petugas')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('status', 10)->default(''); // H, H2, L, I, S, TK, atau kosong
            $table->timestamps();

            $table->unique(['unit_sppg_id', 'petugas_id', 'tanggal'], 'unique_shifting_record');
            $table->index(['unit_sppg_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifting_petugas');
    }
};
