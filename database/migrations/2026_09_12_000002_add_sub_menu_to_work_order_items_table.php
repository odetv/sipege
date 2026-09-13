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
            $table->string('sub_menu_key')->nullable()->after('work_order_id'); // sub_menu_1, sub_menu_2, dst
            $table->string('sub_menu_block_id')->nullable()->after('sub_menu_key'); // sub_menu_1_normal, sub_menu_2_alergi_0, dst
            $table->string('nama_sub_menu')->nullable()->after('sub_menu_block_id'); // Nasi Putih, Ayam Guling, dsb
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->dropColumn(['sub_menu_key', 'sub_menu_block_id', 'nama_sub_menu']);
        });
    }
};
