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
            DB::statement('ALTER TABLE purchase_orders ALTER COLUMN vendor DROP NOT NULL;');
        } catch (\Throwable $e) {
            // Fallback for non-postgres or if already nullable
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->string('vendor')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
