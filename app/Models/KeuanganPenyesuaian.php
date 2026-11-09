<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeuanganPenyesuaian extends Model
{
    use HasFactory;

    protected $table = 'keuangan_penyesuaians';

    protected $fillable = [
        'unit_sppg_id',
        'user_id',
        'nomor_penyesuaian',
        'tanggal',
        'arah',
        'jenis',
        'jumlah',
        'alasan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'float',
    ];

    public function unitSppg(): BelongsTo
    {
        return $this->belongsTo(UnitSppg::class, 'unit_sppg_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
