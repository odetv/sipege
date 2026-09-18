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
        Schema::table('survei_harga_pasars', function (Blueprint $table) {
            $table->string('revisi')->nullable()->change();
            $table->string('lokasi_survei')->nullable()->change();
            $table->string('petugas_survei_2')->nullable()->after('petugas_survei');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survei_harga_pasars', function (Blueprint $table) {
            $table->dropColumn('petugas_survei_2');
        });
    }
};
