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
        Schema::table('work_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('work_order_items', 'satuan')) {
                $table->string('satuan', 50)->default('Kg')->after('kategori');
            }
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_order_items', 'satuan')) {
                $table->string('satuan', 50)->default('Kg')->after('kategori');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('work_order_items', 'satuan')) {
                $table->dropColumn('satuan');
            }
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_order_items', 'satuan')) {
                $table->dropColumn('satuan');
            }
        });
    }
};
