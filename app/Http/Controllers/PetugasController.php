<?php

namespace App\Http\Controllers;

use App\Models\AbsensiPetugas;
use App\Models\Periode;
use App\Models\Petugas;
use App\Models\TransaksiKeuangan;
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
            // Urutkan personil aktif terlebih dahulu sesuai urutan, lalu personil nonaktif di paling bawah
            $petugas = Petugas::where('unit_sppg_id', $unitSppg->id)
                ->orderByRaw("CASE WHEN status = 'Nonaktif' THEN 1 ELSE 0 END ASC")
                ->orderBy('urutan', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $petugasAktif = $petugas->where('status', 'Aktif');
            $petugasNonaktif = $petugas->where('status', '!=', 'Aktif');

            // Summary nominal rill hanya menghitung personil yang aktif
            $summary['total_petugas'] = $petugasAktif->count();
            $summary['total_petugas_all'] = $petugas->count();
            $summary['total_petugas_nonaktif'] = $petugasNonaktif->count();
            $summary['total_laki'] = $petugasAktif->where('jenis_kelamin', 'L')->count();
            $summary['total_perempuan'] = $petugasAktif->where('jenis_kelamin', 'P')->count();
            $summary['total_gaji_harian_bgn'] = (int) $petugasAktif->sum('gaji_harian_bgn');
            $summary['total_bonus_harian_mitra'] = (int) $petugasAktif->sum('bonus_harian_mitra');
            $summary['total_bpjs_tk'] = (int) $petugasAktif->sum('iuran_bpjs_tk');
            $summary['total_pengeluaran_harian'] = $summary['total_gaji_harian_bgn'] + $summary['total_bonus_harian_mitra'];
            $summary['total_pengeluaran_periodik'] = $summary['total_pengeluaran_harian'] * 10; // 10 hari kerja operasional (periodik)
            $summary['total_pengeluaran_bulanan_20'] = $summary['total_pengeluaran_harian'] * 20; // 20 hari kerja operasional standar BGN (bulanan)
            $summary['total_pengeluaran_bulanan'] = $summary['total_pengeluaran_bulanan_20'];

            $daftarJabatan = $this->extractDaftarJabatan($petugas);
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
    public function rekapKehadiran(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();

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
            // Default: gunakan periode terbaru jika hari ini dalam rentang periode tersebut,
            // bilamana periode terbaru tidak dalam rentang hari ini maka barulah gunakan hari ini.
            $latestPeriode = Periode::orderBy('nomor_periode', 'desc')->orderBy('tanggal_mulai', 'desc')->first();
            $today = Carbon::today();

            $targetPeriode = null;
            if (
                $latestPeriode &&
                $latestPeriode->tanggal_mulai &&
                $latestPeriode->tanggal_selesai &&
                $today->between($latestPeriode->tanggal_mulai->copy()->startOfDay(), $latestPeriode->tanggal_selesai->copy()->startOfDay())
            ) {
                $targetPeriode = $latestPeriode;
            } else {
                // Alternatif jika ada periode aktif yang menaungi hari ini
                $activePeriodeDb = Periode::whereDate('tanggal_mulai', '<=', $today->format('Y-m-d'))
                    ->whereDate('tanggal_selesai', '>=', $today->format('Y-m-d'))
                    ->orderBy('nomor_periode', 'desc')
                    ->first();
                if ($activePeriodeDb) {
                    $targetPeriode = $activePeriodeDb;
                }
            }

            if ($targetPeriode) {
                $startDate = $targetPeriode->tanggal_mulai->copy()->startOfDay();
                $endDate = $targetPeriode->tanggal_selesai->copy()->startOfDay();
                $periodeId = (string) $targetPeriode->id;
                $diffDays = $startDate->diffInDays($endDate) + 1;
                $mode = $diffDays <= 14 ? 'periodik' : ($diffDays <= 28 ? 'bulanan' : 'custom');
            } else {
                $startDate = Carbon::today();
                if ($mode === 'periodik') {
                    $endDate = $startDate->copy()->addDays(13); // 14 hari kerja
                } else {
                    $endDate = $startDate->copy()->addDays(27); // 28 hari kerja (bulanan)
                }
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
                $periodeId = (string) $matchedPeriode->id;
            }
        }

        if ($unitSppg) {
            $petugas = Petugas::where('unit_sppg_id', $unitSppg->id)
                ->orderBy('urutan', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $totalHarian = (int) $petugas->where('status', 'Aktif')->sum(function ($p) {
                return (int) $p->gaji_harian_bgn;
            });

            $summary['total_petugas'] = $petugas->count();
            $summary['total_aktif'] = $petugas->where('status', 'Aktif')->count();
            $summary['total_nonaktif'] = $petugas->where('status', 'Nonaktif')->count();
            $summary['total_gaji_harian'] = $totalHarian;
            $summary['total_pengeluaran_periodik'] = $totalHarian * 10; // 10 hari kerja operasional (periodik)
            $summary['total_pengeluaran_bulanan'] = $totalHarian * 20; // 20 hari kerja operasional (bulanan)

            $daftarJabatan = $this->extractDaftarJabatan($petugas);

            // Format dropdown periode lengkap dengan rentang tanggalnya
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

            // Load records presensi yang sudah ada di database untuk rentang ini
            $petugasIds = $petugas->pluck('id')->all();
            $presensiRecords = AbsensiPetugas::where('unit_sppg_id', $unitSppg->id)
                ->whereIn('petugas_id', $petugasIds)
                ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get();

            $presensiDetailsMap = [];
            foreach ($presensiRecords as $rec) {
                $tglStr = $rec->tanggal instanceof Carbon ? $rec->tanggal->format('Y-m-d') : substr((string)$rec->tanggal, 0, 10);
                $presensiMap[$rec->petugas_id][$tglStr] = $rec->status;
                $presensiDetailsMap[$rec->petugas_id][$tglStr] = [
                    'status' => $rec->status,
                    'nama' => $rec->nama,
                    'nik' => $rec->nik,
                    'jabatan' => $rec->jabatan,
                    'gaji_harian_bgn' => (int) ($rec->gaji_harian_bgn ?? 0),
                    'bonus_harian_mitra' => (int) ($rec->bonus_harian_mitra ?? 0),
                    'iuran_bpjs_tk' => (int) ($rec->iuran_bpjs_tk ?? 0),
                    'status_petugas' => $rec->status_petugas ?? 'Aktif',
                ];
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
            'initialPresensiDetailsMap' => $presensiDetailsMap ?? [],
        ]);
    }

    /**
     * Menampilkan halaman Rekap Bonus Petugas SPPG.
     */
    public function rekapBonus(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();

        $petugas = [];
        $summary = [
            'total_petugas' => 0,
            'total_aktif' => 0,
            'total_nonaktif' => 0,
            'total_bonus_harian' => 0,
            'total_pengeluaran_bonus_periodik' => 0,
            'total_pengeluaran_bonus_bulanan' => 0,
        ];
        $daftarJabatan = [];
        $periodes = [];
        $presensiMap = [];

        $mode = $request->input('mode', 'bulanan');
        $tglMulai = $request->input('tanggal_mulai');
        $tglSelesai = $request->input('tanggal_selesai');
        $periodeId = $request->input('periode_id');

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
            $latestPeriode = Periode::orderBy('nomor_periode', 'desc')->orderBy('tanggal_mulai', 'desc')->first();
            $today = Carbon::today();

            $targetPeriode = null;
            if (
                $latestPeriode &&
                $latestPeriode->tanggal_mulai &&
                $latestPeriode->tanggal_selesai &&
                $today->between($latestPeriode->tanggal_mulai->copy()->startOfDay(), $latestPeriode->tanggal_selesai->copy()->startOfDay())
            ) {
                $targetPeriode = $latestPeriode;
            } else {
                $activePeriodeDb = Periode::whereDate('tanggal_mulai', '<=', $today->format('Y-m-d'))
                    ->whereDate('tanggal_selesai', '>=', $today->format('Y-m-d'))
                    ->orderBy('nomor_periode', 'desc')
                    ->first();
                if ($activePeriodeDb) {
                    $targetPeriode = $activePeriodeDb;
                }
            }

            if ($targetPeriode) {
                $startDate = $targetPeriode->tanggal_mulai->copy()->startOfDay();
                $endDate = $targetPeriode->tanggal_selesai->copy()->startOfDay();
                $periodeId = (string) $targetPeriode->id;
                $diffDays = $startDate->diffInDays($endDate) + 1;
                $mode = $diffDays <= 14 ? 'periodik' : ($diffDays <= 28 ? 'bulanan' : 'custom');
            } else {
                $startDate = Carbon::today();
                if ($mode === 'periodik') {
                    $endDate = $startDate->copy()->addDays(13);
                } else {
                    $endDate = $startDate->copy()->addDays(27);
                }
            }
        }

        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy()->addDays($mode === 'periodik' ? 13 : 27);
        }

        if (!$periodeId || $periodeId === 'all') {
            $matchedPeriode = Periode::whereDate('tanggal_mulai', $startDate->format('Y-m-d'))
                ->whereDate('tanggal_selesai', $endDate->format('Y-m-d'))
                ->first();
            if ($matchedPeriode) {
                $periodeId = (string) $matchedPeriode->id;
            }
        }

        if ($unitSppg) {
            $petugas = Petugas::where('unit_sppg_id', $unitSppg->id)
                ->orderBy('urutan', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $totalBonusHarian = (int) $petugas->where('status', 'Aktif')->sum('bonus_harian_mitra');

            $summary['total_petugas'] = $petugas->count();
            $summary['total_aktif'] = $petugas->where('status', 'Aktif')->count();
            $summary['total_nonaktif'] = $petugas->where('status', 'Nonaktif')->count();
            $summary['total_bonus_harian'] = $totalBonusHarian;
            $summary['total_pengeluaran_bonus_periodik'] = $totalBonusHarian * 10;
            $summary['total_pengeluaran_bonus_bulanan'] = $totalBonusHarian * 20;

            $daftarJabatan = $this->extractDaftarJabatan($petugas);

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

            $petugasIds = $petugas->pluck('id')->all();
            $presensiRecords = AbsensiPetugas::where('unit_sppg_id', $unitSppg->id)
                ->whereIn('petugas_id', $petugasIds)
                ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get();

            $presensiDetailsMap = [];
            foreach ($presensiRecords as $rec) {
                $tglStr = $rec->tanggal instanceof Carbon ? $rec->tanggal->format('Y-m-d') : substr((string)$rec->tanggal, 0, 10);
                $presensiMap[$rec->petugas_id][$tglStr] = $rec->status;
                $presensiDetailsMap[$rec->petugas_id][$tglStr] = [
                    'status' => $rec->status,
                    'nama' => $rec->nama,
                    'nik' => $rec->nik,
                    'jabatan' => $rec->jabatan,
                    'gaji_harian_bgn' => (int) ($rec->gaji_harian_bgn ?? 0),
                    'bonus_harian_mitra' => (int) ($rec->bonus_harian_mitra ?? 0),
                    'iuran_bpjs_tk' => (int) ($rec->iuran_bpjs_tk ?? 0),
                    'status_petugas' => $rec->status_petugas ?? 'Aktif',
                ];
            }
        }

        return Inertia::render('Petugas/RekapBonus', [
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
            'initialPresensiDetailsMap' => $presensiDetailsMap ?? [],
        ]);
    }

    /**
     * Menampilkan halaman Rekap BPJS Ketenagakerjaan Petugas SPPG.
     */
    public function rekapBpjsTk(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();

        $petugas = [];
        $summary = [
            'total_petugas' => 0,
            'total_aktif' => 0,
            'total_terdaftar_bpjs' => 0,
            'total_iuran_bulanan' => 0,
            'total_iuran_periodik' => 0,
        ];
        $daftarJabatan = [];

        if ($unitSppg) {
            $petugas = Petugas::where('unit_sppg_id', $unitSppg->id)
                ->orderBy('urutan', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $summary['total_petugas'] = $petugas->count();
            $summary['total_aktif'] = $petugas->where('status', 'Aktif')->count();
            $summary['total_terdaftar_bpjs'] = $petugas->where('iuran_bpjs_tk', '>', 0)->count();
            $summary['total_iuran_bulanan'] = (int) $petugas->where('status', 'Aktif')->sum('iuran_bpjs_tk');
            $summary['total_iuran_periodik'] = (int) round($summary['total_iuran_bulanan'] / 2);

            $daftarJabatan = $this->extractDaftarJabatan($petugas);
        }

        return Inertia::render('Petugas/RekapBpjsTk', [
            'petugas' => $petugas,
            'summary' => $summary,
            'daftarJabatan' => $daftarJabatan,
            'unitSppg' => $unitSppg,
        ]);
    }

    /**
     * Update iuran BPJS TK (bisa serentak atau per personil).
     */
    public function updateIuranBpjsTk(Request $request): RedirectResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        if ($request->has('petugas_id') && $request->has('iuran_bpjs_tk')) {
            $p = Petugas::where('unit_sppg_id', $unitSppg->id)->find($request->input('petugas_id'));
            if ($p) {
                $p->update(['iuran_bpjs_tk' => max(0, (int) $request->input('iuran_bpjs_tk'))]);
            }
            return back()->with('success', 'Iuran BPJS TK petugas berhasil diperbarui.');
        }

        if ($request->has('nominal_serentak')) {
            $nominal = max(0, (int) $request->input('nominal_serentak'));
            Petugas::where('unit_sppg_id', $unitSppg->id)
                ->where('status', 'Aktif')
                ->update(['iuran_bpjs_tk' => $nominal]);
            return back()->with('success', 'Iuran BPJS TK seluruh personil aktif berhasil diatur serentak menjadi Rp ' . number_format($nominal, 0, ',', '.'));
        }

        return back()->with('error', 'Parameter tidak valid.');
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

        $allPetugas = Petugas::where('unit_sppg_id', $unitSppg->id)->get()->keyBy('id');
        $existingRecords = AbsensiPetugas::where('unit_sppg_id', $unitSppg->id)
            ->whereIn('petugas_id', array_keys($matrix))
            ->get()
            ->keyBy(function ($r) {
                $tStr = $r->tanggal instanceof Carbon ? $r->tanggal->format('Y-m-d') : substr((string)$r->tanggal, 0, 10);
                return $r->petugas_id . '_' . $tStr;
            });

        $todayStr = Carbon::today()->format('Y-m-d');
        $allowedStatuses = ['H', 'H2', 'L', 'I', 'S', 'TK'];
        $now = Carbon::now();
        $records = [];
        $deleteByPetugas = [];

        foreach ($matrix as $petugasId => $dates) {
            if (! is_array($dates)) {
                continue;
            }
            $p = $allPetugas->get((int) $petugasId);

            foreach ($dates as $tgl => $status) {
                if (in_array($status, $allowedStatuses)) {
                    $existKey = ((int) $petugasId) . '_' . $tgl;
                    $exist = $existingRecords->get($existKey);

                    // Jika catatan presensi sudah ada dan memiliki gaji_harian_bgn (atau snapshot), pertahankan nilai tersebut
                    // agar kustomisasi gaji harian maupun rekap historis masa lalu tidak tertimpa data master petugas.
                    if ($exist && $exist->gaji_harian_bgn !== null) {
                        $pNama = !empty($exist->nama) ? $exist->nama : ($p?->nama ?? '-');
                        $pNik = $exist->nik ?? ($p?->nik ?? '-');
                        $pJabatan = $exist->jabatan ?? ($p?->jabatan ?? '-');
                        $pGaji = (int) $exist->gaji_harian_bgn;
                        $pBonus = (int) ($exist->bonus_harian_mitra ?? ($p?->bonus_harian_mitra ?? 0));
                        $pBpjs = (int) ($exist->iuran_bpjs_tk ?? ($p?->iuran_bpjs_tk ?? 0));
                        $pStatus = $exist->status_petugas ?? ($p?->status ?? 'Aktif');
                        $pSnapshot = is_array($exist->snapshot_petugas) ? json_encode($exist->snapshot_petugas) : ($exist->snapshot_petugas ?? json_encode([
                            'nama' => $pNama,
                            'nik' => $pNik,
                            'jabatan' => $pJabatan,
                            'jam_kerja' => $p?->jam_kerja ?? '-',
                            'gaji_harian_bgn' => $pGaji,
                            'bonus_harian_mitra' => $pBonus,
                            'iuran_bpjs_tk' => $pBpjs,
                            'status' => $pStatus,
                        ]));
                    } else {
                        // Untuk hari ini, masa depan, atau yang belum punya snapshot: gunakan profil master petugas saat ini
                        $pNama = $p?->nama;
                        $pNik = $p?->nik;
                        $pJabatan = $p?->jabatan;
                        $pGaji = (int) ($p?->gaji_harian_bgn ?? 0);
                        $pBonus = (int) ($p?->bonus_harian_mitra ?? 0);
                        $pBpjs = (int) ($p?->iuran_bpjs_tk ?? 0);
                        $pStatus = $p?->status ?? 'Aktif';
                        $pSnapshot = json_encode([
                            'nama' => $pNama,
                            'nik' => $pNik,
                            'jabatan' => $pJabatan,
                            'jam_kerja' => $p?->jam_kerja ?? '-',
                            'gaji_harian_bgn' => $pGaji,
                            'bonus_harian_mitra' => $pBonus,
                            'iuran_bpjs_tk' => $pBpjs,
                            'status' => $pStatus,
                        ]);
                    }

                    $records[] = [
                        'petugas_id' => (int) $petugasId,
                        'unit_sppg_id' => $unitSppg->id,
                        'tanggal' => $tgl,
                        'status' => $status,
                        'nama' => $pNama,
                        'nik' => $pNik,
                        'jabatan' => $pJabatan,
                        'gaji_harian_bgn' => $pGaji,
                        'bonus_harian_mitra' => $pBonus,
                        'iuran_bpjs_tk' => $pBpjs,
                        'status_petugas' => $pStatus,
                        'snapshot_petugas' => $pSnapshot,
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
                    [
                        'unit_sppg_id',
                        'status',
                        'nama',
                        'nik',
                        'jabatan',
                        'gaji_harian_bgn',
                        'bonus_harian_mitra',
                        'iuran_bpjs_tk',
                        'status_petugas',
                        'snapshot_petugas',
                        'updated_at',
                    ]
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
     * Update nominal gaji harian BGN presensi per personil pada tanggal tertentu atau periode aktif.
     */
    public function updateGajiHarianPresensi(Request $request): RedirectResponse
    {
        $request->validate([
            'petugas_id' => 'required|exists:petugas,id',
            'tanggal' => 'required|date_format:Y-m-d',
            'gaji_harian_bgn' => 'required|numeric|min:0',
            'apply_to_period' => 'nullable|boolean',
            'tanggal_mulai' => 'nullable|date_format:Y-m-d',
            'tanggal_selesai' => 'nullable|date_format:Y-m-d',
            'status' => 'nullable|string',
        ]);

        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();

        if (! $unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $petugas = Petugas::where('unit_sppg_id', $unitSppg->id)->findOrFail($request->input('petugas_id'));
        $newGaji = max(0, (int) $request->input('gaji_harian_bgn'));
        $applyToPeriod = filter_var($request->input('apply_to_period', false), FILTER_VALIDATE_BOOLEAN);
        $targetTanggal = $request->input('tanggal');
        $inputStatus = $request->input('status');

        $targetDates = [$targetTanggal];

        if ($applyToPeriod && $request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $startDate = Carbon::parse($request->input('tanggal_mulai'))->startOfDay();
            $endDate = Carbon::parse($request->input('tanggal_selesai'))->startOfDay();
            if ($endDate->gte($startDate)) {
                $targetDates = [];
                $curr = $startDate->copy();
                while ($curr->lte($endDate)) {
                    $targetDates[] = $curr->format('Y-m-d');
                    $curr->addDay();
                }
            }
        }

        $existing = AbsensiPetugas::where('unit_sppg_id', $unitSppg->id)
            ->where('petugas_id', $petugas->id)
            ->whereIn('tanggal', $targetDates)
            ->get()
            ->keyBy(fn ($r) => $r->tanggal instanceof Carbon ? $r->tanggal->format('Y-m-d') : substr((string) $r->tanggal, 0, 10));

        foreach ($targetDates as $tgl) {
            $rec = $existing->get($tgl);
            if ($rec) {
                $snapshot = is_array($rec->snapshot_petugas) ? $rec->snapshot_petugas : (json_decode($rec->snapshot_petugas, true) ?? []);
                $snapshot['gaji_harian_bgn'] = $newGaji;
                $snapshot['nama'] = $rec->nama ?: $petugas->nama;
                $snapshot['jabatan'] = $rec->jabatan ?: $petugas->jabatan;

                $updateData = [
                    'gaji_harian_bgn' => $newGaji,
                    'snapshot_petugas' => $snapshot,
                ];
                if ($inputStatus && empty($rec->status)) {
                    $updateData['status'] = $inputStatus;
                }
                $rec->update($updateData);
            } else {
                $recStatus = ($tgl === $targetTanggal && $inputStatus) ? $inputStatus : 'H';
                $snapshot = [
                    'nama' => $petugas->nama,
                    'nik' => $petugas->nik,
                    'jabatan' => $petugas->jabatan,
                    'jam_kerja' => $petugas->jam_kerja ?? '-',
                    'gaji_harian_bgn' => $newGaji,
                    'bonus_harian_mitra' => (int) ($petugas->bonus_harian_mitra ?? 0),
                    'iuran_bpjs_tk' => (int) ($petugas->iuran_bpjs_tk ?? 0),
                    'status' => $petugas->status ?? 'Aktif',
                ];

                AbsensiPetugas::create([
                    'petugas_id' => $petugas->id,
                    'unit_sppg_id' => $unitSppg->id,
                    'tanggal' => $tgl,
                    'status' => $recStatus,
                    'nama' => $petugas->nama,
                    'nik' => $petugas->nik,
                    'jabatan' => $petugas->jabatan,
                    'gaji_harian_bgn' => $newGaji,
                    'bonus_harian_mitra' => (int) ($petugas->bonus_harian_mitra ?? 0),
                    'iuran_bpjs_tk' => (int) ($petugas->iuran_bpjs_tk ?? 0),
                    'status_petugas' => $petugas->status ?? 'Aktif',
                    'snapshot_petugas' => $snapshot,
                ]);
            }
        }

        $formattedNominal = 'Rp ' . number_format($newGaji, 0, ',', '.');
        $msg = $applyToPeriod
            ? "Gaji harian {$petugas->nama} berhasil diatur menjadi {$formattedNominal} untuk seluruh tanggal pada rentang periode ini."
            : "Gaji harian {$petugas->nama} pada tanggal " . Carbon::parse($targetTanggal)->translatedFormat('d F Y') . " berhasil diubah menjadi {$formattedNominal}.";

        return back()->with('success', $msg);
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

        // Standardisasi nomor telepon / WhatsApp agar selalu tersimpan sebagai 628xxxxxxxx
        if ($request->has('no_telp')) {
            $rawTelp = preg_replace('/\D/', '', (string) $request->input('no_telp'));
            if (str_starts_with($rawTelp, '0')) {
                $rawTelp = '62' . substr($rawTelp, 1);
            } elseif (!str_starts_with($rawTelp, '62') && !empty($rawTelp)) {
                $rawTelp = '62' . $rawTelp;
            }
            $request->merge(['no_telp' => $rawTelp]);
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
            'urutan' => ['nullable', 'integer', 'min:1'],
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
            'bonus_harian_mitra.required' => 'Bonus harian wajib diisi (hanya angka, bisa 0).',
            'iuran_bpjs_tk.required' => 'Iuran BPJS TK wajib diisi (hanya angka, bisa 0).',
            'status.required' => 'Status petugas wajib dipilih (Aktif atau Nonaktif).',
            'status.in' => 'Status petugas hanya boleh Aktif atau Nonaktif.',
            'keterangan.required' => 'Keterangan wajib diisi (isikan "-" jika tidak ada).',
        ]);

        $validated['unit_sppg_id'] = $unitSppg->id;
        $validated['jenis_bank'] = $validated['jenis_bank'] ?? 'BNI';
        if (empty($validated['urutan'])) {
            $validated['urutan'] = (Petugas::where('unit_sppg_id', $unitSppg->id)->max('urutan') ?? 0) + 1;
        }

        Petugas::create($validated);
        $this->reorderPetugasByStatus($unitSppg->id);

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

        // Standardisasi nomor telepon / WhatsApp agar selalu tersimpan sebagai 628xxxxxxxx
        if ($request->has('no_telp')) {
            $rawTelp = preg_replace('/\D/', '', (string) $request->input('no_telp'));
            if (str_starts_with($rawTelp, '0')) {
                $rawTelp = '62' . substr($rawTelp, 1);
            } elseif (!str_starts_with($rawTelp, '62') && !empty($rawTelp)) {
                $rawTelp = '62' . $rawTelp;
            }
            $request->merge(['no_telp' => $rawTelp]);
        }

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
            'urutan' => ['nullable', 'integer', 'min:1'],
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
            'bonus_harian_mitra.required' => 'Bonus harian wajib diisi (hanya angka, bisa 0).',
            'iuran_bpjs_tk.required' => 'Iuran BPJS TK wajib diisi (hanya angka, bisa 0).',
            'status.required' => 'Status petugas wajib dipilih (Aktif atau Nonaktif).',
            'status.in' => 'Status petugas hanya boleh Aktif atau Nonaktif.',
            'keterangan.required' => 'Keterangan wajib diisi (isikan "-" jika tidak ada).',
        ]);

        $petugas->update($validated);
        $this->reorderPetugasByStatus($unitSppg->id);

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
        $this->reorderPetugasByStatus($unitSppg->id);

        return back()->with('success', "Data Petugas \"{$nama}\" berhasil dihapus.");
    }

    /**
     * Otomatis rapikan urutan petugas: personil Aktif berada di urutan atas (1..N),
     * sedangkan personil Nonaktif otomatis dipindahkan ke urutan paling bawah.
     */
    private function reorderPetugasByStatus(int $unitSppgId): void
    {
        $all = Petugas::where('unit_sppg_id', $unitSppgId)
            ->orderByRaw("CASE WHEN status = 'Nonaktif' THEN 1 ELSE 0 END ASC")
            ->orderBy('urutan', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $urutan = 1;
        foreach ($all as $p) {
            if ($p->urutan !== $urutan) {
                $p->update(['urutan' => $urutan]);
            }
            $urutan++;
        }
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

        $mode = $request->input('mode', 'hari_ini');
        $filterMode = $request->input('filter_mode', $mode); // 'hari_ini' | 'periode' | 'rentang' | 'custom_dates'
        $tglMulai = $request->input('tanggal_mulai');
        $tglSelesai = $request->input('tanggal_selesai');
        $periodeId = $request->input('periode_id', 'all');
        $selectedDatesParam = $request->input('selected_dates');

        $selectedDatesList = [];
        if ($filterMode === 'custom_dates' || !empty($selectedDatesParam)) {
            if (is_array($selectedDatesParam)) {
                $rawList = $selectedDatesParam;
            } elseif (is_string($selectedDatesParam) && trim($selectedDatesParam) !== '') {
                $rawList = explode(',', $selectedDatesParam);
            } else {
                $rawList = [];
            }

            foreach ($rawList as $d) {
                $trimmed = trim($d);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed)) {
                    $selectedDatesList[] = $trimmed;
                }
            }
            $selectedDatesList = array_values(array_unique($selectedDatesList));
            sort($selectedDatesList);
        }

        if (!empty($selectedDatesList)) {
            $filterMode = 'custom_dates';
            $startDate = Carbon::parse($selectedDatesList[0])->startOfDay();
            $endDate = Carbon::parse(end($selectedDatesList))->startOfDay();
            $periodeId = 'all';
        } else {
            // Jika mode eksplisit adalah 'periode' dan ada periode_id valid
            if (($filterMode === 'periode' || $mode === 'periode') && $periodeId && $periodeId !== 'all') {
                $p = Periode::find($periodeId);
                if ($p && $p->tanggal_mulai && $p->tanggal_selesai) {
                    $startDate = $p->tanggal_mulai->copy()->startOfDay();
                    $endDate = $p->tanggal_selesai->copy()->startOfDay();
                }
            }

            // Jika ada parameter tanggal_mulai dan tanggal_selesai (misal mode Rentang atau Hari Ini)
            if (!isset($startDate) || !isset($endDate)) {
                if ($tglMulai && $tglSelesai) {
                    $startDate = Carbon::parse($tglMulai)->startOfDay();
                    $endDate = Carbon::parse($tglSelesai)->startOfDay();
                } else {
                    // Default sesuai permintaan user: Pembayaran gaji defaultnya hari ini
                    $startDate = Carbon::today();
                    $endDate = Carbon::today();
                    $filterMode = 'hari_ini';
                    $periodeId = 'all';
                }
            }

            if ($endDate->lt($startDate)) {
                $endDate = $startDate->copy();
            }

            // Sinkronisasi otomatis: jika rentang tanggal cocok persis dengan salah satu periode di DB
            if ((!$periodeId || $periodeId === 'all') && $startDate->ne($endDate)) {
                $matchedPeriode = Periode::whereDate('tanggal_mulai', $startDate->format('Y-m-d'))
                    ->whereDate('tanggal_selesai', $endDate->format('Y-m-d'))
                    ->first();
                if ($matchedPeriode) {
                    $periodeId = $matchedPeriode->id;
                }
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
                ->orderBy('urutan', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $daftarJabatan = $this->extractDaftarJabatan($petugas);

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
            $presensiQuery = AbsensiPetugas::where('unit_sppg_id', $unitSppg->id)
                ->whereIn('petugas_id', $petugasIds);

            if ($filterMode === 'custom_dates' && !empty($selectedDatesList)) {
                $presensiRecords = $presensiQuery->whereIn('tanggal', $selectedDatesList)->get();
            } else {
                $presensiRecords = $presensiQuery->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->get();
            }

            $presensiDetailsMap = [];
            foreach ($presensiRecords as $rec) {
                $tglStr = $rec->tanggal instanceof Carbon ? $rec->tanggal->format('Y-m-d') : substr((string)$rec->tanggal, 0, 10);
                $presensiMap[$rec->petugas_id][$tglStr] = $rec->status;
                $presensiDetailsMap[$rec->petugas_id][$tglStr] = [
                    'status' => $rec->status,
                    'nama' => $rec->nama,
                    'nik' => $rec->nik,
                    'jabatan' => $rec->jabatan,
                    'gaji_harian_bgn' => (int) ($rec->gaji_harian_bgn ?? 0),
                    'bonus_harian_mitra' => (int) ($rec->bonus_harian_mitra ?? 0),
                    'iuran_bpjs_tk' => (int) ($rec->iuran_bpjs_tk ?? 0),
                    'status_petugas' => $rec->status_petugas ?? 'Aktif',
                ];
            }

            // Hitung mandays dan total gaji harian presensi per petugas berbasis snapshot per hari
            foreach ($petugas as $p) {
                $pDates = $presensiMap[$p->id] ?? [];
                $mandays = 0;
                $hadirCount = 0;
                $setengahCount = 0;
                $liburCount = 0;
                $izinCount = 0;
                $sakitCount = 0;
                $tkCount = 0;
                $totalGajiPresensi = 0;
                $totalBonusPresensi = 0;

                foreach ($pDates as $tglStr => $st) {
                    $snap = $presensiDetailsMap[$p->id][$tglStr] ?? null;
                    $dailyGaji = ($snap && isset($snap['gaji_harian_bgn']) && $snap['gaji_harian_bgn'] !== null)
                        ? (int) $snap['gaji_harian_bgn']
                        : (int) $p->gaji_harian_bgn;
                    $dailyBonus = ($snap && isset($snap['bonus_harian_mitra']) && $snap['bonus_harian_mitra'] !== null)
                        ? (int) $snap['bonus_harian_mitra']
                        : (int) $p->bonus_harian_mitra;

                    if ($st === 'H') {
                        $mandays += 1.0;
                        $hadirCount++;
                        $totalGajiPresensi += $dailyGaji * 1.0;
                        $totalBonusPresensi += $dailyBonus * 1.0;
                    } elseif ($st === 'H2') {
                        $mandays += 0.5;
                        $setengahCount++;
                        $totalGajiPresensi += $dailyGaji * 0.5;
                        $totalBonusPresensi += $dailyBonus * 0.5;
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
                    'total_gaji_presensi' => (int) round($totalGajiPresensi),
                    'total_bonus_presensi' => (int) round($totalBonusPresensi),
                ];
            }

            $summary['total_petugas'] = $petugas->count();
            $summary['total_aktif'] = $petugas->where('status', 'Aktif')->count();
            $summary['total_siap_bni'] = $petugas->where('status', 'Aktif')->whereNotNull('nomor_rekening')->where('nomor_rekening', '!=', '')->count();
            $summary['total_rekening_kosong'] = $petugas->where('status', 'Aktif')->filter(fn($p) => empty($p->nomor_rekening))->count();
        }

        // Cek status transaksi pengajuan pembayaran gaji untuk rentang tanggal aktif
        $refTag = "[REF:GAJI-PETUGAS:{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}]";
        $existingTrx = TransaksiKeuangan::where('unit_sppg_id', $unitSppg?->id)
            ->where('kategori', 'Operasional')
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

        return Inertia::render('Petugas/PembayaranGaji', [
            'petugas' => $petugas,
            'summary' => $summary,
            'daftarJabatan' => $daftarJabatan,
            'periodes' => $periodes,
            'unitSppg' => $unitSppg,
            'initialMode' => $filterMode,
            'initialTanggalMulai' => $startDate->format('Y-m-d'),
            'initialTanggalSelesai' => $endDate->format('Y-m-d'),
            'initialPeriodeId' => $periodeId ?? 'all',
            'initialPresensiMap' => $presensiMap,
            'initialPresensiDetailsMap' => $presensiDetailsMap ?? [],
            'mandaysMap' => $mandaysMap,
            'initialSelectedDates' => $selectedDatesList,
            'transaksiPengajuan' => $transaksiPengajuan,
            'statusPengajuan' => $transaksiPengajuan['status'] ?? null,
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
                    try {
                        $wb->Close(false);
                    } catch (\Throwable $ex) {
                    }
                }
                if ($excel !== null) {
                    try {
                        $excel->Quit();
                    } catch (\Throwable $ex) {
                    }
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

    /**
     * Download template resmi Daftar Nominatif Pembayaran Relawan Harian (.xlsx)
     * Mengisi sheet Daftar_Relawan (No, Nama Relawan, Peran, Hari Kerja, Besaran Harian, Metode Bayar, Tanggal Bayar, Keterangan)
     * sambil mempertahankan seluruh formula pada kolom Jumlah Diterima (F), Status Baris (J), Rekap Per Peran, dan validasi dropdown Ref.
     */
    public function downloadDaftarRelawanHarian(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.nama' => 'required|string',
            'items.*.peran' => 'nullable|string',
            'items.*.hari_kerja' => 'nullable|numeric',
            'items.*.besaran_harian' => 'nullable|numeric',
            'items.*.metode_bayar' => 'nullable|string',
            'items.*.tanggal_bayar' => 'nullable|string',
            'items.*.keterangan' => 'nullable|string',
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

        $validRoles = [
            'Pengawas Sanitasi',
            'Juru Masak',
            'Asisten Lapangan/Aslap',
            'Persiapan Bahan Makanan',
            'Pengolahan Bahan Makanan',
            'Pemorsian',
            'Packing',
            'Distribusi',
            'Petugas Kebersihan',
            'Pencuci Alat Makan',
            'Petugas Keamanan',
            'Penanggung Jawab Satuan Pendidikan',
            'Kader Posyandu',
        ];

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
            $items = array_values($validated['items']);
            $count = count($items);

            for ($i = 0; $i < $count; $i++) {
                $item = $items[$i];
                $row = $startRow + $i;

                $nama = trim((string)($item['nama'] ?? ''));
                $peranRaw = trim((string)($item['peran'] ?? ''));

                // Jika peran belum sesuai salah satu dari 13 opsi template, petakan otomatis
                $peran = in_array($peranRaw, $validRoles, true) ? $peranRaw : $this->mapJabatanToPeranBgn($peranRaw);

                $hariKerja = (float)($item['hari_kerja'] ?? 1);
                if ($hariKerja <= 0) $hariKerja = 1;

                $besaranHarian = (float)($item['besaran_harian'] ?? 0);
                $metodeBayar = in_array(ucfirst(strtolower(trim((string)($item['metode_bayar'] ?? '')))), ['Transfer', 'Tunai']) 
                    ? ucfirst(strtolower(trim($item['metode_bayar']))) 
                    : 'Transfer';

                $keterangan = trim((string)($item['keterangan'] ?? ''));

                // Col A: No
                $sheet->setCellValue("A{$row}", $i + 1);
                // Col B: Nama Relawan
                $sheet->setCellValue("B{$row}", $nama);
                // Col C: Peran
                $sheet->setCellValue("C{$row}", $peran);
                // Col D: Hari Kerja
                $sheet->setCellValue("D{$row}", $hariKerja);
                // Col E: Besaran Harian (Rp)
                $sheet->setCellValue("E{$row}", $besaranHarian);
                // Col F: Formula Jumlah Diterima (Rp)
                $sheet->setCellValue("F{$row}", "=IF(OR(D{$row}=\"\",E{$row}=\"\"),\"\",D{$row}*E{$row})");
                // Col G: Metode Bayar
                $sheet->setCellValue("G{$row}", $metodeBayar);
                // Col H: Tanggal Bayar (tanggal saat download)
                $sheet->setCellValue("H{$row}", $tglSaatDownload);
                // Col I: Keterangan
                $sheet->setCellValue("I{$row}", $keterangan);
                // Col J: Formula Status Baris
                $sheet->setCellValue("J{$row}", "=IF(B{$row}=\"\",\"\",IF(COUNTIF(\$B\$9:\$B\$108,B{$row})>1,\"DUPLIKAT NAMA\",IF(OR(C{$row}=\"\",D{$row}=\"\",E{$row}=\"\",G{$row}=\"\",H{$row}=\"\"),\"BELUM LENGKAP\",\"OK\")))");
            }

            // Jika ada baris sisa di bawahnya sampai baris 108, kosongkan kolom data agar tidak ada sisa dummy
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

            // Pastikan formula validasi sinkronisasi total di sheet Rekap_Per_Peran membandingkan baris total D18
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
            $tempFile = $tempDir . DIRECTORY_SEPARATOR . 'Daftar_Relawan_' . uniqid() . '.xlsx';

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
            $filename = "Payroll ERP Gaji Petugas SPPG {$dateLabel}.xlsx";

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            \Log::error('Error generate Payroll ERP Gaji Petugas SPPG: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => 'Gagal memproses file template Payroll ERP Gaji Petugas SPPG: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memetakan jabatan/divisi internal SPPG ke dalam 13 opsi Peran standar BGN.
     */
    private function mapJabatanToPeranBgn(?string $jabatan): string
    {
        if (!$jabatan) return 'Pengawas Sanitasi';
        $j = strtolower($jabatan);

        if (str_contains($j, 'chef') || str_contains($j, 'masak')) return 'Juru Masak';
        if (str_contains($j, 'koordinator lapangan') || str_contains($j, 'aslap')) return 'Asisten Lapangan/Aslap';
        if (str_contains($j, 'persiapan')) return 'Persiapan Bahan Makanan';
        if (str_contains($j, 'pengolahan')) return 'Pengolahan Bahan Makanan';
        if (str_contains($j, 'pemorsian')) return 'Pemorsian';
        if (str_contains($j, 'pengemudi') || str_contains($j, 'distribusi') || str_contains($j, 'sopir') || str_contains($j, 'driver')) return 'Distribusi';
        if (str_contains($j, 'cuci') || str_contains($j, 'ompreng') || str_contains($j, 'pencuci')) return 'Pencuci Alat Makan';
        if (str_contains($j, 'kebersihan')) return 'Petugas Kebersihan';
        if (str_contains($j, 'keamanan') || str_contains($j, 'satpam') || str_contains($j, 'security')) return 'Petugas Keamanan';
        if (str_contains($j, 'sanitasi') || str_contains($j, 'gizi')) return 'Pengawas Sanitasi';
        if (str_contains($j, 'packing') || str_contains($j, 'kemas')) return 'Packing';
        if (str_contains($j, 'posyandu')) return 'Kader Posyandu';
        if (str_contains($j, 'satuan pendidikan') || str_contains($j, 'sekolah')) return 'Penanggung Jawab Satuan Pendidikan';

        return 'Pengawas Sanitasi';
    }

    /**
     * Mengekstrak daftar seluruh jabatan unik individual dari koleksi petugas (mendukung multi jabatan).
     */
    private function extractDaftarJabatan($petugasCollection): array
    {
        $all = $petugasCollection->flatMap(function ($p) {
            if (!$p->jabatan) return [];
            $str = preg_replace('/\(a\.k\.a\.?\s*([^)]+)\)/i', ', $1', $p->jabatan);
            $str = preg_replace('/\ba\.k\.a\.?\s+/i', ', ', $str);
            $str = preg_replace('/\((Koordinator[^)]*)\)/i', ', $1', $str);
            $str = preg_replace('/\bKoordinator\s*[12]\b/i', 'Koordinator', $str);
            $parts = preg_split('~[,&/]~', $str);
            return array_filter(array_map(fn($s) => trim($s, " \t\n\r\0\x0B()"), $parts));
        })->unique()->values()->all();

        sort($all);
        return $all;
    }

    /**
     * Ajukan Transaksi Pembayaran Gaji Petugas SPPG ke Menu Keuangan (Status Ditunda).
     */
    public function ajukanPembayaranGaji(Request $request)
    {
        $validated = $request->validate([
            'tanggal_transaksi' => ['required', 'date'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
            'periode_id' => ['nullable'],
            'periode_label' => ['nullable', 'string', 'max:255'],
            'nominal' => ['required', 'numeric', 'min:1'],
            'total_personil' => ['nullable', 'integer'],
            'total_mandays' => ['nullable', 'numeric'],
            'metode_bayar' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        $tglTransaksi = $validated['tanggal_transaksi'];
        $tglMulai = $validated['tanggal_mulai'] ?: $tglTransaksi;
        $tglSelesai = $validated['tanggal_selesai'] ?: $tglTransaksi;
        $label = $validated['periode_label'] ?: ($tglMulai === $tglSelesai ? Carbon::parse($tglTransaksi)->translatedFormat('d F Y') : Carbon::parse($tglMulai)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($tglSelesai)->translatedFormat('d M Y'));

        $namaTransaksi = "Pembayaran Gaji Petugas SPPG - " . $label;
        $refTag = "[REF:GAJI-PETUGAS:{$tglMulai}_{$tglSelesai}]";

        // Cek apakah transaksi pengajuan gaji untuk rentang ini sudah pernah dibuat
        $existing = TransaksiKeuangan::where('unit_sppg_id', $unitSppg?->id)
            ->where('kategori', 'Operasional')
            ->where('arus', 'Keluar')
            ->where('keterangan', 'like', "%{$refTag}%")
            ->first();

        $personilCount = $validated['total_personil'] ?? 0;
        $mandaysCount = $validated['total_mandays'] ?? 0;
        $keteranganDetail = "Pengajuan otomatis Pembayaran Gaji Petugas SPPG ({$label}) sejumlah {$personilCount} personil ({$mandaysCount} mandays/hari kerja). {$refTag}";

        if ($existing) {
            if ($existing->status === 'Disetujui') {
                return response()->json([
                    'status' => 'already_approved',
                    'message' => "Pengajuan pembayaran gaji untuk periode ini sudah pernah DISETUJUI pada Menu Transaksi ({$existing->nomor_transaksi}) dan tidak dapat diajukan kembali.",
                    'data' => $existing,
                ], 422);
            }

            if ($existing->status === 'Ditunda') {
                return response()->json([
                    'status' => 'already_pending',
                    'message' => "Pengajuan pembayaran gaji untuk periode ini sudah TELAH DIAJUKAN dengan status DITUNDA pada Menu Transaksi ({$existing->nomor_transaksi}). Pengajuan hanya bisa dilakukan 1 kali dan tidak boleh duplikasi.",
                    'data' => $existing,
                ], 422);
            }

            // Jika status sebelumnya Ditolak atau Dibatalkan, perbarui transaksi tersebut kembali ke status Ditunda (diajukan ulang)
            $existing->update([
                'tanggal' => $tglTransaksi,
                'tanggal_pesanan' => $tglTransaksi,
                'nama_transaksi' => $namaTransaksi,
                'nominal' => $validated['nominal'],
                'volume' => $personilCount > 0 ? $personilCount : 1,
                'satuan' => 'Personil',
                'status' => 'Ditunda',
                'metode_bayar' => $validated['metode_bayar'] ?: 'Transfer',
                'keterangan' => $keteranganDetail,
            ]);

            return response()->json([
                'status' => 'updated',
                'message' => "Pengajuan ulang pembayaran gaji berhasil dikirim ke Menu Transaksi dengan status DITUNDA (Nomor: {$existing->nomor_transaksi}). Silakan disetujui di Menu Keuangan agar sah terhitung.",
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
            'volume' => $personilCount > 0 ? $personilCount : 1,
            'satuan' => 'Personil',
            'nominal' => $validated['nominal'],
            'status' => 'Ditunda',
            'perlu_npo' => false,
            'metode_bayar' => $validated['metode_bayar'] ?: 'Transfer',
            'keterangan' => $keteranganDetail,
        ]);

        return response()->json([
            'status' => 'created',
            'message' => "Pengajuan pembayaran gaji sebesar Rp " . number_format($validated['nominal'], 0, ',', '.') . " berhasil masuk ke Menu Transaksi dengan status DITUNDA (Nomor: {$newTrx->nomor_transaksi}). Silakan disetujui di Menu Keuangan agar sah terhitung.",
            'data' => $newTrx,
        ]);
    }
}
