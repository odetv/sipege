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
            if (!Schema::hasColumn('users', 'alamat_lengkap_ktp')) {
                $table->text('alamat_lengkap_ktp')->nullable()->after('kode_pos_ktp');
            }
            if (!Schema::hasColumn('users', 'alamat_lengkap_domisili')) {
                $table->text('alamat_lengkap_domisili')->nullable()->after('kode_pos_domisili');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'alamat_lengkap_ktp')) {
                $table->dropColumn('alamat_lengkap_ktp');
            }
            if (Schema::hasColumn('users', 'alamat_lengkap_domisili')) {
                $table->dropColumn('alamat_lengkap_domisili');
            }
        });
    }
};
