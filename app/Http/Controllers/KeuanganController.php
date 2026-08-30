<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Sub-menu Baru: Verifikasi PO (Telaah & Persetujuan Pengajuan Tim Gizi).
     */
    public function verifikasiPoIndex(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'verifikasi-po');
    }

    /**
     * Sub-menu: Daftar PO Resmi (PO yang telah diverifikasi & disetujui).
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
     * Sub-menu: Stok Persediaan Bahan Pangan.
     */
    public function stok(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'stok');
    }

    /**
     * Sub-menu: Laporan Harian Keuangan.
     */
    public function laporanHarian(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'laporan-harian');
    }

    /**
     * Sub-menu: Laporan Periodik Keuangan.
     */
    public function laporanPeriodik(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'laporan-periodik');
    }

    /**
     * Verifikasi & Approval PO oleh Akuntan Keuangan.
     */
    public function verifikasiPo(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $po = PurchaseOrder::with(['items', 'workOrder'])->findOrFail($id);

        $validated = $request->validate([
            'status_po' => ['required', 'string'],
            'catatan' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($po, $validated, $user) {
            $totalAktual = 0;

            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $itemData) {
                    if (isset($itemData['id'])) {
                        $poItem = $po->items()->find($itemData['id']);
                        if ($poItem) {
                            $hargaAktual = (float) ($itemData['harga_aktual'] ?? $poItem->harga_master);
                            $gross = (float) $poItem->gross_kg;
                            $subtotal = round($gross * $hargaAktual);
                            if ($gross > 0 && $hargaAktual > 0 && $subtotal == 0) {
                                $subtotal = ceil($gross * $hargaAktual);
                            }
                            $poItem->update([
                                'harga_aktual' => $hargaAktual,
                                'subtotal_aktual' => $subtotal,
                            ]);
                            $totalAktual += $subtotal;
                        }
                    }
                }
            } else {
                $totalAktual = $po->total_nominal_aktual ?: $po->total_nominal_master;
            }

            // Ambil riwayat log lama
            $existingPoLogs = [];
            if (!empty($po->riwayat_verifikasi) && is_array($po->riwayat_verifikasi)) {
                $existingPoLogs = $po->riwayat_verifikasi;
            } elseif (!empty($po->riwayat_verifikasi) && is_string($po->riwayat_verifikasi)) {
                $existingPoLogs = json_decode($po->riwayat_verifikasi, true) ?: [];
            } elseif ($po->workOrder && !empty($po->workOrder->riwayat_verifikasi)) {
                $existingPoLogs = is_array($po->workOrder->riwayat_verifikasi)
                    ? $po->workOrder->riwayat_verifikasi
                    : (json_decode($po->workOrder->riwayat_verifikasi, true) ?: []);
            }

            $catatanBaru = !empty($validated['catatan']) ? trim($validated['catatan']) : '';

            $defaultMsg = 'Purchase Order diverifikasi';
            if ($validated['status_po'] === 'Terverifikasi' || $validated['status_po'] === 'Siap Produksi') {
                $defaultMsg = 'Purchase Order disetujui & diverifikasi untuk siap produksi.';
            } elseif ($validated['status_po'] === 'Ditolak') {
                $defaultMsg = 'Pengajuan Purchase Order ditolak, menunggu revisi tim gizi.';
            } elseif ($validated['status_po'] === 'Draft Verifikasi') {
                $defaultMsg = 'Catatan telaah disimpan sebagai Draft Verifikasi.';
            }

            $logEntry = [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'status' => $validated['status_po'],
                'catatan' => $catatanBaru ?: $defaultMsg,
                'user_id' => $user->id,
                'user_nama' => $user->nama_lengkap ?? $user->name ?? 'Verifikator Keuangan',
                'role' => $user->role ?? 'Keuangan',
                'waktu' => now()->toIso8601String(),
            ];
            $existingPoLogs[] = $logEntry;

            $po->update([
                'status_po' => $validated['status_po'],
                'catatan' => $catatanBaru ?: ($po->catatan ?: $defaultMsg),
                'total_nominal_aktual' => $totalAktual,
                'diverifikasi_oleh' => $user->id,
                'diverifikasi_pada' => now(),
                'riwayat_verifikasi' => $existingPoLogs,
            ]);

            // Sinkronisasi status & riwayat kembali ke WorkOrder
            if ($po->workOrder) {
                $updateData = [
                    'riwayat_verifikasi' => $existingPoLogs,
                ];
                if (!empty($catatanBaru)) {
                    $updateData['catatan_keuangan'] = $catatanBaru;
                }
                if ($validated['status_po'] === 'Terverifikasi' || $validated['status_po'] === 'Siap Produksi') {
                    $updateData['status'] = 'Siap Produksi';
                    $updateData['total_anggaran_aktual'] = $totalAktual;
                    $updateData['disetujui_pada'] = now();
                } elseif ($validated['status_po'] === 'Ditolak') {
                    $updateData['status'] = 'Ditolak Keuangan';
                    $updateData['ditolak_pada'] = now();
                }
                $po->workOrder->update($updateData);
            }
        });

        return back()->with('success', 'Purchase Order berhasil diverifikasi.');
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
        $poList = [];

        if ($unitSppg) {
            $kelompokList = KelompokPenerimaManfaat::where('unit_sppg_id', $unitSppg->id)
                ->with('rincian')
                ->get();

            $totalPenerima = $kelompokList->sum('total_penerima');
            $totalPorsiKecil = $kelompokList->sum('total_porsi_kecil');
            $totalPorsiBesar = $kelompokList->sum('total_porsi_besar');

            // Ambil seluruh data PO dari database
            $allDbPo = PurchaseOrder::where('unit_sppg_id', $unitSppg->id)
                ->with(['items', 'workOrder', 'verifikator'])
                ->orderBy('tanggal', 'desc')
                ->get();

            $mapPoItem = function ($po) {
                return [
                    'id' => $po->nomor_po,
                    'db_id' => $po->id,
                    'wo_id' => $po->workOrder ? $po->workOrder->nomor_wo : 'WO-MBG',
                    'wo_status' => $po->workOrder ? $po->workOrder->status : 'Draft',
                    'tanggal' => $po->tanggal ? $po->tanggal->format('Y-m-d') : now()->toDateString(),
                    'menu' => $po->workOrder ? $po->workOrder->nama_menu : 'Menu MBG',
                    'vendor' => $po->vendor,
                    'items_count' => $po->items->count(),
                    'total_nominal' => $po->total_nominal_aktual ?: $po->total_nominal_master,
                    'total_nominal_master' => $po->total_nominal_master,
                    'status_po' => $po->status_po,
                    'status_bayar' => $po->status_bayar,
                    'catatan' => $po->catatan,
                    'created_at' => $po->created_at ? $po->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $po->updated_at ? $po->updated_at->format('Y-m-d H:i:s') : null,
                    'diverifikasi_pada' => $po->diverifikasi_pada ? $po->diverifikasi_pada->format('Y-m-d H:i:s') : null,
                    'riwayat_verifikasi' => $po->riwayat_verifikasi ?: ($po->workOrder ? $po->workOrder->riwayat_verifikasi : []) ?: [],
                    'items' => $po->items->map(function ($it) {
                        return [
                            'id' => $it->id,
                            'nama' => $it->nama,
                            'kategori' => $it->kategori,
                            'tipe' => $it->tipe,
                            'gross_kg' => $it->gross_kg,
                            'harga_master' => $it->harga_master,
                            'harga_aktual' => $it->harga_aktual ?: $it->harga_master,
                            'subtotal_aktual' => $it->subtotal_aktual ?: ($it->gross_kg * $it->harga_master),
                        ];
                    }),
                ];
            };

            // 1. Pengajuan PO Masuk untuk Sub-menu Verifikasi PO
            $verifikasiPoList = $allDbPo->filter(function ($po) {
                return in_array($po->status_po, ['Menunggu Verifikasi', 'Diajukan ke Keuangan', 'Draft Verifikasi', 'Ditolak']);
            })->map($mapPoItem)->values()->toArray();

            // 2. Daftar PO Resmi yang sudah Disetujui / Terverifikasi untuk Sub-menu Daftar PO
            $poList = $allDbPo->filter(function ($po) {
                return in_array($po->status_po, ['Terverifikasi', 'Siap Produksi', 'Selesai']);
            })->map($mapPoItem)->values()->toArray();
        }

        // Estimasi Biaya Standar per Porsi BGN (dalam Rupiah)
        $costPorsiKecil = 8000;
        $costPorsiBesar = 10000;

        $paguHarian = ($totalPorsiKecil * $costPorsiKecil) + ($totalPorsiBesar * $costPorsiBesar);
        $hariEfektifBulan = 25;
        $paguBulanan = $paguHarian * $hariEfektifBulan;

        return Inertia::render('Keuangan/Index', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
            'activeTab' => $activeTab,
            'verifikasiPoList' => $verifikasiPoList ?? [],
            'poList' => $poList,
            'stats' => [
                'total_penerima' => $totalPenerima,
                'total_porsi_kecil' => $totalPorsiKecil,
                'total_porsi_besar' => $totalPorsiBesar,
                'cost_pk' => $costPorsiKecil,
                'cost_pb' => $costPorsiBesar,
                'pagu_harian' => $paguHarian,
                'pagu_bulanan' => $paguBulanan,
                'hari_efektif' => $hariEfektifBulan,
            ],
        ]);
    }
}
