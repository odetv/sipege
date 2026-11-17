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
        Schema::table('rab_anggarans', function (Blueprint $table) {
            $table->json('jadwal_pm_harian')->nullable()->after('rincian_operasional_bantu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rab_anggarans', function (Blueprint $table) {
            $table->dropColumn('jadwal_pm_harian');
        });
    }
};
