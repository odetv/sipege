<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
use App\Models\Periode;
use App\Models\WorkOrder;
use App\Models\WorkOrderKelompok;
use App\Models\WorkOrderItem;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WorkOrderController extends Controller
{
    /**
     * Tampilkan Halaman Menu Khusus Work Order (Redirect ke Perencanaan / Daftar)
     */
    public function index(Request $request): RedirectResponse
    {
        if ($request->query('tab') === 'daftar') {
            return redirect()->route('work-order.daftar');
        }
        return redirect()->route('work-order.buat', $request->query());
    }

    /**
     * Tampilkan Halaman Perencanaan Produksi
     */
    public function perencanaan(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user->unitSppg;

        $kelompokList = [];
        $workOrders = [];

        if ($unitSppg) {
            $kelompokList = KelompokPenerimaManfaat::getCachedListForUnit($unitSppg->id);

            $workOrders = WorkOrder::where('unit_sppg_id', $unitSppg->id)
                ->with(['kelompoks', 'items'])
                ->orderBy('tanggal_distribusi', 'desc')
                ->get();

            foreach ($workOrders as $wo) {
                if (empty($wo->uuid)) {
                    $wo->uuid = (string) \Illuminate\Support\Str::uuid();
                    $wo->saveQuietly();
                }
            }
        }

        $editWoId = $request->route('id') ?: ($request->query('id') ?: ($request->query('uuid') ?: $request->query('edit_id')));
        $editWo = null;

        if ($editWoId && !empty($workOrders)) {
            $editWoStr = (string) $editWoId;
            if (is_numeric($editWoStr)) {
                $editWo = $workOrders->firstWhere('id', (int) $editWoStr)
                    ?: $workOrders->firstWhere('uuid', $editWoStr)
                    ?: $workOrders->firstWhere('nomor_wo', $editWoStr);
            } else {
                $editWo = $workOrders->firstWhere('uuid', $editWoStr)
                    ?: $workOrders->firstWhere('nomor_wo', $editWoStr);
            }
        }

        return Inertia::render('WorkOrder/Perencanaan', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
            'workOrdersList' => $workOrders,
            'editWorkOrder' => $editWo,
        ]);
    }

    /**
     * Tampilkan Halaman Daftar Seluruh Work Order
     */
    public function daftar(Request $request): Response
    {
        $user = $request->user();
        $unitSppg = $user->unitSppg;

        $kelompokList = [];
        $workOrders = [];

        if ($unitSppg) {
            $kelompokList = KelompokPenerimaManfaat::getCachedListForUnit($unitSppg->id);

            $workOrders = WorkOrder::where('unit_sppg_id', $unitSppg->id)
                ->with(['kelompoks', 'items'])
                ->orderBy('tanggal_distribusi', 'desc')
                ->get();

            foreach ($workOrders as $wo) {
                if (empty($wo->uuid)) {
                    $wo->uuid = (string) \Illuminate\Support\Str::uuid();
                    $wo->saveQuietly();
                }
            }
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

        return Inertia::render('WorkOrder/Daftar', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
            'workOrdersList' => $workOrders,
            'periodes' => $periodes,
        ]);
    }

    /**
     * Simpan / Perbarui Perencanaan Produksi Work Order
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG belum terdaftar.');
        }

        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'nomor_wo' => ['required', 'string'],
            'tanggal_distribusi' => ['required', 'date'],
            'nama_menu' => ['required', 'string', 'max:255'],
            'siklus_ke' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
            'metode_wo' => ['required', 'string', 'in:sistem,manual'],
            'database_pangan' => ['nullable', 'string', 'max:30'],
            'sub_menus' => ['nullable', 'array'],
            'sub_menu_1' => ['nullable', 'string'],
            'sub_menu_2' => ['nullable', 'string'],
            'sub_menu_3' => ['nullable', 'string'],
            'sub_menu_4' => ['nullable', 'string'],
            'sub_menu_5' => ['nullable', 'string'],
            'sub_menu_alergi' => ['nullable', 'array'],
            'jadwal_operasional' => ['nullable', 'array'],
            'lanjut_ke_rancang_menu' => ['nullable', 'boolean'],
            'total_pm' => ['required', 'integer'],
            'total_pk' => ['required', 'integer'],
            'total_pb' => ['required', 'integer'],
            'total_alergi' => ['nullable', 'integer'],
            'total_kelompok' => ['nullable', 'integer'],
            'akg_pk' => ['nullable', 'array'],
            'akg_pb' => ['nullable', 'array'],
            'akg_alergi' => ['nullable', 'array'],
            'food_cost_pk' => ['nullable', 'numeric'],
            'food_cost_pb' => ['nullable', 'numeric'],
            'total_anggaran_master' => ['nullable', 'numeric'],
            'porsi_tambahan' => ['nullable', 'array'],
            'catatan' => ['nullable'],
            'kelompoks' => ['nullable', 'array'],
        ]);

        // Cek duplikasi tanggal distribusi dalam unit yang sama (1 WO per tanggal)
        $existingWo = WorkOrder::where('unit_sppg_id', $unitSppg->id)
            ->where('tanggal_distribusi', $validated['tanggal_distribusi'])
            ->first();

        if ($existingWo && (!empty($validated['id']) ? $existingWo->id !== (int) $validated['id'] : $existingWo->nomor_wo !== $validated['nomor_wo'])) {
            return back()->with('error', 'Tanggal distribusi ' . $validated['tanggal_distribusi'] . ' sudah digunakan oleh Work Order ' . $existingWo->nomor_wo . ' (' . $existingWo->nama_menu . ').');
        }

        DB::transaction(function () use ($unitSppg, $validated, &$workOrder) {
            $subMenus = $validated['sub_menus'] ?? [];
            if (empty($subMenus)) {
                $subMenus = array_filter([
                    $validated['sub_menu_1'] ?? null,
                    $validated['sub_menu_2'] ?? null,
                    $validated['sub_menu_3'] ?? null,
                    $validated['sub_menu_4'] ?? null,
                    $validated['sub_menu_5'] ?? null,
                ]);
            }

            $status = $validated['status'] ?? 'Draft';
            $jadwalOperasional = $validated['jadwal_operasional'] ?? ($validated['catatan']['jadwal_operasional'] ?? null);

            $workOrder = WorkOrder::updateOrCreate(
                [
                    'unit_sppg_id' => $unitSppg->id,
                    'nomor_wo' => $validated['nomor_wo'],
                ],
                [
                    'tanggal_distribusi' => $validated['tanggal_distribusi'],
                    'nama_menu' => $validated['nama_menu'],
                    'siklus_ke' => $validated['siklus_ke'] ?? 1,
                    'status' => $status,
                    'metode_wo' => $validated['metode_wo'],
                    'database_pangan' => $validated['database_pangan'] ?? 'tkpi2020',
                    'current_step' => 1,
                    'sub_menus' => $subMenus,
                    'sub_menu_1' => $subMenus[0] ?? $validated['sub_menu_1'] ?? null,
                    'sub_menu_2' => $subMenus[1] ?? $validated['sub_menu_2'] ?? null,
                    'sub_menu_3' => $subMenus[2] ?? $validated['sub_menu_3'] ?? null,
                    'sub_menu_4' => $subMenus[3] ?? $validated['sub_menu_4'] ?? null,
                    'sub_menu_5' => $subMenus[4] ?? $validated['sub_menu_5'] ?? null,
                    'sub_menu_alergi' => $validated['sub_menu_alergi'] ?? null,
                    'jadwal_operasional' => $jadwalOperasional,
                    'total_pm' => $validated['total_pm'],
                    'total_pk' => $validated['total_pk'],
                    'total_pb' => $validated['total_pb'],
                    'total_alergi' => $validated['total_alergi'] ?? 0,
                    'total_kelompok' => $validated['total_kelompok'] ?? 0,
                    'akg_pk' => $validated['akg_pk'] ?? null,
                    'akg_pb' => $validated['akg_pb'] ?? null,
                    'akg_alergi' => $validated['akg_alergi'] ?? null,
                    'food_cost_pk' => $validated['food_cost_pk'] ?? 0,
                    'food_cost_pb' => $validated['food_cost_pb'] ?? 0,
                    'total_anggaran_master' => $validated['total_anggaran_master'] ?? 0,
                    'porsi_tambahan' => $validated['porsi_tambahan'] ?? null,
                    'catatan' => $validated['catatan'] ?? ($jadwalOperasional ? ['jadwal_operasional' => $jadwalOperasional] : null),
                ]
            );

            // Pastikan UUID selalu ada
            if (empty($workOrder->uuid)) {
                $workOrder->uuid = (string) \Illuminate\Support\Str::uuid();
                $workOrder->saveQuietly();
            }

            // Simpan snapshot kelompok penerima manfaat
            $kelompoksInput = $validated['kelompoks'] ?? [];
            if (empty($kelompoksInput)) {
                $masterKelompoks = KelompokPenerimaManfaat::getCachedListForUnit($unitSppg->id);
                foreach ($masterKelompoks as $mk) {
                    $kelompoksInput[] = [
                        'kelompok_id' => $mk->id,
                        'nama_kelompok' => $mk->nama_kelompok,
                        'kategori' => $mk->kategori,
                        'is_menerima' => true,
                        'total_penerima' => $mk->total_penerima,
                        'total_porsi_kecil' => $mk->total_porsi_kecil,
                        'total_porsi_besar' => $mk->total_porsi_besar,
                        'rincian' => $mk->rincian,
                        'detail_alergi' => $mk->keterangan_alergi,
                    ];
                }
            }

            if (!empty($kelompoksInput)) {
                $workOrder->kelompoks()->delete();
                foreach ($kelompoksInput as $kel) {
                    $workOrder->kelompoks()->create([
                        'kelompok_id' => $kel['kelompok_id'] ?? $kel['id'] ?? null,
                        'nama_kelompok' => $kel['nama_kelompok'] ?? 'Kelompok',
                        'kategori' => $kel['kategori'] ?? 'SD',
                        'is_menerima' => isset($kel['is_menerima']) ? (bool) $kel['is_menerima'] : true,
                        'total_penerima' => $kel['total_penerima'] ?? 0,
                        'porsi_kecil' => $kel['total_porsi_kecil'] ?? $kel['porsi_kecil'] ?? 0,
                        'porsi_besar' => $kel['total_porsi_besar'] ?? $kel['porsi_besar'] ?? 0,
                        'status_alergi' => !empty($kel['detail_alergi']) || !empty($kel['keterangan_alergi']) || !empty($kel['status_alergi']),
                        'rincian' => $kel['rincian'] ?? null,
                        'detail_alergi' => $kel['detail_alergi'] ?? $kel['keterangan_alergi'] ?? [],
                    ]);
                }
            }
        });

        if ($request->boolean('lanjut_ke_rancang_menu') || $request->input('lanjut_ke_rancang_menu')) {
            $step = $request->input('step', 'formula');
            return redirect()->route('gizi.rancang-menu', ['id' => $workOrder->uuid, 'step' => $step])
                ->with('success', 'Perencanaan Produksi WO (' . $workOrder->nomor_wo . ') berhasil disimpan. Silakan lanjutkan ke Formula Makanan.');
        }

        if ($request->boolean('tetap_di_halaman') || $request->input('tetap_di_halaman')) {
            $tab = $request->input('tab_tujuan', 'perencanaan');
            return redirect()->route('work-order.buat', ['id' => $workOrder->uuid, 'tab' => $tab])
                ->with('success', 'Work Order (' . $workOrder->nomor_wo . ') berhasil disimpan.');
        }

        $msg = $validated['metode_wo'] === 'manual'
            ? 'Work Order Manual (' . $workOrder->nomor_wo . ') berhasil disimpan.'
            : 'Perencanaan Produksi WO (' . $workOrder->nomor_wo . ') berhasil disimpan.';

        return redirect()->route('gizi.daftar-menu')->with('success', $msg);
    }

    /**
     * Update Data Manual Work Order (Komponen Sub Menu, Nilai Gizi AKG, dan Alergi)
     */
    public function updateManual(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG belum terdaftar.');
        }

        $workOrder = WorkOrder::where('unit_sppg_id', $unitSppg->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'nama_menu' => ['required', 'string', 'max:255'],
            'sub_menus' => ['nullable', 'array'],
            'sub_menu_alergi' => ['nullable', 'array'],
            'jadwal_operasional' => ['nullable', 'array'],
            'akg_pk' => ['nullable', 'array'],
            'akg_pb' => ['nullable', 'array'],
            'akg_alergi' => ['nullable', 'array'],
            'porsi_tambahan' => ['nullable', 'array'],
            'catatan' => ['nullable'],
            'status' => ['nullable', 'string'],
        ]);

        $subMenus = $validated['sub_menus'] ?? $workOrder->sub_menus ?? [];

        $workOrder->update([
            'nama_menu' => $validated['nama_menu'],
            'sub_menus' => $subMenus,
            'sub_menu_1' => $subMenus[0] ?? $workOrder->sub_menu_1,
            'sub_menu_2' => $subMenus[1] ?? $workOrder->sub_menu_2,
            'sub_menu_3' => $subMenus[2] ?? $workOrder->sub_menu_3,
            'sub_menu_4' => $subMenus[3] ?? $workOrder->sub_menu_4,
            'sub_menu_5' => $subMenus[4] ?? $workOrder->sub_menu_5,
            'sub_menu_alergi' => $validated['sub_menu_alergi'] ?? $workOrder->sub_menu_alergi,
            'jadwal_operasional' => $validated['jadwal_operasional'] ?? $workOrder->jadwal_operasional,
            'akg_pk' => $validated['akg_pk'] ?? $workOrder->akg_pk,
            'akg_pb' => $validated['akg_pb'] ?? $workOrder->akg_pb,
            'akg_alergi' => $validated['akg_alergi'] ?? $workOrder->akg_alergi,
            'porsi_tambahan' => $validated['porsi_tambahan'] ?? $workOrder->porsi_tambahan,
            'catatan' => $validated['catatan'] ?? $workOrder->catatan,
            'status' => $validated['status'] ?? $workOrder->status,
        ]);

        return back()->with('success', 'Data Manual Work Order berhasil diperbarui.');
    }

    /**
     * Ajukan Work Order ke Keuangan
     */
    public function ajukanKeuangan(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->unitSppg;

        $workOrder = WorkOrder::where('unit_sppg_id', $unitSppg->id)
            ->where('id', $id)
            ->firstOrFail();

        $workOrder->update([
            'status' => 'Diajukan ke Keuangan',
            'diajukan_pada' => Carbon::now(),
        ]);

        return back()->with('success', "Work Order {$workOrder->nomor_wo} berhasil diajukan ke Keuangan.");
    }

    /**
     * Hapus Work Order (Hanya jika status Draft atau Dibatalkan)
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->unitSppg;

        $workOrder = WorkOrder::where('unit_sppg_id', $unitSppg->id)
            ->where('id', $id)
            ->firstOrFail();

        if (!in_array(strtolower($workOrder->status), ['draft', 'dibatalkan', 'ditolak ke gizi'])) {
            return back()->with('error', "Work Order dengan status '{$workOrder->status}' tidak dapat dihapus.");
        }

        $workOrder->items()->delete();
        $workOrder->kelompoks()->delete();
        $workOrder->delete();

        return back()->with('success', "Work Order {$workOrder->nomor_wo} berhasil dihapus.");
    }
}
