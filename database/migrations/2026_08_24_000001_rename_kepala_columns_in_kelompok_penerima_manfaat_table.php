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
            if (Schema::hasColumn('kelompok_penerima_manfaat', 'nama_kepala_sekolah')) {
                $table->renameColumn('nama_kepala_sekolah', 'nama_kepala');
            }
            if (Schema::hasColumn('kelompok_penerima_manfaat', 'email_kepala_sekolah')) {
                $table->renameColumn('email_kepala_sekolah', 'email_kepala');
            }
            if (Schema::hasColumn('kelompok_penerima_manfaat', 'telepon_kepala_sekolah')) {
                $table->renameColumn('telepon_kepala_sekolah', 'telepon_kepala');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelompok_penerima_manfaat', function (Blueprint $table) {
            $table->renameColumn('nama_kepala', 'nama_kepala_sekolah');
            $table->renameColumn('email_kepala', 'email_kepala_sekolah');
            $table->renameColumn('telepon_kepala', 'telepon_kepala_sekolah');
        });
    }
};
