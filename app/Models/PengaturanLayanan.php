<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengaturanLayanan extends Model
{
    use HasFactory;

    protected $table = 'pengaturan_layanans';

    protected $fillable = [
        'kategori',
        'driver',
        'konfigurasi',
        'is_active',
        'status_koneksi',
        'last_tested_at',
        'pesan_koneksi',
    ];

    protected $casts = [
        'konfigurasi' => 'array',
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    /**
     * Helper cepat untuk mengambil konfigurasi layanan berdasarkan kategori (storage, mail)
     */
    public static function getKategori(string $kategori): ?self
    {
        return static::where('kategori', $kategori)->first();
    }
}
