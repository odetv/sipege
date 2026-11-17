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
            $table->json('rincian_operasional_bantu')->nullable()->after('rincian_operasional');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rab_anggarans', function (Blueprint $table) {
            $table->dropColumn('rincian_operasional_bantu');
        });
    }
};

