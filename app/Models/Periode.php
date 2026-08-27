<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Periode extends Model
{
    protected $table = 'periodes';

    protected $fillable = [
        'nomor_periode',
        'tanggal_mulai',
        'tanggal_selesai',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
    ];

    /**
     * Status otomatis berdasarkan tanggal hari ini:
     * - 'aktif'       : hari ini berada dalam rentang periode
     * - 'selesai'     : periode sudah lewat
     * - 'akan_datang' : periode belum dimulai
     */
    public function getStatusAttribute(): string
    {
        $today = Carbon::today();

        if ($today->between($this->tanggal_mulai, $this->tanggal_selesai)) {
            return 'aktif';
        }

        if ($today->gt($this->tanggal_selesai)) {
            return 'selesai';
        }

        return 'akan_datang';
    }

    /**
     * Label singkat periode, misal: "Periode 1 (04–16 Mei 2026)".
     */
    public function getLabelAttribute(): string
    {
        $mulai   = $this->tanggal_mulai?->format('d M Y') ?? '-';
        $selesai = $this->tanggal_selesai?->format('d M Y') ?? '-';

        return "Periode {$this->nomor_periode} ({$mulai} – {$selesai})";
    }
}
