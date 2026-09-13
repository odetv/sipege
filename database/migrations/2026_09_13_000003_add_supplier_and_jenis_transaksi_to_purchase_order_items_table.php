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
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('work_order_id')->constrained('suppliers')->nullOnDelete();
            $table->string('jenis_transaksi')->nullable()->after('tipe'); // Bahan Baku, Operasional
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'jenis_transaksi']);
        });
    }
};
