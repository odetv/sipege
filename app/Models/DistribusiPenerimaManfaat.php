<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistribusiPenerimaManfaat extends Model
{
    use HasFactory;

    protected $table = 'distribusi_penerima_manfaat';

    protected $fillable = [
        'kelompok_id',
        'unit_sppg_id',
        'tanggal',
        'status',
        'porsi_kecil',
        'porsi_besar',
        'total_porsi',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'porsi_kecil' => 'integer',
        'porsi_besar' => 'integer',
        'total_porsi' => 'integer',
    ];

    public function kelompok()
    {
        return $this->belongsTo(KelompokPenerimaManfaat::class, 'kelompok_id');
    }

    public function unitSppg()
    {
        return $this->belongsTo(UnitSppg::class, 'unit_sppg_id');
    }
}
