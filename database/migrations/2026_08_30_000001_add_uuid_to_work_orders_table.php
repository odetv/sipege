<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id')->index();
        });

        // Generate UUID untuk data yang sudah ada
        $existingWos = DB::table('work_orders')->get();
        foreach ($existingWos as $wo) {
            DB::table('work_orders')->where('id', $wo->id)->update([
                'uuid' => (string) Str::uuid(),
            ]);
        }

        Schema::table('work_orders', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
