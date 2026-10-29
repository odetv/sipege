<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengaturanApi extends Model
{
    use HasFactory;

    protected $table = 'pengaturan_apis';

    protected $fillable = [
        'unit_sppg_id',
        'provider',
        'api_key',
        'model',
        'is_active',
        'status_koneksi',
        'last_tested_at',
        'pesan_koneksi',
        'extra_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
        'extra_config' => 'array',
    ];

    public function unitSppg(): BelongsTo
    {
        return $this->belongsTo(UnitSppg::class, 'unit_sppg_id');
    }
}
