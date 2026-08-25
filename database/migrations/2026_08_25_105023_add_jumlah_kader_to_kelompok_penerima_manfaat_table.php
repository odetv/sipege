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
        Schema::table('kelompok_penerima_manfaat', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_kader')->default(0)->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelompok_penerima_manfaat', function (Blueprint $table) {
            $table->dropColumn('jumlah_kader');
        });
    }
};
