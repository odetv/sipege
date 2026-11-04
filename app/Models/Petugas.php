<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Petugas extends Model
{
    use HasFactory;

    public const JABATAN_ADMIN = 'admin';
    public const JABATAN_KEPALA_SPPG = 'kepala_sppg';
    public const JABATAN_PENGAWAS_GIZI = 'pengawas_gizi';
    public const JABATAN_PENGAWAS_KEUANGAN = 'pengawas_keuangan';
    public const JABATAN_ASISTEN_LAPANGAN = 'asisten_lapangan';
    public const JABATAN_PERSIAPAN = 'persiapan';
    public const JABATAN_PENGOLAHAN = 'pengolahan';
    public const JABATAN_PEMORSIAN = 'pemorsian';
    public const JABATAN_PENGEMUDI = 'pengemudi';
    public const JABATAN_CUCI_OMPRENG = 'cuci_ompreng';
    public const JABATAN_KEBERSIHAN = 'kebersihan';
    public const JABATAN_KEAMANAN = 'keamanan';

    public const JABATAN_LIST = [
        self::JABATAN_ADMIN => 'Admin',
        self::JABATAN_KEPALA_SPPG => 'Kepala SPPG',
        self::JABATAN_PENGAWAS_GIZI => 'Pengawas Gizi',
        self::JABATAN_PENGAWAS_KEUANGAN => 'Pengawas Keuangan',
        self::JABATAN_ASISTEN_LAPANGAN => 'Asisten Lapangan',
        self::JABATAN_PERSIAPAN => 'Persiapan',
        self::JABATAN_PENGOLAHAN => 'Pengolahan',
        self::JABATAN_PEMORSIAN => 'Pemorsian',
        self::JABATAN_PENGEMUDI => 'Pengemudi',
        self::JABATAN_CUCI_OMPRENG => 'Cuci Ompreng',
        self::JABATAN_KEBERSIHAN => 'Kebersihan',
        self::JABATAN_KEAMANAN => 'Keamanan',
    ];

    protected $table = 'petugas';

    protected $guarded = ['id'];

    protected $appends = ['umur'];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'gaji_harian_bgn' => 'integer',
            'gaji_harian_shifting' => 'integer',
            'bonus_harian_mitra' => 'integer',
            'iuran_bpjs_tk' => 'integer',
        ];
    }

    /**
     * Relasi ke Unit SPPG
     */
    public function unitSppg(): BelongsTo
    {
        return $this->belongsTo(UnitSppg::class, 'unit_sppg_id');
    }

    /**
     * Accessor untuk menghitung umur petugas berdasarkan tanggal lahir
     */
    protected function umur(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->tanggal_lahir) {
                    return null;
                }

                return Carbon::parse($this->tanggal_lahir)->age;
            }
        );
    }
}
