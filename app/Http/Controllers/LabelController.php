<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
use App\Models\Periode;
use App\Models\SavedLabel;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LabelController extends Controller
{
    /**
     * Helper data loader for Label pages.
     */
    private function getLabelPageData(Request $request, string $activeSubMenu = 'buat'): array
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        $kelompokList = [];
        $workOrders = [];
        $savedLabels = [];
        $todayStr = date('Y-m-d');
        $activeWorkOrder = null;

        if ($unitSppg) {
            $kelompokList = KelompokPenerimaManfaat::getCachedListForUnit($unitSppg->id);

            $workOrders = WorkOrder::where('unit_sppg_id', $unitSppg->id)
                ->with(['items', 'kelompoks', 'purchaseOrder'])
                ->orderBy('tanggal_distribusi', 'desc')
                ->get()
                ->map(function ($wo) {
                    return [
                        'id' => $wo->nomor_wo,
                        'db_id' => $wo->id,
                        'uuid' => $wo->uuid,
                        'nama' => $wo->nama_menu,
                        'tanggal' => $wo->tanggal_distribusi ? (is_string($wo->tanggal_distribusi) ? substr($wo->tanggal_distribusi, 0, 10) : $wo->tanggal_distribusi->format('Y-m-d')) : null,
                        'status' => $wo->status,
                        'current_step' => (int)($wo->current_step ?: 1),
                        'total_porsi' => (int)$wo->total_pm,
                        'porsi_pk' => (int)$wo->total_pk,
                        'porsi_pb' => (int)$wo->total_pb,
                        'total_alergi' => (int)($wo->total_alergi ?: 0),
                        'sub_menu_1' => $wo->sub_menu_1,
                        'sub_menu_2' => $wo->sub_menu_2,
                        'sub_menu_3' => $wo->sub_menu_3,
                        'sub_menu_4' => $wo->sub_menu_4,
                        'sub_menu_5' => $wo->sub_menu_5,
                        'sub_menu_alergi' => $wo->sub_menu_alergi,
                        'komponen' => array_values(array_filter([
                            $wo->sub_menu_1 ?? $wo->komponen_energi,
                            $wo->sub_menu_2 ?? $wo->komponen_protein,
                            $wo->sub_menu_3 ?? $wo->komponen_lemak,
                            $wo->sub_menu_4 ?? $wo->komponen_karbohidrat,
                            $wo->sub_menu_5 ?? $wo->komponen_serat,
                        ])),
                        'akg_pk' => $wo->akg_pk,
                        'akg_pb' => $wo->akg_pb,
                        'akg_alergi' => $this->calculateAkgAlergiForWo($wo),
                        'food_cost_pk' => (float)$wo->food_cost_pk,
                        'food_cost_pb' => (float)$wo->food_cost_pb,
                        'items' => $wo->items->map(function ($it) {
                            $hargaMaster = (float)($it->harga_master ?: 0);
                            $gramPk = (float)($it->gram_pk ?: 0);
                            $gramPb = (float)($it->gram_pb ?: 0);
                            $costPk = $hargaMaster > 0 && $gramPk > 0 ? round(($gramPk / 1000) * $hargaMaster) : 0;
                            $costPb = $hargaMaster > 0 && $gramPb > 0 ? round(($gramPb / 1000) * $hargaMaster) : 0;
                            return [
                                'id' => $it->id,
                                'nama' => $it->nama,
                                'kategori' => $it->kategori,
                                'tipe_porsi' => $it->tipe_porsi,
                                'jenis_alergi' => $it->jenis_alergi,
                                'alergen' => $it->alergen,
                                'sub_menu_key' => $it->sub_menu_key,
                                'gram_pk' => $gramPk,
                                'gram_pb' => $gramPb,
                                'harga_master' => $hargaMaster,
                                'cost_pk' => $costPk,
                                'cost_pb' => $costPb,
                                'nutrisi_pk' => $it->nutrisi_pk,
                                'nutrisi_pb' => $it->nutrisi_pb,
                            ];
                        }),
                        'kelompoks' => $wo->kelompoks->map(function ($wk) {
                            return [
                                'id' => $wk->kelompok_id ?: $wk->id,
                                'kelompok_id' => $wk->kelompok_id,
                                'nama_kelompok' => $wk->nama_kelompok,
                                'kategori' => $wk->kategori,
                                'is_menerima' => $wk->is_menerima !== false,
                                'total_porsi_kecil' => (int)$wk->porsi_kecil,
                                'total_porsi_besar' => (int)$wk->porsi_besar,
                                'total_penerima' => (int)$wk->total_penerima,
                                'status_alergi' => $wk->status_alergi,
                                'rincian' => $wk->rincian,
                                'detail_alergi' => $wk->detail_alergi,
                            ];
                        }),
                        'po' => $wo->purchaseOrder ? [
                            'id' => $wo->purchaseOrder->nomor_po,
                            'status_po' => $wo->purchaseOrder->status_po,
                        ] : null,
                    ];
                });

            // Find WO for today or requested wo_id
            $targetWoId = $request->query('wo_id');
            if ($targetWoId) {
                $activeWorkOrder = $workOrders->firstWhere('id', $targetWoId) ?: $workOrders->firstWhere('uuid', $targetWoId);
            }
            if (!$activeWorkOrder) {
                $activeWorkOrder = $workOrders->firstWhere('tanggal', $todayStr) ?: $workOrders->first();
            }

            // Ambil saved labels
            $savedLabels = SavedLabel::where('unit_sppg_id', $unitSppg->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

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

        return [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
            'workOrders' => $workOrders,
            'initialActiveWo' => $activeWorkOrder,
            'savedLabels' => $savedLabels,
            'periodes' => $periodes,
            'activeSubMenu' => $request->query('tab') ?: $activeSubMenu,
            'editLabelId' => $request->query('edit_id'),
        ];
    }

    /**
     * Tampilkan halaman generator label (Default: Buat Label).
     */
    public function index(Request $request): Response
    {
        $tab = $request->query('tab', 'buat');
        return Inertia::render('Label/Index', $this->getLabelPageData($request, $tab));
    }

    /**
     * Submenu: Buat Label
     */
    public function buat(Request $request): Response
    {
        return Inertia::render('Label/Index', $this->getLabelPageData($request, 'buat'));
    }

    /**
     * Submenu: Daftar Label
     */
    public function daftar(Request $request): Response
    {
        return Inertia::render('Label/Index', $this->getLabelPageData($request, 'daftar'));
    }

    /**
     * Simpan label baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return redirect()->back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $validated = $request->validate([
            'work_order_id' => 'nullable',
            'nama_menu' => 'required|string|max:255',
            'tanggal_produksi' => 'required|date',
            'jam_produksi' => 'nullable|string|max:20',
            'batas_konsumsi' => 'nullable|string|max:20',
            'petunjuk_menu' => 'nullable|string',
            'template_id' => 'nullable|string|max:100',
            'template_name' => 'nullable|string|max:255',
            'aspect_ratio' => 'nullable|string|max:20',
            'gizi_data' => 'nullable|array',
            'selected_kelompok_ids' => 'nullable|array',
            'kelompoks_snapshot' => 'nullable|array',
            'total_sasaran' => 'nullable|integer',
            'total_porsi' => 'nullable|integer',
            'total_pk' => 'nullable|integer',
            'total_pb' => 'nullable|integer',
            'keterangan' => 'nullable|string',
        ]);

        // Generate Nomor Label unik misal LBL-20260909-001
        $dateCode = date('Ymd', strtotime($validated['tanggal_produksi']));
        $countToday = SavedLabel::where('unit_sppg_id', $unitSppg->id)
            ->whereDate('tanggal_produksi', $validated['tanggal_produksi'])
            ->count();
        $nomorLabel = sprintf('LBL-%s-%03d', $dateCode, $countToday + 1);

        // Jika work_order_id berupa string nomor_wo, id numerik, atau uuid
        $woId = null;
        if (!empty($validated['work_order_id'])) {
            $woVal = (string)$validated['work_order_id'];
            if (is_numeric($woVal)) {
                $foundWo = WorkOrder::where('unit_sppg_id', $unitSppg->id)
                    ->where('id', (int)$woVal)
                    ->first();
                $woId = $foundWo?->id;
            } elseif (\Illuminate\Support\Str::isUuid($woVal)) {
                $foundWo = WorkOrder::where('unit_sppg_id', $unitSppg->id)
                    ->where('uuid', $woVal)
                    ->first();
                $woId = $foundWo?->id;
            } else {
                $foundWo = WorkOrder::where('unit_sppg_id', $unitSppg->id)
                    ->where('nomor_wo', $woVal)
                    ->first();
                $woId = $foundWo?->id;
            }
        }

        SavedLabel::create([
            'nomor_label' => $nomorLabel,
            'unit_sppg_id' => $unitSppg->id,
            'work_order_id' => $woId,
            'nama_menu' => $validated['nama_menu'],
            'tanggal_produksi' => $validated['tanggal_produksi'],
            'jam_produksi' => $validated['jam_produksi'] ?? '07:00',
            'batas_konsumsi' => $validated['batas_konsumsi'] ?? '09:00',
            'petunjuk_menu' => $validated['petunjuk_menu'] ?? null,
            'template_id' => $validated['template_id'] ?? 'bgn_standard_fixed_white',
            'template_name' => $validated['template_name'] ?? 'Standar Resmi BGN (Putih)',
            'aspect_ratio' => $validated['aspect_ratio'] ?? '3:2',
            'gizi_data' => $validated['gizi_data'] ?? [],
            'selected_kelompok_ids' => $validated['selected_kelompok_ids'] ?? [],
            'kelompoks_snapshot' => $validated['kelompoks_snapshot'] ?? [],
            'total_sasaran' => (int)($validated['total_sasaran'] ?? 0),
            'total_porsi' => (int)($validated['total_porsi'] ?? 0),
            'total_pk' => (int)($validated['total_pk'] ?? 0),
            'total_pb' => (int)($validated['total_pb'] ?? 0),
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        return redirect()->route('label.daftar')->with('success', "Label {$nomorLabel} berhasil disimpan ke Daftar Label.");
    }

    /**
     * Perbarui label yang sudah ada.
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return redirect()->back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $savedLabel = SavedLabel::where('unit_sppg_id', $unitSppg->id)
            ->where(function ($q) use ($id) {
                if (is_numeric($id)) {
                    $q->where('id', $id);
                } else {
                    $q->where('nomor_label', $id);
                }
            })
            ->firstOrFail();

        $validated = $request->validate([
            'nama_menu' => 'required|string|max:255',
            'tanggal_produksi' => 'required|date',
            'jam_produksi' => 'nullable|string|max:20',
            'batas_konsumsi' => 'nullable|string|max:20',
            'petunjuk_menu' => 'nullable|string',
            'template_id' => 'nullable|string|max:100',
            'template_name' => 'nullable|string|max:255',
            'aspect_ratio' => 'nullable|string|max:20',
            'gizi_data' => 'nullable|array',
            'selected_kelompok_ids' => 'nullable|array',
            'kelompoks_snapshot' => 'nullable|array',
            'total_sasaran' => 'nullable|integer',
            'total_porsi' => 'nullable|integer',
            'total_pk' => 'nullable|integer',
            'total_pb' => 'nullable|integer',
            'keterangan' => 'nullable|string',
        ]);

        $savedLabel->update([
            'nama_menu' => $validated['nama_menu'],
            'tanggal_produksi' => $validated['tanggal_produksi'],
            'jam_produksi' => $validated['jam_produksi'] ?? ($savedLabel->jam_produksi ?: '07:00'),
            'batas_konsumsi' => $validated['batas_konsumsi'] ?? ($savedLabel->batas_konsumsi ?: '09:00'),
            'petunjuk_menu' => $validated['petunjuk_menu'] ?? $savedLabel->petunjuk_menu,
            'template_id' => $validated['template_id'] ?? $savedLabel->template_id,
            'template_name' => $validated['template_name'] ?? $savedLabel->template_name,
            'aspect_ratio' => $validated['aspect_ratio'] ?? $savedLabel->aspect_ratio,
            'gizi_data' => $validated['gizi_data'] ?? $savedLabel->gizi_data,
            'selected_kelompok_ids' => $validated['selected_kelompok_ids'] ?? $savedLabel->selected_kelompok_ids,
            'kelompoks_snapshot' => $validated['kelompoks_snapshot'] ?? $savedLabel->kelompoks_snapshot,
            'total_sasaran' => (int)($validated['total_sasaran'] ?? $savedLabel->total_sasaran),
            'total_porsi' => (int)($validated['total_porsi'] ?? $savedLabel->total_porsi),
            'total_pk' => (int)($validated['total_pk'] ?? $savedLabel->total_pk),
            'total_pb' => (int)($validated['total_pb'] ?? $savedLabel->total_pb),
            'keterangan' => $validated['keterangan'] ?? $savedLabel->keterangan,
        ]);

        return redirect()->route('label.daftar')->with('success', "Label {$savedLabel->nomor_label} berhasil diperbarui.");
    }

    /**
     * Hapus label tersimpan dari database.
     */
    public function destroy(Request $request, int|string $id): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return redirect()->back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $savedLabel = SavedLabel::where('unit_sppg_id', $unitSppg->id)
            ->where(function ($q) use ($id) {
                if (is_numeric($id)) {
                    $q->where('id', $id);
                } else {
                    $q->where('nomor_label', $id);
                }
            })
            ->firstOrFail();

        $nomor = $savedLabel->nomor_label;
        $savedLabel->delete();

        return redirect()->route('label.daftar')->with('success', "Label {$nomor} berhasil dihapus.");
    }

    /**
     * Hitung nilai AKG spesifik per jenis alergi berdasarkan bahan substitusi di Work Order
     */
    private function calculateAkgAlergiForWo(WorkOrder $wo): array
    {
        $items = $wo->items;
        $rawAlergi = $wo->sub_menu_alergi ?: [];

        $configuredAllergies = [];
        if (is_array($rawAlergi)) {
            foreach ($rawAlergi as $subKey => $arr) {
                if (is_array($arr)) {
                    foreach ($arr as $rep) {
                        $j = trim($rep['jenis_alergi'] ?? '');
                        if ($j && !in_array($j, $configuredAllergies)) {
                            $configuredAllergies[] = $j;
                        }
                    }
                }
            }
        }

        foreach ($items as $it) {
            if ($it->tipe_porsi === 'alergi' && !empty($it->jenis_alergi)) {
                $j = trim($it->jenis_alergi);
                if ($j && !in_array($j, $configuredAllergies)) {
                    $configuredAllergies[] = $j;
                }
            }
        }

        $result = [];
        foreach ($configuredAllergies as $jenis) {
            $replacedSubMenuKeys = [];
            if (is_array($rawAlergi)) {
                foreach ($rawAlergi as $subKey => $arr) {
                    if (is_array($arr)) {
                        foreach ($arr as $rep) {
                            if (strcasecmp(trim($rep['jenis_alergi'] ?? ''), $jenis) === 0) {
                                $replacedSubMenuKeys[] = $subKey;
                            }
                        }
                    }
                }
            }

            $akgPB = ['energi' => 0, 'protein' => 0, 'lemak' => 0, 'karbohidrat' => 0, 'serat' => 0];
            $akgPK = ['energi' => 0, 'protein' => 0, 'lemak' => 0, 'karbohidrat' => 0, 'serat' => 0];

            foreach ($items as $it) {
                if ($it->tipe_porsi === 'normal') {
                    if (in_array($it->sub_menu_key, $replacedSubMenuKeys)) {
                        continue;
                    }
                    if (!empty($it->alergen) && $this->matchesAllergenExact($it->alergen, $jenis)) {
                        continue;
                    }

                    $npb = $it->nutrisi_pb ?: [];
                    $npk = $it->nutrisi_pk ?: [];
                    foreach (['energi', 'protein', 'lemak', 'karbohidrat', 'serat'] as $nut) {
                        $akgPB[$nut] += (float)($npb[$nut] ?? 0);
                        $akgPK[$nut] += (float)($npk[$nut] ?? 0);
                    }
                }
            }

            foreach ($items as $it) {
                if ($it->tipe_porsi === 'alergi' && strcasecmp(trim($it->jenis_alergi ?? ''), $jenis) === 0) {
                    $npb = $it->nutrisi_pb ?: [];
                    $npk = $it->nutrisi_pk ?: [];
                    foreach (['energi', 'protein', 'lemak', 'karbohidrat', 'serat'] as $nut) {
                        $akgPB[$nut] += (float)($npb[$nut] ?? 0);
                        $akgPK[$nut] += (float)($npk[$nut] ?? 0);
                    }
                }
            }

            foreach (['energi', 'protein', 'lemak', 'karbohidrat', 'serat'] as $nut) {
                $akgPB[$nut] = round($akgPB[$nut], 1);
                $akgPK[$nut] = round($akgPK[$nut], 1);
            }

            $result[$jenis] = [
                'akg_pb' => $akgPB,
                'akg_pk' => $akgPK,
            ];
        }

        return $result;
    }

    private function matchesAllergenExact(string $text, string $jenis): bool
    {
        $textLower = strtolower(trim($text));
        $jenisLower = strtolower(trim($jenis));
        if ($jenisLower === 'telur' || $jenisLower === 'telur ayam') {
            if (str_contains($textLower, 'puyuh')) {
                return false;
            }
            return (bool)preg_match('/\b(telur|egg|dadar|ceplok|omelet)\b/i', $textLower);
        }
        if ($jenisLower === 'telur puyuh') {
            return str_contains($textLower, 'puyuh');
        }
        if ($jenisLower === 'daging ayam' || $jenisLower === 'ayam') {
            if (str_contains($textLower, 'hati') || str_contains($textLower, 'ati')) {
                return false;
            }
            return (bool)preg_match('/\b(ayam|chicken)\b/i', $textLower);
        }
        if ($jenisLower === 'hati ayam') {
            return str_contains($textLower, 'hati') || str_contains($textLower, 'ati');
        }
        return stripos($textLower, $jenisLower) !== false;
    }
}
