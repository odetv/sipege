<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KeuanganController extends Controller
{
    /**
     * Tampilkan halaman tata kelola keuangan & anggaran SPPG.
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        $totalPenerima = 0;
        $totalPorsiKecil = 0;
        $totalPorsiBesar = 0;
        $kelompokList = [];

        if ($unitSppg) {
            $kelompokList = KelompokPenerimaManfaat::where('unit_sppg_id', $unitSppg->id)
                ->with('rincian')
                ->get();

            $totalPenerima = $kelompokList->sum('total_penerima');
            $totalPorsiKecil = $kelompokList->sum('total_porsi_kecil');
            $totalPorsiBesar = $kelompokList->sum('total_porsi_besar');
        }

        // Estimasi Biaya Standar per Porsi (dalam Rupiah)
        $costPorsiKecil = 12000;
        $costPorsiBesar = 15000;

        $estimasiHarianPK = $totalPorsiKecil * $costPorsiKecil;
        $estimasiHarianPB = $totalPorsiBesar * $costPorsiBesar;
        $estimasiHarianTotal = $estimasiHarianPK + $estimasiHarianPB;
        $estimasiBulanan = $estimasiHarianTotal * 22; // 22 hari operasional sekolah/bulan

        return Inertia::render('Keuangan/Index', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
            'summary' => [
                'total_kelompok' => count($kelompokList),
                'total_penerima' => $totalPenerima,
                'total_porsi_kecil' => $totalPorsiKecil,
                'total_porsi_besar' => $totalPorsiBesar,
                'cost_porsi_kecil' => $costPorsiKecil,
                'cost_porsi_besar' => $costPorsiBesar,
                'estimasi_harian_pk' => $estimasiHarianPK,
                'estimasi_harian_pb' => $estimasiHarianPB,
                'estimasi_harian_total' => $estimasiHarianTotal,
                'estimasi_bulanan_total' => $estimasiBulanan,
            ],
        ]);
    }
}
