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
            $table->json('old_tokens_keamanan')->nullable()->after('token_keamanan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_sppg', function (Blueprint $table) {
            $table->dropColumn('old_tokens_keamanan');
        });
    }
};

