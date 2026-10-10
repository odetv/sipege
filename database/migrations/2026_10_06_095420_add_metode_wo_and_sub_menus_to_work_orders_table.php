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
            $table->string('metode_wo')->default('sistem')->after('status'); // 'sistem' (dengan bahan & formula) | 'manual' (input langsung)
            $table->json('sub_menus')->nullable()->after('sub_menu_5'); // Array dinamis sub menu: ["Nasi", "Lauk 1", ..., "Sub 6", "Sub 7"]
            $table->json('akg_alergi')->nullable()->after('akg_pb'); // { "Telur": { akg_pk: {...}, akg_pb: {...} }, ... }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['metode_wo', 'sub_menus', 'akg_alergi']);
        });
    }
};
