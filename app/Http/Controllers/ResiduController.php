<?php

namespace App\Http\Controllers;

use App\Models\Periode;
use App\Models\Petugas;
use App\Models\ResiduLimbah;
use App\Models\SettingKopDokumen;
use App\Models\UnitSppg;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\AiResiduService;

class ResiduController extends Controller
{
    /**
     * Tampilkan halaman daftar pencatatan residu & limbah harian.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();
        $unitId = $unitSppg?->id;

        $kopConfig = SettingKopDokumen::getActiveConfig($unitId);
        $periodeList = Periode::orderBy('id', 'desc')->get();

        // Pejabat Penandatangan Dokumen (4 TTD: Kepala SPPG, Pengawas Gizi, Pengawas Keuangan, Koordinator Lapangan)
        $kepalaSppg = Petugas::where('unit_sppg_id', $unitId)
            ->where('jabatan', 'like', '%Kepala%')
            ->value('nama')
            ?? $unitSppg?->nama_kepala
            ?? $user?->nama_lengkap
            ?? $user?->nama
            ?? 'I Gede Gelgel Abdiutama, S.Kom.';

        $pengawasGizi = Petugas::where('unit_sppg_id', $unitId)
            ->where(function ($q) {
                $q->where('jabatan', 'like', '%Pengawas Gizi%')
                  ->orWhere('jabatan', 'like', '%PLOG%');
            })
            ->value('nama') ?? 'Ika Septiana';

        $pengawasKeuangan = Petugas::where('unit_sppg_id', $unitId)
            ->where(function ($q) {
                $q->where('jabatan', 'like', '%Pengawas Keuangan%')
                  ->orWhere('jabatan', 'like', '%PLOK%');
            })
            ->value('nama') ?? 'I Gusti Ayu Made Padmi Swari';

        $koordinatorLapangan = Petugas::where('unit_sppg_id', $unitId)
            ->where(function ($q) {
                $q->where('jabatan', 'like', '%Koordinator Lapangan%')
                  ->orWhere('jabatan', 'like', '%Korlap%');
            })
            ->value('nama') ?? 'Didik Marhadi';

        $pejabatTtd = [
            'kepala_sppg' => $kepalaSppg,
            'pengawas_gizi' => $pengawasGizi,
            'pengawas_keuangan' => $pengawasKeuangan,
            'koordinator_lapangan' => $koordinatorLapangan,
        ];

        // Filter Mode: hari_ini | periode | rentang | all_time
        $isAllTime = filter_var($request->query('is_all_time') ?? $request->query('isAllTime'), FILTER_VALIDATE_BOOLEAN);
        $mode = $request->query('mode', 'periode');
        $todayStr = Carbon::today()->format('Y-m-d');

        $activePeriode = null;
        if ($request->query('periode_id')) {
            $activePeriode = $periodeList->firstWhere('id', (int) $request->query('periode_id'));
        }
        if (!$activePeriode) {
            $activePeriode = $periodeList->firstWhere('id', 13) ?? $periodeList->first();
        }

        $startDate = $request->query('tanggal_mulai') ?: $request->query('start_date');
        $endDate = $request->query('tanggal_selesai') ?: $request->query('end_date');

        if ($isAllTime) {
            $startDate = null;
            $endDate = null;
        } elseif ($mode === 'hari_ini') {
            $startDate = $todayStr;
            $endDate = $todayStr;
        } elseif ($mode === 'periode' && $activePeriode) {
            $startDate = Carbon::parse($activePeriode->tanggal_mulai)->format('Y-m-d');
            $endDate = Carbon::parse($activePeriode->tanggal_selesai)->format('Y-m-d');
        } elseif ($mode === 'rentang') {
            $startDate = $startDate ?: ($activePeriode ? Carbon::parse($activePeriode->tanggal_mulai)->format('Y-m-d') : $todayStr);
            $endDate = $endDate ?: ($activePeriode ? Carbon::parse($activePeriode->tanggal_selesai)->format('Y-m-d') : $todayStr);
        } else {
            $startDate = $todayStr;
            $endDate = $todayStr;
        }

        // Query Dasar
        $query = ResiduLimbah::query()->when($unitId, fn($q) => $q->where('unit_sppg_id', $unitId));

        if (!$isAllTime && $startDate && $endDate) {
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        // Filter Tambahan (Bahasa Indonesia dengan fallback)
        $search = $request->query('cari') ?: $request->query('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_limbah', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        $jenis = $request->query('jenis') ?: $request->query('jenis_limbah');
        if ($jenis && $jenis !== 'all') {
            $query->where('jenis_limbah', $jenis);
        }

        $sumber = $request->query('sumber') ?: $request->query('sumber_limbah');
        if ($sumber && $sumber !== 'all') {
            $query->where('sumber_limbah', $sumber);
        }

        $metode = $request->query('metode') ?: $request->query('metode_penanganan');
        if ($metode && $metode !== 'all') {
            $query->where('metode_penanganan', $metode);
        }

        $status = $request->query('status') ?: $request->query('status_berbahaya');
        if ($status && $status !== 'all') {
            $query->where('status_berbahaya', $status);
        }

        $residuList = (clone $query)->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->get();

        // Hitung KPI Sesuai Filter / Periode yang Dipilih
        $summary = [
            'total_catatan' => $residuList->count(),
            'total_berat_kg' => round((float) $residuList->where('satuan', 'Kg')->sum('jumlah'), 2),
            'total_volume_l' => round((float) $residuList->where('satuan', 'L')->sum('jumlah'), 2),
            'total_organik_kg' => round((float) $residuList->where('jenis_limbah', 'Organik')->where('satuan', 'Kg')->sum('jumlah'), 2),
            'total_anorganik_kg' => round((float) $residuList->where('jenis_limbah', 'Anorganik')->where('satuan', 'Kg')->sum('jumlah'), 2),
            'total_berbahaya' => $residuList->where('status_berbahaya', 'Berbahaya')->count(),
            'total_tidak_berbahaya' => $residuList->where('status_berbahaya', 'Tidak Berbahaya')->count(),
        ];

        // Hitung KPI Akumulasi Seluruh Database (All-Time / Semua Waktu)
        $allBaseQuery = ResiduLimbah::query()->when($unitId, fn($q) => $q->where('unit_sppg_id', $unitId));
        $allTimeSummary = [
            'total_catatan' => (clone $allBaseQuery)->count(),
            'total_berat_kg' => round((float) (clone $allBaseQuery)->where('satuan', 'Kg')->sum('jumlah'), 2),
            'total_volume_l' => round((float) (clone $allBaseQuery)->where('satuan', 'L')->sum('jumlah'), 2),
            'total_organik_kg' => round((float) (clone $allBaseQuery)->where('jenis_limbah', 'Organik')->where('satuan', 'Kg')->sum('jumlah'), 2),
            'total_anorganik_kg' => round((float) (clone $allBaseQuery)->where('jenis_limbah', 'Anorganik')->where('satuan', 'Kg')->sum('jumlah'), 2),
            'total_berbahaya' => (clone $allBaseQuery)->where('status_berbahaya', 'Berbahaya')->count(),
            'total_tidak_berbahaya' => (clone $allBaseQuery)->where('status_berbahaya', 'Tidak Berbahaya')->count(),
        ];

        return Inertia::render('Residu/Index', [
            'unitSppg' => $unitSppg,
            'kopConfig' => $kopConfig,
            'periodeList' => $periodeList,
            'activePeriode' => $activePeriode,
            'filters' => [
                'tanggal_mulai' => $startDate ?? '',
                'tanggal_selesai' => $endDate ?? '',
                'mode' => $mode,
                'periode_id' => $activePeriode?->id,
                'is_all_time' => $isAllTime,
                'isAllTime' => $isAllTime,
                'cari' => $search ?? '',
                'jenis' => $jenis ?? 'all',
                'sumber' => $sumber ?? 'all',
                'metode' => $metode ?? 'all',
                'status' => $status ?? 'all',
                // Fallback alias
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
                'search' => $search ?? '',
                'jenis_limbah' => $jenis ?? 'all',
                'sumber_limbah' => $sumber ?? 'all',
                'metode_penanganan' => $metode ?? 'all',
                'status_berbahaya' => $status ?? 'all',
            ],
            'pejabatTtd' => $pejabatTtd,
            'summary' => $summary,
            'allTimeSummary' => $allTimeSummary,
            'residuList' => $residuList,
        ]);
    }

    /**
     * Simpan pencatatan limbah harian baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'nama_limbah' => ['required', 'string', 'max:255'],
            'jumlah' => ['required', 'numeric', 'min:0.01'],
            'satuan' => ['required', 'in:Kg,L'],
            'jenis_limbah' => ['required', 'in:Organik,Anorganik'],
            'sumber_limbah' => ['required', 'in:Persiapan,Pengolahan,Ompreng,Lainnya'],
            'metode_penanganan' => ['required', 'in:Donasi,Kompos,Daur Ulang,Pembuangan'],
            'status_berbahaya' => ['required', 'in:Tidak Berbahaya,Berbahaya'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ], [
            'tanggal.required' => 'Tanggal pencatatan limbah wajib diisi.',
            'nama_limbah.required' => 'Nama limbah wajib diisi.',
            'jumlah.required' => 'Jumlah limbah wajib diisi (angka desimal diperbolehkan).',
            'jumlah.min' => 'Jumlah limbah harus lebih dari 0.',
            'satuan.required' => 'Satuan limbah wajib dipilih (Kg atau L).',
            'jenis_limbah.required' => 'Jenis limbah wajib dipilih (Organik atau Anorganik).',
            'sumber_limbah.required' => 'Sumber limbah wajib dipilih.',
            'metode_penanganan.required' => 'Metode penanganan limbah wajib dipilih.',
            'status_berbahaya.required' => 'Status bahaya limbah wajib dipilih.',
        ]);

        $validated['unit_sppg_id'] = $unitSppg?->id;

        ResiduLimbah::create($validated);

        return back()->with('success', "Catatan limbah '{$validated['nama_limbah']}' berhasil ditambahkan.");
    }

    /**
     * Perbarui data pencatatan limbah.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();

        $residu = ResiduLimbah::when($unitSppg?->id, fn($q) => $q->where('unit_sppg_id', $unitSppg->id))->findOrFail($id);

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'nama_limbah' => ['required', 'string', 'max:255'],
            'jumlah' => ['required', 'numeric', 'min:0.01'],
            'satuan' => ['required', 'in:Kg,L'],
            'jenis_limbah' => ['required', 'in:Organik,Anorganik'],
            'sumber_limbah' => ['required', 'in:Persiapan,Pengolahan,Ompreng,Lainnya'],
            'metode_penanganan' => ['required', 'in:Donasi,Kompos,Daur Ulang,Pembuangan'],
            'status_berbahaya' => ['required', 'in:Tidak Berbahaya,Berbahaya'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ], [
            'tanggal.required' => 'Tanggal pencatatan limbah wajib diisi.',
            'nama_limbah.required' => 'Nama limbah wajib diisi.',
            'jumlah.required' => 'Jumlah limbah wajib diisi (angka desimal diperbolehkan).',
            'jumlah.min' => 'Jumlah limbah harus lebih dari 0.',
            'satuan.required' => 'Satuan limbah wajib dipilih (Kg atau L).',
            'jenis_limbah.required' => 'Jenis limbah wajib dipilih (Organik atau Anorganik).',
            'sumber_limbah.required' => 'Sumber limbah wajib dipilih.',
            'metode_penanganan.required' => 'Metode penanganan limbah wajib dipilih.',
            'status_berbahaya.required' => 'Status bahaya limbah wajib dipilih.',
        ]);

        $residu->update($validated);

        return back()->with('success', "Catatan limbah '{$validated['nama_limbah']}' berhasil diperbarui.");
    }

    /**
     * Hapus data pencatatan limbah.
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();

        $residu = ResiduLimbah::when($unitSppg?->id, fn($q) => $q->where('unit_sppg_id', $unitSppg->id))->findOrFail($id);
        $nama = $residu->nama_limbah;
        $residu->delete();

        return back()->with('success', "Catatan limbah '{$nama}' berhasil dihapus.");
    }

    /**
     * Hapus beberapa atau semua catatan limbah sekaligus.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();
        $unitId = $unitSppg?->id;

        $ids = $request->input('ids', []);

        $deletedCount = ResiduLimbah::whereIn('id', $ids)
            ->when($unitId, fn($q) => $q->where('unit_sppg_id', $unitId))
            ->delete();

        return back()->with('success', "Berhasil menghapus {$deletedCount} catatan limbah terpilih.");
    }

    /**
     * Parse input pesan singkat atau file dokumen (Excel/CSV/TXT) menggunakan Universal AI / Smart Heuristic.
     * Mendukung multiple files sekaligus dan selalu mengurutkan catatan berdasarkan tanggal secara kronologis.
     */
    public function aiParse(Request $request, AiResiduService $aiService): JsonResponse
    {
        $request->validate([
            'mode' => ['required', 'in:text,chat,file'],
            'prompt' => ['nullable', 'string', 'max:10000'],
            'file' => ['nullable', 'file', 'max:25600'],
            'files' => ['nullable', 'array', 'max:30'],
            'files.*' => ['nullable', 'file', 'max:25600'],
        ], [
            'file.max' => 'Ukuran file dokumen maksimal 25MB.',
            'files.*.max' => 'Ukuran setiap file dokumen maksimal 25MB.',
            'files.max' => 'Maksimal 30 file dokumen sekaligus.',
        ]);

        try {
            $mode = $request->input('mode');
            $items = [];
            $execution = [];

            if ($mode === 'text' || $mode === 'chat') {
                $prompt = trim((string) $request->input('prompt', ''));
                if (empty($prompt)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pesan singkat atau teks catatan limbah tidak boleh kosong.',
                    ], 422);
                }
                $parseResult = $aiService->parseFromTextWithMeta($prompt);
                $items = $parseResult['items'] ?? [];
                $execution = $parseResult['execution'] ?? [];
            } elseif ($mode === 'file') {
                $uploadedFiles = [];
                if ($request->hasFile('files')) {
                    $rawFiles = $request->file('files');
                    if (is_array($rawFiles)) {
                        $uploadedFiles = $rawFiles;
                    }
                } elseif ($request->hasFile('file')) {
                    $uploadedFiles = [$request->file('file')];
                }

                if (empty($uploadedFiles)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Silakan pilih atau seret minimal satu file dokumen (Excel, CSV, atau TXT).',
                    ], 422);
                }

                $fileReports = [];
                $isAnyAi = false;
                $lastExecution = [];

                foreach ($uploadedFiles as $uFile) {
                    if (!$uFile || !$uFile->isValid()) {
                        continue;
                    }

                    $fName = $uFile->getClientOriginalName();
                    $fParse = $aiService->parseFromFileWithMeta($uFile);
                    $fItems = $fParse['items'] ?? [];
                    $fExec = $fParse['execution'] ?? [];

                    if (!empty($fExec['is_ai'])) {
                        $isAnyAi = true;
                    }
                    $lastExecution = $fExec;

                    $fileReports[] = [
                        'file' => $fName,
                        'count' => count($fItems),
                    ];

                    foreach ($fItems as $item) {
                        $items[] = $item;
                    }
                }

                $execution = $lastExecution;
                if (!empty($execution)) {
                    $execution['is_ai'] = $isAnyAi;
                    $execution['multiple_files'] = count($uploadedFiles) > 1;
                    $execution['files_processed'] = $fileReports;
                }
            }

            if (empty($items)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sistem tidak menemukan entri limbah yang valid dari dokumen/input yang diberikan. Pastikan terdapat informasi minimal nama limbah dan jumlah.',
                    'execution' => $execution,
                ], 422);
            }

            // ─── WAJIB: SELALU URUTKAN DATA BERDASARKAN TANGGAL ASCENDING (KRONOLOGIS) ───
            usort($items, function ($a, $b) {
                $tglA = $a['tanggal'] ?? '';
                $tglB = $b['tanggal'] ?? '';
                if ($tglA === $tglB) {
                    return 0;
                }
                return ($tglA < $tglB) ? -1 : 1;
            });

            $successMsg = count($items) . ' catatan limbah berhasil diekstrak dan diurutkan berdasarkan tanggal.';
            if (!empty($fileReports) && count($fileReports) > 1) {
                $successMsg = 'Berhasil mengekstrak ' . count($items) . ' catatan limbah dari ' . count($fileReports) . ' file dokumen secara kronologis.';
            }

            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'data' => $items,
                'execution' => $execution,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kendala saat memproses: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Simpan secara batch banyak catatan limbah hasil ekstraksi AI yang telah diverifikasi pengguna.
     * Selalu disimpan berurutan menurut tanggal agar data logbook tersusun rapi.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        // Pra-sanitasi tanggal jika ada format cacat seperti 2026-00-24 dari browser/Excel
        $rawItems = $request->input('items', []);
        $today = now()->format('Y-m-d');
        $currentMonth = (int) now()->format('m');

        if (is_array($rawItems)) {
            foreach ($rawItems as &$it) {
                if (is_array($it)) {
                    $rawTgl = trim((string)($it['tanggal'] ?? ''));
                    // Jika ada format 2026-00-24, koreksi otomatis ke bulan berjalan (misal 2026-10-24)
                    if (preg_match('/^(\d{4})-00-(\d{1,2})$/', $rawTgl, $zm)) {
                        $rawTgl = sprintf('%04d-%02d-%02d', (int)$zm[1], $currentMonth, (int)$zm[2]);
                    } elseif (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $rawTgl)) {
                        try {
                            $parsed = \Carbon\Carbon::parse($rawTgl);
                            $rawTgl = $parsed->format('Y-m-d');
                        } catch (\Throwable $e) {
                            $rawTgl = $today;
                        }
                    }
                    $it['tanggal'] = $rawTgl;
                }
            }
            unset($it);
            $request->merge(['items' => $rawItems]);
        }

        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.tanggal' => ['required', 'date'],
            'items.*.nama_limbah' => ['required', 'string', 'max:255'],
            'items.*.jumlah' => ['required', 'numeric', 'min:0.01'],
            'items.*.satuan' => ['required', 'in:Kg,L'],
            'items.*.jenis_limbah' => ['required', 'in:Organik,Anorganik'],
            'items.*.sumber_limbah' => ['required', 'in:Persiapan,Pengolahan,Ompreng,Lainnya'],
            'items.*.metode_penanganan' => ['required', 'in:Donasi,Kompos,Daur Ulang,Pembuangan'],
            'items.*.status_berbahaya' => ['required', 'in:Tidak Berbahaya,Berbahaya'],
            'items.*.keterangan' => ['nullable', 'string', 'max:500'],
        ], [
            'items.required' => 'Daftar catatan limbah tidak boleh kosong.',
            'items.*.tanggal.date' => 'Format tanggal harus berupa tanggal kalender yang valid (YYYY-MM-DD).',
            'items.*.nama_limbah.required' => 'Nama limbah wajib diisi pada setiap baris.',
            'items.*.jumlah.min' => 'Jumlah limbah harus lebih dari 0.',
        ]);

        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : UnitSppg::first();
        $unitId = $unitSppg?->id;

        $items = $request->input('items', []);

        // ─── WAJIB: URUTKAN BERDASARKAN TANGGAL ASCENDING SEBELUM DISIMPAN KE DB ───
        usort($items, function ($a, $b) {
            $tglA = $a['tanggal'] ?? '';
            $tglB = $b['tanggal'] ?? '';
            if ($tglA === $tglB) {
                return 0;
            }
            return ($tglA < $tglB) ? -1 : 1;
        });
        $savedCount = 0;

        foreach ($items as $item) {
            ResiduLimbah::create([
                'unit_sppg_id' => $unitId,
                'tanggal' => $item['tanggal'],
                'nama_limbah' => $item['nama_limbah'],
                'jumlah' => $item['jumlah'],
                'satuan' => $item['satuan'],
                'jenis_limbah' => $item['jenis_limbah'],
                'sumber_limbah' => $item['sumber_limbah'],
                'metode_penanganan' => $item['metode_penanganan'],
                'status_berbahaya' => $item['status_berbahaya'],
                'keterangan' => $item['keterangan'] ?? 'Dibuat via AI Auto-Logger',
            ]);
            $savedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil menyimpan {$savedCount} catatan limbah ke database.",
            'count' => $savedCount,
        ]);
    }
}
