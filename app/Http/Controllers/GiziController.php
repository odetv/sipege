<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderKelompok;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GiziController extends Controller
{
    /**
     * Tampilkan halaman utama gizi (default ke sub menu Database Pangan).
     */
    public function index(Request $request): Response
    {
        return $this->renderGiziView($request, 'database-pangan');
    }

    /**
     * Sub-menu 1: Database Pangan (NutriSurvey Indo .fta & TKPI 2020 .csv).
     */
    public function databasePangan(Request $request): Response
    {
        return $this->renderGiziView($request, 'database-pangan');
    }

    /**
     * Alias backwards-compatibility untuk route /gizi/tkpi.
     */
    public function tkpi(Request $request): Response
    {
        return $this->databasePangan($request);
    }

    /**
     * Sub-menu 2: Analisa PM (Analisa Penerima Manfaat & Porsi Sasaran).
     */
    public function analisaPm(Request $request): Response
    {
        return $this->renderGiziView($request, 'analisa-pm');
    }

    /**
     * Sub-menu 3: Daftar Menu (Jadwal & Siklus Menu Harian MBG).
     */
    public function daftarMenu(Request $request): Response
    {
        return $this->renderGiziView($request, 'daftar-menu');
    }

    /**
     * Sub-menu 4: Rancang Menu (Perencanaan Produksi & Formulasi Gizi).
     */
    public function rancangMenu(Request $request): Response
    {
        return $this->renderGiziView($request, 'rancang-menu', $request->query('step'));
    }

    /**
     * Alias Buat Menu.
     */
    public function buatMenu(Request $request): Response
    {
        return $this->rancangMenu($request);
    }

    /**
     * Sub-menu 5: Kalender Menu (Jadwal & Siklus Menu Harian MBG).
     */
    public function kalenderMenu(Request $request): Response
    {
        return $this->renderGiziView($request, 'kalender-menu');
    }

    /**
     * Simpan (Create / Update) Work Order beserta Items, Kelompoks, dan Otomatisasi PO Keuangan.
     */
    public function storeWorkOrder(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG belum terdaftar.');
        }

        $validated = $request->validate([
            'nomor_wo' => ['required', 'string'],
            'tanggal_distribusi' => ['required', 'date'],
            'nama_menu' => ['required', 'string', 'max:255'],
            'siklus_ke' => ['nullable', 'integer'],
            'status' => ['required', 'string'],
            'database_pangan' => ['nullable', 'string', 'max:30'],
            'komponen_energi' => ['nullable', 'string'],
            'komponen_protein' => ['nullable', 'string'],
            'komponen_lemak' => ['nullable', 'string'],
            'komponen_karbohidrat' => ['nullable', 'string'],
            'komponen_serat' => ['nullable', 'string'],
            'total_pm' => ['required', 'integer'],
            'total_pk' => ['required', 'integer'],
            'total_pb' => ['required', 'integer'],
            'total_alergi' => ['nullable', 'integer'],
            'total_kelompok' => ['nullable', 'integer'],
            'akg_pk' => ['nullable', 'array'],
            'akg_pb' => ['nullable', 'array'],
            'food_cost_pk' => ['nullable', 'numeric'],
            'food_cost_pb' => ['nullable', 'numeric'],
            'current_step' => ['nullable', 'integer'],
            'total_anggaran_master' => ['nullable', 'numeric'],
            'items' => ['nullable', 'array'],
            'kelompoks' => ['nullable', 'array'],
        ]);

        // Validasi 1 Work Order per 1 Tanggal Distribusi dalam unit SPPG
        $existingWoOnDate = WorkOrder::where('unit_sppg_id', $unitSppg->id)
            ->where('tanggal_distribusi', $validated['tanggal_distribusi'])
            ->first();

        if ($existingWoOnDate && $existingWoOnDate->nomor_wo !== $validated['nomor_wo']) {
            return back()->with('error', 'Hanya diperbolehkan 1 Work Order per tanggal distribusi. Tanggal ' . $validated['tanggal_distribusi'] . ' sudah terdaftar untuk menu "' . $existingWoOnDate->nama_menu . '" (' . $existingWoOnDate->nomor_wo . ').');
        }

        DB::transaction(function () use ($unitSppg, $validated) {
            // Find existing or new WorkOrder by nomor_wo or unit & tanggal
            $workOrder = WorkOrder::updateOrCreate(
                [
                    'unit_sppg_id' => $unitSppg->id,
                    'nomor_wo' => $validated['nomor_wo'],
                ],
                [
                    'tanggal_distribusi' => $validated['tanggal_distribusi'],
                    'nama_menu' => $validated['nama_menu'],
                    'siklus_ke' => $validated['siklus_ke'] ?? 1,
                    'status' => $validated['status'],
                    'database_pangan' => $validated['database_pangan'] ?? 'fta',
                    'current_step' => $validated['current_step'] ?? 1,
                    'komponen_energi' => $validated['komponen_energi'] ?? null,
                    'komponen_protein' => $validated['komponen_protein'] ?? null,
                    'komponen_lemak' => $validated['komponen_lemak'] ?? null,
                    'komponen_karbohidrat' => $validated['komponen_karbohidrat'] ?? null,
                    'komponen_serat' => $validated['komponen_serat'] ?? null,
                    'total_pm' => $validated['total_pm'],
                    'total_pk' => $validated['total_pk'],
                    'total_pb' => $validated['total_pb'],
                    'total_alergi' => $validated['total_alergi'] ?? 0,
                    'total_kelompok' => $validated['total_kelompok'] ?? 0,
                    'akg_pk' => $validated['akg_pk'] ?? null,
                    'akg_pb' => $validated['akg_pb'] ?? null,
                    'food_cost_pk' => $validated['food_cost_pk'] ?? 0,
                    'food_cost_pb' => $validated['food_cost_pb'] ?? 0,
                    'total_anggaran_master' => $validated['total_anggaran_master'] ?? 0,
                ]
            );

            // Sync Items
            $workOrder->items()->delete();
            foreach ($validated['items'] ?? [] as $item) {
                $workOrder->items()->create([
                    'tkpi_id' => $item['tkpi_id'] ?? $item['id'] ?? null,
                    'nama' => $item['nama'] ?? 'Bahan',
                    'nama_po' => $item['nama_po'] ?? $item['nama'] ?? 'Bahan',
                    'kategori' => $item['kategori'] ?? 'Lainnya',
                    'tipe_porsi' => $item['tipe_porsi'] ?? 'normal',
                    'jenis_alergi' => $item['jenis_alergi'] ?? null,
                    'alergen' => $item['alergen'] ?? null,
                    'gram_pk' => $item['gram_pk'] ?? 0,
                    'gram_pb' => $item['gram_pb'] ?? 0,
                    'bdd' => $item['bdd'] ?? 100,
                    'buffer' => $item['buffer'] ?? 0,
                    'gross_kg_pk' => $item['grossKgPK'] ?? 0,
                    'gross_kg_pb' => $item['grossKgPB'] ?? 0,
                    'total_gross_kg' => $item['totalGrossKg'] ?? 0,
                    'harga_master' => $item['harga_master'] ?? 0,
                    'subtotal_master' => $item['subtotalMaster'] ?? 0,
                    'nutrisi_pk' => $item['nutrisiPK'] ?? null,
                    'nutrisi_pb' => $item['nutrisiPB'] ?? null,
                ]);
            }

            // Sync Kelompoks snapshot
            if (!empty($validated['kelompoks'])) {
                $workOrder->kelompoks()->delete();
                foreach ($validated['kelompoks'] as $kel) {
                    $workOrder->kelompoks()->create([
                        'kelompok_id' => $kel['id'] ?? null,
                        'nama_kelompok' => $kel['nama_kelompok'] ?? 'Kelompok',
                        'kategori' => $kel['kategori'] ?? 'Sekolah',
                        'is_menerima' => $kel['is_menerima'] ?? true,
                        'porsi_kecil' => $kel['total_porsi_kecil'] ?? 0,
                        'porsi_besar' => $kel['total_porsi_besar'] ?? 0,
                        'total_penerima' => $kel['total_penerima'] ?? 0,
                        'status_alergi' => $kel['status_alergi'] ?? null,
                        'rincian' => $kel['rincian'] ?? null,
                        'detail_alergi' => $kel['detail_alergi'] ?? null,
                    ]);
                }
            }

            // Otomatisasi Purchase Order (PO) ke tabel Keuangan
            $tglStr = str_replace('-', '', $validated['tanggal_distribusi']);
            $nomorPo = 'PO-' . $tglStr . '-' . str_pad($workOrder->id, 3, '0', STR_PAD_LEFT);
            $poStatus = ($validated['status'] === 'Diajukan ke Keuangan' || $validated['status'] === 'Siap Produksi') ? 'Menunggu Verifikasi' : 'Draft PO';

            $po = PurchaseOrder::updateOrCreate(
                [
                    'work_order_id' => $workOrder->id,
                ],
                [
                    'unit_sppg_id' => $unitSppg->id,
                    'nomor_po' => $nomorPo,
                    'tanggal' => $validated['tanggal_distribusi'],
                    'vendor' => 'Rekanan Pangan SPPG',
                    'items_count' => count($validated['items']),
                    'total_nominal_master' => $validated['total_anggaran_master'] ?? 0,
                    'total_nominal_aktual' => $validated['total_anggaran_master'] ?? 0,
                    'status_po' => $poStatus,
                    'status_bayar' => 'Belum Bayar',
                    'catatan' => 'Dibuat dari ' . $workOrder->nomor_wo . ' - ' . $workOrder->nama_menu,
                    'riwayat_verifikasi' => $existingWoLogs ?? null,
                ]
            );

            $po->items()->delete();
            foreach ($workOrder->items as $woItem) {
                $po->items()->create([
                    'work_order_item_id' => $woItem->id,
                    'nama' => $woItem->nama_po ?: $woItem->nama,
                    'kategori' => $woItem->kategori,
                    'tipe' => $woItem->tipe_porsi === 'alergi' ? 'Alergi' : 'Normal',
                    'gross_kg' => $woItem->total_gross_kg,
                    'harga_master' => $woItem->harga_master,
                    'harga_aktual' => $woItem->harga_master,
                    'subtotal_aktual' => $woItem->total_gross_kg * $woItem->harga_master,
                ]);
            }
        });

        return redirect()->route('gizi.daftar-menu')->with('success', 'Rancangan menu berhasil disimpan ke database.');
    }

    /**
     * Hapus Work Order.
     */
    public function destroyWorkOrder($id): RedirectResponse
    {
        $query = WorkOrder::query();
        if (is_numeric($id)) {
            $query->where('id', (int) $id);
        } else {
            $query->where(function ($q) use ($id) {
                $q->where('uuid', $id)->orWhere('nomor_wo', $id);
            });
        }
        $workOrder = $query->firstOrFail();

        $statusAllowed = ['Draft', 'Ditolak', 'Ditolak Keuangan'];
        if (!in_array($workOrder->status, $statusAllowed)) {
            return back()->with('error', 'Hanya menu dengan status Draft atau Ditolak yang dapat dihapus.');
        }

        // Hapus juga purchase order terkait jika ada
        if ($workOrder->purchaseOrder) {
            $workOrder->purchaseOrder->items()->delete();
            $workOrder->purchaseOrder->delete();
        }

        $workOrder->items()->delete();
        $workOrder->kelompoks()->delete();
        $workOrder->delete();

        return back()->with('success', 'Work Order berhasil dihapus.');
    }

    /**
     * Helper render view Gizi dengan data lengkap dan activeTab.
     */
    private function renderGiziView(Request $request, string $activeTab, ?string $step = null): Response
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        $kelompokList = [];
        $totalPenerima = 0;
        $totalPorsiKecil = 0;
        $totalPorsiBesar = 0;
        $kategoriCount = [];
        $workOrders = [];
        $activeWorkOrder = null;

        if ($unitSppg) {
            $kelompokList = KelompokPenerimaManfaat::where('unit_sppg_id', $unitSppg->id)
                ->with('rincian')
                ->get();

            $totalPenerima = $kelompokList->sum('total_penerima');
            $totalPorsiKecil = $kelompokList->sum('total_porsi_kecil');
            $totalPorsiBesar = $kelompokList->sum('total_porsi_besar');

            $kategoriCount = $kelompokList->groupBy('kategori')->map(function ($items) {
                return [
                    'count' => $items->count(),
                    'total_penerima' => $items->sum('total_penerima'),
                    'total_porsi_kecil' => $items->sum('total_porsi_kecil'),
                    'total_porsi_besar' => $items->sum('total_porsi_besar'),
                ];
            });

            // Load all Work Orders for this unit
            $workOrders = WorkOrder::where('unit_sppg_id', $unitSppg->id)
                ->with(['items', 'kelompoks', 'purchaseOrder'])
                ->orderBy('tanggal_distribusi', 'desc')
                ->get();

            // Load active work order if requested via query
            if ($request->query('wo_id')) {
                $woQuery = (string) $request->query('wo_id');
                $activeWorkOrder = WorkOrder::where('unit_sppg_id', $unitSppg->id)
                    ->where(function ($q) use ($woQuery) {
                        if (is_numeric($woQuery)) {
                            $q->where('id', (int) $woQuery)
                              ->orWhere('nomor_wo', $woQuery);
                        } else {
                            $q->where('uuid', $woQuery)
                              ->orWhere('nomor_wo', $woQuery);
                        }
                    })
                    ->with(['items', 'kelompoks.kelompok.rincian'])
                    ->first();
            } elseif ($request->query('tanggal')) {
                $activeWorkOrder = WorkOrder::where('unit_sppg_id', $unitSppg->id)
                    ->where('tanggal_distribusi', $request->query('tanggal'))
                    ->with(['items', 'kelompoks.kelompok.rincian'])
                    ->first();
            }
        }

        $ftaData = file_exists(database_path('data/indo.fta')) ? $this->parseFtaData(database_path('data/indo.fta')) : [];
        $csvData = file_exists(database_path('data/tkpi2020.csv')) ? $this->parseCsvData(database_path('data/tkpi2020.csv')) : [];

        $defaultSource = ($activeWorkOrder && !empty($activeWorkOrder->database_pangan)) 
            ? $activeWorkOrder->database_pangan 
            : 'fta';

        $initialTkpiList = ($defaultSource === 'csv' && !empty($csvData)) ? $csvData : (!empty($ftaData) ? $ftaData : $csvData);

        return Inertia::render('Gizi/Index', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
            'defaultSource' => $defaultSource,
            'tkpiList' => $initialTkpiList,
            'tkpiDatasets' => [
                'fta' => $ftaData,
                'csv' => $csvData,
            ],
            'activeTab' => $activeTab,
            'initialStep' => $step,
            'workOrdersList' => $workOrders,
            'activeWorkOrder' => $activeWorkOrder,
            'stats' => [
                'total_kelompok' => count($kelompokList),
                'total_sekolah' => $kelompokList->where('kategori', '!=', 'Posyandu')->count(),
                'total_posyandu' => $kelompokList->where('kategori', 'Posyandu')->count(),
                'total_penerima' => $totalPenerima,
                'total_porsi_kecil' => $totalPorsiKecil,
                'total_porsi_besar' => $totalPorsiBesar,
                'kategori_breakdown' => $kategoriCount,
            ],
        ]);
    }

    /**
     * Membaca dan mem-parsing data resmi TKPI dari database/data/indo.fta (NutriSurvey Indonesian Food Composition Table)
     * dengan fallback ke database/data/tkpi2020.csv jika file .fta tidak ditemukan.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTkpiData(): array
    {
        $ftaPath = database_path('data/indo.fta');
        if (file_exists($ftaPath)) {
            return $this->parseFtaData($ftaPath);
        }

        $csvPath = database_path('data/tkpi2020.csv');
        if (file_exists($csvPath)) {
            return $this->parseCsvData($csvPath);
        }

        return [];
    }

    /**
     * Parse NutriSurvey .fta binary database (1105 Indonesian Food Records).
     *
     * @param string $ftaPath
     * @return array<int, array<string, mixed>>
     */
    private function parseFtaData(string $ftaPath): array
    {
        $data = file_get_contents($ftaPath);
        if ($data === false) {
            return [];
        }

        $recordSize = 1156;
        $total = (int)(strlen($data) / $recordSize);
        $items = [];

        for ($r = 0; $r < $total; $r++) {
            $rec = substr($data, $r * $recordSize, $recordSize);

            $codeLen = ord($rec[0]);
            $code = substr($rec, 1, $codeLen);

            $nameLen = ord($rec[8]);
            $name = trim(substr($rec, 9, $nameLen));
            if ($name === '') {
                continue;
            }

            $energyKj = unpack('f', substr($rec, 210, 4))[1] ?? 0;
            $energy = (!is_nan($energyKj) && $energyKj > 0) ? round($energyKj / 4.184, 1) : 0;

            $protein = unpack('f', substr($rec, 218, 4))[1] ?? 0;
            $protein = (!is_nan($protein) && $protein >= 0) ? round($protein, 1) : 0;

            $fat = unpack('f', substr($rec, 222, 4))[1] ?? 0;
            $fat = (!is_nan($fat) && $fat >= 0) ? round($fat, 1) : 0;

            $carb = unpack('f', substr($rec, 226, 4))[1] ?? 0;
            $carb = (!is_nan($carb) && $carb >= 0) ? round($carb, 1) : 0;

            $fiber = unpack('f', substr($rec, 230, 4))[1] ?? 0;
            $fiber = (!is_nan($fiber) && $fiber >= 0) ? round($fiber, 1) : 0;

            $nameLower = ' ' . strtolower($name) . ' ';
            $kategori = $this->categorizeFtaFood($nameLower);
            $alergen = $this->detectFtaAllergen($nameLower);
            $hargaMaster = $this->estimateFtaPrice($nameLower);

            $items[] = [
                'id' => $code,
                'code' => $code,
                'nama' => ucwords(strtolower($name)),
                'kategori' => $kategori,
                'kategori_raw' => $kategori,
                'energi' => $energy,
                'protein' => $protein,
                'lemak' => $fat,
                'karbohidrat' => $carb,
                'serat' => $fiber,
                'bdd' => 100,
                'fmm' => 100,
                'buffer' => 4,
                'harga_master' => $hargaMaster,
                'alergen' => $alergen,
            ];
        }

        return $items;
    }

    private function categorizeFtaFood(string $nl): string
    {
        if (str_contains($nl, 'beras') || str_contains($nl, 'nasi') || str_contains($nl, 'terigu') || str_contains($nl, 'gandum') || str_contains($nl, 'jagung') || str_contains($nl, 'oat') || str_contains($nl, 'mie') || str_contains($nl, 'bihun') || str_contains($nl, 'sereal') || str_contains($nl, 'roti') || str_contains($nl, 'havermout') || str_contains($nl, 'biskuit') || str_contains($nl, 'makaroni')) {
            return 'Serealia & Hasil Olahannya';
        }
        if (str_contains($nl, 'singkong') || str_contains($nl, 'ubi') || str_contains($nl, 'kentang') || str_contains($nl, 'talas') || str_contains($nl, 'tapioka') || str_contains($nl, 'sagu') || str_contains($nl, 'gaplek')) {
            return 'Umbi-umbian & Olahannya';
        }
        if (str_contains($nl, 'tempe') || str_contains($nl, 'tahu') || str_contains($nl, 'kedelai') || str_contains($nl, 'kacang') || str_contains($nl, 'oncom') || str_contains($nl, 'wijen')) {
            return 'Kacang-kacangan & Olahannya';
        }
        if (str_contains($nl, 'daging sapi') || str_contains($nl, 'daging ayam') || str_contains($nl, 'ayam') || str_contains($nl, 'sapi') || str_contains($nl, 'kambing') || str_contains($nl, 'bebek') || str_contains($nl, 'unggas') || str_contains($nl, 'kornet') || str_contains($nl, 'sosis') || str_contains($nl, 'bakso') || str_contains($nl, 'hati') || str_contains($nl, 'daging')) {
            return 'Daging & Unggas';
        }
        if (str_contains($nl, 'ikan') || str_contains($nl, 'udang') || str_contains($nl, 'cumi') || str_contains($nl, 'kepiting') || str_contains($nl, 'kerang') || preg_match('/\bteri\b/', $nl) || str_contains($nl, 'bandeng') || str_contains($nl, 'tuna') || str_contains($nl, 'tongkol') || str_contains($nl, 'belut')) {
            return 'Ikan & Hasil Laut';
        }
        if (str_contains($nl, 'telur')) {
            return 'Telur';
        }
        if (str_contains($nl, 'susu') || str_contains($nl, 'keju') || str_contains($nl, 'yogurt') || str_contains($nl, 'butter') || str_contains($nl, 'lactogen') || str_contains($nl, 'sgm') || str_contains($nl, 'sustagen')) {
            return 'Susu & Olahannya';
        }
        if (str_contains($nl, 'minyak') || str_contains($nl, 'mentega') || str_contains($nl, 'margarin') || str_contains($nl, 'lemak') || str_contains($nl, 'santan') || str_contains($nl, 'kelapa')) {
            return 'Minyak & Lemak';
        }
        if (str_contains($nl, 'gula') || str_contains($nl, 'madu') || str_contains($nl, 'sirup') || str_contains($nl, 'permen') || str_contains($nl, 'cokelat') || str_contains($nl, 'coklat')) {
            return 'Gula & Manisan';
        }
        if (str_contains($nl, 'bayam') || str_contains($nl, 'kangkung') || str_contains($nl, 'sawi') || str_contains($nl, 'wortel') || str_contains($nl, 'tomat') || str_contains($nl, 'buncis') || str_contains($nl, 'labu') || str_contains($nl, 'terong') || str_contains($nl, 'timun') || str_contains($nl, 'mentimun') || str_contains($nl, 'kacang panjang') || str_contains($nl, 'kubis') || str_contains($nl, 'kol') || str_contains($nl, 'daun') || str_contains($nl, 'jamur') || str_contains($nl, 'sayur') || str_contains($nl, 'tauge') || str_contains($nl, 'togé') || str_contains($nl, 'rebung') || str_contains($nl, 'pare')) {
            return 'Sayuran & Olahan Sayur';
        }
        if (str_contains($nl, 'pisang') || str_contains($nl, 'pepaya') || str_contains($nl, 'jeruk') || str_contains($nl, 'mangga') || str_contains($nl, 'apel') || str_contains($nl, 'semangka') || str_contains($nl, 'melon') || str_contains($nl, 'nanas') || str_contains($nl, 'jambu') || str_contains($nl, 'alpukat') || str_contains($nl, 'anggur') || str_contains($nl, 'durian') || str_contains($nl, 'rambutan') || str_contains($nl, 'buah') || str_contains($nl, 'salak') || str_contains($nl, 'nangka') || str_contains($nl, 'belimbing') || str_contains($nl, 'sawo') || str_contains($nl, 'duku') || str_contains($nl, 'sirsak')) {
            return 'Buah-buahan';
        }
        return 'Makanan Campuran & Olahan';
    }

    private function detectFtaAllergen(string $nl): ?string
    {
        if (str_contains($nl, 'telur')) {
            return 'Telur';
        }
        if (str_contains($nl, 'ikan') || str_contains($nl, 'udang') || str_contains($nl, 'cumi') || str_contains($nl, 'kepiting') || str_contains($nl, 'kerang') || preg_match('/\bteri\b/', $nl)) {
            return 'Seafood/Ikan';
        }
        if (str_contains($nl, 'kedelai') || str_contains($nl, 'tempe') || str_contains($nl, 'tahu')) {
            return 'Kedelai';
        }
        if (str_contains($nl, 'kacang') || str_contains($nl, 'wijen')) {
            return 'Kacang';
        }
        if (str_contains($nl, 'susu') || str_contains($nl, 'keju') || str_contains($nl, 'lactogen') || str_contains($nl, 'sgm')) {
            return 'Laktosa / Susu';
        }
        if (str_contains($nl, 'gandum') || str_contains($nl, 'terigu') || str_contains($nl, 'roti') || str_contains($nl, 'biskuit') || str_contains($nl, 'mie') || str_contains($nl, 'havermout')) {
            return 'Gluten';
        }
        return null;
    }

    private function estimateFtaPrice(string $nl): int
    {
        if (str_contains($nl, 'daging') || str_contains($nl, 'sapi') || str_contains($nl, 'kambing')) {
            return 120000;
        }
        if (str_contains($nl, 'ayam') || str_contains($nl, 'unggas') || str_contains($nl, 'bebek')) {
            return 38000;
        }
        if (str_contains($nl, 'ikan') || str_contains($nl, 'udang') || str_contains($nl, 'cumi') || str_contains($nl, 'kepiting')) {
            return 45000;
        }
        if (str_contains($nl, 'telur')) {
            return 29000;
        }
        if (str_contains($nl, 'tempe') || str_contains($nl, 'tahu')) {
            return 15000;
        }
        if (str_contains($nl, 'beras')) {
            return 16000;
        }
        if (str_contains($nl, 'sayur') || str_contains($nl, 'bayam') || str_contains($nl, 'wortel') || str_contains($nl, 'kangkung') || str_contains($nl, 'buncis')) {
            return 14000;
        }
        if (str_contains($nl, 'pisang') || str_contains($nl, 'buah') || str_contains($nl, 'jeruk') || str_contains($nl, 'pepaya') || str_contains($nl, 'semangka')) {
            return 18000;
        }
        if (str_contains($nl, 'minyak')) {
            return 17500;
        }
        if (str_contains($nl, 'susu') || str_contains($nl, 'keju')) {
            return 28000;
        }
        return 15000;
    }

    private function parseCsvData(string $csvPath): array
    {
        $items = [];
        if (($handle = fopen($csvPath, 'r')) !== false) {
            fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 28) {
                    continue;
                }

                $catRaw = $row[3] ?? 'Lainnya';
                $catClean = preg_replace('/^\d+\.\d+\.\s*/', '', $catRaw);
                $catClean = ucwords(strtolower(trim($catClean)));

                $code = trim($row[0] ?? '');
                $name = trim($row[1] ?? '');
                $energy = (float) ($row[7] ?? 0);
                $protein = (float) ($row[8] ?? 0);
                $fat = (float) ($row[9] ?? 0);
                $carb = (float) ($row[10] ?? 0);
                $fiber = (float) ($row[11] ?? 0);
                $bdd = (float) ($row[27] ?? 100);

                $nameLower = ' ' . strtolower($name) . ' ';
                $hargaMaster = $this->estimateFtaPrice($nameLower);
                $alergen = $this->detectFtaAllergen($nameLower);

                $items[] = [
                    'id' => $code,
                    'code' => $code,
                    'nama' => $name,
                    'kategori' => $catClean ?: 'Lainnya',
                    'kategori_raw' => $catRaw,
                    'energi' => $energy,
                    'protein' => $protein,
                    'lemak' => $fat,
                    'karbohidrat' => $carb,
                    'serat' => $fiber,
                    'bdd' => $bdd > 0 ? $bdd : 100,
                    'fmm' => 100,
                    'buffer' => 4,
                    'harga_master' => $hargaMaster,
                    'alergen' => $alergen,
                ];
            }
            fclose($handle);
        }

        return $items;
    }
}
