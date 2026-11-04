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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'paket_langganan')) {
                $table->string('paket_langganan', 50)->default('basic')->after('catatan_pembayaran');
            }
            if (!Schema::hasColumn('users', 'nominal_langganan')) {
                $table->decimal('nominal_langganan', 15, 2)->default(500000)->after('paket_langganan');
            }
        });

        // Migrasikan nilai existing dari paket_sewa ke paket_langganan jika ada
        if (Schema::hasColumn('users', 'paket_sewa')) {
            DB::statement("
                UPDATE users 
                SET paket_langganan = CASE 
                    WHEN paket_sewa = 'bulanan' THEN 'pro'
                    WHEN paket_sewa IN ('basic', 'standart', 'pro') THEN paket_sewa
                    ELSE 'basic'
                END
                WHERE paket_langganan IS NULL OR paket_langganan = 'basic'
            ");
        }

        if (Schema::hasColumn('users', 'nominal_sewa')) {
            DB::statement("
                UPDATE users 
                SET nominal_langganan = COALESCE(nominal_sewa, 500000)
                WHERE nominal_langganan IS NULL OR nominal_langganan = 500000
            ");
        }

        // Standarisasi Role User:
        // 1. Akun dengan role administrator tetap 'administrator'
        // 2. Akun yang status pembayarannya lunas mendapatkan role 'member'
        // 3. Akun yang belum bayar / baru mendaftar mendapatkan role 'guest'
        DB::table('users')
            ->where('role', '!=', 'administrator')
            ->where('status_pembayaran', 'lunas')
            ->update(['role' => 'member']);

        DB::table('users')
            ->where('role', '!=', 'administrator')
            ->where('role', '!=', 'member')
            ->update(['role' => 'guest']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'nominal_langganan')) {
                $table->dropColumn('nominal_langganan');
            }
            if (Schema::hasColumn('users', 'paket_langganan')) {
                $table->dropColumn('paket_langganan');
            }
        });
    }
};
