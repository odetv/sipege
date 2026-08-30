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
            $table->decimal('gross_kg_pk', 12, 4)->default(0)->change();
            $table->decimal('gross_kg_pb', 12, 4)->default(0)->change();
            $table->decimal('total_gross_kg', 12, 4)->default(0)->change();
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('gross_kg', 12, 4)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->decimal('gross_kg_pk', 10, 2)->default(0)->change();
            $table->decimal('gross_kg_pb', 10, 2)->default(0)->change();
            $table->decimal('total_gross_kg', 10, 2)->default(0)->change();
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('gross_kg', 10, 2)->default(0)->change();
        });
    }
};
