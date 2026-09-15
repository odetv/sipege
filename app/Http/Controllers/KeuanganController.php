<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
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
        $po = is_numeric($id)
            ? PurchaseOrder::with(['items', 'workOrder'])->find($id)
            : PurchaseOrder::with(['items', 'workOrder'])->where('nomor_po', $id)->first();

        if (!$po) {
            $po = PurchaseOrder::with(['items', 'workOrder'])->where('nomor_po', $id)->firstOrFail();
        }

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
                            
                            // Hitung pengalihan stok & kuantitas belanja PO aktual
                            $stokDigunakan = isset($itemData['stok_digunakan_kg']) ? (float) $itemData['stok_digunakan_kg'] : (float) ($poItem->stok_digunakan_kg ?? 0);
                            $stokDigunakan = max(0, min($gross, $stokDigunakan));
                            $qtyBeli = max(0, round($gross - $stokDigunakan, 4));

                            $sumberPengadaan = 'Beli PO';
                            if ($gross > 0 && $stokDigunakan >= $gross) {
                                $sumberPengadaan = '100% Dari Stok';
                                $qtyBeli = 0;
                            } elseif ($stokDigunakan > 0) {
                                $sumberPengadaan = 'Parsial Stok';
                            }

                            // Subtotal dihitung dari kuantitas belanja aktual dikali harga satuan
                            $subtotal = round($qtyBeli * $hargaAktual);
                            if ($qtyBeli > 0 && $hargaAktual > 0 && $subtotal == 0) {
                                $subtotal = ceil($qtyBeli * $hargaAktual);
                            }

                            $poItem->update([
                                'harga_aktual' => $hargaAktual,
                                'stok_digunakan_kg' => $stokDigunakan,
                                'qty_beli_po_kg' => $qtyBeli,
                                'sumber_pengadaan' => $sumberPengadaan,
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
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ];

            $updatedLogs = array_merge($existingPoLogs, [$logEntry]);

            $po->update([
                'status_po' => $validated['status_po'],
                'catatan' => $catatanBaru ?: $po->catatan,
                'total_nominal_aktual' => $totalAktual,
                'diverifikasi_pada' => now(),
                'diverifikasi_oleh' => $user->id,
                'riwayat_verifikasi' => $updatedLogs,
            ]);

            // Sinkronisasi status Work Order jika ada
            if ($po->workOrder) {
                $updateData = [
                    'riwayat_verifikasi' => $updatedLogs,
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
     * Menetapkan Supplier dan Jenis Transaksi untuk PO resmi (per item & header).
     */
    public function updatePoSupplier(Request $request, $id): RedirectResponse
    {
        $po = is_numeric($id)
            ? PurchaseOrder::with('items')->find($id)
            : PurchaseOrder::with('items')->where('nomor_po', $id)->first();

        if (!$po) {
            $po = PurchaseOrder::with('items')->where('nomor_po', $id)->firstOrFail();
        }

        $validated = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'jenis_transaksi' => ['nullable', 'string', 'in:Bahan Baku,Operasional'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.supplier_id' => ['required', 'exists:suppliers,id'],
            'items.*.jenis_transaksi' => ['required', 'string', 'in:Bahan Baku,Operasional'],
        ], [
            'items.*.supplier_id.required' => 'Seluruh baris bahan wajib dipilih Supplier Rekanannya.',
            'items.*.supplier_id.exists' => 'Supplier yang dipilih tidak valid.',
            'items.*.jenis_transaksi.required' => 'Seluruh baris bahan wajib dipilih Jenis Transaksinya.',
            'items.*.jenis_transaksi.in' => 'Jenis transaksi harus berupa Bahan Baku atau Operasional.',
        ]);

        $defaultSupplierId = $validated['supplier_id'] ?? null;
        $defaultJenisTransaksi = $validated['jenis_transaksi'] ?? null;

        // Update items per baris
        foreach ($validated['items'] as $itData) {
            $poItem = $po->items()->find($itData['id']);
            if ($poItem) {
                $itemSupId = !empty($itData['supplier_id']) ? (int)$itData['supplier_id'] : null;
                $poItem->update([
                    'supplier_id' => $itemSupId,
                    'jenis_transaksi' => $itData['jenis_transaksi'],
                ]);
            }
        }
        $firstWithSup = collect($validated['items'])->first(fn($i) => !empty($i['supplier_id']));
        if ($firstWithSup && !$defaultSupplierId) {
            $defaultSupplierId = (int)$firstWithSup['supplier_id'];
        }
        if (!$defaultJenisTransaksi && !empty($validated['items'][0]['jenis_transaksi'])) {
            $defaultJenisTransaksi = $validated['items'][0]['jenis_transaksi'];
        }

        $vendorName = $validated['vendor'] ?? null;
        if (!empty($defaultSupplierId)) {
            $supplier = Supplier::find($defaultSupplierId);
            if ($supplier) {
                $vendorName = $supplier->nama_usaha;
            }
        }

        $po->update([
            'supplier_id' => $defaultSupplierId,
            'vendor' => $vendorName ?: ($po->vendor && $po->vendor !== 'Rekanan Pangan SPPG' ? $po->vendor : null),
            'jenis_transaksi' => $defaultJenisTransaksi ?: $po->jenis_transaksi,
        ]);

        return back()->with('success', 'Penetapan Supplier dan Jenis Transaksi per item berhasil disimpan.');
    }

    /**
     * Helper render view Keuangan dengan data lengkap dan activeTab.
     */
    private function renderKeuanganView(Request $request, string $activeTab): Response
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        $totalPenerima = 0;
        $totalPorsiKecil = 0;
        $totalPorsiBesar = 0;
        $kelompokList = [];
        $poList = [];
        $suppliers = [];

        if ($unitSppg) {
            $suppliers = Supplier::where('unit_sppg_id', $unitSppg->id)
                ->orderBy('nama_usaha', 'asc')
                ->get();

            $kelompokList = KelompokPenerimaManfaat::getCachedListForUnit($unitSppg->id);

            $totalPenerima = $kelompokList->sum('total_penerima');
            $totalPorsiKecil = $kelompokList->sum('total_porsi_kecil');
            $totalPorsiBesar = $kelompokList->sum('total_porsi_besar');

            // Ambil seluruh data PO dari database
            $allDbPo = PurchaseOrder::where('unit_sppg_id', $unitSppg->id)
                ->with(['items.supplier', 'workOrder', 'verifikator', 'supplier'])
                ->orderBy('tanggal', 'desc')
                ->get();

            $mapPoItem = function ($po) use ($totalPenerima, $totalPorsiKecil, $totalPorsiBesar) {
                $rawWo = $po->workOrder ? [
                    'sub_menu_1' => $po->workOrder->sub_menu_1,
                    'sub_menu_2' => $po->workOrder->sub_menu_2,
                    'sub_menu_3' => $po->workOrder->sub_menu_3,
                    'sub_menu_4' => $po->workOrder->sub_menu_4,
                    'sub_menu_5' => $po->workOrder->sub_menu_5,
                    'sub_menu_alergi' => $po->workOrder->sub_menu_alergi,
                    'riwayat_verifikasi' => $po->workOrder->riwayat_verifikasi,
                ] : null;

                $totalPorsiVal = $po->workOrder ? ($po->workOrder->total_pm ?: ($po->workOrder->total_porsi ?: ($totalPenerima ?? 0))) : ($totalPenerima ?? 0);
                $porsiPkVal = $po->workOrder ? ($po->workOrder->total_pk ?: ($po->workOrder->porsi_pk ?: ($totalPorsiKecil ?? 0))) : ($totalPorsiKecil ?? 0);
                $porsiPbVal = $po->workOrder ? ($po->workOrder->total_pb ?: ($po->workOrder->porsi_pb ?: ($totalPorsiBesar ?? 0))) : ($totalPorsiBesar ?? 0);

                return [
                    'id' => $po->nomor_po,
                    'db_id' => $po->id,
                    'wo_id' => $po->workOrder ? $po->workOrder->nomor_wo : 'WO-MBG',
                    'wo_status' => $po->workOrder ? $po->workOrder->status : 'Draft',
                    'siklus' => $po->workOrder ? $po->workOrder->siklus_ke : 'Hari ke-1',
                    'total_porsi' => $totalPorsiVal,
                    'total_pm' => $totalPorsiVal,
                    'porsi_pk' => $porsiPkVal,
                    'total_pk' => $porsiPkVal,
                    'porsi_pb' => $porsiPbVal,
                    'total_pb' => $porsiPbVal,
                    'sub_menu_1' => $po->workOrder ? $po->workOrder->sub_menu_1 : null,
                    'sub_menu_2' => $po->workOrder ? $po->workOrder->sub_menu_2 : null,
                    'sub_menu_3' => $po->workOrder ? $po->workOrder->sub_menu_3 : null,
                    'sub_menu_4' => $po->workOrder ? $po->workOrder->sub_menu_4 : null,
                    'sub_menu_5' => $po->workOrder ? $po->workOrder->sub_menu_5 : null,
                    'energi_pk' => $po->workOrder ? ($po->workOrder->energi_pk ?: ($po->workOrder->akg_pk['energi'] ?? 0)) : 0,
                    'energi_pb' => $po->workOrder ? ($po->workOrder->energi_pb ?: ($po->workOrder->akg_pb['energi'] ?? 0)) : 0,
                    'protein_pk' => $po->workOrder ? ($po->workOrder->protein_pk ?: ($po->workOrder->akg_pk['protein'] ?? 0)) : 0,
                    'protein_pb' => $po->workOrder ? ($po->workOrder->protein_pb ?: ($po->workOrder->akg_pb['protein'] ?? 0)) : 0,
                    'cost_pk' => $po->workOrder ? ($po->workOrder->food_cost_pk ?: $po->workOrder->cost_pk) : 0,
                    'cost_pb' => $po->workOrder ? ($po->workOrder->food_cost_pb ?: $po->workOrder->cost_pb) : 0,
                    'tanggal' => $po->tanggal ? $po->tanggal->format('Y-m-d') : now()->toDateString(),
                    'menu' => $po->workOrder ? $po->workOrder->nama_menu : 'Menu MBG',
                    'supplier_id' => $po->supplier_id,
                    'supplier' => $po->supplier ? [
                        'id' => $po->supplier->id,
                        'nama_usaha' => $po->supplier->nama_usaha,
                        'jenis_supplier' => $po->supplier->jenis_supplier,
                        'nama_pemilik' => $po->supplier->nama_pemilik,
                        'no_telp' => $po->supplier->no_telp,
                        'alamat_lengkap' => $po->supplier->alamat_lengkap,
                    ] : null,
                    'vendor' => $po->supplier ? $po->supplier->nama_usaha : ($po->vendor && $po->vendor !== 'Rekanan Pangan SPPG' ? $po->vendor : null),
                    'jenis_transaksi' => $po->jenis_transaksi,
                    'items_count' => $po->items->count(),
                    'total_nominal' => $po->total_nominal_aktual ?: $po->total_nominal_master,
                    'total_nominal_master' => $po->total_nominal_master,
                    'status_po' => $po->status_po,
                    'status_bayar' => $po->status_bayar,
                    'catatan' => $po->catatan,
                    'catatan_rancang_menu' => $po->workOrder ? $po->workOrder->catatan : null,
                    'catatan_keuangan' => $po->workOrder ? $po->workOrder->catatan_keuangan : null,
                    'created_at' => $po->created_at ? $po->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $po->updated_at ? $po->updated_at->format('Y-m-d H:i:s') : null,
                    'diverifikasi_pada' => $po->diverifikasi_pada ? $po->diverifikasi_pada->format('Y-m-d H:i:s') : null,
                    'riwayat_verifikasi' => $po->riwayat_verifikasi ?: ($po->workOrder ? $po->workOrder->riwayat_verifikasi : []) ?: [],
                    'raw' => $rawWo,
                    'items' => $po->items->map(function ($it) use ($po) {
                        $woItem = $it->workOrderItem;
                        if (!$woItem && $po->workOrder && $po->workOrder->items) {
                            $woItem = $po->workOrder->items->first(function ($w) use ($it) {
                                return $w->nama_po === $it->nama || $w->nama === $it->nama || $w->id === $it->work_order_item_id;
                            });
                        }

                        $subKey = $woItem ? ($woItem->sub_menu_key ?: null) : null;
                        if (!$subKey) {
                            $kat = strtolower(($woItem ? $woItem->kategori : $it->kategori) ?? '');
                            $nm = strtolower(($woItem ? $woItem->nama : $it->nama) ?? '');
                            if (str_contains($kat, 'serealia') || str_contains($kat, 'karbohidrat') || str_contains($nm, 'beras') || str_contains($nm, 'nasi')) {
                                $subKey = 'sub_menu_1';
                            } elseif (str_contains($kat, 'daging') || str_contains($kat, 'unggas') || str_contains($kat, 'ikan') || str_contains($kat, 'telur') || str_contains($nm, 'ayam') || str_contains($nm, 'ikan')) {
                                $subKey = 'sub_menu_2';
                            } elseif (str_contains($kat, 'kacang') || str_contains($kat, 'tahu') || str_contains($kat, 'tempe') || str_contains($kat, 'nabati') || str_contains($nm, 'tempe') || str_contains($nm, 'tahu')) {
                                $subKey = 'sub_menu_3';
                            } elseif (str_contains($kat, 'sayur') || str_contains($nm, 'sayur') || str_contains($nm, 'urap') || str_contains($nm, 'wortel')) {
                                $subKey = 'sub_menu_4';
                            } elseif (str_contains($kat, 'buah') || str_contains($nm, 'semangka') || str_contains($nm, 'melon') || str_contains($nm, 'pisang')) {
                                $subKey = 'sub_menu_5';
                            } else {
                                $subKey = 'sub_menu_1';
                            }
                        }

                        $subNama = $woItem ? ($woItem->nama_sub_menu ?: ($po->workOrder ? ($po->workOrder->{$subKey} ?? null) : null)) : ($po->workOrder ? ($po->workOrder->{$subKey} ?? null) : null);

                        return [
                            'id' => $it->id,
                            'nama' => $woItem ? $woItem->nama : $it->nama,
                            'nama_po' => $woItem ? ($woItem->nama_po ?: $woItem->nama) : $it->nama,
                            'sub_menu_key' => $subKey,
                            'sub_menu_block_id' => $woItem ? $woItem->sub_menu_block_id : null,
                            'nama_sub_menu' => $subNama,
                            'kategori' => $woItem ? ($woItem->kategori ?: $it->kategori) : $it->kategori,
                            'tipe_porsi' => $woItem ? ($woItem->tipe_porsi ?: strtolower($it->tipe ?: 'normal')) : strtolower($it->tipe ?: 'normal'),
                            'tipe' => $it->tipe,
                            'jenis_alergi' => $woItem ? ($woItem->jenis_alergi ?: '') : '',
                            'alergen' => $woItem ? ($woItem->alergen ?: '') : '',
                            'gram_pk' => $woItem ? (float)$woItem->gram_pk : 0,
                            'gram_pb' => $woItem ? (float)$woItem->gram_pb : 0,
                            'bdd' => $woItem ? (float)($woItem->bdd ?: 100) : 100,
                            'buffer' => $woItem ? (float)($woItem->buffer ?: 0) : 0,
                            'gross_kg' => (float)$it->gross_kg,
                            'stok_digunakan_kg' => (float)($it->stok_digunakan_kg ?? 0),
                            'qty_beli_po_kg' => $it->qty_beli_po_kg !== null ? (float)$it->qty_beli_po_kg : (float)$it->gross_kg,
                            'sumber_pengadaan' => $it->sumber_pengadaan ?: ($it->stok_digunakan_kg > 0 ? ($it->stok_digunakan_kg >= $it->gross_kg ? '100% Dari Stok' : 'Parsial Stok') : 'Beli PO'),
                            'harga_master' => (float)$it->harga_master,
                            'harga_aktual' => (float)($it->harga_aktual ?: $it->harga_master),
                            'subtotal_master' => (float)($woItem ? ($woItem->subtotal_master ?: ($it->gross_kg * $it->harga_master)) : ($it->gross_kg * $it->harga_master)),
                            'subtotal_aktual' => (float)($it->subtotal_aktual !== null ? $it->subtotal_aktual : (($it->qty_beli_po_kg !== null ? $it->qty_beli_po_kg : $it->gross_kg) * ($it->harga_aktual ?: $it->harga_master))),
                            'keterangan' => $woItem ? ($woItem->keterangan ?: ($it->keterangan ?? '-')) : ($it->keterangan ?? '-'),
                            'supplier_id' => $it->supplier_id,
                            'supplier' => $it->supplier ? [
                                'id' => $it->supplier->id,
                                'nama_usaha' => $it->supplier->nama_usaha,
                                'jenis_supplier' => $it->supplier->jenis_supplier,
                                'nama_pemilik' => $it->supplier->nama_pemilik,
                                'no_telp' => $it->supplier->no_telp,
                            ] : null,
                            'jenis_transaksi' => $it->jenis_transaksi,
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
            'suppliers' => $suppliers ?? [],
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
