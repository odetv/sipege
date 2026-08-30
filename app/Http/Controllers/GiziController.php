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
     * Tampilkan halaman utama gizi (default ke sub menu TKPI).
     */
    public function index(Request $request): Response
    {
        return $this->renderGiziView($request, 'tkpi');
    }

    /**
     * Sub-menu 1: TKPI 2020 (Database Komposisi Pangan Indonesia).
     */
    public function tkpi(Request $request): Response
    {
        return $this->renderGiziView($request, 'tkpi');
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
            'total_anggaran_master' => ['nullable', 'numeric'],
            'items' => ['required', 'array'],
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
            foreach ($validated['items'] as $item) {
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

        return Inertia::render('Gizi/Index', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
            'tkpiList' => $this->getTkpiData(),
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
     * Membaca dan mem-parsing data resmi TKPI 2020 dari file database/data/tkpi2020.csv.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTkpiData(): array
    {
        $csvPath = database_path('data/tkpi2020.csv');
        if (!file_exists($csvPath)) {
            return [];
        }

        $items = [];
        if (($handle = fopen($csvPath, 'r')) !== false) {
            // Read header row
            fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 28) {
                    continue;
                }

                $catRaw = $row[3] ?? 'Lainnya';
                // Bersihkan kode angka seperti "4.1. SEREALIA DAN HASIL OLAHANNYA"
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

                // Estimasi harga master awal per kg berdasarkan jenis bahan
                $hargaMaster = 15000;
                $nameLower = strtolower($name);
                if (str_contains($nameLower, 'daging') || str_contains($nameLower, 'sapi')) {
                    $hargaMaster = 120000;
                } elseif (str_contains($nameLower, 'ayam') || str_contains($nameLower, 'unggas')) {
                    $hargaMaster = 38000;
                } elseif (str_contains($nameLower, 'ikan') || str_contains($nameLower, 'udang') || str_contains($nameLower, 'cumi')) {
                    $hargaMaster = 45000;
                } elseif (str_contains($nameLower, 'telur')) {
                    $hargaMaster = 29000;
                } elseif (str_contains($nameLower, 'tempe') || str_contains($nameLower, 'tahu')) {
                    $hargaMaster = 15000;
                } elseif (str_contains($nameLower, 'beras')) {
                    $hargaMaster = 16000;
                } elseif (str_contains($nameLower, 'sayur') || str_contains($nameLower, 'bayam') || str_contains($nameLower, 'wortel') || str_contains($nameLower, 'kangkung') || str_contains($nameLower, 'buncis')) {
                    $hargaMaster = 14000;
                } elseif (str_contains($nameLower, 'pisang') || str_contains($nameLower, 'buah') || str_contains($nameLower, 'jeruk') || str_contains($nameLower, 'pepaya') || str_contains($nameLower, 'semangka')) {
                    $hargaMaster = 18000;
                } elseif (str_contains($nameLower, 'minyak')) {
                    $hargaMaster = 17500;
                } elseif (str_contains($nameLower, 'susu')) {
                    $hargaMaster = 28000;
                }

                // Deteksi alergen
                $alergen = null;
                if (str_contains($nameLower, 'telur')) {
                    $alergen = 'Telur';
                } elseif (str_contains($nameLower, 'ikan') || str_contains($nameLower, 'udang') || str_contains($nameLower, 'cumi') || str_contains($nameLower, 'kepiting') || str_contains($nameLower, 'kerang')) {
                    $alergen = 'Seafood/Ikan';
                } elseif (str_contains($nameLower, 'kedelai') || str_contains($nameLower, 'tempe') || str_contains($nameLower, 'tahu')) {
                    $alergen = 'Kedelai';
                } elseif (str_contains($nameLower, 'kacang')) {
                    $alergen = 'Kacang';
                } elseif (str_contains($nameLower, 'susu') || str_contains($nameLower, 'keju')) {
                    $alergen = 'Laktosa / Susu';
                } elseif (str_contains($nameLower, 'gandum') || str_contains($nameLower, 'terigu') || str_contains($nameLower, 'roti') || str_contains($nameLower, 'biskuit')) {
                    $alergen = 'Gluten';
                }

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
