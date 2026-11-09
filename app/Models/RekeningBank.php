<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekeningBank extends Model
{
    use HasFactory;

    protected $table = 'rekening_banks';

    protected $fillable = [
        'unit_sppg_id',
        'user_id',
        'jenis_bank',
        'nama_bank_lainnya',
        'nama_rekening',
        'nomor_rekening',
        'nomor_virtual_account',
        'kantor_cabang',
        'keterangan',
        'is_utama',
        'status_aktif',
    ];

    protected $casts = [
        'is_utama' => 'boolean',
        'status_aktif' => 'boolean',
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

