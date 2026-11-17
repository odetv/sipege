<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiKeamananIzin extends Model
{
    use HasFactory;

    protected $table = 'mutasi_keamanan_izin';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_izin' => 'date',
            'tanggal_keluar' => 'date',
            'tanggal_kembali' => 'date',
            'lat_izin' => 'float',
            'lng_izin' => 'float',
            'lat_awal' => 'float',
            'lng_awal' => 'float',
            'lat_tujuan' => 'float',
            'lng_tujuan' => 'float',
            'lat_kembali' => 'float',
            'lng_kembali' => 'float',
            'durasi_keluar_menit' => 'integer',
            'durasi_total_menit' => 'integer',
        ];
    }

    public function mutasiKeamanan(): BelongsTo
    {
        return $this->belongsTo(MutasiKeamanan::class, 'mutasi_keamanan_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'petugas_id');
    }
}

