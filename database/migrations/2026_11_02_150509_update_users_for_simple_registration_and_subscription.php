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
        // 1. Jadikan kolom profil nullable agar registrasi awal hanya butuh nama, email, password
        $nullableCols = [
            'nik',
            'agama',
            'jenis_kelamin',
            'tempat_lahir',
            'tanggal_lahir',
            'status_kawin',
            'provinsi_ktp',
            'kabupaten_ktp',
            'kecamatan_ktp',
            'desa_kelurahan_ktp',
            'provinsi_domisili',
            'kabupaten_domisili',
            'kecamatan_domisili',
            'desa_kelurahan_domisili',
            'latitude_domisili',
            'longitude_domisili',
            'telepon',
        ];

        foreach ($nullableCols as $col) {
            DB::statement("ALTER TABLE users ALTER COLUMN {$col} DROP NOT NULL;");
        }

        // 2. Tambahkan kolom status pembayaran sewa & onboarding kelengkapan data
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'status_pembayaran')) {
                $table->string('status_pembayaran', 50)->default('menunggu_pembayaran')->after('role');
            }
            if (!Schema::hasColumn('users', 'tgl_pembayaran')) {
                $table->timestamp('tgl_pembayaran')->nullable()->after('status_pembayaran');
            }
            if (!Schema::hasColumn('users', 'status_onboarding')) {
                $table->string('status_onboarding', 50)->default('menunggu_pembayaran')->after('tgl_pembayaran');
            }
            if (!Schema::hasColumn('users', 'bukti_pembayaran')) {
                $table->string('bukti_pembayaran', 255)->nullable()->after('status_onboarding');
            }
            if (!Schema::hasColumn('users', 'catatan_pembayaran')) {
                $table->text('catatan_pembayaran')->nullable()->after('bukti_pembayaran');
            }
            if (!Schema::hasColumn('users', 'paket_sewa')) {
                $table->string('paket_sewa', 100)->default('bulanan')->after('catatan_pembayaran');
            }
            if (!Schema::hasColumn('users', 'nominal_sewa')) {
                $table->decimal('nominal_sewa', 15, 2)->default(1500000)->after('paket_sewa');
            }
        });

        // 3. Set akun existing (administrator / yang sudah punya NIK) langsung lunas & selesai onboarding
        DB::table('users')->where('role', 'administrator')->orWhereNotNull('nik')->update([
            'status_pembayaran' => 'lunas',
            'status_onboarding' => 'selesai',
            'tgl_pembayaran' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'status_pembayaran',
                'tgl_pembayaran',
                'status_onboarding',
                'bukti_pembayaran',
                'catatan_pembayaran',
                'paket_sewa',
                'nominal_sewa',
            ]);
        });
    }
};
