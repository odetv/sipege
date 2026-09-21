<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingKopDokumen extends Model
{
    use HasFactory;

    protected $table = 'setting_kop_dokumens';

    protected $fillable = [
        'unit_sppg_id',
        'nama_instansi_1',
        'nama_instansi_2',
        'nama_unit',
        'kode_unit',
        'id_sppg',
        'alamat_lengkap',
        'desa_kelurahan',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'kode_pos',
        'telepon',
        'whatsapp',
        'email',
        'website',
        'logo_kiri_url',
        'logo_kanan_url',
        'layout_logo',
        'gaya_garis',
        'template_style',
        'is_aktif',
        'extra_attributes',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
        'extra_attributes' => 'array',
    ];

    public function unitSppg(): BelongsTo
    {
        return $this->belongsTo(UnitSppg::class, 'unit_sppg_id');
    }

    /**
     * Dapatkan konfigurasi kop aktif untuk unit SPPG atau buat default jika belum ada.
     */
    public static function getActiveConfig(?int $unitSppgId = null): self
    {
        $query = self::query()->where('is_aktif', true);
        if ($unitSppgId) {
            $query->where('unit_sppg_id', $unitSppgId);
        }

        $config = $query->first();

        if (!$config) {
            $unit = $unitSppgId ? UnitSppg::find($unitSppgId) : UnitSppg::first();
            $config = self::createDefaultFromUnit($unit);
        }

        return $config;
    }

    /**
     * Buat data default berdasarkan data unit SPPG.
     */
    public static function createDefaultFromUnit(?UnitSppg $unit = null): self
    {
        return self::create([
            'unit_sppg_id' => $unit?->id,
            'nama_instansi_1' => 'SPPG BULELENG SUKASADA TEGALLINGGAH',
            'nama_instansi_2' => 'YAYASAN PESANTREN MIFTAHUL ULUM',
            'nama_unit' => $unit?->nama ?? 'SPPG Buleleng Sukasada Tegallinggah',
            'kode_unit' => $unit?->kode_sppg ?? '51.08.05.2013.03',
            'id_sppg' => $unit?->id_sppg ?? 'QQCV0LUG',
            'alamat_lengkap' => $unit?->alamat_lengkap ?? 'Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali',
            'desa_kelurahan' => $unit?->desa_kelurahan ?? 'Tegallinggah',
            'kecamatan' => $unit?->kecamatan ?? 'Sukasada',
            'kabupaten' => $unit?->kabupaten ?? 'Buleleng',
            'provinsi' => $unit?->provinsi ?? 'Bali',
            'kode_pos' => $unit?->kode_pos ?? '81161',
            'telepon' => null,
            'whatsapp' => null,
            'email' => 'sppgsukasadategallinggah@gmail.com',
            'website' => null,
            'logo_kiri_url' => '/images/logo/BGN_LOGO_MAIN.png',
            'logo_kanan_url' => '/images/logo/Logo_Yayasan.png',
            'layout_logo' => 'dual',
            'gaya_garis' => 'ganda_kedinasan',
            'template_style' => 'klasik_formal',
            'is_aktif' => true,
        ]);
    }
}
