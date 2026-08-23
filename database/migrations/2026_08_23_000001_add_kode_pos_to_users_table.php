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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'kode_pos_ktp')) {
                $table->string('kode_pos_ktp', 10)->nullable()->after('desa_kelurahan_ktp');
            }
            if (!Schema::hasColumn('users', 'kode_pos_domisili')) {
                $table->string('kode_pos_domisili', 10)->nullable()->after('desa_kelurahan_domisili');
            }
            if (Schema::hasColumn('users', 'kode_pos')) {
                $table->dropColumn('kode_pos');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['kode_pos_ktp', 'kode_pos_domisili']);
        });
    }
};
