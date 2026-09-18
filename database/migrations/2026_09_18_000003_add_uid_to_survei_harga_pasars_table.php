<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('survei_harga_pasars', function (Blueprint $table) {
            $table->uuid('uid')->nullable()->unique()->after('id');
        });

        // Populate existing rows with uuid
        $records = DB::table('survei_harga_pasars')->get();
        foreach ($records as $r) {
            DB::table('survei_harga_pasars')->where('id', $r->id)->update([
                'uid' => (string) Str::uuid(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survei_harga_pasars', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};
