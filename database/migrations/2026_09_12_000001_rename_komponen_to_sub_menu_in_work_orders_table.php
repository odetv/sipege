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
            $table->renameColumn('komponen_energi', 'sub_menu_1');
            $table->renameColumn('komponen_protein', 'sub_menu_2');
            $table->renameColumn('komponen_lemak', 'sub_menu_3');
            $table->renameColumn('komponen_karbohidrat', 'sub_menu_4');
            $table->renameColumn('komponen_serat', 'sub_menu_5');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->renameColumn('sub_menu_1', 'komponen_energi');
            $table->renameColumn('sub_menu_2', 'komponen_protein');
            $table->renameColumn('sub_menu_3', 'komponen_lemak');
            $table->renameColumn('sub_menu_4', 'komponen_karbohidrat');
            $table->renameColumn('sub_menu_5', 'komponen_serat');
        });
    }
};
