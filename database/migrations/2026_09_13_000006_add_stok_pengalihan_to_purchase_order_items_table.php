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
            $table->decimal('stok_digunakan_kg', 10, 4)->default(0)->after('gross_kg');
            $table->decimal('qty_beli_po_kg', 10, 4)->nullable()->after('stok_digunakan_kg');
            $table->string('sumber_pengadaan')->default('Beli PO')->after('qty_beli_po_kg'); // 'Beli PO', 'Sebagian Stok', 'Full Stok'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['stok_digunakan_kg', 'qty_beli_po_kg', 'sumber_pengadaan']);
        });
    }
};
