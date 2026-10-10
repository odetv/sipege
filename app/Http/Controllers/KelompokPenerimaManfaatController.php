<?php

namespace App\Http\Controllers;

use App\Models\DistribusiPenerimaManfaat;
use App\Models\KelompokPenerimaManfaat;
use App\Models\Periode;
use App\Models\RincianPenerimaManfaat;
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

        // Ambil data catatan distribusi yang tersimpan di database
        $distribusiRecords = DistribusiPenerimaManfaat::where('unit_sppg_id', $unitSppg ? $unitSppg->id : 1)
            ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $matrixDistribusiMap = [];
        foreach ($distribusiRecords as $rec) {
            $tglStr = $rec->tanggal instanceof Carbon ? $rec->tanggal->format('Y-m-d') : substr((string)$rec->tanggal, 0, 10);
            $matrixDistribusiMap[$rec->kelompok_id][$tglStr] = $rec->status;
        }

        $distribusiList = $allKelompok->map(function ($kpm) use ($matrixDistribusiMap, $totalHari) {
            $kId = $kpm->id;
            $basePk = (int) $kpm->total_porsi_kecil;
            $basePb = (int) $kpm->total_porsi_besar;
            $baseTotal = (int) $kpm->total_penerima;
            $totalPorsiHarian = $basePk + $basePb;

            // Hitung hari kirim hanya dari yang berstatus 'T'
            $kpmDates = $matrixDistribusiMap[$kId] ?? [];
            $totalHariKirim = 0;
            foreach ($kpmDates as $st) {
                if ($st === 'T') {
                    $totalHariKirim++;
                }
            }

            $totalPorsiAkumulasi = $totalPorsiHarian * $totalHariKirim;
            $totalPkAkumulasi = $basePk * $totalHariKirim;
            $totalPbAkumulasi = $basePb * $totalHariKirim;

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
            'distribusiList' => $distribusiList,
            'summary' => $summary,
            'stats' => $summary,
            'initialMode' => $mode,
            'initialTanggalMulai' => $startDate->format('Y-m-d'),
            'initialTanggalSelesai' => $endDate->format('Y-m-d'),
            'initialPeriodeId' => $periodeId ?? 'all',
            'initialDistribusiMap' => $matrixDistribusiMap,
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

        foreach ($matrix as $kelompokId => $dates) {
            if (!is_array($dates)) continue;
            $kpm = $kelompoks->get((int) $kelompokId);
            $pk = $kpm ? (int) $kpm->total_porsi_kecil : 0;
            $pb = $kpm ? (int) $kpm->total_porsi_besar : 0;
            $total = $pk + $pb;

            foreach ($dates as $tgl => $status) {
                if (in_array($status, $allowedStatuses)) {
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
        $mode = $request->input('mode');
        $search = trim($request->input('search', ''));
        $kategoriFilter = $request->input('kategori', 'all');

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

        // Ambil data catatan distribusi yang tersimpan di database untuk rentang ini
        $distribusiRecords = DistribusiPenerimaManfaat::where('unit_sppg_id', $unitSppg ? $unitSppg->id : 1)
            ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $distribusiCountMap = [];
        foreach ($distribusiRecords as $rec) {
            if ($rec->status === 'T') {
                $distribusiCountMap[$rec->kelompok_id] = ($distribusiCountMap[$rec->kelompok_id] ?? 0) + 1;
            }
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

        $allKelompok = $query->orderBy('kategori', 'asc')->orderBy('nama_kelompok', 'asc')->get();

        $insentifList = $allKelompok->map(function ($kpm) use ($hariOperasional, $distribusiCountMap) {
            $isPosyandu = $kpm->kategori === 'Posyandu';
            $totalPm = (int) $kpm->total_penerima;

            if ($isPosyandu) {
                // Perhitungan Resmi Posyandu: Rata-rata Rp 1.000 / PM (Ibu Hamil, Menyusui, Balita) per hari operasional
                $ratePerPm = 1000;
                $tarifHarian = $totalPm * $ratePerPm;
                $skemaTier = "Posyandu: Rp 1.000 / PM / hari";
                $tipePenerima = "Kader Posyandu (Pendamping PM)";
                $deskripsiTarif = "{$totalPm} PM × Rp 1.000";
            } else {
                // Perhitungan Resmi Satuan Pendidikan (Tabel 3 BGN):
                // < 100 siswa: Rp 20.000
                // 100 s.d. 500 siswa: Rp 30.000
                // 501 s.d. 750 siswa: Rp 50.000
                // 751 s.d. 1.000: Rp 60.000
                // 1.001 s.d. 2000 siswa: Rp 100.000
                // 2.001 s.d. 3.000 siswa: Rp 200.000
                if ($totalPm < 100) {
                    $tarifHarian = 20000;
                    $skemaTier = "< 100 Siswa (Tier 1)";
                } elseif ($totalPm <= 500) {
                    $tarifHarian = 30000;
                    $skemaTier = "100 - 500 Siswa (Tier 2)";
                } elseif ($totalPm <= 750) {
                    $tarifHarian = 50000;
                    $skemaTier = "501 - 750 Siswa (Tier 3)";
                } elseif ($totalPm <= 1000) {
                    $tarifHarian = 60000;
                    $skemaTier = "751 - 1.000 Siswa (Tier 4)";
                } elseif ($totalPm <= 2000) {
                    $tarifHarian = 100000;
                    $skemaTier = "1.001 - 2.000 Siswa (Tier 5)";
                } else {
                    $tarifHarian = 200000;
                    $skemaTier = "2.001 - 3.000 Siswa (Tier 6)";
                }
                $tipePenerima = "PJ Satuan Pendidikan (PIC)";
                $deskripsiTarif = "Rp " . number_format($tarifHarian, 0, ',', '.') . "/hari";
            }

            // Sesuai permintaan user: hari kerja disesuaikan dengan rekap distribusi yang terdistribusikan
            $hariDistribusi = (int) ($distribusiCountMap[$kpm->id] ?? 0);
            $hariKerjaRiil = $hariDistribusi;
            $totalInsentif = $tarifHarian * $hariKerjaRiil;

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
                'hari_operasional' => $hariKerjaRiil,
                'hari_rentang' => $hariOperasional,
                'hari_distribusi' => $hariDistribusi,
                'amount' => $totalInsentif,
                'metode_bayar' => 'Tunai Langsung',
                'desa_kelurahan' => $kpm->desa_kelurahan,
                'is_valid' => $totalInsentif > 0,
                'status_label' => $totalInsentif > 0 ? 'Siap Disalurkan (Tunai)' : 'Belum Ada Distribusi',
            ];
        });

        $totalAnggaran = $insentifList->sum('amount');
        $insentifSekolah = $insentifList->where('kategori', '!=', 'Posyandu')->sum('amount');
        $insentifPosyandu = $insentifList->where('kategori', 'Posyandu')->sum('amount');

        $summary = [
            'total_kelompok' => $insentifList->count(),
            'total_rows' => $insentifList->count(),
            'total_sekolah' => $insentifList->where('kategori', '!=', 'Posyandu')->count(),
            'total_posyandu' => $insentifList->where('kategori', 'Posyandu')->count(),
            'total_penerima_jiwa' => $insentifList->sum('total_penerima'),
            'hari_operasional' => $hariOperasional,
            'total_anggaran' => $totalAnggaran,
            'insentif_sekolah' => $insentifSekolah,
            'insentif_posyandu' => $insentifPosyandu,
            'rata_rata_insentif' => $insentifList->count() > 0 ? round($totalAnggaran / $insentifList->count()) : 0,
        ];

        return Inertia::render('PenerimaManfaat/PembayaranInsentif', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'periodes' => $periodes,
            'activePeriode' => $activePeriode,
            'insentifList' => $insentifList,
            'distribusiCountMap' => $distribusiCountMap,
            'summary' => $summary,
            'stats' => $summary,
            'initialMode' => $mode,
            'initialTanggalMulai' => $startDate->format('Y-m-d'),
            'initialTanggalSelesai' => $endDate->format('Y-m-d'),
            'initialPeriodeId' => $periodeId ?? 'all',
            'filters' => [
                'mode' => $mode,
                'tanggal_mulai' => $startDate->format('Y-m-d'),
                'tanggal_selesai' => $endDate->format('Y-m-d'),
                'periode_id' => $periodeId ?? 'all',
                'kategori' => $kategoriFilter,
                'search' => $search,
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
}
