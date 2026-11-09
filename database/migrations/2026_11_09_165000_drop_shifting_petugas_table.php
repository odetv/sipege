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
        Schema::dropIfExists('shifting_petugas');

        if (Schema::hasTable('petugas') && Schema::hasColumn('petugas', 'gaji_harian_shifting')) {
            Schema::table('petugas', function (Blueprint $table) {
                $table->dropColumn('gaji_harian_shifting');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('petugas') && !Schema::hasColumn('petugas', 'gaji_harian_shifting')) {
            Schema::table('petugas', function (Blueprint $table) {
                $table->bigInteger('gaji_harian_shifting')->nullable()->after('gaji_harian_bgn');
            });
        }

        if (!Schema::hasTable('shifting_petugas')) {
            Schema::create('shifting_petugas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('unit_sppg_id')->constrained('unit_sppg')->cascadeOnDelete();
                $table->foreignId('petugas_id')->constrained('petugas')->cascadeOnDelete();
                $table->date('tanggal');
                $table->string('status', 10)->default('H');
                $table->bigInteger('tarif_harian')->nullable();
                $table->timestamps();

                $table->unique(['unit_sppg_id', 'petugas_id', 'tanggal'], 'shifting_petugas_unique');
            });
        }
    }
};
