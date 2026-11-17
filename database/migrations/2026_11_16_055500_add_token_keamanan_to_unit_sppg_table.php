<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\UnitSppg;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('unit_sppg', function (Blueprint $table) {
            $table->string('token_keamanan', 64)->nullable()->unique()->after('id_sppg');
        });

        // Generate token keamanan untuk setiap Unit SPPG yang sudah ada
        foreach (UnitSppg::all() as $unit) {
            $unit->update([
                'token_keamanan' => Str::random(24),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_sppg', function (Blueprint $table) {
            $table->dropColumn('token_keamanan');
        });
    }
};

