<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SurveiHargaPasar;
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
     * Sub-menu: Survei Harga Pasar.
     */
    public function surveiHarga(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'survei-harga');
    }

    /**
     * Simpan data survei harga pasar baru.
     */
    public function storeSurveiHarga(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        $validated = $request->validate([
            'no_dokumen' => ['nullable', 'string', 'max:255'],
            'revisi' => ['nullable', 'string', 'max:50'],
            'tanggal_berlaku' => ['nullable', 'date'],
            'tanggal_survei' => ['required', 'date'],
            'hari_survei' => ['nullable', 'string', 'max:50'],
            'lokasi_survei' => ['nullable', 'string', 'max:255'],
            'petugas_survei' => ['nullable', 'string', 'max:255'],
            'petugas_survei_2' => ['nullable', 'string', 'max:255'],
            'mengetahui_nama' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array'],
            'catatan' => ['nullable', 'string'],
            'uid' => ['nullable', 'string', 'max:100'],
        ]);

        $indonesianDays = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $carbonDate = \Carbon\Carbon::parse($validated['tanggal_survei']);
        $computedHari = $indonesianDays[$carbonDate->dayOfWeek] ?? '';

        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        $romanMonth = $romanMonths[(int) date('n')] ?? 'I';
        $year = date('Y');
        $countThisYear = SurveiHargaPasar::where('unit_sppg_id', $unitSppg?->id)
            ->whereYear('created_at', $year)
            ->count() + 1;
        $defaultNoDokumen = sprintf('%03d/SPPG/KEU/SHP/%s/%s', $countThisYear, $romanMonth, $year);

        $survei = SurveiHargaPasar::create([
            'uid' => !empty($validated['uid']) ? $validated['uid'] : (string) \Illuminate\Support\Str::uuid(),
            'unit_sppg_id' => $unitSppg?->id,
            'user_id' => $user->id,
            'no_dokumen' => $validated['no_dokumen'] ?? $defaultNoDokumen,
            'revisi' => !empty($validated['revisi']) ? $validated['revisi'] : null,
            'tanggal_berlaku' => $validated['tanggal_berlaku'] ?? now()->toDateString(),
            'tanggal_survei' => $validated['tanggal_survei'],
            'hari_survei' => !empty($validated['hari_survei']) ? $validated['hari_survei'] : $computedHari,
            'lokasi_survei' => $validated['lokasi_survei'] ?? null,
            'petugas_survei' => $validated['petugas_survei'] ?? ($user->nama_lengkap ?: ($user->nama ?: 'Kepala SPPG')),
            'petugas_survei_2' => $validated['petugas_survei_2'] ?? null,
            'mengetahui_nama' => $validated['mengetahui_nama'] ?? null,
            'status' => $validated['status'] ?? 'Selesai',
            'items' => $validated['items'],
            'catatan' => $validated['catatan'] ?? null,
        ]);

        return back()->with('success', 'Data formulir survei harga pasar berhasil disimpan.');
    }

    /**
     * Update data survei harga pasar.
     */
    public function updateSurveiHarga(Request $request, $id): RedirectResponse
    {
        $survei = is_numeric($id)
            ? SurveiHargaPasar::where('id', $id)->firstOrFail()
            : SurveiHargaPasar::where('uid', $id)->firstOrFail();
        $user = $request->user();

        $validated = $request->validate([
            'no_dokumen' => ['nullable', 'string', 'max:255'],
            'revisi' => ['nullable', 'string', 'max:50'],
            'tanggal_berlaku' => ['nullable', 'date'],
            'tanggal_survei' => ['required', 'date'],
            'hari_survei' => ['nullable', 'string', 'max:50'],
            'lokasi_survei' => ['nullable', 'string', 'max:255'],
            'petugas_survei' => ['nullable', 'string', 'max:255'],
            'petugas_survei_2' => ['nullable', 'string', 'max:255'],
            'mengetahui_nama' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array'],
            'catatan' => ['nullable', 'string'],
        ]);

        $indonesianDays = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $carbonDate = \Carbon\Carbon::parse($validated['tanggal_survei']);
        $computedHari = $indonesianDays[$carbonDate->dayOfWeek] ?? '';

        $survei->update([
            'no_dokumen' => $validated['no_dokumen'] ?? $survei->no_dokumen,
            'revisi' => !empty($validated['revisi']) ? $validated['revisi'] : null,
            'tanggal_berlaku' => $validated['tanggal_berlaku'] ?? $survei->tanggal_berlaku,
            'tanggal_survei' => $validated['tanggal_survei'],
            'hari_survei' => !empty($validated['hari_survei']) ? $validated['hari_survei'] : $computedHari,
            'lokasi_survei' => $validated['lokasi_survei'] ?? null,
            'petugas_survei' => $validated['petugas_survei'] ?? ($survei->petugas_survei ?: ($user->nama_lengkap ?: $user->nama)),
            'petugas_survei_2' => $validated['petugas_survei_2'] ?? null,
            'mengetahui_nama' => $validated['mengetahui_nama'] ?? null,
            'status' => $validated['status'] ?? 'Selesai',
            'items' => $validated['items'],
            'catatan' => $validated['catatan'] ?? null,
        ]);

        return back()->with('success', 'Formulir survei harga pasar berhasil diperbarui.');
    }

    /**
     * Hapus data survei harga pasar.
     */
    public function destroySurveiHarga($id): RedirectResponse
    {
        $survei = is_numeric($id)
            ? SurveiHargaPasar::where('id', $id)->firstOrFail()
            : SurveiHargaPasar::where('uid', $id)->firstOrFail();
        $survei->delete();

        return back()->with('success', 'Formulir survei harga pasar berhasil dihapus.');
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
                            'satuan' => $woItem ? ($woItem->satuan ?: ($it->satuan ?: 'Kg')) : ($it->satuan ?: 'Kg'),
                            'jenis' => $woItem ? ($woItem->jenis ?: ($it->jenis ?: 'bahan_baku')) : ($it->jenis ?: 'bahan_baku'),
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

        // 3. Data Survei Harga Pasar
        $surveiHargaList = SurveiHargaPasar::with(['user', 'unitSppg'])
            ->when($unitSppg, function ($q) use ($unitSppg) {
                $q->where('unit_sppg_id', $unitSppg->id);
            })
            ->latest('tanggal_survei')
            ->latest('id')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'uid' => $s->uid,
                    'no_dokumen' => $s->no_dokumen,
                    'revisi' => $s->revisi,
                    'tanggal_berlaku' => $s->tanggal_berlaku ? $s->tanggal_berlaku->format('Y-m-d') : null,
                    'tanggal_survei' => $s->tanggal_survei ? $s->tanggal_survei->format('Y-m-d') : null,
                    'hari_survei' => $s->hari_survei,
                    'lokasi_survei' => $s->lokasi_survei,
                    'petugas_survei' => $s->petugas_survei,
                    'petugas_survei_2' => $s->petugas_survei_2,
                    'mengetahui_nama' => $s->mengetahui_nama,
                    'status' => $s->status,
                    'catatan' => $s->catatan,
                    'items' => $s->items ?? [],
                    'total_items' => is_array($s->items) ? count($s->items) : 0,
                    'created_at' => $s->created_at ? $s->created_at->format('Y-m-d H:i:s') : null,
                ];
            });

        $defaultSurveiItems = self::getDefaultSurveiItems();

        return Inertia::render('Keuangan/Index', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
            'activeTab' => $activeTab,
            'verifikasiPoList' => $verifikasiPoList ?? [],
            'poList' => $poList,
            'suppliers' => $suppliers ?? [],
            'surveiHargaList' => $surveiHargaList,
            'defaultSurveiItems' => $defaultSurveiItems,
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

    /**
     * Template Daftar Bahan Baku Standar Formulir Survei Harga Pasar (Sesuai Format Resmi BGN).
     */
    public static function getDefaultSurveiItems(): array
    {
        return [
            // 1. KARBOHIDRAT
            ['id' => 1, 'kategori' => 'karbohidrat', 'kategori_label' => 'KARBOHIDRAT', 'nomor' => 1, 'nama_bahan' => 'Beras', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 2, 'kategori' => 'karbohidrat', 'kategori_label' => 'KARBOHIDRAT', 'nomor' => 2, 'nama_bahan' => 'Kentang', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],

            // 2. HEWANI
            ['id' => 3, 'kategori' => 'hewani', 'kategori_label' => 'HEWANI', 'nomor' => 1, 'nama_bahan' => 'Ayam Potong', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 4, 'kategori' => 'hewani', 'kategori_label' => 'HEWANI', 'nomor' => 2, 'nama_bahan' => 'Ayam Fillet', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 5, 'kategori' => 'hewani', 'kategori_label' => 'HEWANI', 'nomor' => 3, 'nama_bahan' => 'Telur Ukuran Besar', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 6, 'kategori' => 'hewani', 'kategori_label' => 'HEWANI', 'nomor' => 4, 'nama_bahan' => 'Telur Ukuran Sedang', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 7, 'kategori' => 'hewani', 'kategori_label' => 'HEWANI', 'nomor' => 5, 'nama_bahan' => 'Telur Ukuran Kecil', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 8, 'kategori' => 'hewani', 'kategori_label' => 'HEWANI', 'nomor' => 6, 'nama_bahan' => 'Daging Sapi', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 9, 'kategori' => 'hewani', 'kategori_label' => 'HEWANI', 'nomor' => 7, 'nama_bahan' => 'Telur Puyuh', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 10, 'kategori' => 'hewani', 'kategori_label' => 'HEWANI', 'nomor' => 8, 'nama_bahan' => 'Susu Full Cream', 'satuan' => 'Pcs', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],

            // 3. NABATI
            ['id' => 11, 'kategori' => 'nabati', 'kategori_label' => 'NABATI', 'nomor' => 1, 'nama_bahan' => 'Tahu', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 12, 'kategori' => 'nabati', 'kategori_label' => 'NABATI', 'nomor' => 2, 'nama_bahan' => 'Tempe', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],

            // 4. SAYURAN
            ['id' => 13, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 1, 'nama_bahan' => 'Bayam', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 14, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 2, 'nama_bahan' => 'Buncis', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 15, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 3, 'nama_bahan' => 'Jagung Pipil', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 16, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 4, 'nama_bahan' => 'Kol Putih', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 17, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 5, 'nama_bahan' => 'Wortel Lokal', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 18, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 6, 'nama_bahan' => 'Wortel Berastagi', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 19, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 7, 'nama_bahan' => 'Brokoli', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 20, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 8, 'nama_bahan' => 'Kembang Kol', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 21, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 9, 'nama_bahan' => 'Tomat', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 22, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 10, 'nama_bahan' => 'Daun Pandan', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 23, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 11, 'nama_bahan' => 'Daun Seledri', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 24, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 12, 'nama_bahan' => 'Daun Salam', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 25, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 13, 'nama_bahan' => 'Cabe Teropong Merah', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 26, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 14, 'nama_bahan' => 'Cabe Keriting', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 27, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 15, 'nama_bahan' => 'Jeruk Nipis', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 28, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 16, 'nama_bahan' => 'Kunyit Mentah', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 29, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 17, 'nama_bahan' => 'Lengkuas', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 30, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 18, 'nama_bahan' => 'Pala', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 31, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 19, 'nama_bahan' => 'Bawang Merah Kupas', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 32, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 20, 'nama_bahan' => 'Bawang Putih Kupas', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 33, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 21, 'nama_bahan' => 'Asam Jawa', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 34, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 22, 'nama_bahan' => 'Bawang Bombay', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 35, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 23, 'nama_bahan' => 'Gula Merah', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 36, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 24, 'nama_bahan' => 'Sereh', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 37, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 25, 'nama_bahan' => 'Bawang Merah Utuh', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 38, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 26, 'nama_bahan' => 'Bawang Putih Utuh', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 39, 'kategori' => 'sayuran', 'kategori_label' => 'SAYURAN', 'nomor' => 27, 'nama_bahan' => 'Kemiri Pecah', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],

            // 5. BUAH
            ['id' => 40, 'kategori' => 'buah', 'kategori_label' => 'BUAH', 'nomor' => 1, 'nama_bahan' => 'Buah Naga', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 41, 'kategori' => 'buah', 'kategori_label' => 'BUAH', 'nomor' => 2, 'nama_bahan' => 'Jeruk Manis', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 42, 'kategori' => 'buah', 'kategori_label' => 'BUAH', 'nomor' => 3, 'nama_bahan' => 'Jeruk Santang', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 43, 'kategori' => 'buah', 'kategori_label' => 'BUAH', 'nomor' => 4, 'nama_bahan' => 'Kelengkeng', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 44, 'kategori' => 'buah', 'kategori_label' => 'BUAH', 'nomor' => 5, 'nama_bahan' => 'Anggur', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 45, 'kategori' => 'buah', 'kategori_label' => 'BUAH', 'nomor' => 6, 'nama_bahan' => 'Apel Fuji', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 46, 'kategori' => 'buah', 'kategori_label' => 'BUAH', 'nomor' => 7, 'nama_bahan' => 'Melon', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 47, 'kategori' => 'buah', 'kategori_label' => 'BUAH', 'nomor' => 8, 'nama_bahan' => 'Semangka Merah', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 48, 'kategori' => 'buah', 'kategori_label' => 'BUAH', 'nomor' => 9, 'nama_bahan' => 'Semangka Kuning', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 49, 'kategori' => 'buah', 'kategori_label' => 'BUAH', 'nomor' => 10, 'nama_bahan' => 'Pisang Mas', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 50, 'kategori' => 'buah', 'kategori_label' => 'BUAH', 'nomor' => 11, 'nama_bahan' => 'Pisang Ambon', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 51, 'kategori' => 'buah', 'kategori_label' => 'BUAH', 'nomor' => 12, 'nama_bahan' => 'Salak', 'satuan' => 'Kg', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],

            // 6. LAIN-LAIN
            ['id' => 52, 'kategori' => 'lain_lain', 'kategori_label' => 'Lain-lain', 'nomor' => 1, 'nama_bahan' => 'Sabun Cuci Piring', 'satuan' => 'Pcs', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
            ['id' => 53, 'kategori' => 'lain_lain', 'kategori_label' => 'Lain-lain', 'nomor' => 2, 'nama_bahan' => 'Karbol', 'satuan' => 'Btl', 'harga' => null, 'nama_toko' => '', 'kontak' => '', 'keterangan' => ''],
        ];
    }
}

