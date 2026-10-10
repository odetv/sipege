<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
use App\Models\SettingKopDokumen;
use App\Models\UnitSppg;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaporanController extends Controller
{
    /**
     * Tampilkan Halaman Laporan Harian Lintas Sektor.
     */
    public function harianLintasSektor(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg() ?? UnitSppg::first();
        $unitId = $unitSppg?->id;

        // Kop Dokumen aktif
        $kopConfig = SettingKopDokumen::getActiveConfig($unitId);

        // Kepala SPPG (dari user pemilik unit atau user aktif)
        $kepalaSppg = $unitSppg?->user?->nama_lengkap ?? $user->nama_lengkap ?? $user->nama;

        // Seluruh WO untuk kalender dan dropdown
        $woQuery = WorkOrder::query();
        if ($unitId) {
            $woQuery->where('unit_sppg_id', $unitId);
        }
        $allWorkOrders = $woQuery->orderBy('tanggal_distribusi', 'desc')->get(['id', 'uuid', 'nomor_wo', 'tanggal_distribusi', 'nama_menu', 'status', 'total_pm', 'total_pk', 'total_pb']);

        // Tentukan tanggal yang dipilih (default: hari ini, atau parameter query ?tanggal=...)
        $todayStr = Carbon::today()->format('Y-m-d');
        $selectedDate = $request->query('tanggal') ?: $todayStr;

        // Ambil data detail Work Order untuk tanggal yang dipilih
        $workOrder = null;
        if ($selectedDate) {
            $woDateQuery = WorkOrder::query();
            if ($unitId) {
                $woDateQuery->where('unit_sppg_id', $unitId);
            }
            $workOrder = $woDateQuery->whereDate('tanggal_distribusi', $selectedDate)
                ->with(['items', 'kelompoks.kelompok.rincian', 'unitSppg.user'])
                ->latest()
                ->first();

            if ($workOrder) {
                $this->resolveAkgNormalForWo($workOrder);
                $workOrder->akg_alergi = $this->calculateAkgAlergiForWo($workOrder);
            }
        }

        // Map seluruh WO untuk indikator kalender
        $calendarWorkOrders = $allWorkOrders->map(function ($item) {
            return [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'nomor_wo' => $item->nomor_wo,
                'tanggal' => Carbon::parse($item->tanggal_distribusi)->format('Y-m-d'),
                'nama_menu' => $item->nama_menu,
                'status' => $item->status,
                'total_pm' => $item->total_pm,
            ];
        });

        return Inertia::render('Laporan/HarianLintasSektor', [
            'unitSppg' => $unitSppg,
            'kopConfig' => $kopConfig,
            'kepalaSppg' => $kepalaSppg,
            'selectedDate' => $selectedDate,
            'todayDate' => $todayStr,
            'workOrder' => $workOrder,
            'calendarWorkOrders' => $calendarWorkOrders,
        ]);
    }

    /**
     * Pastikan nilai AKG PK & PB normal selalu tersedia (termasuk untuk WO manual)
     */
    /**
     * Pastikan nilai AKG PK & PB normal selalu tersedia (termasuk untuk WO manual)
     */
    private function resolveAkgNormalForWo(WorkOrder $wo): void
    {
        $rawPk = $wo->getRawOriginal('akg_pk');
        $rawPb = $wo->getRawOriginal('akg_pb');

        // Jika kedua porsi sudah tersimpan di database (meski salah satunya atau keduanya 0), hormati data tersimpan
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
     * Hitung nilai AKG spesifik per jenis alergi berdasarkan data di Work Order
     */
    private function calculateAkgAlergiForWo(WorkOrder $wo): array
    {
        // 1. Jika Work Order memiliki akg_alergi riil yang tersimpan, prioritaskan itu!
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
        if ($jenisLower === 'ikan' || $jenisLower === 'seafood') {
            return (bool)preg_match('/\b(ikan|fish|tongkol|lele|nila|patin|tenggiri|salmon|tuna|udang|cumi)\b/i', $textLower);
        }
        if ($jenisLower === 'udang') {
            return (bool)preg_match('/\b(udang|shrimp|prawn)\b/i', $textLower);
        }
        if ($jenisLower === 'kacang' || $jenisLower === 'kacang tanah') {
            return (bool)preg_match('/\b(kacang|peanut)\b/i', $textLower);
        }
        return str_contains($textLower, $jenisLower);
    }
}
