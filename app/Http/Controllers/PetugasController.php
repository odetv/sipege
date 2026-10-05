<?php

namespace App\Http\Controllers;

use App\Models\AbsensiPetugas;
use App\Models\Periode;
use App\Models\Petugas;
use App\Models\UnitSppg;
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
            $summary['total_pengeluaran_bulanan_20'] = $summary['total_pengeluaran_harian'] * 20; // 20 hari kerja operasional standar BGN (excel)
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

        // Sinkronisasi otomatis: jika rentang tanggal cocok persis dengan salah satu periode di DB
        if (!$periodeId || $periodeId === 'all') {
            $matchedPeriode = Periode::whereDate('tanggal_mulai', $startDate->format('Y-m-d'))
                ->whereDate('tanggal_selesai', $endDate->format('Y-m-d'))
                ->first();
            if ($matchedPeriode) {
                $periodeId = $matchedPeriode->id;
            }
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
            'jenis_bank' => ['nullable', 'string', 'max:50'],
            'nomor_rekening' => ['nullable', 'string', 'max:50'],
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
        $validated['jenis_bank'] = $validated['jenis_bank'] ?? 'BNI';

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
            'jenis_bank' => ['nullable', 'string', 'max:50'],
            'nomor_rekening' => ['nullable', 'string', 'max:50'],
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

    /**
     * Halaman Pembayaran Gaji Petugas SPPG & Generator Payroll BNI Direct.
     */
    public function pembayaranGaji(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();

        $petugas = [];
        $daftarJabatan = [];
        $periodes = [];
        $presensiMap = [];
        $mandaysMap = [];

        $mode = $request->input('mode', 'presensi'); // 'presensi' | 'bgn_20' | 'periodik_14' | 'bulanan_28'
        $tglMulai = $request->input('tanggal_mulai');
        $tglSelesai = $request->input('tanggal_selesai');
        $periodeId = $request->input('periode_id');

        // Tentukan rentang tanggal dengan resolusi dua arah yang sinkron
        if ($periodeId && $periodeId !== 'all') {
            $p = Periode::find($periodeId);
            if ($p && $p->tanggal_mulai && $p->tanggal_selesai) {
                $startDate = $p->tanggal_mulai->copy()->startOfDay();
                $endDate = $p->tanggal_selesai->copy()->startOfDay();
            }
        }

        if (!isset($startDate) || !isset($endDate)) {
            if ($tglMulai && $tglSelesai) {
                $startDate = Carbon::parse($tglMulai)->startOfDay();
                $endDate = Carbon::parse($tglSelesai)->startOfDay();
            } else {
                // Saat pertama kali dibuka tanpa filter: hadapkan tanggal hari ini (Carbon::today())
                $startDate = Carbon::today();
                $endDate = Carbon::today();
            }
        }

        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy();
        }

        // Sinkronisasi otomatis: jika rentang tanggal cocok persis dengan salah satu periode di DB
        if (!$periodeId || $periodeId === 'all') {
            $matchedPeriode = Periode::whereDate('tanggal_mulai', $startDate->format('Y-m-d'))
                ->whereDate('tanggal_selesai', $endDate->format('Y-m-d'))
                ->first();
            if ($matchedPeriode) {
                $periodeId = $matchedPeriode->id;
            }
        }

        $summary = [
            'total_petugas' => 0,
            'total_aktif' => 0,
            'total_siap_bni' => 0,
            'total_rekening_kosong' => 0,
            'rek_debet_default' => '5268080021123800',
        ];

        if ($unitSppg) {
            $petugas = Petugas::where('unit_sppg_id', $unitSppg->id)
                ->orderBy('id', 'asc')
                ->get();

            $daftarJabatan = $petugas->pluck('jabatan')->unique()->values()->all();

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

            // Ambil data absensi
            $petugasIds = $petugas->pluck('id')->all();
            $presensiRecords = AbsensiPetugas::where('unit_sppg_id', $unitSppg->id)
                ->whereIn('petugas_id', $petugasIds)
                ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get();

            foreach ($presensiRecords as $rec) {
                $tglStr = $rec->tanggal instanceof Carbon ? $rec->tanggal->format('Y-m-d') : substr((string)$rec->tanggal, 0, 10);
                $presensiMap[$rec->petugas_id][$tglStr] = $rec->status;
            }

            // Hitung mandays per petugas
            foreach ($petugas as $p) {
                $pDates = $presensiMap[$p->id] ?? [];
                $mandays = 0;
                $hadirCount = 0;
                $setengahCount = 0;
                $liburCount = 0;
                $izinCount = 0;
                $sakitCount = 0;
                $tkCount = 0;

                foreach ($pDates as $st) {
                    if ($st === 'H') {
                        $mandays += 1.0;
                        $hadirCount++;
                    } elseif ($st === 'H2') {
                        $mandays += 0.5;
                        $setengahCount++;
                    } elseif ($st === 'L') {
                        $liburCount++;
                    } elseif ($st === 'I') {
                        $izinCount++;
                    } elseif ($st === 'S') {
                        $sakitCount++;
                    } elseif ($st === 'TK') {
                        $tkCount++;
                    }
                }

                $mandaysMap[$p->id] = [
                    'mandays' => $mandays,
                    'hadir' => $hadirCount,
                    'setengah' => $setengahCount,
                    'libur' => $liburCount,
                    'izin' => $izinCount,
                    'sakit' => $sakitCount,
                    'tk' => $tkCount,
                ];
            }

            $summary['total_petugas'] = $petugas->count();
            $summary['total_aktif'] = $petugas->where('status', 'Aktif')->count();
            $summary['total_siap_bni'] = $petugas->where('status', 'Aktif')->whereNotNull('nomor_rekening')->where('nomor_rekening', '!=', '')->count();
            $summary['total_rekening_kosong'] = $petugas->where('status', 'Aktif')->filter(fn($p) => empty($p->nomor_rekening))->count();
        }

        return Inertia::render('Petugas/PembayaranGaji', [
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
            'mandaysMap' => $mandaysMap,
        ]);
    }

    /**
     * Generate & Download file CSV format BNI Direct Inhouse Payroll.
     */
    public function generateBniDirectCsv(Request $request)
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
            $rawRemark = "Gaji Petugas SPPG {$tglIndo}";
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
        $filename = "Uploadfile_IH_{$timestampFile}.csv";

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Membagi keterangan remark ke Remark1 (maks 33) dan Remark2 (maks 50) sesuai spesifikasi BNI Direct Inhouse
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
        // Karakter terlarang BNI: , ` ~ ! @ # $ % ^ & * _ { } < > [ ] = \ ; " '
        $restricted = [',', '`', '~', '!', '@', '#', '$', '%', '^', '&', '*', '_', '{', '}', '<', '>', '[', ']', '=', '\\', ';', '"', "'"];
        $cleaned = str_replace($restricted, ' ', $text);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = trim($cleaned);
        return mb_substr($cleaned, 0, $maxLen);
    }

    /**
     * Download template resmi BNI Direct (.xls) asli dengan hanya mengisi data 3 kolom wajib (A: Rek. Tujuan, B: Nama Penerima, C: Amount)
     * tanpa mengubah struktur template, macro, maupun sheet lainnya.
     */
    public function downloadBniDirectXls(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.rek_tujuan' => 'nullable',
            'items.*.nama' => 'nullable',
            'items.*.amount' => 'nullable',
            'items.*.email' => 'nullable',
            'rek_debet' => 'nullable|string',
            'tgl_transaksi' => 'nullable|string',
            'remark' => 'nullable|string',
        ]);

        $templatePath = public_path('templates/BNIDIRECT-EXCEL_TEMPLATE_v1.9.6.xls');
        if (!file_exists($templatePath)) {
            $templatePath = database_path('data/BNIDIRECT-EXCEL_TEMPLATE_v1.9.6.xls');
        }

        if (!file_exists($templatePath)) {
            return response()->json(['error' => 'Template file BNIDIRECT-EXCEL_TEMPLATE_v1.9.6.xls tidak ditemukan.'], 404);
        }

        $rekDebet = preg_replace('/\D/', '', (string) ($validated['rek_debet'] ?? '5268080021123800'));
        if (empty($rekDebet)) $rekDebet = '5268080021123800';

        $tglTransaksiRaw = $validated['tgl_transaksi'] ?? null;
        $tglTransaksi = $tglTransaksiRaw ? Carbon::parse($tglTransaksiRaw)->format('Ymd') : Carbon::now()->format('Ymd');

        $rawRemark = $validated['remark'] ?? null;
        if (!$rawRemark) {
            $tglIndo = Carbon::now()->format('d-m-Y');
            $rawRemark = "Gaji Petugas SPPG {$tglIndo}";
        }
        list($rem1, $rem2) = $this->splitBniRemark($rawRemark);

        $now = Carbon::now();
        $timestampCreation = $now->format('Y/m/d_H:i:s');

        $totalRecords = count($validated['items']);
        $totalAmount = 0;
        foreach ($validated['items'] as $it) {
            $totalAmount += (float) ($it['amount'] ?? 0);
        }

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $tempFile = $tempDir . DIRECTORY_SEPARATOR . 'BNIDIRECT_' . uniqid() . '.xls';
        $jsonFile = $tempDir . DIRECTORY_SEPARATOR . 'BNI_DATA_' . uniqid() . '.json';

        $payload = [
            'timestamp_creation' => $timestampCreation,
            'tgl_transaksi' => $tglTransaksi,
            'rek_debet' => $rekDebet,
            'total_records' => $totalRecords,
            'total_amount' => $totalAmount,
            'remark1' => $rem1,
            'remark2' => $rem2,
            'items' => $validated['items'],
        ];

        file_put_contents($jsonFile, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $scriptPath = base_path('app/Scripts/populate_bni_template.py');
        $success = false;

        // Jalur 1: Menggunakan PHP COM jika class COM tersedia di environment proses PHP
        if (class_exists('\COM')) {
            $excel = null;
            $wb = null;
            try {
                copy($templatePath, $tempFile);
                $excel = new \COM("Excel.Application");
                $excel->Visible = false;
                $excel->DisplayAlerts = false;

                $wb = $excel->Workbooks->Open($tempFile);
                $ws = $wb->Sheets("Inhouse");

                // Update Header Metadata
                $ws->Range("A6")->Value = $timestampCreation;
                $ws->Range("A8")->Value = "P";
                $ws->Range("B8")->NumberFormat = "@";
                $ws->Range("B8")->Value = $tglTransaksi;
                $ws->Range("C8")->NumberFormat = "@";
                $ws->Range("C8")->Value = $rekDebet;
                $ws->Range("D8")->Value = (int) $totalRecords;
                $ws->Range("E8")->Value = (float) $totalAmount;

                $ws->Range("A10:T5000")->ClearContents();

                $row = 10;
                foreach ($validated['items'] as $item) {
                    $amount = (float) ($item['amount'] ?? 0);
                    if ($amount <= 0 && empty($item['rek_tujuan']) && empty($item['nama'])) continue;

                    $rek = preg_replace('/\D/', '', (string) ($item['rek_tujuan'] ?? ''));
                    $nama = $this->sanitizeBniText((string) ($item['nama'] ?? ''), 40);
                    $email = trim((string) ($item['email'] ?? ''));
                    $hasEmail = (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL));

                    // Col 1 (A): Rek. Tujuan(16)
                    $ws->Cells($row, 1)->NumberFormat = "@";
                    $ws->Cells($row, 1)->Value = $rek;

                    // Col 2 (B): Nama Penerima(40)
                    $ws->Cells($row, 2)->Value = $nama;

                    // Col 3 (C): Amount
                    $ws->Cells($row, 3)->Value = $amount;

                    // Col 4 (D): Remark1(33)
                    $ws->Cells($row, 4)->Value = $rem1;

                    // Col 5 (E): Remark2(50)
                    $ws->Cells($row, 5)->Value = $rem2;

                    // Col 17 (Q): EMAIL FLAG(1) & Col 18 (R): Email(100)
                    $ws->Cells($row, 17)->Value = $hasEmail ? "Y" : "N";
                    $ws->Cells($row, 18)->Value = $hasEmail ? $email : "";

                    // Col 20 (T): FLAG(1)
                    $ws->Cells($row, 20)->Value = "N";

                    $row++;
                }

                $wb->Save();
                $wb->Close(false);
                $wb = null;

                $excel->Quit();
                $excel = null;
                $success = true;
            } catch (\Throwable $e) {
                if ($wb !== null) {
                    try { $wb->Close(false); } catch (\Throwable $ex) {}
                }
                if ($excel !== null) {
                    try { $excel->Quit(); } catch (\Throwable $ex) {}
                }
            }
        }

        // Jalur 2: Jika PHP COM belum aktif / server belum di-restart, gunakan helper Python (win32com)
        if (!$success && file_exists($scriptPath)) {
            $cmd = 'python ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($templatePath) . ' ' . escapeshellarg($tempFile) . ' ' . escapeshellarg($jsonFile) . ' 2>&1';
            $output = [];
            $returnVar = 0;
            exec($cmd, $output, $returnVar);

            if ($returnVar === 0 && file_exists($tempFile)) {
                $success = true;
            }
        }

        if (file_exists($jsonFile)) {
            @unlink($jsonFile);
        }

        if (!$success || !file_exists($tempFile)) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
            return response()->json(['error' => 'Gagal memproses file template .xls. Pastikan Microsoft Excel tersedia di sistem.'], 500);
        }

        $filename = 'BNIDIRECT-EXCEL_TEMPLATE_v1.9.6.xls';

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
        ])->deleteFileAfterSend(true);
    }
}

