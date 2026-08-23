<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'kode_pos')) {
                $table->dropColumn('kode_pos');
            }
            if (!Schema::hasColumn('users', 'kode_pos_ktp')) {
                $table->string('kode_pos_ktp', 10)->nullable()->after('desa_kelurahan_ktp');
            }
            if (!Schema::hasColumn('users', 'kode_pos_domisili')) {
                $table->string('kode_pos_domisili', 10)->nullable()->after('desa_kelurahan_domisili');
            }
        });

        // Update existing user records that have null kode_pos_domisili or kode_pos_ktp
        DB::table('users')->whereNull('kode_pos_domisili')->update([
            'kode_pos_domisili' => '81161',
        ]);
        DB::table('users')->whereNull('kode_pos_ktp')->update([
            'kode_pos_ktp' => '81161',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('kode_pos', 10)->nullable();
        });
    }
};
