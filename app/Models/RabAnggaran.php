<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RabAnggaran extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'rab_anggarans';

    protected $fillable = [
        'unit_sppg_id',
        'tahun',
        'bulan',
        'hari_efektif',
        'rincian_porsi',
        'rincian_operasional',
        'rincian_operasional_bantu',
        'jadwal_pm_harian',
        'total_biaya_per_hari',
        'total_biaya_2mingguan',
        'total_biaya_per_bulan',
        'catatan',
        'kepala_sppg_nama',
        'pengawas_keuangan_nama',
        'pengawas_gizi_nama',
        'nama_yayasan',
        'nomor_rekening_yayasan',
        'npwp',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'bulan' => 'integer',
        'hari_efektif' => 'integer',
        'rincian_porsi' => 'array',
        'rincian_operasional' => 'array',
        'rincian_operasional_bantu' => 'array',
        'jadwal_pm_harian' => 'array',
        'total_biaya_per_hari' => 'decimal:2',
        'total_biaya_2mingguan' => 'decimal:2',
        'total_biaya_per_bulan' => 'decimal:2',
    ];

    public function unitSppg(): BelongsTo
    {
        return $this->belongsTo(UnitSppg::class, 'unit_sppg_id');
    }
}

