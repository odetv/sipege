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
use Illuminate\Support\Facades\Cache;
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
     * Sub-menu 1: Database Pangan (NutriSurvey Indo .fta & Kemenkes .csv).
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
            'sub_menu_1' => ['nullable', 'string'],
            'sub_menu_2' => ['nullable', 'string'],
            'sub_menu_3' => ['nullable', 'string'],
            'sub_menu_4' => ['nullable', 'string'],
            'sub_menu_5' => ['nullable', 'string'],
            'sub_menu_alergi' => ['nullable', 'array'],
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
                    'sub_menu_1' => $validated['sub_menu_1'] ?? $validated['komponen_energi'] ?? null,
                    'sub_menu_2' => $validated['sub_menu_2'] ?? $validated['komponen_protein'] ?? null,
                    'sub_menu_3' => $validated['sub_menu_3'] ?? $validated['komponen_lemak'] ?? null,
                    'sub_menu_4' => $validated['sub_menu_4'] ?? $validated['komponen_karbohidrat'] ?? null,
                    'sub_menu_5' => $validated['sub_menu_5'] ?? $validated['komponen_serat'] ?? null,
                    'sub_menu_alergi' => $validated['sub_menu_alergi'] ?? null,
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
                    'sub_menu_key' => $item['sub_menu_key'] ?? null,
                    'sub_menu_block_id' => $item['sub_menu_block_id'] ?? null,
                    'nama_sub_menu' => $item['nama_sub_menu'] ?? null,
                    'tkpi_id' => $item['tkpi_id'] ?? $item['id'] ?? null,
                    'nama' => $item['nama'] ?? 'Bahan',
                    'nama_po' => $item['nama_po'] ?? $item['nama'] ?? 'Bahan',
                    'kategori' => $item['kategori'] ?? 'Lainnya',
                    'satuan' => $item['satuan'] ?? 'Kg',
                    'jenis' => $item['jenis'] ?? 'bahan_baku',
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
                    'keterangan' => $item['keterangan'] ?? null,
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
            $existingPo = PurchaseOrder::where('work_order_id', $workOrder->id)->first();

            $po = PurchaseOrder::updateOrCreate(
                [
                    'work_order_id' => $workOrder->id,
                ],
                [
                    'unit_sppg_id' => $unitSppg->id,
                    'nomor_po' => $nomorPo,
                    'tanggal' => $validated['tanggal_distribusi'],
                    'supplier_id' => $existingPo ? $existingPo->supplier_id : null,
                    'vendor' => $existingPo ? $existingPo->vendor : null,
                    'jenis_transaksi' => $existingPo ? $existingPo->jenis_transaksi : null,
                    'items_count' => count($validated['items']),
                    'total_nominal_master' => $validated['total_anggaran_master'] ?? 0,
                    'total_nominal_aktual' => $validated['total_anggaran_master'] ?? 0,
                    'status_po' => $poStatus,
                    'status_bayar' => $existingPo ? $existingPo->status_bayar : 'Belum Bayar',
                    'catatan' => 'Dibuat dari ' . $workOrder->nomor_wo . ' - ' . $workOrder->nama_menu,
                    'riwayat_verifikasi' => $existingWoLogs ?? ($existingPo ? $existingPo->riwayat_verifikasi : null),
                ]
            );

            $po->items()->delete();
            $groupedItems = [];
            foreach ($workOrder->items as $woItem) {
                $rawName = $woItem->nama_po ?: $woItem->nama;
                $normName = mb_strtolower(trim($rawName));
                $normSatuan = mb_strtolower(trim($woItem->satuan ?? 'kg'));
                $normJenis = mb_strtolower(trim($woItem->jenis ?? 'bahan_baku'));
                $groupKey = $normName . '|' . $normSatuan . '|' . $normJenis;

                if (!isset($groupedItems[$groupKey])) {
                    $groupedItems[$groupKey] = [
                        'work_order_item_id' => $woItem->id,
                        'nama' => $rawName,
                        'kategori' => $woItem->kategori,
                        'satuan' => $woItem->satuan ?? 'Kg',
                        'jenis' => $woItem->jenis ?? 'bahan_baku',
                        'tipe' => $woItem->tipe_porsi === 'alergi' ? 'Alergi' : 'Normal',
                        'gross_kg' => 0,
                        'stok_digunakan_kg' => 0,
                        'qty_beli_po_kg' => 0,
                        'sumber_pengadaan' => 'Beli PO',
                        'harga_master' => (float) $woItem->harga_master,
                        'harga_aktual' => (float) $woItem->harga_master,
                        'subtotal_aktual' => 0,
                        'keterangan_list' => [],
                    ];
                }

                $gross = (float) $woItem->total_gross_kg;
                $groupedItems[$groupKey]['gross_kg'] += $gross;
                $groupedItems[$groupKey]['qty_beli_po_kg'] += $gross;
                if ($woItem->tipe_porsi === 'alergi') {
                    $groupedItems[$groupKey]['tipe'] = 'Alergi';
                }
                if ($woItem->keterangan && $woItem->keterangan !== '-' && !in_array($woItem->keterangan, $groupedItems[$groupKey]['keterangan_list'])) {
                    $groupedItems[$groupKey]['keterangan_list'][] = $woItem->keterangan;
                }
            }

            foreach ($groupedItems as $gItem) {
                $subtotal = round($gItem['qty_beli_po_kg'] * $gItem['harga_aktual']);
                $po->items()->create([
                    'work_order_item_id' => $gItem['work_order_item_id'],
                    'nama' => $gItem['nama'],
                    'kategori' => $gItem['kategori'],
                    'satuan' => $gItem['satuan'],
                    'jenis' => $gItem['jenis'],
                    'tipe' => $gItem['tipe'],
                    'gross_kg' => round($gItem['gross_kg'], 4),
                    'stok_digunakan_kg' => 0,
                    'qty_beli_po_kg' => round($gItem['qty_beli_po_kg'], 4),
                    'sumber_pengadaan' => 'Beli PO',
                    'harga_master' => $gItem['harga_master'],
                    'harga_aktual' => $gItem['harga_aktual'],
                    'subtotal_aktual' => $subtotal,
                    'keterangan' => !empty($gItem['keterangan_list']) ? implode('; ', $gItem['keterangan_list']) : null,
                ]);
            }

            $po->update([
                'items_count' => count($groupedItems),
                'total_nominal_master' => $po->items()->sum('subtotal_aktual'),
                'total_nominal_aktual' => $po->items()->sum('subtotal_aktual'),
            ]);
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
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        $kelompokList = [];
        $totalPenerima = 0;
        $totalPorsiKecil = 0;
        $totalPorsiBesar = 0;
        $kategoriCount = [];
        $workOrders = [];
        $activeWorkOrder = null;

        if ($unitSppg) {
            $kelompokList = KelompokPenerimaManfaat::getCachedListForUnit($unitSppg->id);

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
                    ->with(['items', 'kelompoks'])
                    ->first();
            } elseif ($request->query('tanggal')) {
                $activeWorkOrder = WorkOrder::where('unit_sppg_id', $unitSppg->id)
                    ->where('tanggal_distribusi', $request->query('tanggal'))
                    ->with(['items', 'kelompoks'])
                    ->first();
            }
        }

        $needsFullTkpi = in_array($activeTab, ['database-pangan', 'tkpi', 'rancang-menu', 'buat-menu']);

        $ftaData = $needsFullTkpi ? Cache::rememberForever('tkpi_fta_data_parsed', function () {
            return file_exists(database_path('data/indo.fta')) ? $this->parseFtaData(database_path('data/indo.fta')) : [];
        }) : [];

        $csvData = $needsFullTkpi ? Cache::rememberForever('tkpi_csv_data_parsed', function () {
            return file_exists(database_path('data/tkpi2020.csv')) ? $this->parseCsvData(database_path('data/tkpi2020.csv')) : [];
        }) : [];

        $defaultSource = ($activeWorkOrder && !empty($activeWorkOrder->database_pangan))
            ? $activeWorkOrder->database_pangan
            : 'csv';

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
        $tkpiBddMap = $this->getTkpiBddLookup();

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

            $unpackFloat = function (int $offset) use ($rec): ?float {
                $v = unpack('f', substr($rec, $offset, 4))[1] ?? null;
                if ($v === null || is_nan($v) || $v < 0 || $v > 500000) {
                    return null;
                }
                return round($v, 2);
            };

            $thiamin = $unpackFloat(274);
            $riboflavin = $unpackFloat(278);
            $niasin = $unpackFloat(282);
            $vitaminC = $unpackFloat(306);
            $sodium = $unpackFloat(318);
            $potassium = $unpackFloat(322);
            $calcium = $unpackFloat(326);
            $phosphorus = $unpackFloat(334);
            $iron = $unpackFloat(338);
            $zinc = $unpackFloat(350);
            $copper = $unpackFloat(354);

            $nameLower = ' ' . strtolower($name) . ' ';
            $kategori = $this->categorizeFtaFood($nameLower);
            $alergen = $this->detectFtaAllergen($nameLower);
            $bdd = $this->resolveFtaBdd($name, $tkpiBddMap);

            $items[] = [
                'id' => $code,
                'code' => $code,
                'nama' => ucwords(strtolower($name)),
                'kategori' => $kategori,
                'kategori_raw' => $kategori,
                'sumber' => 'Nutri Survey (.fta)',
                'air' => null,
                'energi' => $energy,
                'protein' => $protein,
                'lemak' => $fat,
                'karbohidrat' => $carb,
                'serat' => $fiber,
                'abu' => null,
                'kalsium' => $calcium,
                'fosfor' => $phosphorus,
                'besi' => $iron,
                'natrium' => $sodium,
                'kalium' => $potassium,
                'tembaga' => $copper,
                'seng' => $zinc,
                'retinol' => null,
                'b_karoten' => null,
                'karoten_total' => null,
                'tiamin' => $thiamin,
                'riboflavin' => $riboflavin,
                'niasin' => $niasin,
                'vitamin_c' => $vitaminC,
                'bdd' => $bdd,
                'fmm' => 100,
                'buffer' => 4,
                'harga_master' => null,
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

    private function matchAllergenKeywords(string $text, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            $kw = trim($kw);
            if ($kw === '') {
                continue;
            }
            $escaped = preg_quote($kw, '/');
            if (preg_match('/(?<![a-zA-Z0-9])' . $escaped . '(?![a-zA-Z0-9])/i', $text)) {
                return true;
            }
        }
        return false;
    }

    private function detectFtaAllergen(string $nl): ?string
    {
        if ($this->matchAllergenKeywords($nl, ['telur', 'egg', 'dadar', 'ceplok', 'omelet', 'mayones', 'mayonnaise', 'telur puyuh', 'telur bebek', 'telur asin', 'telor'])) {
            return 'Telur';
        }
        if ($this->matchAllergenKeywords($nl, ['udang', 'shrimp', 'prawn', 'ebi', 'rebon'])) {
            return 'Udang';
        }
        if ($this->matchAllergenKeywords($nl, ['kepiting', 'crab', 'rajungan', 'lobster'])) {
            return 'Kepiting';
        }
        if ($this->matchAllergenKeywords($nl, ['cumi', 'cumi-cumi', 'squid', 'sotong', 'gurita', 'octopus'])) {
            return 'Cumi-cumi';
        }
        if ($this->matchAllergenKeywords($nl, ['kerang', 'clam', 'mussel', 'scallop', 'tiram', 'remis', 'kupang'])) {
            return 'Kerang';
        }
        if ($this->matchAllergenKeywords($nl, ['ikan', 'fish', 'tuna', 'tongkol', 'bandeng', 'teri', 'belut', 'lele', 'gurame', 'gurami', 'nila', 'kakap', 'tenggiri', 'kembung', 'pindang', 'dori', 'salmon', 'patin', 'bawal', 'cakalang', 'mujair', 'ikan mas', 'gabus'])) {
            return 'Ikan';
        }
        if ($this->matchAllergenKeywords($nl, ['susu', 'milk', 'dairy', 'laktosa', 'yogurt', 'yoghurt', 'butter', 'mentega', 'krim', 'cream', 'lactogen', 'sgm'])) {
            return 'Susu dan produk olahannya';
        }
        if ($this->matchAllergenKeywords($nl, ['keju', 'cheese', 'cheddar', 'mozzarella', 'parmesan'])) {
            return 'Keju';
        }
        if ($this->matchAllergenKeywords($nl, ['kacang tanah', 'peanut', 'bumbu kacang', 'pecel', 'gado-gado', 'saus kacang'])) {
            return 'Kacang Tanah';
        }
        if ($this->matchAllergenKeywords($nl, ['kacang kedelai', 'soybean', 'biji kedelai'])) {
            return 'Kacang Kedelai';
        }
        if ($this->matchAllergenKeywords($nl, ['tahu', 'tofu'])) {
            return 'Tahu';
        }
        if ($this->matchAllergenKeywords($nl, ['tempe', 'tempeh'])) {
            return 'Tempe';
        }
        if ($this->matchAllergenKeywords($nl, ['kedelai', 'soy', 'soya', 'tauco', 'kecap', 'edamame'])) {
            return 'Kedelai';
        }
        if ($this->matchAllergenKeywords($nl, ['almond', 'badam', 'kacang almond'])) {
            return 'Kacang Almond';
        }
        if ($this->matchAllergenKeywords($nl, ['mete', 'mede', 'cashew', 'kacang mete', 'kacang mede'])) {
            return 'Kacang Mete';
        }
        if ($this->matchAllergenKeywords($nl, ['hazelnut', 'kacang hazelnut'])) {
            return 'Kacang Hazelnut';
        }
        if ($this->matchAllergenKeywords($nl, ['kenari', 'walnut', 'kacang kenari'])) {
            return 'Kacang Kenari';
        }
        if ($this->matchAllergenKeywords($nl, ['kacang merah', 'kacang hijau', 'kacang polong', 'kacang tolo', 'pistachio', 'macadamia', 'kacang'])) {
            return 'Kacang-kacangan lainnya';
        }
        if ($this->matchAllergenKeywords($nl, ['gandum', 'terigu', 'roti', 'biskuit', 'mie', 'bakmi', 'pasta', 'spageti', 'spaghetti', 'makaroni', 'tepung terigu'])) {
            return 'Gandum/Tepung Terigu';
        }
        if ($this->matchAllergenKeywords($nl, ['gluten', 'seitan'])) {
            return 'Gluten';
        }
        if ($this->matchAllergenKeywords($nl, ['wijen', 'sesame', 'tahini', 'biji wijen'])) {
            return 'Wijen';
        }
        if ($this->matchAllergenKeywords($nl, ['ayam', 'chicken', 'bebek', 'unggas', 'kalkun', 'dada ayam', 'paha ayam', 'fillet ayam'])) {
            return 'Daging Ayam';
        }
        if ($this->matchAllergenKeywords($nl, ['sapi', 'beef', 'kornet', 'rendang', 'rawon', 'empal', 'iga sapi', 'buntut sapi', 'daging sapi'])) {
            return 'Daging Sapi';
        }
        if ($this->matchAllergenKeywords($nl, ['cokelat', 'coklat', 'kakao', 'cocoa', 'chocolate'])) {
            return 'Cokelat/Kakao';
        }
        if ($this->matchAllergenKeywords($nl, ['madu', 'honey'])) {
            return 'Madu';
        }
        if ($this->matchAllergenKeywords($nl, ['nanas', 'nenas', 'pineapple'])) {
            return 'Nanas';
        }
        if ($this->matchAllergenKeywords($nl, ['tomat', 'tomato'])) {
            return 'Tomat';
        }
        if ($this->matchAllergenKeywords($nl, ['jagung', 'corn', 'maizena'])) {
            return 'Jagung';
        }
        if ($this->matchAllergenKeywords($nl, ['kentang', 'potato'])) {
            return 'Kentang';
        }
        if ($this->matchAllergenKeywords($nl, ['wortel', 'carrot'])) {
            return 'Wortel';
        }
        if ($this->matchAllergenKeywords($nl, ['stroberi', 'strawberry'])) {
            return 'Stroberi';
        }
        if ($this->matchAllergenKeywords($nl, ['mangga', 'mango'])) {
            return 'Mangga';
        }
        if ($this->matchAllergenKeywords($nl, ['melon', 'cantaloupe', 'honeydew'])) {
            return 'Melon';
        }
        if ($this->matchAllergenKeywords($nl, ['pisang', 'banana'])) {
            return 'Pisang';
        }
        if ($this->matchAllergenKeywords($nl, ['alpukat', 'avokad', 'avocado'])) {
            return 'Alpukat';
        }
        if ($this->matchAllergenKeywords($nl, ['jeruk', 'orange', 'citrus', 'lemon', 'mandarin'])) {
            return 'Jeruk';
        }
        if ($this->matchAllergenKeywords($nl, ['buah naga', 'dragon fruit'])) {
            return 'Buah Naga';
        }
        return null;
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

                $parseNum = function ($val): ?float {
                    $v = trim((string) $val);
                    if ($v === '' || $v === '-' || !is_numeric($v)) {
                        return null;
                    }
                    return round((float) $v, 2);
                };

                $code = trim($row[0] ?? '');
                $name = trim($row[1] ?? '');
                $source = trim($row[2] ?? '');
                $air = $parseNum($row[6] ?? null);
                $energy = (float) ($row[7] ?? 0);
                $protein = (float) ($row[8] ?? 0);
                $fat = (float) ($row[9] ?? 0);
                $carb = (float) ($row[10] ?? 0);
                $fiber = $parseNum($row[11] ?? null);
                $ash = $parseNum($row[12] ?? null);
                $calcium = $parseNum($row[13] ?? null);
                $phosphorus = $parseNum($row[14] ?? null);
                $iron = $parseNum($row[15] ?? null);
                $sodium = $parseNum($row[16] ?? null);
                $potassium = $parseNum($row[17] ?? null);
                $copper = $parseNum($row[18] ?? null);
                $zinc = $parseNum($row[19] ?? null);
                $retinol = $parseNum($row[20] ?? null);
                $betaCarotene = $parseNum($row[21] ?? null);
                $caroteneTotal = $parseNum($row[22] ?? null);
                $thiamin = $parseNum($row[23] ?? null);
                $riboflavin = $parseNum($row[24] ?? null);
                $niacin = $parseNum($row[25] ?? null);
                $vitaminC = $parseNum($row[26] ?? null);
                $bdd = (float) ($row[27] ?? 100);

                $nameLower = ' ' . strtolower($name) . ' ';
                $alergen = $this->detectFtaAllergen($nameLower);

                $items[] = [
                    'id' => $code,
                    'code' => $code,
                    'nama' => $name,
                    'kategori' => $catClean ?: 'Lainnya',
                    'kategori_raw' => $catRaw,
                    'sumber' => $source ?: 'Kemenkes (.csv)',
                    'air' => $air,
                    'energi' => $energy,
                    'protein' => $protein,
                    'lemak' => $fat,
                    'karbohidrat' => $carb,
                    'serat' => $fiber,
                    'abu' => $ash,
                    'kalsium' => $calcium,
                    'fosfor' => $phosphorus,
                    'besi' => $iron,
                    'natrium' => $sodium,
                    'kalium' => $potassium,
                    'tembaga' => $copper,
                    'seng' => $zinc,
                    'retinol' => $retinol,
                    'b_karoten' => $betaCarotene,
                    'karoten_total' => $caroteneTotal,
                    'tiamin' => $thiamin,
                    'riboflavin' => $riboflavin,
                    'niasin' => $niacin,
                    'vitamin_c' => $vitaminC,
                    'bdd' => $bdd > 0 ? $bdd : 100,
                    'fmm' => 100,
                    'buffer' => 4,
                    'harga_master' => null,
                    'alergen' => $alergen,
                ];
            }
            fclose($handle);
        }

        return $items;
    }

    /**
     * Membaca tabel lookup BDD resmi dari file TKPI 2020 CSV (Kemenkes RI).
     *
     * @return array<string, float>
     */
    private function getTkpiBddLookup(): array
    {
        $csvPath = database_path('data/tkpi2020.csv');
        if (!file_exists($csvPath)) {
            return [];
        }

        $lookup = [];
        if (($handle = fopen($csvPath, 'r')) !== false) {
            $header = fgetcsv($handle);
            $bddIdx = array_search('bdd_percent', $header);
            $nameIdx = array_search('name', $header);

            if ($bddIdx !== false && $nameIdx !== false) {
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) <= $bddIdx) {
                        continue;
                    }
                    $name = trim($row[$nameIdx] ?? '');
                    $bdd = (float) ($row[$bddIdx] ?? 100);
                    $clean = strtolower((string) preg_replace('/[^a-z0-9]/', '', $name));
                    if ($clean !== '') {
                        $lookup[$clean] = $bdd > 0 ? $bdd : 100;
                    }
                }
            }
            fclose($handle);
        }

        return $lookup;
    }

    /**
     * Menentukan nilai BDD (Berat Dapat Dimakan / Edible Portion %) resmi untuk bahan pangan FTA.
     * Menggunakan matching database resmi TKPI Kemenkes dan standar DKBM Indonesia.
     *
     * @param string $rawName
     * @param array<string, float> $tkpiMap
     * @return float
     */
    private function resolveFtaBdd(string $rawName, array $tkpiMap): float
    {
        $name = strtolower(trim($rawName));
        $clean = strtolower((string) preg_replace('/[^a-z0-9]/', '', $name));

        // 1. Exact match di TKPI
        if (isset($tkpiMap[$clean])) {
            return $tkpiMap[$clean];
        }

        // 2. Olahan matang / bubuk / tepung / minyak / kecap / susu cair / bumbu instan / nasi = BDD 100%
        if (preg_match('/\b(tepung|goreng|rebus|kukus|panggang|bakar|asin|kering|bubuk|saus|kecap|sirup|jus|susu|minyak|mentega|margarin|gula|nasi|bubur|kerupuk|krupuk|biskuit|kue|mie|bihun|soun|roti|dodol|selai|olahan|tim|tumis|bacem|gulai|semur|rendang|opor|sup|sop|soto|abon|dendeng|sosis|nugget|bakso|siomay|pempek|tahu|tempe|oncom)\b/i', $name)) {
            return 100;
        }

        // 3. Substring match pada database resmi TKPI Kemenkes
        foreach ($tkpiMap as $tName => $tBdd) {
            if ($tBdd < 100 && (str_contains($clean, $tName) || str_contains($tName, $clean))) {
                return $tBdd;
            }
        }

        // 4. Standar Resmi DKBM / TKPI Kemenkes untuk Bahan Baku Mentah Segar
        // Telur utuh berkulit cangkang
        if (preg_match('/\btelur\b/i', $name)) {
            if (preg_match('/(putih|kuning)/i', $name)) return 100;
            if (preg_match('/(bebek|itik|puyuh)/i', $name)) return 90;
            return 89; // Telur ayam ras / kampung utuh segar (89%)
        }

        // Daging Unggas (Ayam, Bebek, Burung) mentah utuh
        if (preg_match('/\b(ayam|bebek|itik|burung dara|burung puyuh)\b/i', $name)) {
            if (preg_match('/(fillet|dada tanpa tulang|hati|ampela|rempelo|jantung|usus|otak|darah)/i', $name)) return 100;
            return 58; // Daging ayam mentah segar (TKPI resmi 58%)
        }

        // Daging Sapi / Kambing
        if (preg_match('/\b(sapi|kambing|domba|kerbau)\b/i', $name)) {
            if (preg_match('/(iga|buntut|tulang|kaki|tetelan)/i', $name)) return 70;
            return 100;
        }

        // Ikan & Seafood mentah segar
        if (preg_match('/\b(ikan|bandeng|tongkol|tenggiri|lele|mas|nila|gurame|kakap|kembung|mujair|patin|belut|gabus|bawal|teri segar)\b/i', $name)) {
            if (preg_match('/(fillet|giling|asin|kering|kaleng|sarden)/i', $name)) return 100;
            if (preg_match('/belut/i', $name)) return 84;
            if (preg_match('/teri/i', $name)) return 100;
            return 80; // Standar rata-rata BDD ikan segar TKPI 80%
        }
        if (preg_match('/\b(udang)\b/i', $name)) {
            if (preg_match('/kering|rebon|tanpa kulit|kupas/i', $name)) return 100;
            return 68; // Udang segar berkulit (68%)
        }
        if (preg_match('/\b(cumi|kepiting|rajungan|kerang)\b/i', $name)) {
            if (preg_match('/kepiting|rajungan/i', $name)) return 45;
            if (preg_match('/kerang/i', $name)) return 20;
            return 80; // Cumi-cumi segar
        }

        // Buah-buahan segar
        if (preg_match('/\b(pisang)\b/i', $name)) {
            if (preg_match('/(ambon|raja|barangan|susu|kepok)/i', $name)) return 75;
            if (preg_match('/mas/i', $name)) return 85;
            return 75;
        }
        if (preg_match('/\b(semangka)\b/i', $name)) return 46;
        if (preg_match('/\b(melon)\b/i', $name)) return 58;
        if (preg_match('/\b(pepaya)\b/i', $name)) return 75;
        if (preg_match('/\b(jeruk)\b/i', $name)) {
            if (preg_match('/bali/i', $name)) return 62;
            if (preg_match('/nipis|purut/i', $name)) return 76;
            return 72;
        }
        if (preg_match('/\b(mangga)\b/i', $name)) return 65;
        if (preg_match('/\b(nenas|nanas)\b/i', $name)) return 53;
        if (preg_match('/\b(apel)\b/i', $name)) return 88;
        if (preg_match('/\b(pir|pear)\b/i', $name)) return 88;
        if (preg_match('/\b(jambu biji|jambu merah|jambu klutuk)\b/i', $name)) return 82;
        if (preg_match('/\b(jambu air)\b/i', $name)) return 90;
        if (preg_match('/\b(alpukat|avokad)\b/i', $name)) return 61;
        if (preg_match('/\b(salak)\b/i', $name)) return 52;
        if (preg_match('/\b(rambutan)\b/i', $name)) return 40;
        if (preg_match('/\b(kelengkeng|lengkeng)\b/i', $name)) return 60;
        if (preg_match('/\b(durian)\b/i', $name)) return 22;
        if (preg_match('/\b(sirsak)\b/i', $name)) return 68;
        if (preg_match('/\b(sawo)\b/i', $name)) return 77;
        if (preg_match('/\b(kedondong)\b/i', $name)) return 58;
        if (preg_match('/\b(belimbing)\b/i', $name)) return 86;
        if (preg_match('/\b(manggis)\b/i', $name)) return 29;
        if (preg_match('/\b(nangka masak|nangka matang)\b/i', $name)) return 28;
        if (preg_match('/\b(kelapa tua|kelapa muda|kelapa setengah tua)\b/i', $name)) return 53;

        // Sayuran segar mentah
        if (preg_match('/\b(bayam)\b/i', $name)) return 71;
        if (preg_match('/\b(kangkung)\b/i', $name)) return 70;
        if (preg_match('/\b(daun singkong|daun ubi)\b/i', $name)) return 87;
        if (preg_match('/\b(daun kelor)\b/i', $name)) return 65;
        if (preg_match('/\b(daun katuk)\b/i', $name)) return 69;
        if (preg_match('/\b(daun pepaya)\b/i', $name)) return 71;
        if (preg_match('/\b(daun bawang)\b/i', $name)) return 67;
        if (preg_match('/\b(wortel)\b/i', $name)) return 88;
        if (preg_match('/\b(buncis)\b/i', $name)) return 90;
        if (preg_match('/\b(kacang panjang)\b/i', $name)) return 92;
        if (preg_match('/\b(terong|terung)\b/i', $name)) return 90;
        if (preg_match('/\b(labu siam|jepang)\b/i', $name)) return 83;
        if (preg_match('/\b(labu kuning|waluh)\b/i', $name)) return 77;
        if (preg_match('/\b(labu air)\b/i', $name)) return 70;
        if (preg_match('/\b(tomat)\b/i', $name)) return 95;
        if (preg_match('/\b(mentimun|timun)\b/i', $name)) return 70;
        if (preg_match('/\b(sawi|caisim|pakcoy|pokcoy)\b/i', $name)) return 87;
        if (preg_match('/\b(kembang kol|bunga kol)\b/i', $name)) return 57;
        if (preg_match('/\b(brokoli)\b/i', $name)) return 60;
        if (preg_match('/\b(kubis|kol)\b/i', $name)) return 75;
        if (preg_match('/\b(pare|paria)\b/i', $name)) return 75;
        if (preg_match('/\b(gambas|oyong)\b/i', $name)) return 85;
        if (preg_match('/\b(jantung pisang)\b/i', $name)) return 25;
        if (preg_match('/\b(rebung)\b/i', $name)) return 42;
        if (preg_match('/\b(nangka muda|gori)\b/i', $name)) return 80;
        if (preg_match('/\b(jamur)\b/i', $name)) return 90;

        // Umbi-umbian mentah
        if (preg_match('/\b(singkong|ubi kayu)\b/i', $name)) return 75;
        if (preg_match('/\b(kentang)\b/i', $name)) return 85;
        if (preg_match('/\b(ubi jalar|ubi manis)\b/i', $name)) return 85;
        if (preg_match('/\b(talas)\b/i', $name)) return 90;
        if (preg_match('/\b(gadung|ganyong|gembili|garut)\b/i', $name)) return 80;

        // Bumbu dapur mentah
        if (preg_match('/\b(bawang merah|bawang putih|bawang bombay)\b/i', $name)) return 90;
        if (preg_match('/\b(cabai|cabe)\b/i', $name)) return 90;
        if (preg_match('/\b(jahe|kunyit|lengkuas|kencur|temulawak)\b/i', $name)) return 85;

        // Kacang-kacangan berkulit
        if (preg_match('/\b(petai|pete)\b/i', $name)) return 36;
        if (preg_match('/\b(jengkol)\b/i', $name)) return 58;
        if (preg_match('/\b(kacang tanah muda|kacang tanah kulit)\b/i', $name)) return 43;

        return 100;
    }
}
