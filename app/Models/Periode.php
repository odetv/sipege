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
        'is_penyesuaian',
        'tampilkan_di_list',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_mulai'     => 'date',
        'tanggal_selesai'   => 'date',
        'is_penyesuaian'    => 'boolean',
        'tampilkan_di_list' => 'boolean',
    ];

    /**
     * Scope untuk mengambil periode yang diizinkan tampil di dropdown & list umum
     */
    public function scopeUntukList($query)
    {
        return $query->where('tampilkan_di_list', true);
    }

    /**
     * Helper untuk mengambil list periode untuk dropdown (mengikuti preferensi tampilkan_di_list pengguna)
     */
    public static function getUntukDropdown()
    {
        $list = static::untukList()->orderBy('nomor_periode', 'asc')->get();
        if ($list->isEmpty()) {
            $list = static::orderBy('nomor_periode', 'asc')->get();
        }
        return $list;
    }

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
