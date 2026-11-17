<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiKeamananDetail extends Model
{
    use HasFactory;

    protected $table = 'mutasi_keamanan_detail';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'data_pengecekan' => 'array',
        ];
    }

    public function mutasi(): BelongsTo
    {
        return $this->belongsTo(MutasiKeamanan::class, 'mutasi_keamanan_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class);
    }
}

