<?php

namespace App\Http\Controllers;

use App\Models\DistribusiPenerimaManfaat;
use App\Models\KelompokPenerimaManfaat;
use App\Models\Periode;
use App\Models\Petugas;
use App\Models\TransaksiKeuangan;
use App\Models\WorkOrder;
use App\Models\WorkOrderKelompok;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KelompokPenerimaManfaatController extends Controller
{
    /**
     * Display a listing of Kelompok Penerima Manfaat.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        if (!$unitSppg) {
            return Inertia::render('PenerimaManfaat/Index', [
                'user' => $user,
                'unitSppg' => null,
                'kelompokList' => [],
                'stats' => [
                    'total_kelompok' => 0,
                    'total_laki_laki' => 0,
                    'total_perempuan' => 0,
                    'total_penerima' => 0,
                ],
                'filters' => $request->only(['search', 'kategori', 'jenis_kepemilikan']),
            ]);
        }

        $hasFilter = $request->filled('search') || $request->filled('kategori') || $request->filled('jenis_kepemilikan');

        $query = KelompokPenerimaManfaat::where('unit_sppg_id', $unitSppg->id)->with('rincian');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nama_kelompok', 'like', "%{$search}%")
                    ->orWhere('kode_identitas', 'like', "%{$search}%")
                    ->orWhere('nama_kepala', 'like', "%{$search}%")
                    ->orWhere('nama_pic', 'like', "%{$search}%")
                    ->orWhere('desa_kelurahan', 'like', "%{$search}%")
                    ->orWhere('kecamatan', 'like', "%{$search}%")
                    ->orWhere('kabupaten', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->input('kategori'));
        }

        if ($request->filled('jenis_kepemilikan')) {
            $query->where('jenis_kepemilikan', $request->input('jenis_kepemilikan'));
        }

        $kelompokList = $query->orderBy('id', 'asc')->get();
        $allKelompok = $hasFilter ? KelompokPenerimaManfaat::where('unit_sppg_id', $unitSppg->id)->with('rincian')->get() : $kelompokList;


        $totalPosyandu = $allKelompok->where('kategori', 'Posyandu')->count();
        $totalSekolah = $allKelompok->where('kategori', '!=', 'Posyandu')->count();

        $summary = [
            'total_kelompok' => $allKelompok->count(),
            'total_sekolah' => $totalSekolah,
            'total_posyandu' => $totalPosyandu,
            'total_penerima' => (int) $allKelompok->sum('total_penerima'),
            'total_laki_laki' => (int) $allKelompok->sum('total_laki_laki'),
            'total_perempuan' => (int) $allKelompok->sum('total_perempuan'),
            'total_porsi_kecil' => (int) $allKelompok->sum('total_porsi_kecil'),
            'total_porsi_besar' => (int) $allKelompok->sum('total_porsi_besar'),
        ];

        return Inertia::render('PenerimaManfaat/Index', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
            'stats' => $summary,
            'summary' => $summary,
            'filters' => [
                'search' => $request->input('search', ''),
                'kategori' => $request->input('kategori', ''),
                'jenis_kepemilikan' => $request->input('jenis_kepemilikan', ''),
            ],
        ]);
    }

    /**
     * Show the form for creating a new Kelompok Penerima Manfaat.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return redirect()->route('dashboard')->with('error', 'Silakan lengkapi profil Unit SPPG terlebih dahulu.');
        }

        return Inertia::render('PenerimaManfaat/Create', [
            'user' => $user,
            'unitSppg' => $unitSppg,
        ]);
    }

    /**
     * Store a newly created Kelompok Penerima Manfaat in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return redirect()->route('dashboard')->with('error', 'Data Unit SPPG tidak ditemukan.');
        }

        $validated = $request->validate([
            'nama_kelompok' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'in:TK,RA,PAUD,SD,MI,SMP,MTs,SMA,SMK,MA,MAK,Posyandu,SD/MI,SMP/MTs,SMA/MA,TK/RA'],
            'jenis_kepemilikan' => ['required', 'string', 'in:Negeri,Swasta'],
            'tipe_identitas' => ['required', 'string', 'in:NPSN,NSPP,NSM,NSNP,TPK,Lainnya'],
            'kode_identitas' => ['required', 'string', 'max:100'],
            'nama_kepala' => ['required', 'string', 'max:255'],
            'email_kepala' => ['required', 'email', 'max:255'],
            'telepon_kepala' => ['required', 'string', 'regex:/^62[0-9]{8,15}$/'],
            'nama_pic' => ['required', 'string', 'max:255'],
            'email_pic' => ['required', 'email', 'max:255'],
            'telepon_pic' => ['required', 'string', 'regex:/^62[0-9]{8,15}$/'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kabupaten' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'desa_kelurahan' => ['required', 'string', 'max:100'],
            'kode_pos' => ['required', 'numeric', 'digits:5'],
            'alamat_lengkap' => ['required', 'string'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'jumlah_kader' => $request->input('kategori') === 'Posyandu' ? ['required', 'integer', 'min:1'] : ['nullable'],
            'alergi_porsi_kecil' => ['nullable', 'integer', 'min:0'],
            'alergi_porsi_besar' => ['nullable', 'integer', 'min:0'],
            'keterangan_alergi' => ['nullable', 'array'],
            'keterangan_alergi.*' => ['nullable'],
            'rincian' => ['required', 'array', 'min:1'],
            'rincian.*.sub_kategori' => ['required', 'string', 'max:255'],
            'rincian.*.waktu_batas_makan' => ['required', 'string', 'max:10'],
            'rincian.*.jumlah_laki_laki' => ['required', 'integer', 'min:0'],
            'rincian.*.jumlah_perempuan' => ['required', 'integer', 'min:0'],
        ], [
            'nama_kelompok.required' => 'Nama kelompok penerima manfaat wajib diisi.',
            'kategori.required' => 'Kategori penerima manfaat wajib dipilih.',
            'jenis_kepemilikan.required' => 'Jenis kepemilikan wajib dipilih.',
            'tipe_identitas.required' => 'Tipe identitas wajib dipilih.',
            'kode_identitas.required' => 'Kode identitas wajib diisi.',
            'nama_kepala.required' => 'Nama Kepala Satuan / Pimpinan wajib diisi.',
            'email_kepala.required' => 'Email Kepala Satuan / Pimpinan wajib diisi.',
            'email_kepala.email' => 'Format email Kepala Satuan tidak valid.',
            'telepon_kepala.required' => 'Nomor telepon Kepala Satuan wajib diisi.',
            'telepon_kepala.regex' => 'Format nomor telepon Kepala Satuan harus diawali 62 (contoh: 6281234567890).',
            'nama_pic.required' => 'Nama PIC wajib diisi.',
            'email_pic.required' => 'Email PIC wajib diisi.',
            'email_pic.email' => 'Format email PIC tidak valid.',
            'telepon_pic.required' => 'Nomor telepon PIC wajib diisi.',
            'telepon_pic.regex' => 'Format nomor telepon PIC harus diawali 62 (contoh: 6281234567890).',
            'kode_pos.digits' => 'Kode pos harus 5 digit angka.',
            'alamat_lengkap.required' => 'Alamat lengkap wajib diisi.',
            'latitude.required' => 'Titik koordinat (Latitude) wajib ditentukan.',
            'longitude.required' => 'Titik koordinat (Longitude) wajib ditentukan.',
            'jumlah_kader.required_if' => 'Jumlah kader posyandu wajib diisi minimal 1 orang.',
            'jumlah_kader.min' => 'Jumlah kader posyandu minimal 1 orang.',
            'rincian.required' => 'Rincian jumlah penerima manfaat wajib diisi.',
            'rincian.*.waktu_batas_makan.required' => 'Waktu batas makan setiap sub kategori wajib diisi.',
        ]);

        DB::transaction(function () use ($validated, $unitSppg) {
            $totalLakiLaki = 0;
            $totalPerempuan = 0;
            $totalPorsiKecil = 0;
            $totalPorsiBesar = 0;

            foreach ($validated['rincian'] as $item) {
                $l = (int) ($item['jumlah_laki_laki'] ?? 0);
                $p = (int) ($item['jumlah_perempuan'] ?? 0);
                $tot = $l + $p;
                $totalLakiLaki += $l;
                $totalPerempuan += $p;

                $jenisPorsi = self::determineJenisPorsi($item['sub_kategori'], $validated['kategori']);
                if ($jenisPorsi === 'Porsi Kecil') {
                    $totalPorsiKecil += $tot;
                } else {
                    $totalPorsiBesar += $tot;
                }
            }

            $totalPenerima = $totalLakiLaki + $totalPerempuan;

            $alergiData = self::processAlergiData(
                $validated['keterangan_alergi'] ?? [],
                $validated['alergi_porsi_kecil'] ?? 0,
                $validated['alergi_porsi_besar'] ?? 0
            );

            $kelompok = KelompokPenerimaManfaat::create([
                'unit_sppg_id' => $unitSppg->id,
                'nama_kelompok' => $validated['nama_kelompok'],
                'kategori' => $validated['kategori'],
                'jenis_kepemilikan' => $validated['jenis_kepemilikan'],
                'tipe_identitas' => $validated['tipe_identitas'],
                'kode_identitas' => $validated['kode_identitas'],
                'nama_kepala' => $validated['nama_kepala'],
                'email_kepala' => $validated['email_kepala'],
                'telepon_kepala' => $validated['telepon_kepala'],
                'nama_pic' => $validated['nama_pic'],
                'email_pic' => $validated['email_pic'],
                'telepon_pic' => $validated['telepon_pic'],
                'provinsi' => $validated['provinsi'],
                'kabupaten' => $validated['kabupaten'],
                'kecamatan' => $validated['kecamatan'],
                'desa_kelurahan' => $validated['desa_kelurahan'],
                'kode_pos' => $validated['kode_pos'],
                'alamat_lengkap' => $validated['alamat_lengkap'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'jumlah_kader' => $validated['kategori'] === 'Posyandu' ? (int) ($validated['jumlah_kader'] ?? 0) : 0,
                'total_laki_laki' => $totalLakiLaki,
                'total_perempuan' => $totalPerempuan,
                'total_porsi_kecil' => $totalPorsiKecil,
                'total_porsi_besar' => $totalPorsiBesar,
                'total_penerima' => $totalPenerima,
                'alergi_porsi_kecil' => $alergiData['alergi_porsi_kecil'],
                'alergi_porsi_besar' => $alergiData['alergi_porsi_besar'],
                'keterangan_alergi' => $alergiData['keterangan_alergi'],
            ]);

            $sortedRincian = self::sortRincianArray($validated['rincian'], $validated['kategori']);
            foreach ($sortedRincian as $item) {
                $l = (int) ($item['jumlah_laki_laki'] ?? 0);
                $p = (int) ($item['jumlah_perempuan'] ?? 0);
                $jenisPorsi = self::determineJenisPorsi($item['sub_kategori'], $validated['kategori']);

                RincianPenerimaManfaat::create([
                    'kelompok_penerima_manfaat_id' => $kelompok->id,
                    'sub_kategori' => $item['sub_kategori'],
                    'jenis_porsi' => $jenisPorsi,
                    'waktu_batas_makan' => $item['waktu_batas_makan'] ?? '10:00',
                    'jumlah_laki_laki' => $l,
                    'jumlah_perempuan' => $p,
                    'total' => $l + $p,
                ]);
            }
            KelompokPenerimaManfaat::clearUnitCache($unitSppg->id);
        });

        return redirect()->route('penerima-manfaat.index')->with('success', 'Data Kelompok Penerima Manfaat berhasil disimpan.');
    }

    /**
     * Show the form for editing the specified Kelompok Penerima Manfaat.
     */
    public function edit(Request $request, KelompokPenerimaManfaat $penerima_manfaat): Response|RedirectResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        if (!$unitSppg || $penerima_manfaat->unit_sppg_id !== $unitSppg->id) {
            return redirect()->route('penerima-manfaat.index')->with('error', 'Akses tidak diizinkan atau data tidak ditemukan.');
        }

        $penerima_manfaat->load('rincian');

        return Inertia::render('PenerimaManfaat/Edit', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompok' => $penerima_manfaat,
        ]);
    }

    /**
     * Dapatkan daftar urutan resmi subkategori berdasarkan jenjang / kategori.
     */
    public static function getSubKategoriOrder(string $kategori): array
    {
        return match ($kategori) {
            'TK', 'RA', 'PAUD', 'TK/RA', 'TK/RA/PAUD' => [
                'Pelajar',
                'Pendukung (Guru)',
                'Pendukung (Tenaga Kependidikan)',
                'Pendukung (Satpam)',
                'Pendukung (Lainnya)',
            ],
            'SD', 'MI', 'SD/MI' => [
                'Kelas 1',
                'Kelas 2',
                'Kelas 3',
                'Kelas 4',
                'Kelas 5',
                'Kelas 6',
                'Pendukung (Guru)',
                'Pendukung (Tenaga Kependidikan)',
                'Pendukung (Satpam)',
                'Pendukung (Lainnya)',
            ],
            'SMP', 'MTs', 'SMP/MTs' => [
                'Kelas 7',
                'Kelas 8',
                'Kelas 9',
                'Pendukung (Guru)',
                'Pendukung (Tenaga Kependidikan)',
                'Pendukung (Satpam)',
                'Pendukung (Lainnya)',
            ],
            'SMA', 'SMK', 'MA', 'MAK', 'SMA/MA', 'SMA/SMK', 'SMA/SMK/MA' => [
                'Kelas 10',
                'Kelas 11',
                'Kelas 12',
                'Pendukung (Guru)',
                'Pendukung (Tenaga Kependidikan)',
                'Pendukung (Satpam)',
                'Pendukung (Lainnya)',
            ],
            'Posyandu' => [
                'Ibu Hamil',
                'Ibu Menyusui',
                'Balita',
                'Pendukung (Lainnya)',
            ],
            default => [
                'Penerima Utama',
                'Pendukung (Guru)',
                'Pendukung (Tenaga Kependidikan)',
                'Pendukung (Satpam)',
                'Pendukung (Lainnya)',
            ],
        };
    }

    /**
     * Urutkan array rincian sesuai urutan resmi kategori.
     */
    public static function sortRincianArray(array $rincianList, string $kategori): array
    {
        $order = self::getSubKategoriOrder($kategori);
        usort($rincianList, function ($a, $b) use ($order) {
            $subA = $a['sub_kategori'] ?? '';
            $subB = $b['sub_kategori'] ?? '';
            $posA = array_search($subA, $order);
            $posB = array_search($subB, $order);
            $idxA = $posA === false ? 999 : $posA;
            $idxB = $posB === false ? 999 : $posB;
            return $idxA <=> $idxB;
        });
        return $rincianList;
    }

    /**
     * Helper untuk menentukan jenis porsi secara otomatis berdasarkan subkategori & jenjang.
     */
    public static function determineJenisPorsi(string $subKategori, string $kategori): string
    {
        $porsiMap = [
            'Pelajar' => 'Porsi Kecil',
            'Kelas 1' => 'Porsi Kecil',
            'Kelas 2' => 'Porsi Kecil',
            'Kelas 3' => 'Porsi Kecil',
            'Kelas 4' => 'Porsi Besar',
            'Kelas 5' => 'Porsi Besar',
            'Kelas 6' => 'Porsi Besar',
            'Kelas 7' => 'Porsi Besar',
            'Kelas 8' => 'Porsi Besar',
            'Kelas 9' => 'Porsi Besar',
            'Kelas 10' => 'Porsi Besar',
            'Kelas 11' => 'Porsi Besar',
            'Kelas 12' => 'Porsi Besar',
            'Ibu Hamil' => 'Porsi Besar',
            'Ibu Menyusui' => 'Porsi Besar',
            'Balita' => 'Porsi Kecil',
            'Pendukung (Guru)' => 'Porsi Besar',
            'Pendukung (Tenaga Kependidikan)' => 'Porsi Besar',
            'Pendukung (Satpam)' => 'Porsi Besar',
            'Pendukung (Lainnya)' => 'Porsi Besar',
        ];

        if (isset($porsiMap[$subKategori])) {
            return $porsiMap[$subKategori];
        }

        if (
            str_contains($subKategori, 'Balita') ||
            str_contains($subKategori, 'Kelas 1') ||
            str_contains($subKategori, 'Kelas 2') ||
            str_contains($subKategori, 'Kelas 3') ||
            ($subKategori === 'Pelajar' && in_array($kategori, ['TK', 'RA', 'PAUD']))
        ) {
            return 'Porsi Kecil';
        }

        return 'Porsi Besar';
    }

    /**
     * Update the specified Kelompok Penerima Manfaat in storage.
     */
    public function update(Request $request, KelompokPenerimaManfaat $penerima_manfaat): RedirectResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        if (!$unitSppg || $penerima_manfaat->unit_sppg_id !== $unitSppg->id) {
            return redirect()->route('penerima-manfaat.index')->with('error', 'Akses tidak diizinkan.');
        }

        $validated = $request->validate([
            'nama_kelompok' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'in:TK,RA,PAUD,SD,MI,SMP,MTs,SMA,SMK,MA,MAK,Posyandu,SD/MI,SMP/MTs,SMA/MA,TK/RA'],
            'jenis_kepemilikan' => ['required', 'string', 'in:Negeri,Swasta'],
            'tipe_identitas' => ['required', 'string', 'in:NPSN,NSPP,NSM,NSNP,TPK,Lainnya'],
            'kode_identitas' => ['required', 'string', 'max:100'],
            'nama_kepala' => ['required', 'string', 'max:255'],
            'email_kepala' => ['required', 'email', 'max:255'],
            'telepon_kepala' => ['required', 'string', 'regex:/^62[0-9]{8,15}$/'],
            'nama_pic' => ['required', 'string', 'max:255'],
            'email_pic' => ['required', 'email', 'max:255'],
            'telepon_pic' => ['required', 'string', 'regex:/^62[0-9]{8,15}$/'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kabupaten' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'desa_kelurahan' => ['required', 'string', 'max:100'],
            'kode_pos' => ['required', 'numeric', 'digits:5'],
            'alamat_lengkap' => ['required', 'string'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'jumlah_kader' => $request->input('kategori') === 'Posyandu' ? ['required', 'integer', 'min:1'] : ['nullable'],
            'alergi_porsi_kecil' => ['nullable', 'integer', 'min:0'],
            'alergi_porsi_besar' => ['nullable', 'integer', 'min:0'],
            'keterangan_alergi' => ['nullable', 'array'],
            'keterangan_alergi.*' => ['nullable'],
            'rincian' => ['required', 'array', 'min:1'],
            'rincian.*.sub_kategori' => ['required', 'string', 'max:255'],
            'rincian.*.waktu_batas_makan' => ['required', 'string', 'max:10'],
            'rincian.*.jumlah_laki_laki' => ['required', 'integer', 'min:0'],
            'rincian.*.jumlah_perempuan' => ['required', 'integer', 'min:0'],
        ], [
            'nama_kelompok.required' => 'Nama kelompok penerima manfaat wajib diisi.',
            'kategori.required' => 'Kategori penerima manfaat wajib dipilih.',
            'jenis_kepemilikan.required' => 'Jenis kepemilikan wajib dipilih.',
            'tipe_identitas.required' => 'Tipe identitas wajib dipilih.',
            'kode_identitas.required' => 'Kode identitas wajib diisi.',
            'nama_kepala.required' => 'Nama Kepala Satuan / Pimpinan wajib diisi.',
            'email_kepala.required' => 'Email Kepala Satuan / Pimpinan wajib diisi.',
            'email_kepala.email' => 'Format email Kepala Satuan tidak valid.',
            'telepon_kepala.required' => 'Nomor telepon Kepala Satuan wajib diisi.',
            'telepon_kepala.regex' => 'Format nomor telepon Kepala Satuan harus diawali 62 (contoh: 6281234567890).',
            'nama_pic.required' => 'Nama PIC wajib diisi.',
            'email_pic.required' => 'Email PIC wajib diisi.',
            'email_pic.email' => 'Format email PIC tidak valid.',
            'telepon_pic.required' => 'Nomor telepon PIC wajib diisi.',
            'telepon_pic.regex' => 'Format nomor telepon PIC harus diawali 62 (contoh: 6281234567890).',
            'kode_pos.digits' => 'Kode pos harus 5 digit angka.',
            'alamat_lengkap.required' => 'Alamat lengkap wajib diisi.',
            'latitude.required' => 'Titik koordinat (Latitude) wajib ditentukan.',
            'longitude.required' => 'Titik koordinat (Longitude) wajib ditentukan.',
            'jumlah_kader.required_if' => 'Jumlah kader posyandu wajib diisi minimal 1 orang.',
            'jumlah_kader.min' => 'Jumlah kader posyandu minimal 1 orang.',
            'rincian.required' => 'Rincian jumlah penerima manfaat wajib diisi.',
            'rincian.*.waktu_batas_makan.required' => 'Waktu batas makan setiap sub kategori wajib diisi.',
        ]);

        DB::transaction(function () use ($validated, $penerima_manfaat, $unitSppg) {
            $totalLakiLaki = 0;
            $totalPerempuan = 0;
            $totalPorsiKecil = 0;
            $totalPorsiBesar = 0;

            foreach ($validated['rincian'] as $item) {
                $l = (int) ($item['jumlah_laki_laki'] ?? 0);
                $p = (int) ($item['jumlah_perempuan'] ?? 0);
                $tot = $l + $p;
                $totalLakiLaki += $l;
                $totalPerempuan += $p;

                $jenisPorsi = self::determineJenisPorsi($item['sub_kategori'], $validated['kategori']);
                if ($jenisPorsi === 'Porsi Kecil') {
                    $totalPorsiKecil += $tot;
                } else {
                    $totalPorsiBesar += $tot;
                }
            }

            $totalPenerima = $totalLakiLaki + $totalPerempuan;

            $alergiData = self::processAlergiData(
                $validated['keterangan_alergi'] ?? [],
                $validated['alergi_porsi_kecil'] ?? 0,
                $validated['alergi_porsi_besar'] ?? 0
            );

            $penerima_manfaat->update([
                'nama_kelompok' => $validated['nama_kelompok'],
                'kategori' => $validated['kategori'],
                'jenis_kepemilikan' => $validated['jenis_kepemilikan'],
                'tipe_identitas' => $validated['tipe_identitas'],
                'kode_identitas' => $validated['kode_identitas'],
                'nama_kepala' => $validated['nama_kepala'],
                'email_kepala' => $validated['email_kepala'],
                'telepon_kepala' => $validated['telepon_kepala'],
                'nama_pic' => $validated['nama_pic'],
                'email_pic' => $validated['email_pic'],
                'telepon_pic' => $validated['telepon_pic'],
                'provinsi' => $validated['provinsi'],
                'kabupaten' => $validated['kabupaten'],
                'kecamatan' => $validated['kecamatan'],
                'desa_kelurahan' => $validated['desa_kelurahan'],
                'kode_pos' => $validated['kode_pos'],
                'alamat_lengkap' => $validated['alamat_lengkap'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'jumlah_kader' => $validated['kategori'] === 'Posyandu' ? (int) ($validated['jumlah_kader'] ?? 0) : 0,
                'total_laki_laki' => $totalLakiLaki,
                'total_perempuan' => $totalPerempuan,
                'total_porsi_kecil' => $totalPorsiKecil,
                'total_porsi_besar' => $totalPorsiBesar,
                'total_penerima' => $totalPenerima,
                'alergi_porsi_kecil' => $alergiData['alergi_porsi_kecil'],
                'alergi_porsi_besar' => $alergiData['alergi_porsi_besar'],
                'keterangan_alergi' => $alergiData['keterangan_alergi'],
            ]);

            // Sync rincian: delete old and recreate
            $penerima_manfaat->rincian()->delete();

            $sortedRincian = self::sortRincianArray($validated['rincian'], $validated['kategori']);
            foreach ($sortedRincian as $item) {
                $l = (int) ($item['jumlah_laki_laki'] ?? 0);
                $p = (int) ($item['jumlah_perempuan'] ?? 0);
                $jenisPorsi = self::determineJenisPorsi($item['sub_kategori'], $validated['kategori']);

                RincianPenerimaManfaat::create([
                    'kelompok_penerima_manfaat_id' => $penerima_manfaat->id,
                    'sub_kategori' => $item['sub_kategori'],
                    'jenis_porsi' => $jenisPorsi,
                    'waktu_batas_makan' => $item['waktu_batas_makan'] ?? '10:00',
                    'jumlah_laki_laki' => $l,
                    'jumlah_perempuan' => $p,
                    'total' => $l + $p,
                ]);
            }
            KelompokPenerimaManfaat::clearUnitCache($unitSppg->id);
        });

        return redirect()->route('penerima-manfaat.index')->with('success', 'Data Kelompok Penerima Manfaat berhasil diperbarui.');
    }

    /**
     * Remove the specified Kelompok Penerima Manfaat from storage.
     */
    public function destroy(Request $request, KelompokPenerimaManfaat $penerima_manfaat): RedirectResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        if (!$unitSppg || $penerima_manfaat->unit_sppg_id !== $unitSppg->id) {
            return redirect()->route('penerima-manfaat.index')->with('error', 'Akses tidak diizinkan.');
        }

        $penerima_manfaat->delete();
        KelompokPenerimaManfaat::clearUnitCache($unitSppg->id);

        return redirect()->route('penerima-manfaat.index')->with('success', 'Kelompok Penerima Manfaat berhasil dihapus.');
    }

    /**
     * Process, validate, and compute allergy totals per allergen item.
     */
    protected static function processAlergiData(?array $alergiList, $directPK = 0, $directPB = 0): array
    {
        $cleanedList = [];
        $totalPK = 0;
        $totalPB = 0;

        if (!empty($alergiList) && is_array($alergiList)) {
            foreach ($alergiList as $item) {
                if (is_array($item) && !empty($item['jenis_alergi'])) {
                    $pk = max(0, (int) ($item['porsi_kecil'] ?? 0));
                    $pb = max(0, (int) ($item['porsi_besar'] ?? 0));
                    $totalPK += $pk;
                    $totalPB += $pb;
                    $cleanedList[] = [
                        'jenis_alergi' => trim($item['jenis_alergi']),
                        'porsi_kecil' => $pk,
                        'porsi_besar' => $pb,
                    ];
                } elseif (is_string($item) && trim($item) !== '') {
                    $cleanedList[] = [
                        'jenis_alergi' => trim($item),
                        'porsi_kecil' => 0,
                        'porsi_besar' => 0,
                    ];
                }
            }
        }

        $finalPK = $totalPK > 0 ? $totalPK : max(0, (int) $directPK);
        $finalPB = $totalPB > 0 ? $totalPB : max(0, (int) $directPB);

        return [
            'keterangan_alergi' => array_values($cleanedList),
            'alergi_porsi_kecil' => $finalPK,
            'alergi_porsi_besar' => $finalPB,
        ];
    }

    /**
     * Sub-menu 2: Rekap Distribusi Penerima Manfaat (Gaya Presensi / Matriks Harian)
     */
    public function rekapDistribusi(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        // Format dropdown periode lengkap dengan rentang tanggalnya (identik Rekap Kehadiran)
        $periodes = Periode::orderBy('nomor_periode', 'asc')->get()->map(function ($p) {
            $mulai = $p->tanggal_mulai ? $p->tanggal_mulai->format('d M Y') : '-';
            $selesai = $p->tanggal_selesai ? $p->tanggal_selesai->format('d M Y') : '-';
            return [
                'id' => $p->id,
                'nomor_periode' => $p->nomor_periode,
                'tanggal_mulai' => $p->tanggal_mulai ? $p->tanggal_mulai->format('Y-m-d') : null,
                'tanggal_selesai' => $p->tanggal_selesai ? $p->tanggal_selesai->format('Y-m-d') : null,
                'status' => $p->status,
                'label' => "Periode {$p->nomor_periode} ({$mulai} – {$selesai})",
            ];
        });

        $tglMulai = $request->input('tanggal_mulai');
        $tglSelesai = $request->input('tanggal_selesai');
        $periodeId = $request->input('periode_id', 'all');
        $mode = $request->input('mode');
        $kategoriFilter = $request->input('kategori', 'all');
        $statusFilter = $request->input('status', 'all');
        $search = trim($request->input('search', ''));

        // Normalisasi mode jika ada format lama
        if ($mode === 'periodik') $mode = 'periode';
        if ($mode === 'bulanan' || $mode === 'custom') $mode = 'rentang';

        // 1. Jika mode eksplisit adalah 'hari_ini'
        if ($mode === 'hari_ini') {
            $startDate = Carbon::today();
            $endDate = Carbon::today();
            $periodeId = 'all';
        }
        // 2. Jika mode eksplisit adalah 'periode' dan periode_id valid
        elseif ($mode === 'periode' && $periodeId && $periodeId !== 'all') {
            $p = Periode::find($periodeId);
            if ($p && $p->tanggal_mulai && $p->tanggal_selesai) {
                $startDate = $p->tanggal_mulai->copy()->startOfDay();
                $endDate = $p->tanggal_selesai->copy()->startOfDay();
            }
        }
        // 3. Jika dikirim parameter tanggal_mulai dan tanggal_selesai (misal mode Rentang)
        elseif ($tglMulai && $tglSelesai) {
            $startDate = Carbon::parse($tglMulai)->startOfDay();
            $endDate = Carbon::parse($tglSelesai)->startOfDay();
            if (!$mode) {
                $mode = $startDate->eq($endDate) ? 'hari_ini' : 'rentang';
            }
        }
        // 4. Default saat pertama kali buka: Sesuai permintaan user, default dengan PERIODE bukan hari ini
        else {
            $defaultPeriode = Periode::orderBy('nomor_periode', 'desc')->orderBy('tanggal_mulai', 'desc')->first();

            if ($defaultPeriode && $defaultPeriode->tanggal_mulai && $defaultPeriode->tanggal_selesai) {
                $startDate = $defaultPeriode->tanggal_mulai->copy()->startOfDay();
                $endDate = $defaultPeriode->tanggal_selesai->copy()->startOfDay();
                $mode = 'periode';
                $periodeId = (string) $defaultPeriode->id;
            } else {
                $startDate = Carbon::today();
                $endDate = Carbon::today();
                $mode = 'hari_ini';
                $periodeId = 'all';
            }
        }

        // Pastikan endDate >= startDate
        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy();
        }

        // Sinkronisasi otomatis jika rentang tanggal cocok dengan salah satu periode
        if ((!$periodeId || $periodeId === 'all') && $startDate->ne($endDate)) {
            $matchedPeriode = Periode::whereDate('tanggal_mulai', $startDate->format('Y-m-d'))
                ->whereDate('tanggal_selesai', $endDate->format('Y-m-d'))
                ->first();
            if ($matchedPeriode) {
                $periodeId = (string) $matchedPeriode->id;
            }
        }

        $activePeriode = $periodes->firstWhere('id', (int) $periodeId) ?: ($periodes->firstWhere('status', 'aktif') ?: $periodes->first());

        $query = KelompokPenerimaManfaat::where('unit_sppg_id', $unitSppg ? $unitSppg->id : 1)->with('rincian');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_kelompok', 'like', "%{$search}%")
                    ->orWhere('kode_identitas', 'like', "%{$search}%")
                    ->orWhere('nama_pic', 'like', "%{$search}%")
                    ->orWhere('desa_kelurahan', 'like', "%{$search}%");
            });
        }

        if (!empty($kategoriFilter) && $kategoriFilter !== 'all') {
            if ($kategoriFilter === 'Sekolah') {
                $query->where('kategori', '!=', 'Posyandu');
            } elseif ($kategoriFilter === 'Posyandu') {
                $query->where('kategori', 'Posyandu');
            } else {
                $query->where('kategori', $kategoriFilter);
            }
        }

        $allKelompok = $query->orderBy('kategori', 'asc')->orderBy('nama_kelompok', 'asc')->get();

        // Hitung selisih hari
        $totalHari = (int) $startDate->diffInDays($endDate) + 1;

        // Ambil data Work Order yang dibuat dalam rentang tanggal beserta rincian kelompoknya
        $workOrders = WorkOrder::where('unit_sppg_id', $unitSppg ? $unitSppg->id : 1)
            ->whereBetween('tanggal_distribusi', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->with(['kelompoks'])
            ->get();

        $woByDateMap = [];
        foreach ($workOrders as $wo) {
            $tglKey = $wo->tanggal_distribusi instanceof Carbon
                ? $wo->tanggal_distribusi->format('Y-m-d')
                : substr((string)$wo->tanggal_distribusi, 0, 10);

            $subMenus = $wo->sub_menus ?: array_values(array_filter([
                $wo->sub_menu_1,
                $wo->sub_menu_2,
                $wo->sub_menu_3,
                $wo->sub_menu_4,
                $wo->sub_menu_5,
            ]));

            $woKelompoks = $wo->kelompoks->map(function ($wk) {
                return [
                    'id' => $wk->id,
                    'kelompok_id' => $wk->kelompok_id,
                    'nama_kelompok' => $wk->nama_kelompok,
                    'kategori' => $wk->kategori,
                    'is_menerima' => (bool) $wk->is_menerima,
                    'porsi_kecil' => (int) $wk->porsi_kecil,
                    'porsi_besar' => (int) $wk->porsi_besar,
                    'total_penerima' => (int) $wk->total_penerima,
                    'status_alergi' => (bool) $wk->status_alergi,
                    'detail_alergi' => $wk->detail_alergi ?: [],
                    'rincian' => $wk->rincian ?: [],
                ];
            });

            $woByDateMap[$tglKey] = [
                'id' => $wo->id,
                'uuid' => $wo->uuid,
                'nomor_wo' => $wo->nomor_wo,
                'nama_menu' => $wo->nama_menu,
                'status' => $wo->status,
                'siklus_ke' => (int) $wo->siklus_ke,
                'total_pm' => (int) $wo->total_pm,
                'total_pk' => (int) $wo->total_pk,
                'total_pb' => (int) $wo->total_pb,
                'total_alergi' => (int) $wo->total_alergi,
                'total_kelompok' => (int) $wo->total_kelompok,
                'sub_menus' => $subMenus,
                'kelompoks' => $woKelompoks,
                'total_kelompok_menerima' => $woKelompoks->where('is_menerima', true)->count(),
                'total_kelompok_libur' => $woKelompoks->where('is_menerima', false)->count(),
            ];
        }

        // Ambil data catatan distribusi yang tersimpan di database
        $distribusiRecords = DistribusiPenerimaManfaat::where('unit_sppg_id', $unitSppg ? $unitSppg->id : 1)
            ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $savedDistribusiMap = [];
        foreach ($distribusiRecords as $rec) {
            $tglStr = $rec->tanggal instanceof Carbon ? $rec->tanggal->format('Y-m-d') : substr((string)$rec->tanggal, 0, 10);
            $savedDistribusiMap[$rec->kelompok_id][$tglStr] = $rec->status;
        }

        // Bangun matrixDetailMap dan matrixDistribusiMap
        $matrixDistribusiMap = [];
        $matrixDetailMap = [];

        foreach ($allKelompok as $kpm) {
            $kId = $kpm->id;
            $matrixDetailMap[$kId] = [];
            $matrixDistribusiMap[$kId] = [];

            $curDate = $startDate->copy();
            while ($curDate->lte($endDate)) {
                $dateStr = $curDate->format('Y-m-d');
                $isSunday = $curDate->isSunday();

                $woInfo = $woByDateMap[$dateStr] ?? null;
                $wok = null;

                if ($woInfo && !empty($woInfo['kelompoks'])) {
                    $wok = collect($woInfo['kelompoks'])->first(function ($item) use ($kId, $kpm) {
                        return (int)$item['kelompok_id'] === (int)$kId || $item['nama_kelompok'] === $kpm->nama_kelompok;
                    });
                }

                // Tentukan status distribusi untuk tanggal ini:
                $savedStatus = $savedDistribusiMap[$kId][$dateStr] ?? null;

                if ($woInfo && $wok) {
                    if (!$wok['is_menerima']) {
                        $finalStatus = 'L'; // Libur / tidak menerima di WO
                    } else {
                        $woStatus = strtolower((string)$woInfo['status']);
                        if (in_array($woStatus, ['selesai', 'siap produksi', 'terkirim'])) {
                            $finalStatus = 'T'; // Terkirim / Siap Produksi
                        } elseif ($savedStatus !== null && $savedStatus !== '') {
                            $finalStatus = $savedStatus;
                        } else {
                            $finalStatus = 'P'; // Proses/Terjadwal
                        }
                    }
                } elseif ($savedStatus !== null && $savedStatus !== '') {
                    $finalStatus = $savedStatus;
                } elseif ($isSunday) {
                    $finalStatus = 'L';
                } else {
                    $finalStatus = '';
                }

                $matrixDistribusiMap[$kId][$dateStr] = $finalStatus;

                $matrixDetailMap[$kId][$dateStr] = [
                    'dateStr' => $dateStr,
                    'status' => $finalStatus,
                    'has_wo' => $woInfo !== null,
                    'wo_id' => $woInfo ? $woInfo['id'] : null,
                    'wo_uuid' => $woInfo ? $woInfo['uuid'] : null,
                    'nomor_wo' => $woInfo ? $woInfo['nomor_wo'] : null,
                    'nama_menu' => $woInfo ? $woInfo['nama_menu'] : null,
                    'wo_status' => $woInfo ? $woInfo['status'] : null,
                    'sub_menus' => $woInfo ? $woInfo['sub_menus'] : [],
                    'is_menerima' => $wok ? $wok['is_menerima'] : ($woInfo ? false : null),
                    'porsi_kecil' => $wok ? $wok['porsi_kecil'] : (int)$kpm->total_porsi_kecil,
                    'porsi_besar' => $wok ? $wok['porsi_besar'] : (int)$kpm->total_porsi_besar,
                    'total_penerima' => $wok ? $wok['total_penerima'] : (int)$kpm->total_penerima,
                    'status_alergi' => $wok ? $wok['status_alergi'] : false,
                    'detail_alergi' => $wok ? $wok['detail_alergi'] : [],
                    'rincian' => $wok ? $wok['rincian'] : ($kpm->rincian ?? []),
                ];

                $curDate->addDay();
            }
        }

        $distribusiList = $allKelompok->map(function ($kpm) use ($matrixDistribusiMap, $matrixDetailMap, $totalHari) {
            $kId = $kpm->id;
            $basePk = (int) $kpm->total_porsi_kecil;
            $basePb = (int) $kpm->total_porsi_besar;
            $baseTotal = (int) $kpm->total_penerima;
            $totalPorsiHarian = $basePk + $basePb;

            // Hitung akumulasi riil dari porsi WO pada tanggal-tanggal berstatus 'T'
            $kpmDates = $matrixDistribusiMap[$kId] ?? [];
            $kpmDetails = $matrixDetailMap[$kId] ?? [];

            $totalHariKirim = 0;
            $totalPkAkumulasi = 0;
            $totalPbAkumulasi = 0;
            $totalPorsiAkumulasi = 0;

            foreach ($kpmDates as $dStr => $st) {
                if ($st === 'T') {
                    $totalHariKirim++;
                    $det = $kpmDetails[$dStr] ?? null;
                    if ($det && $det['has_wo']) {
                        $totalPkAkumulasi += (int) $det['porsi_kecil'];
                        $totalPbAkumulasi += (int) $det['porsi_besar'];
                        $totalPorsiAkumulasi += (int) $det['total_penerima'];
                    } else {
                        $totalPkAkumulasi += $basePk;
                        $totalPbAkumulasi += $basePb;
                        $totalPorsiAkumulasi += $totalPorsiHarian;
                    }
                }
            }

            return [
                'id' => $kpm->id,
                'uid' => $kpm->uid,
                'nama_kelompok' => $kpm->nama_kelompok,
                'kategori' => $kpm->kategori,
                'jenis_kepemilikan' => $kpm->jenis_kepemilikan,
                'tipe_identitas' => $kpm->tipe_identitas,
                'kode_identitas' => $kpm->kode_identitas,
                'desa_kelurahan' => $kpm->desa_kelurahan,
                'kecamatan' => $kpm->kecamatan,
                'alamat_lengkap' => $kpm->alamat_lengkap,
                'nama_pic' => $kpm->nama_pic,
                'telepon_pic' => $kpm->telepon_pic,
                'nama_kepala' => $kpm->nama_kepala,
                'telepon_kepala' => $kpm->telepon_kepala,
                'total_laki_laki' => (int) $kpm->total_laki_laki,
                'total_perempuan' => (int) $kpm->total_perempuan,
                'total_penerima' => $baseTotal,
                'jumlah_kader' => (int) $kpm->jumlah_kader,
                'porsi_kecil_harian' => $basePk,
                'porsi_besar_harian' => $basePb,
                'total_porsi_harian' => $totalPorsiHarian,
                'total_hari_kirim' => $totalHariKirim,
                'total_pk_akumulasi' => $totalPkAkumulasi,
                'total_pb_akumulasi' => $totalPbAkumulasi,
                'total_porsi_akumulasi' => $totalPorsiAkumulasi,
                'status_distribusi' => $totalHariKirim > 0 ? 'Terkirim Sebagian' : 'Belum Ada Pengiriman',
                'persentase_layanan' => $totalHari > 0 ? round(($totalHariKirim / $totalHari) * 100) : 0,
                'history_dates' => $kpmDates,
                'history_details' => $kpmDetails,
            ];
        });

        $totalPorsiHarian = $distribusiList->sum('total_porsi_harian');
        $totalPorsiAkumulasi = $distribusiList->sum('total_porsi_akumulasi');
        $totalPenerimaJiwa = $distribusiList->sum('total_penerima');

        $summary = [
            'total_kelompok' => $distribusiList->count(),
            'total_sekolah' => $distribusiList->where('kategori', '!=', 'Posyandu')->count(),
            'total_posyandu' => $distribusiList->where('kategori', 'Posyandu')->count(),
            'total_penerima' => $totalPenerimaJiwa,
            'total_porsi_kecil_harian' => $distribusiList->sum('porsi_kecil_harian'),
            'total_porsi_besar_harian' => $distribusiList->sum('porsi_besar_harian'),
            'total_porsi_harian' => $totalPorsiHarian,
            'total_porsi_akumulasi' => $totalPorsiAkumulasi,
            'total_hari' => $totalHari,
            'persentase_layanan' => 100,
            'rata_rata_porsi_per_kelompok' => $distribusiList->count() > 0 ? round($totalPorsiHarian / $distribusiList->count()) : 0,
        ];

        return Inertia::render('PenerimaManfaat/RekapDistribusi', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'periodes' => $periodes,
            'activePeriode' => $activePeriode,
            'petugas' => Petugas::where('unit_sppg_id', $unitSppg ? $unitSppg->id : 1)->get(),
            'distribusiList' => $distribusiList,
            'summary' => $summary,
            'stats' => $summary,
            'initialMode' => $mode,
            'initialTanggalMulai' => $startDate->format('Y-m-d'),
            'initialTanggalSelesai' => $endDate->format('Y-m-d'),
            'initialPeriodeId' => $periodeId ?? 'all',
            'initialDistribusiMap' => $matrixDistribusiMap,
            'woByDateMap' => $woByDateMap,
            'matrixDetailMap' => $matrixDetailMap,
            'filters' => [
                'mode' => $mode,
                'tanggal_mulai' => $startDate->format('Y-m-d'),
                'tanggal_selesai' => $endDate->format('Y-m-d'),
                'periode_id' => $periodeId ?? 'all',
                'kategori' => $kategoriFilter,
                'status' => $statusFilter,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Menyimpan data rekap distribusi penerima manfaat harian ke database.
     */
    public function simpanDistribusi(Request $request): RedirectResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $matrix = [];
        if ($request->filled('json_data')) {
            $matrix = json_decode($request->input('json_data'), true) ?? [];
        }

        if (empty($matrix)) {
            return back()->with('error', 'Tidak ada data distribusi yang dikirim.');
        }

        $allowedStatuses = ['T', 'P', 'L'];
        $now = Carbon::now();
        $records = [];
        $deleteByKelompok = [];

        $kelompoks = KelompokPenerimaManfaat::where('unit_sppg_id', $unitSppg->id)->get()->keyBy('id');

        // Kumpulkan semua tanggal unik untuk mencari referensi Work Order yang relevan
        $allDates = [];
        foreach ($matrix as $dates) {
            if (is_array($dates)) {
                foreach (array_keys($dates) as $d) {
                    $allDates[$d] = true;
                }
            }
        }
        $datesList = array_keys($allDates);
        $wos = WorkOrder::where('unit_sppg_id', $unitSppg->id)
            ->whereIn('tanggal_distribusi', $datesList)
            ->with('kelompoks')
            ->get();
        $wosByDate = [];
        foreach ($wos as $wo) {
            $tKey = $wo->tanggal_distribusi instanceof Carbon ? $wo->tanggal_distribusi->format('Y-m-d') : substr((string)$wo->tanggal_distribusi, 0, 10);
            $wosByDate[$tKey] = $wo->kelompoks->keyBy('kelompok_id');
        }

        foreach ($matrix as $kelompokId => $dates) {
            if (!is_array($dates)) continue;
            $kpm = $kelompoks->get((int) $kelompokId);
            $basePk = $kpm ? (int) $kpm->total_porsi_kecil : 0;
            $basePb = $kpm ? (int) $kpm->total_porsi_besar : 0;
            $baseTotal = $basePk + $basePb;

            foreach ($dates as $tgl => $status) {
                if (in_array($status, $allowedStatuses)) {
                    $woKel = $wosByDate[$tgl][(int) $kelompokId] ?? null;
                    $pk = $woKel ? (int) $woKel->porsi_kecil : $basePk;
                    $pb = $woKel ? (int) $woKel->porsi_besar : $basePb;
                    $total = $woKel ? (int) $woKel->total_penerima : $baseTotal;

                    $records[] = [
                        'kelompok_id' => (int) $kelompokId,
                        'unit_sppg_id' => $unitSppg->id,
                        'tanggal' => $tgl,
                        'status' => $status,
                        'porsi_kecil' => $pk,
                        'porsi_besar' => $pb,
                        'total_porsi' => $total,
                        'keterangan' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                } elseif ($status === '' || $status === null || $status === '-') {
                    $deleteByKelompok[(int) $kelompokId][] = $tgl;
                }
            }
        }

        if (!empty($records)) {
            foreach (array_chunk($records, 500) as $chunk) {
                DistribusiPenerimaManfaat::upsert(
                    $chunk,
                    ['kelompok_id', 'tanggal'],
                    ['status', 'porsi_kecil', 'porsi_besar', 'total_porsi', 'updated_at']
                );
            }
        }

        if (!empty($deleteByKelompok)) {
            foreach ($deleteByKelompok as $kId => $tgls) {
                DistribusiPenerimaManfaat::where('unit_sppg_id', $unitSppg->id)
                    ->where('kelompok_id', $kId)
                    ->whereIn('tanggal', $tgls)
                    ->delete();
            }
        }

        return back()->with('success', 'Data rekap distribusi penerima manfaat berhasil disimpan.');
    }

    /**
     * Sub-menu 3: Pembayaran Insentif Penerima Manfaat (Tunai Langsung - Skema Resmi BGN)
     */
    public function pembayaranInsentif(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        // Format dropdown periode lengkap dengan rentang tanggalnya (identik Pembayaran Gaji)
        $periodes = Periode::orderBy('nomor_periode', 'asc')->get()->map(function ($p) {
            $mulai = $p->tanggal_mulai ? $p->tanggal_mulai->format('d M Y') : '-';
            $selesai = $p->tanggal_selesai ? $p->tanggal_selesai->format('d M Y') : '-';
            return [
                'id' => $p->id,
                'nomor_periode' => $p->nomor_periode,
                'tanggal_mulai' => $p->tanggal_mulai ? $p->tanggal_mulai->format('Y-m-d') : null,
                'tanggal_selesai' => $p->tanggal_selesai ? $p->tanggal_selesai->format('Y-m-d') : null,
                'label' => "Periode {$p->nomor_periode} ({$mulai} – {$selesai})",
            ];
        });
        $activePeriode = $periodes->firstWhere('status', 'aktif') ?: $periodes->first();

        $tglMulai = $request->input('tanggal_mulai');
        $tglSelesai = $request->input('tanggal_selesai');
        $periodeId = $request->input('periode_id');
        $mode = $request->input('mode') ?: $request->input('mode_skala');
        $search = trim($request->input('search', ''));
        $kategoriFilter = $request->input('kategori', 'all');
        $aturan = $request->input('aturan') ?: $request->input('aturan_insentif', 'aturan_2');
        if (!in_array($aturan, ['aturan_1', 'aturan_2'], true)) {
            $aturan = 'aturan_2';
        }

        $komponenSekolah = $request->input('komponen_sekolah') ?: $request->input('komponen', 'siswa');
        if (!in_array($komponenSekolah, ['semua', 'siswa', 'guru', 'tendik', 'guru_tendik'], true)) {
            $komponenSekolah = 'siswa';
        }

        // Normalisasi mode jika ada format lama
        if ($mode === 'periodik') $mode = 'periode';
        if ($mode === 'bulanan' || $mode === 'custom') $mode = 'rentang';

        // 1. Jika mode eksplisit adalah 'hari_ini'
        if ($mode === 'hari_ini') {
            $startDate = Carbon::today();
            $endDate = Carbon::today();
            $periodeId = 'all';
        }
        // 2. Jika mode eksplisit adalah 'periode' dan periodeId valid
        elseif ($mode === 'periode' && $periodeId && $periodeId !== 'all') {
            $p = Periode::find($periodeId);
            if ($p && $p->tanggal_mulai && $p->tanggal_selesai) {
                $startDate = $p->tanggal_mulai->copy()->startOfDay();
                $endDate = $p->tanggal_selesai->copy()->startOfDay();
            }
        }
        // 3. Jika dikirim parameter tanggal_mulai dan tanggal_selesai
        elseif ($tglMulai && $tglSelesai) {
            $startDate = Carbon::parse($tglMulai)->startOfDay();
            $endDate = Carbon::parse($tglSelesai)->startOfDay();
            if (!$mode) {
                $mode = $startDate->eq($endDate) ? 'hari_ini' : 'rentang';
            }
        }
        // 4. Default: Sesuai permintaan user, pembayaran insentif defaultnya HARI INI
        else {
            $startDate = Carbon::today();
            $endDate = Carbon::today();
            $mode = 'hari_ini';
            $periodeId = 'all';
        }

        // Pastikan endDate >= startDate (Mendukung 1 hari penuh jika startDate == endDate)
        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy();
        }

        // Sinkronisasi otomatis jika rentang tanggal cocok dengan salah satu periode
        if (!$periodeId || $periodeId === 'all') {
            $matchedPeriode = Periode::whereDate('tanggal_mulai', $startDate->format('Y-m-d'))
                ->whereDate('tanggal_selesai', $endDate->format('Y-m-d'))
                ->first();
            if ($matchedPeriode) {
                $periodeId = (string) $matchedPeriode->id;
            }
        }

        // Hitung jumlah hari operasional (Jika 1 hari, hasilnya 1)
        $hariOperasional = (int) $startDate->diffInDays($endDate) + 1;

        // Ambil data Work Order dalam rentang tanggal beserta rincian kelompoknya
        $workOrders = WorkOrder::where('unit_sppg_id', $unitSppg ? $unitSppg->id : 1)
            ->whereBetween('tanggal_distribusi', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->with(['kelompoks'])
            ->get();

        $woByDateMap = [];
        foreach ($workOrders as $wo) {
            $tglKey = $wo->tanggal_distribusi instanceof Carbon
                ? $wo->tanggal_distribusi->format('Y-m-d')
                : substr((string)$wo->tanggal_distribusi, 0, 10);

            $woKelompoks = $wo->kelompoks->map(function ($wk) {
                return [
                    'id' => $wk->id,
                    'kelompok_id' => $wk->kelompok_id,
                    'nama_kelompok' => $wk->nama_kelompok,
                    'kategori' => $wk->kategori,
                    'is_menerima' => (bool) $wk->is_menerima,
                    'porsi_kecil' => (int) $wk->porsi_kecil,
                    'porsi_besar' => (int) $wk->porsi_besar,
                    'total_penerima' => (int) $wk->total_penerima,
                    'rincian' => $wk->rincian,
                ];
            });

            $woByDateMap[$tglKey] = [
                'id' => $wo->id,
                'nomor_wo' => $wo->nomor_wo,
                'status' => $wo->status,
                'kelompoks' => $woKelompoks,
            ];
        }

        // Ambil data catatan distribusi yang tersimpan di database untuk rentang ini
        $distribusiRecords = DistribusiPenerimaManfaat::where('unit_sppg_id', $unitSppg ? $unitSppg->id : 1)
            ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $savedDistribusiMap = [];
        foreach ($distribusiRecords as $rec) {
            $tglStr = $rec->tanggal instanceof Carbon ? $rec->tanggal->format('Y-m-d') : substr((string)$rec->tanggal, 0, 10);
            $savedDistribusiMap[$rec->kelompok_id][$tglStr] = $rec->status;
        }

        $query = KelompokPenerimaManfaat::where('unit_sppg_id', $unitSppg ? $unitSppg->id : 1);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_kelompok', 'like', "%{$search}%")
                    ->orWhere('nama_pic', 'like', "%{$search}%")
                    ->orWhere('desa_kelurahan', 'like', "%{$search}%");
            });
        }

        if (!empty($kategoriFilter) && $kategoriFilter !== 'all') {
            if ($kategoriFilter === 'Sekolah') {
                $query->where('kategori', '!=', 'Posyandu');
            } elseif ($kategoriFilter === 'Posyandu') {
                $query->where('kategori', 'Posyandu');
            } else {
                $query->where('kategori', $kategoriFilter);
            }
        }

        $allKelompok = $query->with(['rincian'])->orderBy('kategori', 'asc')->orderBy('nama_kelompok', 'asc')->get();

        // Helper penghitung jumlah PM sekolah berdasarkan filter komponen (Semua, Siswa, Guru, Tendik, Guru & Tendik)
        $countPmByKomponen = function ($rincianItems, string $komponen): int {
            if (empty($rincianItems)) return 0;
            $sum = 0;
            foreach ($rincianItems as $r) {
                $sub = is_array($r) ? ($r['sub_kategori'] ?? '') : ($r->sub_kategori ?? '');
                $total = is_array($r) ? (int)($r['total'] ?? 0) : (int)($r->total ?? 0);
                $subLower = strtolower($sub);

                $isGuru = str_contains($subLower, 'guru');
                $isTendik = str_contains($subLower, 'tenaga kependidikan')
                    || str_contains($subLower, 'tendik')
                    || str_contains($subLower, 'satpam')
                    || (str_starts_with($sub, 'Pendukung') && !$isGuru);
                $isSiswa = !$isGuru && !$isTendik;

                if ($komponen === 'semua') {
                    $sum += $total;
                } elseif ($komponen === 'siswa' && $isSiswa) {
                    $sum += $total;
                } elseif ($komponen === 'guru' && $isGuru) {
                    $sum += $total;
                } elseif ($komponen === 'tendik' && $isTendik) {
                    $sum += $total;
                } elseif ($komponen === 'guru_tendik' && ($isGuru || $isTendik)) {
                    $sum += $total;
                }
            }
            return $sum;
        };

        // Helper mapper untuk kelompok individual (sekolah, dan posyandu versi lama)
        $mapKelompokItem = function ($kpm) use ($startDate, $endDate, $hariOperasional, $woByDateMap, $savedDistribusiMap, $mode, $komponenSekolah, $countPmByKomponen) {
            $kId = $kpm->id;
            $isPosyandu = $kpm->kategori === 'Posyandu';
            
            // Tentukan master PM sesuai pilihan komponen untuk sekolah
            if ($isPosyandu) {
                $masterPm = (int) $kpm->total_penerima;
            } else {
                if ($kpm->rincian && $kpm->rincian->isNotEmpty()) {
                    $masterPm = $countPmByKomponen($kpm->rincian, $komponenSekolah);
                } else {
                    $masterPm = ($komponenSekolah === 'semua' || $komponenSekolah === 'siswa') ? (int) $kpm->total_penerima : 0;
                }
            }

            $hariDistribusi = 0;
            $totalInsentif = 0;
            $totalPmAkumulasi = 0;
            $lastWoKpm = null;

            $curDate = $startDate->copy();
            while ($curDate->lte($endDate)) {
                $dateStr = $curDate->format('Y-m-d');

                $woInfo = $woByDateMap[$dateStr] ?? null;
                $wok = null;
                if ($woInfo && !empty($woInfo['kelompoks'])) {
                    $wok = collect($woInfo['kelompoks'])->first(function ($item) use ($kId, $kpm) {
                        return (int)$item['kelompok_id'] === (int)$kId || $item['nama_kelompok'] === $kpm->nama_kelompok;
                    });
                }

                if ($wok) {
                    $lastWoKpm = $wok;
                }

                // Tentukan status distribusi tanggal ini
                $savedStatus = $savedDistribusiMap[$kId][$dateStr] ?? null;
                $isT = false;

                if ($woInfo && $wok) {
                    if (!$wok['is_menerima'] || $wok['total_penerima'] <= 0) {
                        $isT = false;
                    } else {
                        $woStatus = strtolower((string)$woInfo['status']);
                        if (in_array($woStatus, ['selesai', 'siap produksi', 'terkirim'])) {
                            $isT = true;
                        } elseif ($savedStatus !== null && $savedStatus !== '') {
                            $isT = ($savedStatus === 'T');
                        } elseif ($mode === 'hari_ini') {
                            $isT = true;
                        }
                    }
                } elseif ($savedStatus !== null && $savedStatus !== '') {
                    $isT = ($savedStatus === 'T');
                }

                if ($isT) {
                    $hariDistribusi++;
                    if ($isPosyandu) {
                        $pmHariIni = ($wok && isset($wok['total_penerima'])) ? (int)$wok['total_penerima'] : $masterPm;
                    } else {
                        if ($wok && !empty($wok['rincian'])) {
                            $pmHariIni = $countPmByKomponen($wok['rincian'], $komponenSekolah);
                        } else {
                            $pmHariIni = $masterPm;
                        }
                    }
                    $totalPmAkumulasi += $pmHariIni;

                    if ($isPosyandu) {
                        $tarifHariIni = $pmHariIni * 1000;
                    } else {
                        if ($pmHariIni <= 0) {
                            $tarifHariIni = 0;
                        } elseif ($pmHariIni < 100) {
                            $tarifHariIni = 20000;
                        } elseif ($pmHariIni <= 500) {
                            $tarifHariIni = 30000;
                        } elseif ($pmHariIni <= 750) {
                            $tarifHariIni = 50000;
                        } elseif ($pmHariIni <= 1000) {
                            $tarifHariIni = 60000;
                        } elseif ($pmHariIni <= 2000) {
                            $tarifHariIni = 100000;
                        } else {
                            $tarifHariIni = 200000;
                        }
                    }
                    $totalInsentif += $tarifHariIni;
                }

                $curDate->addDay();
            }

            if ($hariDistribusi > 0) {
                $totalPm = (int) round($totalPmAkumulasi / $hariDistribusi);
                $tarifHarian = (int) round($totalInsentif / $hariDistribusi);
            } elseif ($lastWoKpm) {
                if ($isPosyandu) {
                    $totalPm = (int) ($lastWoKpm['total_penerima'] ?? $masterPm);
                    $tarifHarian = $totalPm * 1000;
                } else {
                    $totalPm = !empty($lastWoKpm['rincian'])
                        ? $countPmByKomponen($lastWoKpm['rincian'], $komponenSekolah)
                        : $masterPm;
                    if ($totalPm <= 0) $tarifHarian = 0;
                    elseif ($totalPm < 100) $tarifHarian = 20000;
                    elseif ($totalPm <= 500) $tarifHarian = 30000;
                    elseif ($totalPm <= 750) $tarifHarian = 50000;
                    elseif ($totalPm <= 1000) $tarifHarian = 60000;
                    elseif ($totalPm <= 2000) $tarifHarian = 100000;
                    else $tarifHarian = 200000;
                }
            } else {
                $totalPm = $masterPm;
                if ($isPosyandu) {
                    $tarifHarian = $totalPm * 1000;
                } else {
                    if ($totalPm <= 0) $tarifHarian = 0;
                    elseif ($totalPm < 100) $tarifHarian = 20000;
                    elseif ($totalPm <= 500) $tarifHarian = 30000;
                    elseif ($totalPm <= 750) $tarifHarian = 50000;
                    elseif ($totalPm <= 1000) $tarifHarian = 60000;
                    elseif ($totalPm <= 2000) $tarifHarian = 100000;
                    else $tarifHarian = 200000;
                }
            }

            if ($isPosyandu) {
                $skemaTier = "Aturan 1 (SK No. 401.1 Th 2025): Rp 1.000 / PM / hari";
                $tipePenerima = "Kader Posyandu (3B)";
                $deskripsiTarif = "{$totalPm} PM × Rp 1.000 / hari";
            } else {
                $komponenLabel = match ($komponenSekolah) {
                    'siswa' => 'Siswa',
                    'guru' => 'Guru',
                    'tendik' => 'Tendik',
                    'guru_tendik' => 'Guru & Tendik',
                    default => 'PM',
                };

                if ($totalPm <= 0) {
                    $skemaTier = "0 {$komponenLabel} (Rp 0/hari)";
                } elseif ($totalPm < 100) {
                    $skemaTier = "< 100 {$komponenLabel} (Tier 1)";
                } elseif ($totalPm <= 500) {
                    $skemaTier = "100 - 500 {$komponenLabel} (Tier 2)";
                } elseif ($totalPm <= 750) {
                    $skemaTier = "501 - 750 {$komponenLabel} (Tier 3)";
                } elseif ($totalPm <= 1000) {
                    $skemaTier = "751 - 1.000 {$komponenLabel} (Tier 4)";
                } elseif ($totalPm <= 2000) {
                    $skemaTier = "1.001 - 2.000 {$komponenLabel} (Tier 5)";
                } else {
                    $skemaTier = "2.001 - 3.000 {$komponenLabel} (Tier 6)";
                }
                $tipePenerima = "PJ Satuan Pendidikan (PIC)";
                $deskripsiTarif = "Rp " . number_format($tarifHarian, 0, ',', '.') . "/hari";
            }

            return [
                'id' => $kpm->id,
                'uid' => $kpm->uid,
                'nama_kelompok' => $kpm->nama_kelompok,
                'kategori' => $kpm->kategori,
                'tipe_penerima' => $tipePenerima,
                'nama_pic' => $kpm->nama_pic,
                'telepon_pic' => $kpm->telepon_pic,
                'email_pic' => $kpm->email_pic ?: '',
                'total_penerima' => $totalPm,
                'skema_tier' => $skemaTier,
                'deskripsi_tarif' => $deskripsiTarif,
                'tarif_harian' => $tarifHarian,
                'hari_operasional' => $hariDistribusi,
                'hari_rentang' => $hariOperasional,
                'hari_distribusi' => $hariDistribusi,
                'amount' => $totalInsentif,
                'metode_bayar' => 'Tunai Langsung',
                'desa_kelurahan' => $kpm->desa_kelurahan,
                'is_valid' => $totalInsentif > 0,
                'status_label' => $totalInsentif > 0 ? 'Siap Disalurkan (Tunai)' : 'Belum Ada Distribusi',
                'has_wo' => $lastWoKpm !== null,
                'is_wo_menerima' => $lastWoKpm ? $lastWoKpm['is_menerima'] : null,
                'breakdown_pm' => [
                    'semua' => $isPosyandu ? (int)$kpm->total_penerima : $countPmByKomponen($kpm->rincian, 'semua'),
                    'siswa' => $isPosyandu ? 0 : $countPmByKomponen($kpm->rincian, 'siswa'),
                    'guru' => $isPosyandu ? 0 : $countPmByKomponen($kpm->rincian, 'guru'),
                    'tendik' => $isPosyandu ? 0 : $countPmByKomponen($kpm->rincian, 'tendik'),
                    'guru_tendik' => $isPosyandu ? 0 : $countPmByKomponen($kpm->rincian, 'guru_tendik'),
                ],
                'komponen_sekolah' => $komponenSekolah,
            ];
        };

        // Pilihan Aturan Perhitungan:
        // Aturan 1: Sekolah (Tabel 2/3) + Posyandu individual Rp 1.000 / PM / hari (Lampiran 26)
        // Aturan 2: Sekolah (Tabel 2/3) + 3B Posyandu Kompulir se-SPPG Tabel 1 (Default / Terbaru)
        $isGlobal3bMode = ($aturan === 'aturan_2');

        if (!$isGlobal3bMode) {
            // Aturan 1: Semua sekolah dan masing-masing posyandu tampil individual dengan tarif Rp 1.000 / PM / hari
            $insentifList = $allKelompok->map($mapKelompokItem);
        } else {
            // Aturan 2:
            // 1. Satuan pendidikan (Sekolah) tetap individual
            $sekolahList = $allKelompok->filter(fn($k) => $k->kategori !== 'Posyandu')->map($mapKelompokItem);

            // 2. Kategori Posyandu 3B seluruhnya dikompulir/ditotal secara per SPPG menjadi 1 baris
            $posyanduList = collect();
            if ($kategoriFilter !== 'Sekolah') {
                $posyanduKelompoks = KelompokPenerimaManfaat::where('unit_sppg_id', $unitSppg ? $unitSppg->id : 1)
                    ->where('kategori', 'Posyandu')
                    ->get();

                $matchSearch = true;
                if (!empty($search)) {
                    $s = strtolower($search);
                    $matchSearch = str_contains('3b posyandu', $s)
                        || str_contains('posyandu', $s)
                        || str_contains('kader', $s)
                        || str_contains('tpk', $s)
                        || str_contains('3b', $s)
                        || $posyanduKelompoks->contains(function ($p) use ($s) {
                            return str_contains(strtolower($p->nama_kelompok), $s)
                                || str_contains(strtolower($p->nama_pic ?? ''), $s);
                        });
                }

                if ($matchSearch && $posyanduKelompoks->isNotEmpty()) {
                    $hariDistribusiGlobal = 0;
                    $totalInsentifGlobal = 0;
                    $totalPmAkumulasiGlobal = 0;

                    $posyanduStats = [];
                    foreach ($posyanduKelompoks as $pos) {
                        $posyanduStats[$pos->id] = [
                            'hari_distribusi' => 0,
                            'total_pm_akumulasi' => 0,
                            'last_pm' => (int)$pos->total_penerima,
                        ];
                    }

                    $curDate = $startDate->copy();
                    while ($curDate->lte($endDate)) {
                        $dateStr = $curDate->format('Y-m-d');
                        $woInfo = $woByDateMap[$dateStr] ?? null;

                        $dailyPm3b = 0;
                        $hasDistributionToday = false;

                        foreach ($posyanduKelompoks as $pos) {
                            $kId = $pos->id;
                            $savedStatus = $savedDistribusiMap[$kId][$dateStr] ?? null;
                            $wok = null;
                            if ($woInfo && !empty($woInfo['kelompoks'])) {
                                $wok = collect($woInfo['kelompoks'])->first(function ($item) use ($kId, $pos) {
                                    return (int)$item['kelompok_id'] === (int)$kId || $item['nama_kelompok'] === $pos->nama_kelompok;
                                });
                            }

                            $isT = false;
                            if ($woInfo && $wok) {
                                if ($wok['is_menerima'] && $wok['total_penerima'] > 0) {
                                    $woStatus = strtolower((string)$woInfo['status']);
                                    if (in_array($woStatus, ['selesai', 'siap produksi', 'terkirim'])) {
                                        $isT = true;
                                    } elseif ($savedStatus !== null && $savedStatus !== '') {
                                        $isT = ($savedStatus === 'T');
                                    } elseif ($mode === 'hari_ini') {
                                        $isT = true;
                                    }
                                }
                            } elseif ($savedStatus !== null && $savedStatus !== '') {
                                $isT = ($savedStatus === 'T');
                            }

                            if ($isT) {
                                $hasDistributionToday = true;
                                $pmPos = ($wok && isset($wok['total_penerima'])) ? (int)$wok['total_penerima'] : (int)$pos->total_penerima;
                                $dailyPm3b += $pmPos;
                                $posyanduStats[$pos->id]['hari_distribusi']++;
                                $posyanduStats[$pos->id]['total_pm_akumulasi'] += $pmPos;
                                $posyanduStats[$pos->id]['last_pm'] = $pmPos;
                            }
                        }

                        if ($hasDistributionToday) {
                            $hariDistribusiGlobal++;
                            $totalPmAkumulasiGlobal += $dailyPm3b;

                            // Skema Insentif Harian Penanggung Jawab 3B (Kader/TPK) per SPPG (Aturan 2 - SK No. 63551 Tahun 2026)
                            if ($dailyPm3b <= 0) $tarifHariIni = 0;
                            elseif ($dailyPm3b <= 60) $tarifHariIni = $dailyPm3b * 1000; // Tingkat Khusus: Rp 1.000 / PM
                            elseif ($dailyPm3b <= 100) $tarifHariIni = 60000; // Tingkat 1: Rp 60.000
                            elseif ($dailyPm3b <= 150) $tarifHariIni = $dailyPm3b * 1000; // Tingkat Khusus: Rp 1.000 / PM
                            elseif ($dailyPm3b <= 300) $tarifHariIni = 150000; // Tingkat 2: Rp 150.000
                            elseif ($dailyPm3b <= 500) $tarifHariIni = 250000; // Tingkat 3: Rp 250.000
                            elseif ($dailyPm3b <= 1000) $tarifHariIni = 400000; // Tingkat 4: Rp 400.000
                            elseif ($dailyPm3b <= 1500) $tarifHariIni = 550000; // Tingkat 5: Rp 550.000
                            else $tarifHariIni = 700000; // Tingkat 6: Rp 700.000

                            $totalInsentifGlobal += $tarifHariIni;
                        }

                        $curDate->addDay();
                    }

                    if ($hariDistribusiGlobal > 0) {
                        $totalPmGlobal = (int) round($totalPmAkumulasiGlobal / $hariDistribusiGlobal);
                        $tarifHarianGlobal = (int) round($totalInsentifGlobal / $hariDistribusiGlobal);
                    } else {
                        $totalPmGlobal = (int) $posyanduKelompoks->sum('total_penerima');
                        if ($totalPmGlobal <= 0) $tarifHarianGlobal = 0;
                        elseif ($totalPmGlobal <= 60) $tarifHarianGlobal = $totalPmGlobal * 1000;
                        elseif ($totalPmGlobal <= 100) $tarifHarianGlobal = 60000;
                        elseif ($totalPmGlobal <= 150) $tarifHarianGlobal = $totalPmGlobal * 1000;
                        elseif ($totalPmGlobal <= 300) $tarifHarianGlobal = 150000;
                        elseif ($totalPmGlobal <= 500) $tarifHarianGlobal = 250000;
                        elseif ($totalPmGlobal <= 1000) $tarifHarianGlobal = 400000;
                        elseif ($totalPmGlobal <= 1500) $tarifHarianGlobal = 550000;
                        else $tarifHarianGlobal = 700000;
                        $totalInsentifGlobal = 0;
                    }

                    // Tentukan Label Tingkat Skema 3B (SK No. 63551 Tahun 2026)
                    if ($totalPmGlobal <= 0) $skemaTier = "0 PM 3B (Tidak Ada Sasaran)";
                    elseif ($totalPmGlobal <= 60) $skemaTier = "1 - 60 PM 3B (Tingkat Khusus: Rp 1.000/PM)";
                    elseif ($totalPmGlobal <= 100) $skemaTier = "61 - 100 PM 3B (Tingkat 1: Rp 60.000/hari)";
                    elseif ($totalPmGlobal <= 150) $skemaTier = "101 - 150 PM 3B (Tingkat Khusus: Rp 1.000/PM)";
                    elseif ($totalPmGlobal <= 300) $skemaTier = "151 - 300 PM 3B (Tingkat 2: Rp 150.000/hari)";
                    elseif ($totalPmGlobal <= 500) $skemaTier = "301 - 500 PM 3B (Tingkat 3: Rp 250.000/hari)";
                    elseif ($totalPmGlobal <= 1000) $skemaTier = "501 - 1.000 PM 3B (Tingkat 4: Rp 400.000/hari)";
                    elseif ($totalPmGlobal <= 1500) $skemaTier = "1.001 - 1.500 PM 3B (Tingkat 5: Rp 550.000/hari)";
                    else $skemaTier = "> 1.500 PM 3B (Tingkat 6: Rp 700.000/hari)";

                    $deskripsiTarif = "Rp " . number_format($tarifHarianGlobal, 0, ',', '.') . "/hari";

                    // Rincian posyandu penyusun skema 3B
                    $countPos = $posyanduKelompoks->count();
                    $rincianPosyandu = [];
                    $allocatedSum = 0;

                    $sortedPosyandus = $posyanduKelompoks->sortByDesc('total_penerima')->values();
                    // Filter posyandu yang benar-benar ada catatan distribusi (hari_distribusi > 0)
                    $activePosyandus = $sortedPosyandus->filter(function ($pos) use ($posyanduStats) {
                        return (int)($posyanduStats[$pos->id]['hari_distribusi'] ?? 0) > 0;
                    })->values();

                    $totalPmActive = $activePosyandus->sum('total_penerima');
                    $activeCount = $activePosyandus->count();

                    foreach ($sortedPosyandus as $pos) {
                        $pStats = $posyanduStats[$pos->id] ?? [
                            'hari_distribusi' => 0,
                            'total_pm_akumulasi' => 0,
                            'last_pm' => (int)$pos->total_penerima
                        ];
                        $pDays = (int)($pStats['hari_distribusi'] ?? 0);
                        $pPm = (int)$pos->total_penerima;

                        if ($pDays > 0 && $totalInsentifGlobal > 0) {
                            $persentase = $totalPmActive > 0 ? ($pPm / $totalPmActive) * 100 : ($activeCount > 0 ? 100 / $activeCount : 0);
                            $activeIdx = $activePosyandus->search(fn($item) => $item->id === $pos->id);
                            if ($activeIdx === $activeCount - 1) {
                                $nominalProporsional = max(0, $totalInsentifGlobal - $allocatedSum);
                            } else {
                                $nominalProporsional = (int) round(($persentase / 100) * $totalInsentifGlobal);
                                $allocatedSum += $nominalProporsional;
                            }
                            $tarifHarianPos = $pDays > 0 ? (int) round($nominalProporsional / $pDays) : 0;
                        } else {
                            $persentase = 0;
                            $nominalProporsional = 0;
                            $tarifHarianPos = 0;
                        }

                        $rincianPosyandu[] = [
                            'id' => $pos->id,
                            'uid' => $pos->uid,
                            'parent_id' => 99999,
                            'nama_kelompok' => $pos->nama_kelompok,
                            'kategori' => 'Posyandu',
                            'tipe_penerima' => 'Kader Posyandu (3B)',
                            'nama_pic' => $pos->nama_pic ?: 'Kader Posyandu',
                            'telepon_pic' => $pos->telepon_pic ?: '',
                            'email_pic' => $pos->email_pic ?: '',
                            'desa_kelurahan' => $pos->desa_kelurahan ?: 'Tegallinggah',
                            'total_penerima' => $pPm,
                            'persentase_pm' => round($persentase, 1),
                            'hari_distribusi' => $pDays,
                            'hari_operasional' => $pDays,
                            'hari_rentang' => $hariOperasional,
                            'nominal_proporsional' => $nominalProporsional,
                            'nominal_rata' => $activeCount > 0 ? (int) floor($totalInsentifGlobal / $activeCount) : 0,
                            'amount' => $nominalProporsional,
                            'tarif_harian' => $tarifHarianPos,
                            'skema_tier' => 'Porsi ' . round($persentase, 1) . '% (' . $pPm . ' PM)',
                            'deskripsi_tarif' => 'Rp ' . number_format($tarifHarianPos, 0, ',', '.') . ' / hari',
                            'metode_bayar' => 'Tunai Langsung',
                            'is_valid' => $nominalProporsional > 0,
                            'status_label' => $nominalProporsional > 0 ? 'Siap Disalurkan (Tunai)' : 'Belum Ada Distribusi',
                        ];
                    }

                    $global3bRow = [
                        'id' => 99999,
                        'uid' => 'posyandu-3b',
                        'nama_kelompok' => '3B Posyandu',
                        'kategori' => 'Posyandu',
                        'tipe_penerima' => 'Kader Posyandu (3B)',
                        'nama_pic' => '',
                        'telepon_pic' => '',
                        'email_pic' => '',
                        'total_penerima' => $totalPmGlobal,
                        'skema_tier' => $skemaTier,
                        'deskripsi_tarif' => $deskripsiTarif,
                        'tarif_harian' => $tarifHarianGlobal,
                        'hari_operasional' => $hariDistribusiGlobal,
                        'hari_rentang' => $hariOperasional,
                        'hari_distribusi' => $hariDistribusiGlobal,
                        'amount' => $totalInsentifGlobal,
                        'metode_bayar' => 'Tunai Langsung',
                        'desa_kelurahan' => '',
                        'is_valid' => $totalInsentifGlobal > 0,
                        'status_label' => $totalInsentifGlobal > 0 ? 'Siap Disalurkan (Tunai)' : 'Belum Ada Distribusi',
                        'has_wo' => true,
                        'is_wo_menerima' => true,
                        'rincian_posyandu' => $rincianPosyandu,
                    ];

                    $posyanduList->push($global3bRow);
                }
            }

            $insentifList = $sekolahList->concat($posyanduList)->values();
        }

        $totalAnggaran = $insentifList->sum('amount');
        $insentifSekolah = $insentifList->where('kategori', '!=', 'Posyandu')->sum('amount');
        $insentifPosyandu = $insentifList->where('kategori', 'Posyandu')->sum('amount');

        $actualPosyanduCount = isset($posyanduKelompoks) && $posyanduKelompoks->isNotEmpty()
            ? $posyanduKelompoks->count()
            : $insentifList->where('kategori', 'Posyandu')->count();
        $actualSekolahCount = $insentifList->where('kategori', '!=', 'Posyandu')->count();
        $actualTotalTitik = $actualSekolahCount + $actualPosyanduCount;

        $summary = [
            'total_kelompok' => $actualTotalTitik,
            'total_rows' => $actualTotalTitik,
            'total_sekolah' => $actualSekolahCount,
            'total_posyandu' => $actualPosyanduCount,
            'total_penerima_jiwa' => $insentifList->sum('total_penerima'),
            'hari_operasional' => $hariOperasional,
            'total_anggaran' => $totalAnggaran,
            'insentif_sekolah' => $insentifSekolah,
            'insentif_posyandu' => $insentifPosyandu,
            'rata_rata_insentif' => $actualTotalTitik > 0 ? round($totalAnggaran / $actualTotalTitik) : 0,
        ];

        $distribusiCountMap = [];
        foreach ($insentifList as $item) {
            $distribusiCountMap[$item['id']] = $item['hari_distribusi'];
        }

        // Cek status transaksi pengajuan pembayaran insentif untuk rentang tanggal aktif
        $refTag = "[REF:INSENTIF-KPM:{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}]";
        $existingTrx = TransaksiKeuangan::where('unit_sppg_id', $unitSppg?->id)
            ->whereIn('kategori', ['Operasional', 'Insentif Fasilitas'])
            ->where('arus', 'Keluar')
            ->where('keterangan', 'like', "%{$refTag}%")
            ->latest('id')
            ->first();

        $transaksiPengajuan = $existingTrx ? [
            'id' => $existingTrx->id,
            'nomor_transaksi' => $existingTrx->nomor_transaksi,
            'status' => $existingTrx->status,
            'nominal' => (float) $existingTrx->nominal,
        ] : null;

        return Inertia::render('PenerimaManfaat/PembayaranInsentif', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'periodes' => $periodes,
            'activePeriode' => $activePeriode,
            'insentifList' => $insentifList,
            'distribusiCountMap' => $distribusiCountMap,
            'summary' => $summary,
            'stats' => $summary,
            'aturan' => $aturan,
            'komponenSekolah' => $komponenSekolah,
            'initialMode' => $mode,
            'initialTanggalMulai' => $startDate->format('Y-m-d'),
            'initialTanggalSelesai' => $endDate->format('Y-m-d'),
            'initialPeriodeId' => $periodeId ?? 'all',
            'transaksiPengajuan' => $transaksiPengajuan,
            'statusPengajuan' => $transaksiPengajuan['status'] ?? null,
            'filters' => [
                'mode' => $mode,
                'tanggal_mulai' => $startDate->format('Y-m-d'),
                'tanggal_selesai' => $endDate->format('Y-m-d'),
                'periode_id' => $periodeId ?? 'all',
                'kategori' => $kategoriFilter,
                'search' => $search,
                'aturan' => $aturan,
                'komponen_sekolah' => $komponenSekolah,
            ],
        ]);
    }

    /**
     * Generate BNI Direct Bulk Transfer CSV for Insentif PM
     */
    public function generateInsentifCsv(Request $request)
    {
        $validated = $request->validate([
            'rek_debet' => 'required|string|max:20',
            'tgl_transaksi' => 'required|string',
            'remark' => 'nullable|string|max:100',
            'remark1' => 'nullable|string|max:100',
            'remark2' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.rek_tujuan' => 'required|string',
            'items.*.nama' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.email' => 'nullable|string',
        ]);

        $rekDebet = preg_replace('/\D/', '', $validated['rek_debet']);
        $tglTransaksiRaw = $validated['tgl_transaksi'];
        $tglTransaksi = Carbon::parse($tglTransaksiRaw)->format('Ymd');

        $rawRemark = $validated['remark'] ?? $validated['remark1'] ?? null;
        if (!$rawRemark) {
            $tglIndo = Carbon::parse($tglTransaksiRaw)->format('d-m-Y');
            $rawRemark = "Insentif PM SPPG {$tglIndo}";
        }

        list($rem1, $rem2) = $this->splitBniRemark($rawRemark, $validated['remark2'] ?? null);

        $now = Carbon::now();
        $timestampCreation = $now->format('Y/m/d_H:i:s');
        $timestampFile = $now->format('Ymd_His');

        $items = $validated['items'];
        $totalRecords = count($items);
        $totalAmount = 0;
        foreach ($items as $it) {
            $totalAmount += (int) $it['amount'];
        }

        // Baris 1: Timestamp dan total baris (records + 2 baris header), diikuti 18 koma (total 20 kolom)
        $totalLinesInCsv = $totalRecords + 2;
        $line1 = $timestampCreation . ',' . $totalLinesInCsv . str_repeat(',', 18);

        // Baris 2: 'P', TglTransaksi, RekDebet, TotalRecord, TotalAmount, diikuti 15 koma (total 20 kolom)
        $line2 = 'P,' . $tglTransaksi . ',' . $rekDebet . ',' . $totalRecords . ',' . $totalAmount . str_repeat(',', 15);

        $csvLines = [$line1, $line2];

        // Baris 3+: Data Rows (20 Kolom)
        foreach ($items as $it) {
            $rekTujuan = preg_replace('/\D/', '', (string) ($it['rek_tujuan'] ?? ''));
            $namaClean = $this->sanitizeBniText($it['nama'] ?? '', 40);
            $amount = (int) ($it['amount'] ?? 0);
            $email = trim($it['email'] ?? '');
            $emailFlag = (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) ? 'Y' : 'N';

            $cols = [
                $rekTujuan,
                $namaClean,
                $amount,
                $rem1,
                $rem2,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                $emailFlag === 'Y' ? 'Y' : 'N',
                $emailFlag === 'Y' ? $email : '',
                '',
                'N',
            ];

            $csvLines[] = implode(',', $cols);
        }

        // BNI Direct mewajibkan pemisah baris CRLF (\r\n) dan diakhiri \r\n
        $csvContent = implode("\r\n", $csvLines) . "\r\n";
        $filename = "Uploadfile_IH_Insentif_PM_{$timestampFile}.csv";

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Membagi keterangan remark ke Remark1 (maks 33) dan Remark2 (maks 50) sesuai spesifikasi BNI Direct
     */
    private function splitBniRemark(string $fullRemark, ?string $explicitRem2 = null): array
    {
        $cleaned = $this->sanitizeBniText($fullRemark, 83);
        if ($explicitRem2 !== null && $explicitRem2 !== '') {
            return [
                $this->sanitizeBniText($cleaned, 33),
                $this->sanitizeBniText($explicitRem2, 50),
            ];
        }

        if (mb_strlen($cleaned) <= 33) {
            return [$cleaned, ''];
        }

        $slice33 = mb_substr($cleaned, 0, 33);
        $lastSpace = mb_strrpos($slice33, ' ');

        if ($lastSpace !== false && $lastSpace > 15) {
            $rem1 = trim(mb_substr($cleaned, 0, $lastSpace));
            $rem2 = trim(mb_substr($cleaned, $lastSpace + 1, 50));
            return [$rem1, $rem2];
        }

        $rem1 = trim(mb_substr($cleaned, 0, 33));
        $rem2 = trim(mb_substr($cleaned, 33, 50));
        return [$rem1, $rem2];
    }

    /**
     * Download template resmi Daftar Nominatif Pembayaran Relawan Harian (.xlsx) untuk Insentif Penerima Manfaat.
     * Mengisi sheet Daftar_Relawan dengan:
     * - Kolom B (Nama Relawan): Nama Sekolah / Nama Posyandu yang terdistribusi
     * - Kolom C (Peran): 'Penanggung Jawab Satuan Pendidikan' (untuk sekolah) atau 'Kader Posyandu' (untuk posyandu)
     * - Kolom D (Hari Kerja): Hari distribusi
     * - Kolom E (Besaran Harian): Tarif harian insentif
     * - Kolom F: Formula Jumlah Diterima
     * - Kolom G (Metode Bayar): 'Tunai'
     * - Kolom H (Tanggal Bayar): Tanggal transaksi/bayar
     * - Kolom I (Keterangan): Info tambahan (misal PIC: ...)
     * - Kolom J: Formula Status Baris
     */
    public function downloadInsentifRelawanHarian(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.nama_kelompok' => 'required|string',
            'items.*.kategori' => 'nullable|string',
            'items.*.peran' => 'nullable|string',
            'items.*.hari_distribusi' => 'nullable|numeric',
            'items.*.tarif_harian' => 'nullable|numeric',
            'items.*.amount' => 'nullable|numeric',
            'items.*.metode_bayar' => 'nullable|string',
            'items.*.tanggal_bayar' => 'nullable|string',
            'items.*.keterangan' => 'nullable|string',
            'items.*.nama_pic' => 'nullable|string',
            'tgl_transaksi' => 'nullable|string',
            'tanggal_mulai' => 'nullable|string',
            'tanggal_selesai' => 'nullable|string',
            'periode_label' => 'nullable|string',
        ]);

        $templatePath = database_path('data/Template_Daftar_Relawan_Harian.xlsx');
        if (!file_exists($templatePath)) {
            $templatePath = public_path('templates/Template_Daftar_Relawan_Harian.xlsx');
        }

        if (!file_exists($templatePath)) {
            return response()->json(['error' => 'Template file Template_Daftar_Relawan_Harian.xlsx tidak ditemukan.'], 404);
        }

        // Tanggal bayar pada template resmi adalah tanggal saat proses download dilakukan
        $tglSaatDownload = Carbon::now()->format('d-m-Y');

        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($templatePath);
            $sheet = $spreadsheet->getSheetByName('Daftar_Relawan');

            if (!$sheet) {
                return response()->json(['error' => 'Sheet Daftar_Relawan tidak ditemukan dalam template.'], 422);
            }

            $startRow = 9;
            // Filter tegas server side: HANYA yang benar-benar terdistribusi aktif (hari_distribusi > 0 dan amount > 0)
            $items = array_values(array_filter($validated['items'], function ($it) {
                $hari = (float)($it['hari_distribusi'] ?? 0);
                $amt = (float)($it['amount'] ?? 0);
                return $hari > 0 && $amt > 0;
            }));
            $count = count($items);

            if ($count === 0) {
                return response()->json(['error' => 'Tidak ada data relawan yang terdistribusi (hari distribusi > 0) untuk diunduh.'], 422);
            }

            for ($i = 0; $i < $count; $i++) {
                $item = $items[$i];
                $row = $startRow + $i;

                $namaKelompok = trim((string)($item['nama_kelompok'] ?? ''));
                $kategori = trim((string)($item['kategori'] ?? ''));
                $isPosyandu = strtolower($kategori) === 'posyandu' || str_contains(strtolower($namaKelompok), 'posyandu');

                // Khusus Pembayaran Insentif:
                // Posyandu -> 'Kader Posyandu'
                // Satuan Pendidikan / Sekolah -> 'Penanggung Jawab Satuan Pendidikan'
                $peran = $isPosyandu ? 'Kader Posyandu' : 'Penanggung Jawab Satuan Pendidikan';
                if (!empty($item['peran']) && in_array($item['peran'], ['Penanggung Jawab Satuan Pendidikan', 'Kader Posyandu'], true)) {
                    $peran = $item['peran'];
                }

                $hariKerja = (float)($item['hari_distribusi'] ?? 0);
                if ($hariKerja <= 0) {
                    continue;
                }

                $besaranHarian = (float)($item['tarif_harian'] ?? 0);
                if ($besaranHarian <= 0 && !empty($item['amount']) && $hariKerja > 0) {
                    $besaranHarian = round((float)$item['amount'] / $hariKerja);
                }

                // Sesuai permintaan, pembayaran insentif menggunakan metode Tunai
                $metodeBayar = 'Tunai';

                $pic = trim((string)($item['nama_pic'] ?? ''));
                $keterangan = trim((string)($item['keterangan'] ?? ''));
                if (empty($keterangan) && !empty($pic)) {
                    $keterangan = "PIC: {$pic}";
                }

                // Col A: No
                $sheet->setCellValue("A{$row}", $i + 1);
                // Col B: Nama Relawan (Nama Sekolah / Posyandu)
                $sheet->setCellValue("B{$row}", $namaKelompok);
                // Col C: Peran (Penanggung Jawab Satuan Pendidikan / Kader Posyandu)
                $sheet->setCellValue("C{$row}", $peran);
                // Col D: Hari Kerja (Hari Distribusi)
                $sheet->setCellValue("D{$row}", $hariKerja);
                // Col E: Besaran Harian (Rp)
                $sheet->setCellValue("E{$row}", $besaranHarian);
                // Col F: Formula Jumlah Diterima (Rp)
                $sheet->setCellValue("F{$row}", "=IF(OR(D{$row}=\"\",E{$row}=\"\"),\"\",D{$row}*E{$row})");
                // Col G: Metode Bayar (Tunai)
                $sheet->setCellValue("G{$row}", $metodeBayar);
                // Col H: Tanggal Bayar (tanggal saat download)
                $sheet->setCellValue("H{$row}", $tglSaatDownload);
                // Col I: Keterangan
                $sheet->setCellValue("I{$row}", $keterangan);
                // Col J: Formula Status Baris
                $sheet->setCellValue("J{$row}", "=IF(B{$row}=\"\",\"\",IF(COUNTIF(\$B\$9:\$B\$108,B{$row})>1,\"DUPLIKAT NAMA\",IF(OR(C{$row}=\"\",D{$row}=\"\",E{$row}=\"\",G{$row}=\"\",H{$row}=\"\"),\"BELUM LENGKAP\",\"OK\")))");
            }

            // Kosongkan sisa baris sampai baris 108 agar format bersih sesuai standar ERP
            for ($row = $startRow + $count; $row <= 108; $row++) {
                $sheet->setCellValue("A{$row}", $row - $startRow + 1);
                $sheet->setCellValue("B{$row}", null);
                $sheet->setCellValue("C{$row}", null);
                $sheet->setCellValue("D{$row}", null);
                $sheet->setCellValue("E{$row}", null);
                $sheet->setCellValue("F{$row}", null);
                $sheet->setCellValue("G{$row}", null);
                $sheet->setCellValue("H{$row}", null);
                $sheet->setCellValue("I{$row}", null);
                $sheet->setCellValue("J{$row}", null);
            }

            // Sinkronisasi baris total di sheet Rekap_Per_Peran
            $sheetRekap = $spreadsheet->getSheetByName('Rekap_Per_Peran');
            if ($sheetRekap) {
                $sheetRekap->setCellValue('D19', '=IF(D18=Daftar_Relawan!F110,"OK","PERIKSA: ada baris tanpa peran")');
            }

            // Pastikan sheet aktif saat file dibuka adalah Daftar_Relawan
            $spreadsheet->setActiveSheetIndexByName('Daftar_Relawan');

            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $tempFile = $tempDir . DIRECTORY_SEPARATOR . 'Daftar_Insentif_PIC_PM_' . uniqid() . '.xlsx';

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(true);
            $writer->save($tempFile);

            $formatDmy = function (?string $val): string {
                if (!$val) return Carbon::now()->format('d-m-Y');
                try {
                    return Carbon::parse($val)->format('d-m-Y');
                } catch (\Throwable $e) {
                    return $val;
                }
            };

            $mulai = $validated['tanggal_mulai'] ?? null;
            $selesai = $validated['tanggal_selesai'] ?? null;
            if ($mulai && $selesai && $mulai !== $selesai) {
                $dateLabel = "{$formatDmy($mulai)}_sd_{$formatDmy($selesai)}";
            } elseif ($mulai) {
                $dateLabel = $formatDmy($mulai);
            } elseif (!empty($validated['tgl_transaksi'])) {
                $dateLabel = $formatDmy($validated['tgl_transaksi']);
            } else {
                $dateLabel = Carbon::now()->format('d-m-Y');
            }
            $filename = "Payroll ERP Insentif PIC KPM {$dateLabel}.xlsx";

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            \Log::error('Error generate Payroll ERP Insentif PIC KPM: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => 'Gagal memproses file template Payroll ERP Insentif PIC KPM: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper membersihkan karakter terlarang sesuai sheet 'Restricted Characters' BNI Direct
     */
    private function sanitizeBniText(string $text, int $maxLen = 40): string
    {
        $restricted = [',', '`', '~', '!', '@', '#', '$', '%', '^', '&', '*', '_', '{', '}', '<', '>', '[', ']', '=', '\\', ';', '"', "'"];
        $cleaned = str_replace($restricted, ' ', $text);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = trim($cleaned);
        return mb_substr($cleaned, 0, $maxLen);
    }

    /**
     * Ajukan Transaksi Pembayaran Insentif Penanggung Jawab KPM ke Menu Keuangan (Status Ditunda).
     */
    public function ajukanPembayaranInsentif(Request $request)
    {
        $validated = $request->validate([
            'tanggal_transaksi' => ['required', 'date'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
            'periode_id' => ['nullable'],
            'periode_label' => ['nullable', 'string', 'max:255'],
            'nominal' => ['required', 'numeric', 'min:1'],
            'total_titik' => ['nullable', 'integer'],
            'total_pm' => ['nullable', 'integer'],
            'metode_bayar' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        $tglTransaksi = $validated['tanggal_transaksi'];
        $tglMulai = $validated['tanggal_mulai'] ?: $tglTransaksi;
        $tglSelesai = $validated['tanggal_selesai'] ?: $tglTransaksi;
        $label = $validated['periode_label'] ?: ($tglMulai === $tglSelesai ? Carbon::parse($tglTransaksi)->translatedFormat('d F Y') : Carbon::parse($tglMulai)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($tglSelesai)->translatedFormat('d M Y'));

        $namaTransaksi = "Pembayaran Insentif Penanggung Jawab KPM - " . $label;
        $refTag = "[REF:INSENTIF-KPM:{$tglMulai}_{$tglSelesai}]";

        // Cek apakah transaksi pengajuan insentif untuk rentang ini sudah pernah dibuat
        $existing = TransaksiKeuangan::where('unit_sppg_id', $unitSppg?->id)
            ->whereIn('kategori', ['Operasional', 'Insentif Fasilitas'])
            ->where('arus', 'Keluar')
            ->where('keterangan', 'like', "%{$refTag}%")
            ->first();

        $titikCount = $validated['total_titik'] ?? 0;
        $pmCount = $validated['total_pm'] ?? 0;
        $keteranganDetail = "Pengajuan otomatis Pembayaran Insentif Penanggung Jawab KPM (Satdik & Posyandu) ({$label}) sejumlah {$titikCount} titik terlayani ({$pmCount} PM). {$refTag}";

        if ($existing) {
            if ($existing->status === 'Disetujui') {
                return response()->json([
                    'status' => 'already_approved',
                    'message' => "Pengajuan pembayaran insentif untuk periode ini sudah pernah DISETUJUI pada Menu Transaksi ({$existing->nomor_transaksi}) dan tidak dapat diajukan kembali.",
                    'data' => $existing,
                ], 422);
            }

            if ($existing->status === 'Ditunda') {
                return response()->json([
                    'status' => 'already_pending',
                    'message' => "Pengajuan pembayaran insentif untuk periode ini sudah TELAH DIAJUKAN dengan status DITUNDA pada Menu Transaksi ({$existing->nomor_transaksi}). Pengajuan hanya bisa dilakukan 1 kali dan tidak boleh duplikasi.",
                    'data' => $existing,
                ], 422);
            }

            // Jika status sebelumnya Ditolak atau Dibatalkan, perbarui nominal dan datanya kembali ke status Ditunda (diajukan ulang)
            $existing->update([
                'tanggal' => $tglTransaksi,
                'tanggal_pesanan' => $tglTransaksi,
                'kategori' => 'Operasional',
                'nama_transaksi' => $namaTransaksi,
                'nominal' => $validated['nominal'],
                'volume' => $titikCount > 0 ? $titikCount : 1,
                'satuan' => 'Titik',
                'status' => 'Ditunda',
                'metode_bayar' => $validated['metode_bayar'] ?: 'Tunai',
                'keterangan' => $keteranganDetail,
            ]);

            return response()->json([
                'status' => 'updated',
                'message' => "Pengajuan ulang pembayaran insentif berhasil dikirim ke Menu Transaksi dengan status DITUNDA (Nomor: {$existing->nomor_transaksi}). Silakan disetujui di Menu Keuangan agar sah terhitung.",
                'data' => $existing,
            ]);
        }

        // Buat transaksi baru
        $tglStr = date('Ymd', strtotime($tglTransaksi));
        $countToday = TransaksiKeuangan::whereDate('created_at', now()->toDateString())->count() + 1;
        $nomorTrx = 'TRX-' . $tglStr . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

        $newTrx = TransaksiKeuangan::create([
            'unit_sppg_id' => $unitSppg?->id,
            'user_id' => $user->id,
            'nomor_transaksi' => $nomorTrx,
            'tanggal' => $tglTransaksi,
            'tanggal_pesanan' => $tglTransaksi,
            'kategori' => 'Operasional',
            'arus' => 'Keluar',
            'nama_transaksi' => $namaTransaksi,
            'volume' => $titikCount > 0 ? $titikCount : 1,
            'satuan' => 'Titik',
            'nominal' => $validated['nominal'],
            'status' => 'Ditunda',
            'perlu_npo' => false,
            'metode_bayar' => $validated['metode_bayar'] ?: 'Tunai',
            'keterangan' => $keteranganDetail,
        ]);

        return response()->json([
            'status' => 'created',
            'message' => "Pengajuan pembayaran insentif sebesar Rp " . number_format($validated['nominal'], 0, ',', '.') . " berhasil masuk ke Menu Transaksi dengan status DITUNDA (Nomor: {$newTrx->nomor_transaksi}). Silakan disetujui di Menu Keuangan agar sah terhitung.",
            'data' => $newTrx,
        ]);
    }
}
