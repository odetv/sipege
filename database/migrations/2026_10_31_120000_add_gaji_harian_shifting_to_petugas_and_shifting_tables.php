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
        if (Schema::hasTable('petugas') && !Schema::hasColumn('petugas', 'gaji_harian_shifting')) {
            Schema::table('petugas', function (Blueprint $table) {
                $table->bigInteger('gaji_harian_shifting')->nullable()->after('gaji_harian_bgn');
            });
        }

        if (Schema::hasTable('shifting_petugas') && !Schema::hasColumn('shifting_petugas', 'tarif_harian')) {
            Schema::table('shifting_petugas', function (Blueprint $table) {
                $table->bigInteger('tarif_harian')->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('petugas') && Schema::hasColumn('petugas', 'gaji_harian_shifting')) {
            Schema::table('petugas', function (Blueprint $table) {
                $table->dropColumn('gaji_harian_shifting');
            });
        }

        if (Schema::hasTable('shifting_petugas') && Schema::hasColumn('shifting_petugas', 'tarif_harian')) {
            Schema::table('shifting_petugas', function (Blueprint $table) {
                $table->dropColumn('tarif_harian');
            });
        }
    }
};

