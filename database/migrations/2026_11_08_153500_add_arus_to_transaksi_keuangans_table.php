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
        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksi_keuangans', 'arus')) {
                $table->string('arus', 20)->default('Keluar')->after('kategori');
            }
        });

        // Set existing records' arus based on their category
        DB::table('transaksi_keuangans')
            ->whereNull('arus')
            ->orWhere('arus', '')
            ->update([
                'arus' => DB::raw("CASE WHEN kategori = 'Dana Bantuan Pemerintah' THEN 'Masuk' ELSE 'Keluar' END")
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_keuangans', 'arus')) {
                $table->dropColumn('arus');
            }
        });
    }
};

