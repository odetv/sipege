<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LabelController extends Controller
{
    /**
     * Tampilkan halaman generator & cetak label SPPG.
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        $kelompokList = [];
        $workOrders = [];
        $todayStr = date('Y-m-d');
        $activeWorkOrder = null;

        if ($unitSppg) {
            $kelompokList = KelompokPenerimaManfaat::where('unit_sppg_id', $unitSppg->id)
                ->with('rincian')
                ->orderBy('nama_kelompok', 'asc')
                ->get();

            $workOrders = WorkOrder::where('unit_sppg_id', $unitSppg->id)
                ->with(['items', 'kelompoks.kelompok', 'purchaseOrder'])
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
                        'komponen' => array_values(array_filter([
                            $wo->komponen_energi,
                            $wo->komponen_protein,
                            $wo->komponen_lemak,
                            $wo->komponen_karbohidrat,
                            $wo->komponen_serat,
                        ])),
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
        }

        return Inertia::render('Label/Index', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
            'workOrders' => $workOrders,
            'initialActiveWo' => $activeWorkOrder,
        ]);
    }
}

