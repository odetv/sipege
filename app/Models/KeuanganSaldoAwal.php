<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeuanganSaldoAwal extends Model
{
    use HasFactory;

    protected $table = 'keuangan_saldo_awals';

    protected $fillable = [
        'unit_sppg_id',
        'user_id',
        'tanggal',
        'nominal',
        'keterangan',
        'is_locked',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'float',
        'is_locked' => 'boolean',
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
