<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MutasiKeamanan extends Model
{
    use HasFactory;

    protected $table = 'mutasi_keamanan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function unitSppg(): BelongsTo
    {
        return $this->belongsTo(UnitSppg::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(MutasiKeamananDetail::class, 'mutasi_keamanan_id')->orderBy('jam', 'asc');
    }

    public function izins(): HasMany
    {
        return $this->hasMany(MutasiKeamananIzin::class, 'mutasi_keamanan_id')->orderBy('created_at', 'desc');
    }

    public function serahTerimas(): HasMany
    {
        return $this->hasMany(MutasiKeamananSerahTerima::class, 'mutasi_keamanan_id')->orderBy('created_at', 'desc');
    }
}

