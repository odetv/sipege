<?php

namespace App\Http\Controllers;

use App\Models\AbsensiPetugas;
use App\Models\Periode;
use App\Models\Petugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PetugasController extends Controller
{
    /**
     * Menampilkan daftar seluruh data petugas SPPG.
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        $petugas = [];
        $summary = [
            'total_petugas' => 0,
            'total_laki' => 0,
            'total_perempuan' => 0,
            'total_gaji_harian_bgn' => 0,
            'total_bonus_harian_mitra' => 0,
            'total_bpjs_tk' => 0,
            'total_pengeluaran_harian' => 0,
            'total_pengeluaran_periodik' => 0,
            'total_pengeluaran_bulanan' => 0,
        ];

        $daftarJabatan = [];

        if ($unitSppg) {
            $petugas = Petugas::where('unit_sppg_id', $unitSppg->id)
                ->orderBy('id', 'asc')
                ->get();

            $summary['total_petugas'] = $petugas->count();
            $summary['total_laki'] = $petugas->where('jenis_kelamin', 'L')->count();
            $summary['total_perempuan'] = $petugas->where('jenis_kelamin', 'P')->count();
            $summary['total_gaji_harian_bgn'] = (int) $petugas->sum('gaji_harian_bgn');
            $summary['total_bonus_harian_mitra'] = (int) $petugas->sum('bonus_harian_mitra');
            $summary['total_bpjs_tk'] = (int) $petugas->sum('iuran_bpjs_tk');
            $summary['total_pengeluaran_harian'] = $summary['total_gaji_harian_bgn'] + $summary['total_bonus_harian_mitra'];
            $summary['total_pengeluaran_periodik'] = $summary['total_pengeluaran_harian'] * 14; // 14 hari kerja operasional (periodik)
            $summary['total_pengeluaran_bulanan'] = $summary['total_pengeluaran_harian'] * 28; // 28 hari kerja operasional (bulanan)

            $daftarJabatan = $petugas->pluck('jabatan')->unique()->values()->all();
        }

        return Inertia::render('Petugas/Index', [
            'petugas' => $petugas,
            'summary' => $summary,
            'daftarJabatan' => $daftarJabatan,
            'unitSppg' => $unitSppg,
        ]);
    }

    /**
     * Menampilkan halaman Rekap Kehadiran Petugas SPPG.
     */
    /**
     * Menampilkan halaman Rekap Kehadiran Petugas SPPG.
     */
    public function rekapKehadiran(Request $request): Response
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        $petugas = [];
        $summary = [
            'total_petugas' => 0,
            'total_aktif' => 0,
            'total_nonaktif' => 0,
            'total_gaji_harian' => 0,
            'total_pengeluaran_periodik' => 0,
            'total_pengeluaran_bulanan' => 0,
        ];
        $daftarJabatan = [];
        $periodes = [];
        $presensiMap = [];

        // Mode: 'bulanan' (28 hari) atau 'periodik' (14 hari) atau 'custom'
        $mode = $request->input('mode', 'bulanan');
        $tglMulai = $request->input('tanggal_mulai');
        $tglSelesai = $request->input('tanggal_selesai');
        $periodeId = $request->input('periode_id');

        // Tentukan rentang tanggal
        if ($tglMulai && $tglSelesai) {
            $startDate = Carbon::parse($tglMulai)->startOfDay();
            $endDate = Carbon::parse($tglSelesai)->startOfDay();
        } elseif ($periodeId && $periodeId !== 'all') {
            $p = Periode::find($periodeId);
            if ($p && $p->tanggal_mulai && $p->tanggal_selesai) {
                $startDate = $p->tanggal_mulai->copy()->startOfDay();
                $endDate = $p->tanggal_selesai->copy()->startOfDay();
            } else {
                $startDate = Carbon::today();
                $endDate = $startDate->copy()->addDays($mode === 'periodik' ? 13 : 27);
            }
        } else {
            // Default rentang tanggal saat pertama kali dibuka adalah per hari ini (Carbon::today())
            $startDate = Carbon::today();
            if ($mode === 'periodik') {
                $endDate = $startDate->copy()->addDays(13); // 14 hari kerja
            } else {
                $endDate = $startDate->copy()->addDays(27); // 28 hari kerja (bulanan)
            }
        }

        // Pastikan endDate >= startDate
        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy()->addDays($mode === 'periodik' ? 13 : 27);
        }

        if ($unitSppg) {
            $petugas = Petugas::where('unit_sppg_id', $unitSppg->id)
                ->orderBy('id', 'asc')
                ->get();

            $totalHarian = (int) $petugas->where('status', 'Aktif')->sum(function ($p) {
                return (int) $p->gaji_harian_bgn + (int) $p->bonus_harian_mitra;
            });

            $summary['total_petugas'] = $petugas->count();
            $summary['total_aktif'] = $petugas->where('status', 'Aktif')->count();
            $summary['total_nonaktif'] = $petugas->where('status', 'Nonaktif')->count();
            $summary['total_gaji_harian'] = $totalHarian;
            $summary['total_pengeluaran_periodik'] = $totalHarian * 14; // 14 hari kerja operasional (periodik)
            $summary['total_pengeluaran_bulanan'] = $totalHarian * 28; // 28 hari kerja operasional (bulanan)

            $daftarJabatan = $petugas->pluck('jabatan')->unique()->values()->all();
            
            // Format dropdown periode lengkap dengan rentang tanggalnya
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

            // Load records presensi yang sudah ada di database untuk rentang ini
            $petugasIds = $petugas->pluck('id')->all();
            $presensiRecords = AbsensiPetugas::where('unit_sppg_id', $unitSppg->id)
                ->whereIn('petugas_id', $petugasIds)
                ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get();

            foreach ($presensiRecords as $rec) {
                $tglStr = $rec->tanggal instanceof Carbon ? $rec->tanggal->format('Y-m-d') : substr((string)$rec->tanggal, 0, 10);
                $presensiMap[$rec->petugas_id][$tglStr] = $rec->status;
            }
        }

        return Inertia::render('Petugas/RekapKehadiran', [
            'petugas' => $petugas,
            'summary' => $summary,
            'daftarJabatan' => $daftarJabatan,
            'periodes' => $periodes,
            'unitSppg' => $unitSppg,
            'initialMode' => $mode,
            'initialTanggalMulai' => $startDate->format('Y-m-d'),
            'initialTanggalSelesai' => $endDate->format('Y-m-d'),
            'initialPeriodeId' => $periodeId ?? 'all',
            'initialPresensiMap' => $presensiMap,
        ]);
    }

    /**
     * Menyimpan data presensi petugas harian ke database.
     */
    public function simpanPresensi(Request $request): RedirectResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        if (! $unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $matrix = [];
        if ($request->filled('json_data')) {
            $matrix = json_decode($request->input('json_data'), true) ?? [];
        } elseif ($request->has('items') && is_array($request->input('items'))) {
            foreach ($request->input('items') as $it) {
                if (isset($it['petugas_id'], $it['tanggal'], $it['status'])) {
                    $matrix[$it['petugas_id']][$it['tanggal']] = $it['status'];
                }
            }
        }

        if (empty($matrix)) {
            return back()->with('error', 'Tidak ada data presensi yang dikirim.');
        }

        $allowedStatuses = ['H', 'H2', 'L', 'I', 'S', 'TK'];
        $now = Carbon::now();
        $records = [];
        $deleteByPetugas = [];

        foreach ($matrix as $petugasId => $dates) {
            if (! is_array($dates)) {
                continue;
            }
            foreach ($dates as $tgl => $status) {
                if (in_array($status, $allowedStatuses)) {
                    $records[] = [
                        'petugas_id' => (int) $petugasId,
                        'unit_sppg_id' => $unitSppg->id,
                        'tanggal' => $tgl,
                        'status' => $status,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                } elseif ($status === '' || $status === null || $status === '-') {
                    $deleteByPetugas[(int) $petugasId][] = $tgl;
                }
            }
        }

        if (! empty($records)) {
            foreach (array_chunk($records, 500) as $chunk) {
                AbsensiPetugas::upsert(
                    $chunk,
                    ['petugas_id', 'tanggal'],
                    ['unit_sppg_id', 'status', 'updated_at']
                );
            }
        }

        if (! empty($deleteByPetugas)) {
            foreach ($deleteByPetugas as $pId => $tglList) {
                AbsensiPetugas::where('petugas_id', $pId)
                    ->whereIn('tanggal', $tglList)
                    ->delete();
            }
        }

        // Redirect dengan query params agar state tampilan tetap utuh
        $mode = $request->input('mode', 'bulanan');
        $tglMulai = $request->input('tanggal_mulai');
        $tglSelesai = $request->input('tanggal_selesai');
        $periodeId = $request->input('periode_id', 'all');

        $redirectParams = ['mode' => $mode];
        if ($tglMulai) $redirectParams['tanggal_mulai'] = $tglMulai;
        if ($tglSelesai) $redirectParams['tanggal_selesai'] = $tglSelesai;
        if ($periodeId && $periodeId !== 'all') $redirectParams['periode_id'] = $periodeId;

        return redirect()->route('petugas.rekap-kehadiran', $redirectParams)
            ->with('success', 'Data presensi petugas berhasil disimpan ke database.');
    }

    /**
     * Alias fallback simpanAbsensi
     */
    public function simpanAbsensi(Request $request): RedirectResponse
    {
        return $this->simpanPresensi($request);
    }

    /**
     * Menyimpan data petugas baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        if (! $unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $validated = $request->validate([
            'nama' => ['required', 'string', 'min:3', 'max:255'],
            'nik' => ['required', 'string', 'size:16', 'regex:/^[0-9]{16}$/', 'unique:petugas,nik'],
            'jenis_kelamin' => ['required', 'string', 'in:L,P'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],
            'alamat' => ['required', 'string'],
            'no_telp' => ['required', 'string', 'regex:/^62[0-9]{8,14}$/'],
            'email' => ['required', 'email', 'max:255'],
            'jabatan' => ['required', 'string', 'max:150'],
            'jam_kerja' => ['required', 'string', 'max:255'],
            'gaji_harian_bgn' => ['required', 'integer', 'min:0'],
            'bonus_harian_mitra' => ['required', 'integer', 'min:0'],
            'iuran_bpjs_tk' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:Aktif,Nonaktif'],
            'keterangan' => ['required', 'string', 'max:500'],
        ], [
            'nama.required' => 'Nama lengkap sesuai KTP wajib diisi.',
            'nama.min' => 'Nama lengkap minimal 3 karakter.',
            'nik.required' => 'NIK 16 digit wajib diisi.',
            'nik.size' => 'NIK harus tepat 16 digit angka.',
            'nik.regex' => 'NIK hanya boleh berisi 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar untuk petugas lain.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'tempat_lahir.required' => 'Tempat lahir wajib diisi.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'alamat.required' => 'Alamat lengkap sesuai KTP wajib diisi.',
            'no_telp.required' => 'Nomor WhatsApp / HP wajib diisi.',
            'no_telp.regex' => 'Nomor HP harus diawali dengan 62 dan hanya berupa angka (contoh: 6281234567890).',
            'email.required' => 'Email aktif wajib diisi.',
            'email.email' => 'Format email tidak valid (contoh: petugas@domain.com).',
            'jabatan.required' => 'Jabatan / Divisi petugas wajib dipilih.',
            'jam_kerja.required' => 'Jam kerja petugas wajib diisi (rentang jam mulai s.d jam selesai).',
            'gaji_harian_bgn.required' => 'Gaji harian BGN wajib diisi (hanya angka, bisa 0).',
            'bonus_harian_mitra.required' => 'Bonus harian mitra wajib diisi (hanya angka, bisa 0).',
            'iuran_bpjs_tk.required' => 'Iuran BPJS TK wajib diisi (hanya angka, bisa 0).',
            'status.required' => 'Status petugas wajib dipilih (Aktif atau Nonaktif).',
            'status.in' => 'Status petugas hanya boleh Aktif atau Nonaktif.',
            'keterangan.required' => 'Keterangan wajib diisi (isikan "-" jika tidak ada).',
        ]);

        $validated['unit_sppg_id'] = $unitSppg->id;

        Petugas::create($validated);

        return back()->with('success', 'Data Petugas berhasil ditambahkan.');
    }

    /**
     * Memperbarui data petugas.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        if (! $unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $petugas = Petugas::where('unit_sppg_id', $unitSppg->id)->findOrFail($id);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'min:3', 'max:255'],
            'nik' => ['required', 'string', 'size:16', 'regex:/^[0-9]{16}$/', Rule::unique('petugas', 'nik')->ignore($petugas->id)],
            'jenis_kelamin' => ['required', 'string', 'in:L,P'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],
            'alamat' => ['required', 'string'],
            'no_telp' => ['required', 'string', 'regex:/^62[0-9]{8,14}$/'],
            'email' => ['required', 'email', 'max:255'],
            'jabatan' => ['required', 'string', 'max:150'],
            'jam_kerja' => ['required', 'string', 'max:255'],
            'gaji_harian_bgn' => ['required', 'integer', 'min:0'],
            'bonus_harian_mitra' => ['required', 'integer', 'min:0'],
            'iuran_bpjs_tk' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:Aktif,Nonaktif'],
            'keterangan' => ['required', 'string', 'max:500'],
        ], [
            'nama.required' => 'Nama lengkap sesuai KTP wajib diisi.',
            'nama.min' => 'Nama lengkap minimal 3 karakter.',
            'nik.required' => 'NIK 16 digit wajib diisi.',
            'nik.size' => 'NIK harus tepat 16 digit angka.',
            'nik.regex' => 'NIK hanya boleh berisi 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar untuk petugas lain.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'tempat_lahir.required' => 'Tempat lahir wajib diisi.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'alamat.required' => 'Alamat lengkap sesuai KTP wajib diisi.',
            'no_telp.required' => 'Nomor WhatsApp / HP wajib diisi.',
            'no_telp.regex' => 'Nomor HP harus diawali dengan 62 dan hanya berupa angka (contoh: 6281234567890).',
            'email.required' => 'Email aktif wajib diisi.',
            'email.email' => 'Format email tidak valid (contoh: petugas@domain.com).',
            'jabatan.required' => 'Jabatan / Divisi petugas wajib dipilih.',
            'jam_kerja.required' => 'Jam kerja petugas wajib diisi (rentang jam mulai s.d jam selesai).',
            'gaji_harian_bgn.required' => 'Gaji harian BGN wajib diisi (hanya angka, bisa 0).',
            'bonus_harian_mitra.required' => 'Bonus harian mitra wajib diisi (hanya angka, bisa 0).',
            'iuran_bpjs_tk.required' => 'Iuran BPJS TK wajib diisi (hanya angka, bisa 0).',
            'status.required' => 'Status petugas wajib dipilih (Aktif atau Nonaktif).',
            'status.in' => 'Status petugas hanya boleh Aktif atau Nonaktif.',
            'keterangan.required' => 'Keterangan wajib diisi (isikan "-" jika tidak ada).',
        ]);

        $petugas->update($validated);

        return back()->with('success', 'Data Petugas berhasil diperbarui.');
    }

    /**
     * Menghapus data petugas.
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        if (! $unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $petugas = Petugas::where('unit_sppg_id', $unitSppg->id)->findOrFail($id);
        $nama = $petugas->nama;
        $petugas->delete();

        return back()->with('success', "Data Petugas \"{$nama}\" berhasil dihapus.");
    }
}
