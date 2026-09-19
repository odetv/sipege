<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\UnitSppg;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('TRUNCATE TABLE suppliers RESTART IDENTITY CASCADE;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            DB::table('suppliers')->delete();
            DB::statement('DELETE FROM sqlite_sequence WHERE name = "suppliers";');
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::table('suppliers')->delete();
        }

        $unitSppg = UnitSppg::where('id_sppg', 'QQCV0LUG')->first() ?: UnitSppg::first();
        $unitSppgId = $unitSppg ? $unitSppg->id : 1;

        $suppliers = [
            [
                'unit_sppg_id' => $unitSppgId,
                'jenis_supplier' => 'UMKM',
                'nama_usaha' => 'Malik Abdul Azis',
                'nama_pemilik' => 'Malik Abdul Azis',
                'no_telp' => '6282247782792',
                'komoditas' => ['Beras'],
                'provinsi' => 'Bali',
                'kabupaten' => 'Buleleng',
                'kecamatan' => 'Sukasada',
                'kelurahan' => 'Tegallinggah',
                'alamat_lengkap' => 'Banjar Dinas Mundukkunci, RT002/RW001, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali',
                'kode_pos' => '81161',
            ],
            [
                'unit_sppg_id' => $unitSppgId,
                'jenis_supplier' => 'UD',
                'nama_usaha' => 'UD. Manda',
                'nama_pemilik' => 'Ketut Ngr Hartawan Candra',
                'no_telp' => '6287738268454',
                'komoditas' => ['Gas LPG'],
                'provinsi' => 'Bali',
                'kabupaten' => 'Buleleng',
                'kecamatan' => 'Sukasada',
                'kelurahan' => 'Panji Anom',
                'alamat_lengkap' => 'Banjar Dinas Batupulu, Desa Panji Anom, Kec. Sukasada, Kab. Buleleng, Bali',
                'kode_pos' => '81161',
            ],
            [
                'unit_sppg_id' => $unitSppgId,
                'jenis_supplier' => 'UMKM',
                'nama_usaha' => 'Tsalis Ukhuwah Diniyah',
                'nama_pemilik' => 'Tsalis Ukhuwah Diniyah',
                'no_telp' => '6281999007423',
                'komoditas' => ['Tahu'],
                'provinsi' => 'Bali',
                'kabupaten' => 'Buleleng',
                'kecamatan' => 'Buleleng',
                'kelurahan' => 'Banyuasri',
                'alamat_lengkap' => 'Jalan Jalak Putih 1 Gang 3',
                'kode_pos' => '81116',
            ],
            [
                'unit_sppg_id' => $unitSppgId,
                'jenis_supplier' => 'UD',
                'nama_usaha' => 'UD. Alifa Zahra',
                'nama_pemilik' => 'Andi Rachman',
                'no_telp' => '6287763852566',
                'komoditas' => ['Ayam'],
                'provinsi' => 'Bali',
                'kabupaten' => 'Buleleng',
                'kecamatan' => 'Sukasada',
                'kelurahan' => 'Tegallinggah',
                'alamat_lengkap' => 'Banjar Dinas Mundukkunci, RT001/RW001, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali',
                'kode_pos' => '81161',
            ],
            [
                'unit_sppg_id' => $unitSppgId,
                'jenis_supplier' => 'UMKM',
                'nama_usaha' => 'Rumah Tempe',
                'nama_pemilik' => 'Noer Hakim',
                'no_telp' => '6281916033934',
                'komoditas' => ['Tempe'],
                'provinsi' => 'Bali',
                'kabupaten' => 'Buleleng',
                'kecamatan' => 'Buleleng',
                'kelurahan' => 'Kampung Baru',
                'alamat_lengkap' => 'Jl. Pulau Sugara No. 31',
                'kode_pos' => '81114',
            ],
            [
                'unit_sppg_id' => $unitSppgId,
                'jenis_supplier' => 'UMKM',
                'nama_usaha' => 'Rahmat Hidayat',
                'nama_pemilik' => 'Rahmat Hidayat',
                'no_telp' => '6282237364244',
                'komoditas' => ['Telur Ayam'],
                'provinsi' => 'Bali',
                'kabupaten' => 'Buleleng',
                'kecamatan' => 'Sukasada',
                'kelurahan' => 'Tegallinggah',
                'alamat_lengkap' => 'Banjar Dinas Mundukkunci, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali',
                'kode_pos' => '81161',
            ],
            [
                'unit_sppg_id' => $unitSppgId,
                'jenis_supplier' => 'UMKM',
                'nama_usaha' => 'Zea Fruit Barokah',
                'nama_pemilik' => 'Muhammad Sahrul Efendi',
                'no_telp' => '6281236090828',
                'komoditas' => ['Buah'],
                'provinsi' => 'Bali',
                'kabupaten' => 'Buleleng',
                'kecamatan' => 'Gerokgak',
                'kelurahan' => 'Banyupoh',
                'alamat_lengkap' => 'Banjar Dinas Kertakawat, Desa Banyupoh, Kec. Gerokgak, Kab. Buleleng, Prov. Bali',
                'kode_pos' => '81155',
            ],
            [
                'unit_sppg_id' => $unitSppgId,
                'jenis_supplier' => 'UMKM',
                'nama_usaha' => 'H.M. Bahzah',
                'nama_pemilik' => 'H.M. Bahzah',
                'no_telp' => '6281999609409',
                'komoditas' => ['Sayuran dan Bumbu'],
                'provinsi' => 'Bali',
                'kabupaten' => 'Buleleng',
                'kecamatan' => 'Sukasada',
                'kelurahan' => 'Tegallinggah',
                'alamat_lengkap' => 'Banjar Dinas Mundukkunci Rt 001 / Rw 001 Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Prov. Bali',
                'kode_pos' => '81161',
            ],
            [
                'unit_sppg_id' => $unitSppgId,
                'jenis_supplier' => 'PT',
                'nama_usaha' => 'PT. Delafresh Gyan Nian',
                'nama_pemilik' => 'Ni Made Ayu Puspita',
                'no_telp' => '6281775030926',
                'komoditas' => ['Sayuran dan Buah'],
                'provinsi' => 'Bali',
                'kabupaten' => 'Buleleng',
                'kecamatan' => 'Sawan',
                'kelurahan' => 'Sekumpul',
                'alamat_lengkap' => 'Jalan Raya Desa Sekumpul',
                'kode_pos' => '81171',
            ],
            [
                'unit_sppg_id' => $unitSppgId,
                'jenis_supplier' => 'CV',
                'nama_usaha' => 'CV. Citra Lestari Abadi',
                'nama_pemilik' => 'Kadek Tintin Ratmini',
                'no_telp' => '6287738268454',
                'komoditas' => ['Gas LPG'],
                'provinsi' => 'Bali',
                'kabupaten' => 'Buleleng',
                'kecamatan' => 'Buleleng',
                'kelurahan' => 'Paket Agung',
                'alamat_lengkap' => 'Jl. Gunung Rinjani, Kel. Paket Agung',
                'kode_pos' => '81117',
            ],
            [
                'unit_sppg_id' => $unitSppgId,
                'jenis_supplier' => 'UMKM',
                'nama_usaha' => 'Tsaqila Nur Azizah',
                'nama_pemilik' => 'Tsaqila Nur Azizah',
                'no_telp' => '6289654438290',
                'komoditas' => ['Toko Perlengkapan (ATK, Air Galon, dan Lainnya)'],
                'provinsi' => 'Bali',
                'kabupaten' => 'Buleleng',
                'kecamatan' => 'Sukasada',
                'kelurahan' => 'Tegallinggah',
                'alamat_lengkap' => 'Banjar Dinas Mundukkunci Rt 001 / Rw 001 Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Prov. Bali',
                'kode_pos' => '81161',
            ],
        ];

        foreach ($suppliers as $data) {
            Supplier::create($data);
        }
    }
}
