<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
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
        if ($unitSppg) {
            $kelompokList = KelompokPenerimaManfaat::where('unit_sppg_id', $unitSppg->id)
                ->with('rincian')
                ->orderBy('nama_kelompok', 'asc')
                ->get();
        }

        return Inertia::render('Label/Index', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
        ]);
    }
}
