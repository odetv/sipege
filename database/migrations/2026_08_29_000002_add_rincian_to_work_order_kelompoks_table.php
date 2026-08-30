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
        Schema::table('work_order_kelompoks', function (Blueprint $table) {
            $table->json('rincian')->nullable()->after('status_alergi');
            $table->json('detail_alergi')->nullable()->after('rincian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_kelompoks', function (Blueprint $table) {
            $table->dropColumn(['rincian', 'detail_alergi']);
        });
    }
};
