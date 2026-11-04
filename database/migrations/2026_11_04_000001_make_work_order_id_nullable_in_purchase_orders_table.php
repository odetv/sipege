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
        try {
            DB::statement('ALTER TABLE purchase_orders ALTER COLUMN work_order_id DROP NOT NULL;');
        } catch (\Throwable $e) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->foreignId('work_order_id')->nullable()->change();
            });
        }

        if (!Schema::hasColumn('purchase_orders', 'nama_menu')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->string('nama_menu')->nullable()->after('nomor_po');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('purchase_orders', 'nama_menu')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropColumn('nama_menu');
            });
        }
    }
};

