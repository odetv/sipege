<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KategoriKpm extends Model
{
    use HasFactory;

    protected $table = 'kategori_kpm';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'pagu_per_porsi' => 'integer',
            'urutan' => 'integer',
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
     * Daftar 11 Kategori KPM Default beserta Pagu Standar BGN
     */
    public static function getDefaultKategori(): array
    {
        return [
            [
                'nama' => 'PAUD/TK/RA',
                'pagu_per_porsi' => 8000,
                'jenis_porsi' => 'Porsi Kecil',
                'keterangan' => 'Pendidikan Anak Usia Dini, TK dan Raudhatul Athfal',
                'urutan' => 1,
                'status' => 'Aktif',
            ],
            [
                'nama' => 'SD/MI Kelas 1–3',
                'pagu_per_porsi' => 8000,
                'jenis_porsi' => 'Porsi Kecil',
                'keterangan' => 'Siswa Sekolah Dasar / Madrasah Ibtidaiyah Kelas 1 sampai 3',
                'urutan' => 2,
                'status' => 'Aktif',
            ],
            [
                'nama' => 'SD/MI Kelas 4–6',
                'pagu_per_porsi' => 10000,
                'jenis_porsi' => 'Porsi Besar',
                'keterangan' => 'Siswa Sekolah Dasar / Madrasah Ibtidaiyah Kelas 4 sampai 6',
                'urutan' => 3,
                'status' => 'Aktif',
            ],
            [
                'nama' => 'SMP/MTs',
                'pagu_per_porsi' => 10000,
                'jenis_porsi' => 'Porsi Besar',
                'keterangan' => 'Siswa Sekolah Menengah Pertama / Madrasah Tsanawiyah',
                'urutan' => 4,
                'status' => 'Aktif',
            ],
            [
                'nama' => 'SMA/MA/SMK/SLB',
                'pagu_per_porsi' => 10000,
                'jenis_porsi' => 'Porsi Besar',
                'keterangan' => 'Siswa Tingkat Menengah Atas / Kejuruan / Luar Biasa',
                'urutan' => 5,
                'status' => 'Aktif',
            ],
            [
                'nama' => 'Santri',
                'pagu_per_porsi' => 10000,
                'jenis_porsi' => 'Porsi Besar',
                'keterangan' => 'Santri Pondok Pesantren',
                'urutan' => 6,
                'status' => 'Aktif',
            ],
            [
                'nama' => 'Guru',
                'pagu_per_porsi' => 10000,
                'jenis_porsi' => 'Porsi Besar',
                'keterangan' => 'Guru & Tenaga Pendidik Sekolah/Madrasah/Pesantren',
                'urutan' => 7,
                'status' => 'Aktif',
            ],
            [
                'nama' => 'Tenaga Kependidikan',
                'pagu_per_porsi' => 10000,
                'jenis_porsi' => 'Porsi Besar',
                'keterangan' => 'Staf Tata Usaha & Tenaga Penunjang Sekolah',
                'urutan' => 8,
                'status' => 'Aktif',
            ],
            [
                'nama' => 'Ibu Hamil',
                'pagu_per_porsi' => 10000,
                'jenis_porsi' => 'Porsi Besar',
                'keterangan' => 'Penerima Manfaat Kelompok Khusus Ibu Hamil (Posyandu/BKB)',
                'urutan' => 9,
                'status' => 'Aktif',
            ],
            [
                'nama' => 'Ibu Menyusui',
                'pagu_per_porsi' => 10000,
                'jenis_porsi' => 'Porsi Besar',
                'keterangan' => 'Penerima Manfaat Kelompok Khusus Ibu Menyusui (Posyandu/BKB)',
                'urutan' => 10,
                'status' => 'Aktif',
            ],
            [
                'nama' => 'Anak Balita (6–59 bulan)',
                'pagu_per_porsi' => 8000,
                'jenis_porsi' => 'Porsi Kecil',
                'keterangan' => 'Anak Bawah Lima Tahun usia 6 s.d. 59 bulan',
                'urutan' => 11,
                'status' => 'Aktif',
            ],
        ];
    }

    /**
     * Seed data default jika tabel kosong atau saat reset
     */
    public static function seedDefaults(?int $unitSppgId = null): void
    {
        $defaults = self::getDefaultKategori();
        foreach ($defaults as $item) {
            $item['unit_sppg_id'] = $unitSppgId;
            self::create($item);
        }
    }
}
