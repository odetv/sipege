<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
use App\Models\Periode;
use App\Models\SavedLabel;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
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
                        'nomor_wo' => $wo->nomor_wo,
                        'nama' => $wo->nama_menu,
                        'nama_menu' => $wo->nama_menu,
                        'metode_wo' => $wo->metode_wo ?: 'sistem',
                        'tanggal' => $wo->tanggal_distribusi ? (is_string($wo->tanggal_distribusi) ? substr($wo->tanggal_distribusi, 0, 10) : $wo->tanggal_distribusi->format('Y-m-d')) : null,
                        'tanggal_distribusi' => $wo->tanggal_distribusi ? (is_string($wo->tanggal_distribusi) ? substr($wo->tanggal_distribusi, 0, 10) : $wo->tanggal_distribusi->format('Y-m-d')) : null,
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
                        'sub_menus' => $wo->sub_menus ?: array_values(array_filter([$wo->sub_menu_1, $wo->sub_menu_2, $wo->sub_menu_3, $wo->sub_menu_4, $wo->sub_menu_5])),
                        'sub_menu_alergi' => $wo->sub_menu_alergi,
                        'komponen' => array_values(array_filter([
                            $wo->sub_menu_1 ?? $wo->komponen_energi,
                            $wo->sub_menu_2 ?? $wo->komponen_protein,
                            $wo->sub_menu_3 ?? $wo->komponen_lemak,
                            $wo->sub_menu_4 ?? $wo->komponen_karbohidrat,
                            $wo->sub_menu_5 ?? $wo->komponen_serat,
                        ])),
                        'akg_pk' => tap($wo, fn($w) => $this->resolveAkgNormalForWo($w))->akg_pk,
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
     * Pastikan nilai AKG PK & PB normal selalu tersedia (termasuk untuk WO manual)
     */
    private function resolveAkgNormalForWo(WorkOrder $wo): void
    {
        $rawPk = $wo->getRawOriginal('akg_pk');
        $rawPb = $wo->getRawOriginal('akg_pb');

        // Jika kedua porsi sudah tersimpan di database (meski bernilai 0), hormati data tersimpan
        if ($rawPk !== null && $rawPb !== null) {
            return;
        }

        $hasPk = !empty($wo->akg_pk) && is_array($wo->akg_pk) && (float)($wo->akg_pk['energi'] ?? 0) > 0;
        $hasPb = !empty($wo->akg_pb) && is_array($wo->akg_pb) && (float)($wo->akg_pb['energi'] ?? 0) > 0;

        if ($hasPk && $hasPb) {
            return;
        }

        // 1. Cek dan hitung dari items jika tersedia
        $items = $wo->items;
        if ($items && $items->isNotEmpty()) {
            $akgPk = ['energi' => 0, 'protein' => 0, 'lemak' => 0, 'karbohidrat' => 0, 'serat' => 0];
            $akgPb = ['energi' => 0, 'protein' => 0, 'lemak' => 0, 'karbohidrat' => 0, 'serat' => 0];

            foreach ($items as $it) {
                if ($it->tipe_porsi === 'normal') {
                    $npk = $it->nutrisi_pk ?: [];
                    $npb = $it->nutrisi_pb ?: [];
                    foreach (['energi', 'protein', 'lemak', 'karbohidrat', 'serat'] as $nut) {
                        $akgPk[$nut] += (float)($npk[$nut] ?? 0);
                        $akgPb[$nut] += (float)($npb[$nut] ?? 0);
                    }
                }
            }

            foreach (['energi', 'protein', 'lemak', 'karbohidrat', 'serat'] as $nut) {
                $akgPk[$nut] = round($akgPk[$nut], 1);
                $akgPb[$nut] = round($akgPb[$nut], 1);
            }

            if ($akgPk['energi'] > 0 || $akgPb['energi'] > 0) {
                if ($rawPk === null && $akgPk['energi'] > 0) $wo->akg_pk = $akgPk;
                if ($rawPb === null && $akgPb['energi'] > 0) $wo->akg_pb = $akgPb;
                return;
            }
        }

        // 2. Cek apakah ada Work Order lain dengan nama menu yang sama yang memiliki data AKG
        if (!empty($wo->nama_menu)) {
            $cleanName = trim(strtolower($wo->nama_menu));
            $matchedWo = WorkOrder::where('id', '!=', $wo->id)
                ->whereNotNull('akg_pk')
                ->where(function ($q) use ($cleanName) {
                    $q->whereRaw('LOWER(nama_menu) = ?', [$cleanName])
                      ->orWhereRaw('LOWER(nama_menu) LIKE ?', ['%' . $cleanName . '%']);
                })
                ->latest()
                ->get()
                ->first(function ($other) {
                    return !empty($other->akg_pk) && (float)($other->akg_pk['energi'] ?? 0) > 0;
                });

            if ($matchedWo) {
                if ($rawPk === null && !empty($matchedWo->akg_pk) && (float)($matchedWo->akg_pk['energi'] ?? 0) > 0) {
                    $wo->akg_pk = $matchedWo->akg_pk;
                }
                if ($rawPb === null && !empty($matchedWo->akg_pb) && (float)($matchedWo->akg_pb['energi'] ?? 0) > 0) {
                    $wo->akg_pb = $matchedWo->akg_pb;
                }
                return;
            }
        }

        // 3. Cek dari sub_menus (mencocokkan komponen sub menu dengan resep/items historis)
        $subMenus = is_array($wo->sub_menus) ? $wo->sub_menus : array_filter([
            $wo->sub_menu_1, $wo->sub_menu_2, $wo->sub_menu_3, $wo->sub_menu_4, $wo->sub_menu_5
        ]);

        if (!empty($subMenus)) {
            $akgPk = ['energi' => 0, 'protein' => 0, 'lemak' => 0, 'karbohidrat' => 0, 'serat' => 0];
            $akgPb = ['energi' => 0, 'protein' => 0, 'lemak' => 0, 'karbohidrat' => 0, 'serat' => 0];
            $foundAny = false;

            foreach ($subMenus as $smName) {
                $smClean = trim(strtolower($smName));
                if (!$smClean) continue;

                $matchItem = WorkOrderItem::whereRaw('LOWER(nama_sub_menu) LIKE ?', ['%' . $smClean . '%'])
                    ->whereNotNull('nutrisi_pb')
                    ->latest()
                    ->first();

                if ($matchItem) {
                    $foundAny = true;
                    $npk = $matchItem->nutrisi_pk ?: [];
                    $npb = $matchItem->nutrisi_pb ?: [];
                    foreach (['energi', 'protein', 'lemak', 'karbohidrat', 'serat'] as $nut) {
                        $akgPk[$nut] += (float)($npk[$nut] ?? 0);
                        $akgPb[$nut] += (float)($npb[$nut] ?? 0);
                    }
                }
            }

            if ($foundAny && ($akgPk['energi'] > 0 || $akgPb['energi'] > 0)) {
                foreach (['energi', 'protein', 'lemak', 'karbohidrat', 'serat'] as $nut) {
                    $akgPk[$nut] = round($akgPk[$nut], 1);
                    $akgPb[$nut] = round($akgPb[$nut], 1);
                }
                if ($rawPk === null && $akgPk['energi'] > 0) $wo->akg_pk = $akgPk;
                if ($rawPb === null && $akgPb['energi'] > 0) $wo->akg_pb = $akgPb;
                return;
            }
        }

        // 4. Default Standar AKG MBG Kemenkes HANYA jika data di DB sama sekali belum pernah diisi (keduanya null)
        if ($rawPk === null && $rawPb === null) {
            $hasPkRecipients = (int)($wo->total_porsi_kecil ?? 0) > 0;
            $hasPbRecipients = (int)($wo->total_porsi_besar ?? 0) > 0;

            if (!$hasPkRecipients && !$hasPbRecipients && $wo->relationLoaded('kelompoks')) {
                foreach ($wo->kelompoks as $k) {
                    if ((int)($k->total_porsi_kecil ?? $k->porsi_kecil ?? 0) > 0) $hasPkRecipients = true;
                    if ((int)($k->total_porsi_besar ?? $k->porsi_besar ?? 0) > 0) $hasPbRecipients = true;
                }
            }

            if (!$hasPkRecipients && !$hasPbRecipients) {
                $hasPkRecipients = true;
                $hasPbRecipients = true;
            }

            if ($hasPkRecipients) {
                $wo->akg_pk = [
                    'energi' => 385.0,
                    'protein' => 12.5,
                    'lemak' => 12.0,
                    'karbohidrat' => 54.0,
                    'serat' => 3.5,
                ];
            } else {
                $wo->akg_pk = ['energi' => 0, 'protein' => 0, 'lemak' => 0, 'karbohidrat' => 0, 'serat' => 0];
            }

            if ($hasPbRecipients) {
                $wo->akg_pb = [
                    'energi' => 670.0,
                    'protein' => 24.5,
                    'lemak' => 21.0,
                    'karbohidrat' => 92.0,
                    'serat' => 6.5,
                ];
            } else {
                $wo->akg_pb = ['energi' => 0, 'protein' => 0, 'lemak' => 0, 'karbohidrat' => 0, 'serat' => 0];
            }
        }
    }

    /**
     * Hitung nilai AKG spesifik per jenis alergi berdasarkan bahan substitusi di Work Order
     */
    private function calculateAkgAlergiForWo(WorkOrder $wo): array
    {
        // 1. Jika Work Order memiliki akg_alergi riil yang tersimpan (misal dari mode manual atau edit gizi), prioritaskan itu!
        if (!empty($wo->akg_alergi) && is_array($wo->akg_alergi)) {
            $hasRealData = false;
            foreach ($wo->akg_alergi as $j => $data) {
                if (is_array($data) && (!empty($data['akg_pb']) || !empty($data['akg_pk']))) {
                    $hasRealData = true;
                    break;
                }
            }
            if ($hasRealData) {
                return $wo->akg_alergi;
            }
        }

        $items = $wo->items;
        $rawAlergi = $wo->sub_menu_alergi ?: [];

        $configuredAllergies = [];
        if (is_array($rawAlergi)) {
            foreach ($rawAlergi as $subKey => $arr) {
                if (is_array($arr)) {
                    foreach ($arr as $rep) {
                        if (!is_array($rep)) continue;
                        $rawJ = $rep['jenis_alergi'] ?? '';
                        $j = trim(is_array($rawJ) ? ($rawJ['value'] ?? $rawJ['label'] ?? implode(',', $rawJ)) : (string)$rawJ);
                        if ($j && !in_array($j, $configuredAllergies)) {
                            $configuredAllergies[] = $j;
                        }
                    }
                }
            }
        }

        if ($items && $items->isNotEmpty()) {
            foreach ($items as $it) {
                if ($it->tipe_porsi === 'alergi' && !empty($it->jenis_alergi)) {
                    $j = trim($it->jenis_alergi);
                    if ($j && !in_array($j, $configuredAllergies)) {
                        $configuredAllergies[] = $j;
                    }
                }
            }
        }

        $result = [];

        // Jika ada items dengan formulasi alergi, hitung secara presisi
        if ($items && $items->isNotEmpty()) {
            foreach ($configuredAllergies as $jenis) {
                $replacedSubMenuKeys = [];
                if (is_array($rawAlergi)) {
                    foreach ($rawAlergi as $subKey => $arr) {
                        if (is_array($arr)) {
                            foreach ($arr as $rep) {
                                if (!is_array($rep)) continue;
                                $rawJ2 = $rep['jenis_alergi'] ?? '';
                                $jStr = trim(is_array($rawJ2) ? ($rawJ2['value'] ?? $rawJ2['label'] ?? implode(',', $rawJ2)) : (string)$rawJ2);
                                if (strcasecmp($jStr, $jenis) === 0) {
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

                if ($akgPB['energi'] > 0 || $akgPK['energi'] > 0) {
                    $result[$jenis] = [
                        'akg_pb' => $akgPB,
                        'akg_pk' => $akgPK,
                    ];
                }
            }
        }

        // Untuk alergi yang belum memiliki angka AKG (misal WO manual tanpa items formulasi),
        // ambil referensi dari WO lain atau gunakan baseline AKG normal WO
        foreach ($configuredAllergies as $jenis) {
            if (isset($result[$jenis])) {
                continue;
            }

            // Cek apakah ada WO lain yang pernah mencatat AKG untuk jenis alergi ini
            $otherAkg = WorkOrder::whereNotNull('akg_alergi')
                ->where('id', '!=', $wo->id)
                ->latest()
                ->get()
                ->first(function ($other) use ($jenis) {
                    return !empty($other->akg_alergi[$jenis]['akg_pb']) || !empty($other->akg_alergi[$jenis]['akg_pk']);
                });

            if ($otherAkg && !empty($otherAkg->akg_alergi[$jenis])) {
                $result[$jenis] = $otherAkg->akg_alergi[$jenis];
            } else {
                $pkEnergy = (float)($wo->akg_pk['energi'] ?? 0);
                $pbEnergy = (float)($wo->akg_pb['energi'] ?? 0);

                $basePk = ($pkEnergy > 0 && is_array($wo->akg_pk))
                    ? $wo->akg_pk
                    : ['energi' => 0, 'protein' => 0, 'lemak' => 0, 'karbohidrat' => 0, 'serat' => 0];

                $basePb = ($pbEnergy > 0 && is_array($wo->akg_pb))
                    ? $wo->akg_pb
                    : ['energi' => 0, 'protein' => 0, 'lemak' => 0, 'karbohidrat' => 0, 'serat' => 0];

                if ($pkEnergy > 0 || $pbEnergy > 0) {
                    $result[$jenis] = [
                        'akg_pk' => $basePk,
                        'akg_pb' => $basePb,
                    ];
                }
            }
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
