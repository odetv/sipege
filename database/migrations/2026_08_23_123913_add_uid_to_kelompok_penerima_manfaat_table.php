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
        Schema::table('kelompok_penerima_manfaat', function (Blueprint $table) {
            $table->uuid('uid')->nullable()->unique()->after('id');
        });

        // Generate UUID for existing rows
        $rows = DB::table('kelompok_penerima_manfaat')->whereNull('uid')->get();
        foreach ($rows as $row) {
            DB::table('kelompok_penerima_manfaat')
                ->where('id', $row->id)
                ->update(['uid' => (string) Str::uuid()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelompok_penerima_manfaat', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};
