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
            $table->timestamp('diajukan_pada')->nullable()->after('status');
            $table->timestamp('disetujui_pada')->nullable()->after('diajukan_pada');
            $table->timestamp('ditolak_pada')->nullable()->after('disetujui_pada');
            $table->text('catatan_keuangan')->nullable()->after('ditolak_pada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['diajukan_pada', 'disetujui_pada', 'ditolak_pada', 'catatan_keuangan']);
        });
    }
};
