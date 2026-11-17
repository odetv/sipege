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
        Schema::table('mutasi_keamanan_detail', function (Blueprint $table) {
            $table->json('data_pengecekan')->nullable()->after('uraian_kejadian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mutasi_keamanan_detail', function (Blueprint $table) {
            $table->dropColumn('data_pengecekan');
        });
    }
};
