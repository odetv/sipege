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
        Schema::create('kategori_kpm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_sppg_id')->nullable()->constrained('unit_sppg')->nullOnDelete();
            $table->string('nama');
            $table->unsignedBigInteger('pagu_per_porsi')->default(10000);
            $table->string('jenis_porsi')->nullable()->default('Porsi Besar');
            $table->text('keterangan')->nullable();
            $table->integer('urutan')->default(0);
            $table->string('status', 30)->default('Aktif');
            $table->timestamps();

            $table->index(['unit_sppg_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_kpm');
    }
};
