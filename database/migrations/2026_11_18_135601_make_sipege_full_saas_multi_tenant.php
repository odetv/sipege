<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah unit_sppg_id pada tabel periodes
        if (!Schema::hasColumn('periodes', 'unit_sppg_id')) {
            Schema::table('periodes', function (Blueprint $table) {
                $table->foreignId('unit_sppg_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('unit_sppg')
                    ->cascadeOnDelete();
            });

            // Backfill: seluruh periode yang sudah ada dikaitkan ke unit SPPG pertama
            $firstUnit = DB::table('unit_sppg')->orderBy('id', 'asc')->first();
            if ($firstUnit) {
                DB::table('periodes')
                    ->whereNull('unit_sppg_id')
                    ->update(['unit_sppg_id' => $firstUnit->id]);
            }
        }

        // 2. Tambah unit_sppg_id dan tgl_berakhir_langganan pada tabel users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'unit_sppg_id')) {
                $table->foreignId('unit_sppg_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('unit_sppg')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'tgl_berakhir_langganan')) {
                $table->timestamp('tgl_berakhir_langganan')
                    ->nullable()
                    ->after('tgl_pembayaran');
            }
        });

        // Backfill: Hubungkan user pemilik SPPG ke unit_sppg_id miliknya
        $units = DB::table('unit_sppg')->get();
        foreach ($units as $unit) {
            if ($unit->user_id) {
                DB::table('users')
                    ->where('id', $unit->user_id)
                    ->update(['unit_sppg_id' => $unit->id]);
            }
        }

        // Pastikan akun admin utama (id 1) memiliki role superadmin
        DB::table('users')->where('id', 1)->update([
            'role' => 'superadmin',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('periodes', 'unit_sppg_id')) {
            Schema::table('periodes', function (Blueprint $table) {
                $table->dropForeign(['unit_sppg_id']);
                $table->dropColumn('unit_sppg_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'unit_sppg_id')) {
                $table->dropForeign(['unit_sppg_id']);
                $table->dropColumn('unit_sppg_id');
            }
            if (Schema::hasColumn('users', 'tgl_berakhir_langganan')) {
                $table->dropColumn('tgl_berakhir_langganan');
            }
        });
    }
};
