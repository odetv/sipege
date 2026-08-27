<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KeuanganController extends Controller
{
    /**
     * Tampilkan halaman tata kelola keuangan (default ke sub menu Anggaran).
     */
    public function index(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'anggaran');
    }

    /**
     * Sub-menu 1: Anggaran & Biaya per Porsi.
     */
    public function anggaran(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'anggaran');
    }

    /**
     * Sub-menu 2: Daftar PO (Purchase Order Bahan Baku).
     */
    public function daftarPo(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'daftar-po');
    }

    /**
     * Sub-menu 3: Transaksi & Riwayat Pengeluaran/Penerimaan.
     */
    public function transaksi(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'transaksi');
    }

    /**
     * Sub-menu 4: BKU (Buku Kas Umum).
     */
    public function bku(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'bku');
    }

    /**
     * Sub-menu 5: BP Bank (Buku Pembantu Bank).
     */
    public function bpBank(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'bp-bank');
    }

    /**
     * Sub-menu 6: BP Petty Cash (Buku Pembantu Kas Kecil).
     */
    public function bpPettyCash(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'bp-petty-cash');
    }

    /**
     * Sub-menu 7: BP Bahan Baku (Buku Pembantu Belanja Bahan Baku).
     */
    public function bpBahanBaku(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'bp-bahan-baku');
    }

    /**
     * Sub-menu 8: BP Operasional (Buku Pembantu Biaya Operasional).
     */
    public function bpOperasional(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'bp-operasional');
    }

    /**
     * Sub-menu 9: BP Fasilitas (Buku Pembantu Fasilitas & Sarpras).
     */
    public function bpFasilitas(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'bp-fasilitas');
    }

    /**
     * Sub-menu 10: LPA (Laporan Pertanggungjawaban Anggaran).
     */
    public function lpa(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'lpa');
    }

    /**
     * Sub-menu 11: SPTJ (Surat Pernyataan Tanggung Jawab).
     */
    public function sptj(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'sptj');
    }

    /**
     * Sub-menu 12: BAPSD (Berita Acara Pembayaran & Serah Terima Dokumen).
     */
    public function bapsd(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'bapsd');
    }

    /**
     * Helper render view Keuangan dengan data lengkap dan activeTab.
     */
    private function renderKeuanganView(Request $request, string $activeTab): Response
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

        // Estimasi Biaya Standar per Porsi BGN (dalam Rupiah)
        $costPorsiKecil = 8000;
        $costPorsiBesar = 10000;

        $estimasiHarianPK = $totalPorsiKecil * $costPorsiKecil;
        $estimasiHarianPB = $totalPorsiBesar * $costPorsiBesar;
        $estimasiHarianTotal = $estimasiHarianPK + $estimasiHarianPB;
        $estimasiBulanan = $estimasiHarianTotal * 20; // 20 hari operasional sekolah/bulan

        return Inertia::render('Keuangan/Index', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
            'activeTab' => $activeTab,
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
