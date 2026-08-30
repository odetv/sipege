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
        Schema::table('work_orders', function (Blueprint $table) {
            $table->json('riwayat_verifikasi')->nullable()->after('catatan_keuangan');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->json('riwayat_verifikasi')->nullable()->after('catatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn('riwayat_verifikasi');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('riwayat_verifikasi');
        });
    }
};
