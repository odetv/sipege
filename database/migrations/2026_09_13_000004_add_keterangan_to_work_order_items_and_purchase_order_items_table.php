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
        if (Schema::hasTable('work_order_items') && !Schema::hasColumn('work_order_items', 'keterangan')) {
            Schema::table('work_order_items', function (Blueprint $table) {
                $table->text('keterangan')->nullable()->after('nama_sub_menu');
            });
        }

        if (Schema::hasTable('purchase_order_items') && !Schema::hasColumn('purchase_order_items', 'keterangan')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->text('keterangan')->nullable()->after('subtotal_aktual');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('work_order_items') && Schema::hasColumn('work_order_items', 'keterangan')) {
            Schema::table('work_order_items', function (Blueprint $table) {
                $table->dropColumn('keterangan');
            });
        }

        if (Schema::hasTable('purchase_order_items') && Schema::hasColumn('purchase_order_items', 'keterangan')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->dropColumn('keterangan');
            });
        }
    }
};
