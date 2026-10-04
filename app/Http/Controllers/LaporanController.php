<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
use App\Models\SettingKopDokumen;
use App\Models\UnitSppg;
use App\Models\WorkOrder;
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

        // Tentukan tanggal yang dipilih (default: parameter query ?tanggal=..., atau hari ini jika ada WO hari ini, atau WO terbaru jika hari ini kosong, atau hari ini)
        $todayStr = Carbon::today()->format('Y-m-d');
        $selectedDate = $request->query('tanggal');

        if (!$selectedDate) {
            // Cek apakah ada WO untuk hari ini
            $hasTodayWo = $allWorkOrders->contains(function ($item) use ($todayStr) {
                return Carbon::parse($item->tanggal_distribusi)->format('Y-m-d') === $todayStr;
            });

            if ($hasTodayWo) {
                $selectedDate = $todayStr;
            } elseif ($allWorkOrders->isNotEmpty()) {
                // Default ke WO terbaru jika hari ini tidak ada WO
                $selectedDate = Carbon::parse($allWorkOrders->first()->tanggal_distribusi)->format('Y-m-d');
            } else {
                $selectedDate = $todayStr;
            }
        }

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
}
