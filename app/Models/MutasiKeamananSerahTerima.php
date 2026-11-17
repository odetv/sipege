<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiKeamananSerahTerima extends Model
{
    use HasFactory;

    protected $table = 'mutasi_keamanan_serah_terima';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function mutasi(): BelongsTo
    {
        return $this->belongsTo(MutasiKeamanan::class, 'mutasi_keamanan_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'petugas_id');
    }
}

