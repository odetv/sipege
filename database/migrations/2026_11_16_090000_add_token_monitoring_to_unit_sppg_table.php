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
        Schema::table('unit_sppg', function (Blueprint $table) {
            $table->string('token_monitoring', 64)->nullable()->unique()->after('old_tokens_keamanan');
            $table->json('old_tokens_monitoring')->nullable()->after('token_monitoring');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_sppg', function (Blueprint $table) {
            $table->dropColumn(['token_monitoring', 'old_tokens_monitoring']);
        });
    }
};

