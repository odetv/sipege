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
        Schema::table('absensi_petugas', function (Blueprint $table) {
            $table->string('nama')->nullable()->after('status');
            $table->string('nik', 20)->nullable()->after('nama');
            $table->string('jabatan')->nullable()->after('nik');
            $table->bigInteger('gaji_harian_bgn')->default(0)->after('jabatan');
            $table->bigInteger('bonus_harian_mitra')->default(0)->after('gaji_harian_bgn');
            $table->bigInteger('iuran_bpjs_tk')->default(0)->after('bonus_harian_mitra');
            $table->string('status_petugas', 30)->default('Aktif')->after('iuran_bpjs_tk');
            $table->json('snapshot_petugas')->nullable()->after('status_petugas');
        });

        // Backfill existing absensi_petugas records from current petugas table
        $petugasList = DB::table('petugas')->get();
        foreach ($petugasList as $p) {
            DB::table('absensi_petugas')
                ->where('petugas_id', $p->id)
                ->whereNull('nama')
                ->update([
                    'nama' => $p->nama,
                    'nik' => $p->nik,
                    'jabatan' => $p->jabatan,
                    'gaji_harian_bgn' => (int) $p->gaji_harian_bgn,
                    'bonus_harian_mitra' => (int) $p->bonus_harian_mitra,
                    'iuran_bpjs_tk' => (int) $p->iuran_bpjs_tk,
                    'status_petugas' => $p->status,
                    'snapshot_petugas' => json_encode([
                        'nama' => $p->nama,
                        'nik' => $p->nik,
                        'jabatan' => $p->jabatan,
                        'jam_kerja' => $p->jam_kerja,
                        'gaji_harian_bgn' => (int) $p->gaji_harian_bgn,
                        'bonus_harian_mitra' => (int) $p->bonus_harian_mitra,
                        'iuran_bpjs_tk' => (int) $p->iuran_bpjs_tk,
                        'status' => $p->status,
                    ]),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensi_petugas', function (Blueprint $table) {
            $table->dropColumn([
                'nama',
                'nik',
                'jabatan',
                'gaji_harian_bgn',
                'bonus_harian_mitra',
                'iuran_bpjs_tk',
                'status_petugas',
                'snapshot_petugas',
            ]);
        });
    }
};
