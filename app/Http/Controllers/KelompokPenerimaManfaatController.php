<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
use App\Models\RincianPenerimaManfaat;
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
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

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

        $query = KelompokPenerimaManfaat::with('rincian')
            ->where('unit_sppg_id', $unitSppg->id);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nama_kelompok', 'like', "%{$search}%")
                    ->orWhere('kode_identitas', 'like', "%{$search}%")
                    ->orWhere('nama_kepala_sekolah', 'like', "%{$search}%")
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

        $kelompokList = $query->latest()->get();

        // Calculate all-time summary stats for this unit SPPG
        $allKelompok = KelompokPenerimaManfaat::where('unit_sppg_id', $unitSppg->id)->get();
        $stats = [
            'total_kelompok' => $allKelompok->count(),
            'total_laki_laki' => (int) $allKelompok->sum('total_laki_laki'),
            'total_perempuan' => (int) $allKelompok->sum('total_perempuan'),
            'total_porsi_kecil' => (int) $allKelompok->sum('total_porsi_kecil'),
            'total_porsi_besar' => (int) $allKelompok->sum('total_porsi_besar'),
            'total_penerima' => (int) $allKelompok->sum('total_penerima'),
        ];

        return Inertia::render('PenerimaManfaat/Index', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
            'stats' => $stats,
            'filters' => $request->only(['search', 'kategori', 'jenis_kepemilikan']),
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
            return redirect()->route('dashboard')->with('error', 'Silakan lengkapi data Unit SPPG terlebih dahulu sebelum menambahkan Penerima Manfaat.');
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
            'kategori' => ['required', 'string', 'in:TK,RA,PAUD,SD,MI,SMP,MTs,SMA,SMK,MA,MAK,Posyandu'],
            'jenis_kepemilikan' => ['required', 'string', 'in:Negeri,Swasta'],
            'tipe_identitas' => ['required', 'string', 'in:NPSN,NSPP,NSM,NSNP,TPK,Lainnya'],
            'kode_identitas' => ['required', 'string', 'max:100'],
            'nama_kepala_sekolah' => ['required', 'string', 'max:255'],
            'email_kepala_sekolah' => ['required', 'email', 'max:255'],
            'telepon_kepala_sekolah' => ['required', 'string', 'regex:/^62[0-9]{8,15}$/'],
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
            'nama_kepala_sekolah.required' => 'Nama Kepala Sekolah / Pimpinan wajib diisi.',
            'email_kepala_sekolah.required' => 'Email Kepala Sekolah / Pimpinan wajib diisi.',
            'email_kepala_sekolah.email' => 'Format email Kepala Sekolah tidak valid.',
            'telepon_kepala_sekolah.required' => 'Nomor telepon Kepala Sekolah wajib diisi.',
            'telepon_kepala_sekolah.regex' => 'Format nomor telepon Kepala Sekolah harus diawali 62 (contoh: 6281234567890).',
            'nama_pic.required' => 'Nama PIC wajib diisi.',
            'email_pic.required' => 'Email PIC wajib diisi.',
            'email_pic.email' => 'Format email PIC tidak valid.',
            'telepon_pic.required' => 'Nomor telepon PIC wajib diisi.',
            'telepon_pic.regex' => 'Format nomor telepon PIC harus diawali 62 (contoh: 6281234567890).',
            'kode_pos.digits' => 'Kode pos harus 5 digit angka.',
            'alamat_lengkap.required' => 'Alamat lengkap wajib diisi.',
            'latitude.required' => 'Titik koordinat (Latitude) wajib ditentukan.',
            'longitude.required' => 'Titik koordinat (Longitude) wajib ditentukan.',
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

            $kelompok = KelompokPenerimaManfaat::create([
                'unit_sppg_id' => $unitSppg->id,
                'nama_kelompok' => $validated['nama_kelompok'],
                'kategori' => $validated['kategori'],
                'jenis_kepemilikan' => $validated['jenis_kepemilikan'],
                'tipe_identitas' => $validated['tipe_identitas'],
                'kode_identitas' => $validated['kode_identitas'],
                'nama_kepala_sekolah' => $validated['nama_kepala_sekolah'],
                'email_kepala_sekolah' => $validated['email_kepala_sekolah'],
                'telepon_kepala_sekolah' => $validated['telepon_kepala_sekolah'],
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
                'total_laki_laki' => $totalLakiLaki,
                'total_perempuan' => $totalPerempuan,
                'total_porsi_kecil' => $totalPorsiKecil,
                'total_porsi_besar' => $totalPorsiBesar,
                'total_penerima' => $totalPenerima,
            ]);

            foreach ($validated['rincian'] as $item) {
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
        });

        return redirect()->route('penerima-manfaat.index')->with('success', 'Kelompok Penerima Manfaat berhasil ditambahkan.');
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
            'kategori' => ['required', 'string', 'in:TK,RA,PAUD,SD,MI,SMP,MTs,SMA,SMK,MA,MAK,Posyandu'],
            'jenis_kepemilikan' => ['required', 'string', 'in:Negeri,Swasta'],
            'tipe_identitas' => ['required', 'string', 'in:NPSN,NSPP,NSM,NSNP,TPK,Lainnya'],
            'kode_identitas' => ['required', 'string', 'max:100'],
            'nama_kepala_sekolah' => ['required', 'string', 'max:255'],
            'email_kepala_sekolah' => ['required', 'email', 'max:255'],
            'telepon_kepala_sekolah' => ['required', 'string', 'regex:/^62[0-9]{8,15}$/'],
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
            'nama_kepala_sekolah.required' => 'Nama Kepala Sekolah / Pimpinan wajib diisi.',
            'email_kepala_sekolah.required' => 'Email Kepala Sekolah / Pimpinan wajib diisi.',
            'email_kepala_sekolah.email' => 'Format email Kepala Sekolah tidak valid.',
            'telepon_kepala_sekolah.required' => 'Nomor telepon Kepala Sekolah wajib diisi.',
            'telepon_kepala_sekolah.regex' => 'Format nomor telepon Kepala Sekolah harus diawali 62 (contoh: 6281234567890).',
            'nama_pic.required' => 'Nama PIC wajib diisi.',
            'email_pic.required' => 'Email PIC wajib diisi.',
            'email_pic.email' => 'Format email PIC tidak valid.',
            'telepon_pic.required' => 'Nomor telepon PIC wajib diisi.',
            'telepon_pic.regex' => 'Format nomor telepon PIC harus diawali 62 (contoh: 6281234567890).',
            'kode_pos.digits' => 'Kode pos harus 5 digit angka.',
            'alamat_lengkap.required' => 'Alamat lengkap wajib diisi.',
            'latitude.required' => 'Titik koordinat (Latitude) wajib ditentukan.',
            'longitude.required' => 'Titik koordinat (Longitude) wajib ditentukan.',
            'rincian.required' => 'Rincian jumlah penerima manfaat wajib diisi.',
        ]);

        DB::transaction(function () use ($validated, $penerima_manfaat) {
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

            $penerima_manfaat->update([
                'nama_kelompok' => $validated['nama_kelompok'],
                'kategori' => $validated['kategori'],
                'jenis_kepemilikan' => $validated['jenis_kepemilikan'],
                'tipe_identitas' => $validated['tipe_identitas'],
                'kode_identitas' => $validated['kode_identitas'],
                'nama_kepala_sekolah' => $validated['nama_kepala_sekolah'],
                'email_kepala_sekolah' => $validated['email_kepala_sekolah'],
                'telepon_kepala_sekolah' => $validated['telepon_kepala_sekolah'],
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
                'total_laki_laki' => $totalLakiLaki,
                'total_perempuan' => $totalPerempuan,
                'total_porsi_kecil' => $totalPorsiKecil,
                'total_porsi_besar' => $totalPorsiBesar,
                'total_penerima' => $totalPenerima,
            ]);

            // Sync rincian: delete old and recreate
            $penerima_manfaat->rincian()->delete();

            foreach ($validated['rincian'] as $item) {
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

        return redirect()->route('penerima-manfaat.index')->with('success', 'Kelompok Penerima Manfaat berhasil dihapus.');
    }
}
