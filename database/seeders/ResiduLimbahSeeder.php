<?php

namespace Database\Seeders;

use App\Models\ResiduLimbah;
use App\Models\UnitSppg;
use Illuminate\Database\Seeder;

class ResiduLimbahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unit = UnitSppg::first();
        $unitId = $unit?->id;

        if (ResiduLimbah::count() > 0) {
            return;
        }

        $samples = [
            [
                'tanggal' => '2026-10-19',
                'nama_limbah' => 'Kulit & Bonggol Sayur Mayur',
                'jumlah' => 14.5,
                'satuan' => 'Kg',
                'jenis_limbah' => 'Organik',
                'sumber_limbah' => 'Persiapan',
                'metode_penanganan' => 'Kompos',
                'status_berbahaya' => 'Tidak Berbahaya',
                'keterangan' => 'Sisa kupasan wortel, labu siam dan buncis',
            ],
            [
                'tanggal' => '2026-10-19',
                'nama_limbah' => 'Karton Kardus & Plastik Wrapping Bahan',
                'jumlah' => 3.25,
                'satuan' => 'Kg',
                'jenis_limbah' => 'Anorganik',
                'sumber_limbah' => 'Persiapan',
                'metode_penanganan' => 'Daur Ulang',
                'status_berbahaya' => 'Tidak Berbahaya',
                'keterangan' => 'Kardus telur dan kemasan beras',
            ],
            [
                'tanggal' => '2026-10-20',
                'nama_limbah' => 'Minyak Jelantah Penggorengan',
                'jumlah' => 4.75,
                'satuan' => 'L',
                'jenis_limbah' => 'Anorganik',
                'sumber_limbah' => 'Pengolahan',
                'metode_penanganan' => 'Daur Ulang',
                'status_berbahaya' => 'Berbahaya',
                'keterangan' => 'Minyak jelantah ditampung jerigen untuk biodiesel mitra',
            ],
            [
                'tanggal' => '2026-10-20',
                'nama_limbah' => 'Sisa Tulang Ayam & Lemak Daging',
                'jumlah' => 8.2,
                'satuan' => 'Kg',
                'jenis_limbah' => 'Organik',
                'sumber_limbah' => 'Pengolahan',
                'metode_penanganan' => 'Pembuangan',
                'status_berbahaya' => 'Tidak Berbahaya',
                'keterangan' => 'Dibersihkan dan diangkut DLH',
            ],
            [
                'tanggal' => '2026-10-21',
                'nama_limbah' => 'Sisa Nasi & Sayur Ompreng Siswa',
                'jumlah' => 11.4,
                'satuan' => 'Kg',
                'jenis_limbah' => 'Organik',
                'sumber_limbah' => 'Ompreng',
                'metode_penanganan' => 'Kompos',
                'status_berbahaya' => 'Tidak Berbahaya',
                'keterangan' => 'Sisa makanan dari wadah ompreng sekolah',
            ],
            [
                'tanggal' => '2026-10-22',
                'nama_limbah' => 'Pecahan Kaca Wadah & Kemasan Rusak',
                'jumlah' => 0.8,
                'satuan' => 'Kg',
                'jenis_limbah' => 'Anorganik',
                'sumber_limbah' => 'Lainnya',
                'metode_penanganan' => 'Pembuangan',
                'status_berbahaya' => 'Berbahaya',
                'keterangan' => 'Limbah pecahan kaca dibungkus pelindung khusus',
            ],
            [
                'tanggal' => '2026-10-23',
                'nama_limbah' => 'Sisa Sayur Layu & Kulit Bawang',
                'jumlah' => 9.6,
                'satuan' => 'Kg',
                'jenis_limbah' => 'Organik',
                'sumber_limbah' => 'Persiapan',
                'metode_penanganan' => 'Kompos',
                'status_berbahaya' => 'Tidak Berbahaya',
                'keterangan' => 'Disalurkan ke unit komposter pupuk organik',
            ],
            [
                'tanggal' => '2026-10-26',
                'nama_limbah' => 'Makanan Berlebih Siap Konsumsi (Surplus)',
                'jumlah' => 6.0,
                'satuan' => 'Kg',
                'jenis_limbah' => 'Organik',
                'sumber_limbah' => 'Pengolahan',
                'metode_penanganan' => 'Donasi',
                'status_berbahaya' => 'Tidak Berbahaya',
                'keterangan' => 'Donasi makanan layak konsumsi ke warga sekitar SPPG',
            ],
            [
                'tanggal' => '2026-10-27',
                'nama_limbah' => 'Air Bekas Bilasan Sabun Berbusa',
                'jumlah' => 25.0,
                'satuan' => 'L',
                'jenis_limbah' => 'Anorganik',
                'sumber_limbah' => 'Ompreng',
                'metode_penanganan' => 'Pembuangan',
                'status_berbahaya' => 'Tidak Berbahaya',
                'keterangan' => 'Masuk bak perangkap lemak IPAL SPPG',
            ],
            [
                'tanggal' => '2026-10-28',
                'nama_limbah' => 'Ampas Kelapa & Kulit Telur',
                'jumlah' => 7.85,
                'satuan' => 'Kg',
                'jenis_limbah' => 'Organik',
                'sumber_limbah' => 'Persiapan',
                'metode_penanganan' => 'Kompos',
                'status_berbahaya' => 'Tidak Berbahaya',
                'keterangan' => 'Bahan pembuatan pupuk padat & cair',
            ],
        ];

        foreach ($samples as $s) {
            $s['unit_sppg_id'] = $unitId;
            ResiduLimbah::create($s);
        }
    }
}
