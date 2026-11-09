<?php

namespace App\Http\Controllers;

use App\Models\KelompokPenerimaManfaat;
use App\Models\Periode;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SurveiHargaPasar;
use App\Models\WorkOrder;
use App\Models\PurchaseOrderItem;
use App\Models\TransaksiKeuangan;
use App\Models\KeuanganSaldoAwal;
use App\Models\KeuanganPenyesuaian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KeuanganController extends Controller
{
    /**
     * Tampilkan halaman tata kelola keuangan (default ke sub menu Persetujuan).
     */
    public function index(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'verifikasi-po');
    }

    /**
     * Sub-menu 1: Anggaran & Biaya per Porsi.
     */
    public function anggaran(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'anggaran');
    }

    /**
     * Sub-menu: Persetujuan (yang awalnya bernama Verifikasi).
     */
    public function persetujuan(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'verifikasi-po');
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
     * Simpan transaksi manual baru (tunggal atau delegasi ke batch jika array items dikirim).
     */
    public function storeTransaksi(Request $request): RedirectResponse
    {
        if ($request->has('items') && is_array($request->items)) {
            return $this->storeTransaksiBatch($request);
        }

        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'tanggal_pesanan' => ['nullable', 'date'],
            'kategori' => ['required', 'string', 'in:Bahan Baku,Operasional,Insentif Fasilitas,Dana Bantuan Pemerintah'],
            'arus' => ['required', 'string', 'in:Masuk,Keluar'],
            'nama_transaksi' => ['required', 'string', 'max:255'],
            'volume' => ['nullable', 'numeric', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:Disetujui,Ditunda,Ditolak,Dibatalkan'],
            'perlu_npo' => ['nullable', 'boolean'],
            'supplier_id' => ['nullable'],
            'work_order_id' => ['nullable'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'metode_bayar' => ['nullable', 'string', 'max:100'],
        ]);

        $isSimplified = in_array($validated['kategori'], ['Insentif Fasilitas', 'Dana Bantuan Pemerintah']);

        if (!$isSimplified) {
            if (empty($validated['supplier_id'])) {
                return back()->withErrors(['supplier_id' => 'Supplier wajib dipilih untuk kategori Bahan Baku dan Operasional.']);
            }
            if (!isset($validated['volume']) || $validated['volume'] === '' || $validated['volume'] === null) {
                return back()->withErrors(['volume' => 'Volume (jumlah) wajib diisi.']);
            }
            if (empty($validated['satuan'])) {
                return back()->withErrors(['satuan' => 'Satuan wajib dipilih.']);
            }
            if (empty($validated['work_order_id'])) {
                return back()->withErrors(['work_order_id' => 'Sandingkan Work Order wajib dipilih.']);
            }
        }

        if ($isSimplified) {
            $perluNpo = false;
            $tanggalPesanan = $validated['tanggal'];
            $workOrderId = null;
            $volume = 1;
            $satuan = '-';
            $supplierId = null;
            $vendorName = '-';
        } else {
            $perluNpo = isset($validated['perlu_npo']) ? (bool) $validated['perlu_npo'] : true;
            $tanggalPesanan = $perluNpo ? (!empty($validated['tanggal_pesanan']) ? $validated['tanggal_pesanan'] : $validated['tanggal']) : $validated['tanggal'];
            $rawWoId = $validated['work_order_id'] ?? null;
            $workOrderId = (is_numeric($rawWoId) && (int)$rawWoId > 0) ? (int)$rawWoId : null;
            $volume = $validated['volume'] ?? 1;
            $satuan = $validated['satuan'] ?? 'Kg';
            $supplierId = $validated['supplier_id'] ?? null;
            $vendorName = $validated['vendor'] ?? null;
            if (!empty($supplierId)) {
                $sup = Supplier::find($supplierId);
                if ($sup) {
                    $vendorName = $sup->nama_usaha;
                }
            }
        }

        $tglStr = date('Ymd', strtotime($validated['tanggal']));
        $countToday = TransaksiKeuangan::whereDate('created_at', now()->toDateString())->count() + 1;
        $nomorTrx = 'TRX-' . $tglStr . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

        TransaksiKeuangan::create([
            'unit_sppg_id' => $unitSppg ? $unitSppg->id : null,
            'user_id' => $user->id,
            'nomor_transaksi' => $nomorTrx,
            'tanggal' => $validated['tanggal'],
            'tanggal_pesanan' => $tanggalPesanan,
            'kategori' => $validated['kategori'],
            'arus' => $validated['arus'],
            'nama_transaksi' => $validated['nama_transaksi'],
            'volume' => $volume,
            'satuan' => $satuan,
            'nominal' => $validated['nominal'],
            'status' => $validated['status'],
            'perlu_npo' => $perluNpo,
            'supplier_id' => $supplierId,
            'work_order_id' => $workOrderId,
            'vendor' => $vendorName,
            'keterangan' => $validated['keterangan'] ?? null,
            'metode_bayar' => $validated['metode_bayar'] ?? null,
        ]);

        return back()->with('success', 'Transaksi berhasil ditambahkan.');
    }

    /**
     * Simpan transaksi multi / batch (input tabel banyak baris atau tempel dari Excel).
     */
    public function storeTransaksiBatch(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.tanggal' => ['required', 'date'],
            'items.*.tanggal_pesanan' => ['nullable', 'date'],
            'items.*.kategori' => ['required', 'string', 'in:Bahan Baku,Operasional,Insentif Fasilitas,Dana Bantuan Pemerintah'],
            'items.*.arus' => ['required', 'string', 'in:Masuk,Keluar'],
            'items.*.nama_transaksi' => ['required', 'string', 'max:255'],
            'items.*.volume' => ['nullable', 'numeric', 'min:0'],
            'items.*.satuan' => ['nullable', 'string', 'max:50'],
            'items.*.nominal' => ['required', 'numeric', 'min:0'],
            'items.*.status' => ['required', 'string', 'in:Disetujui,Ditunda,Ditolak,Dibatalkan'],
            'items.*.perlu_npo' => ['nullable', 'boolean'],
            'items.*.supplier_id' => ['nullable'],
            'items.*.work_order_id' => ['nullable'],
            'items.*.vendor' => ['nullable', 'string', 'max:255'],
            'items.*.keterangan' => ['nullable', 'string', 'max:1000'],
            'items.*.metode_bayar' => ['nullable', 'string', 'max:100'],
        ]);

        $suppliersCache = Supplier::pluck('nama_usaha', 'id')->toArray();
        $todayStr = now()->toDateString();
        $countToday = TransaksiKeuangan::whereDate('created_at', $todayStr)->count();

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $user, $unitSppg, $suppliersCache, &$countToday) {
            foreach ($validated['items'] as $item) {
                $isSimplified = in_array($item['kategori'], ['Insentif Fasilitas', 'Dana Bantuan Pemerintah']);

                if ($isSimplified) {
                    $perluNpo = false;
                    $tanggalPesanan = $item['tanggal'];
                    $workOrderId = null;
                    $volume = 1;
                    $satuan = '-';
                    $supplierId = null;
                    $vendorName = '-';
                } else {
                    $perluNpo = isset($item['perlu_npo']) ? (bool) $item['perlu_npo'] : true;
                    $tanggalPesanan = $perluNpo ? (!empty($item['tanggal_pesanan']) ? $item['tanggal_pesanan'] : $item['tanggal']) : $item['tanggal'];
                    $rawWoId = $item['work_order_id'] ?? null;
                    $workOrderId = (is_numeric($rawWoId) && (int)$rawWoId > 0) ? (int)$rawWoId : null;
                    $volume = isset($item['volume']) && $item['volume'] !== '' && $item['volume'] !== null ? $item['volume'] : 1;
                    $satuan = !empty($item['satuan']) && $item['satuan'] !== '-' ? $item['satuan'] : 'Kg';
                    $supplierId = !empty($item['supplier_id']) ? $item['supplier_id'] : null;
                    $vendorName = $item['vendor'] ?? null;
                    if (!empty($supplierId) && isset($suppliersCache[$supplierId])) {
                        $vendorName = $suppliersCache[$supplierId];
                    }
                }

                $countToday++;
                $tglStr = date('Ymd', strtotime($item['tanggal']));
                $nomorTrx = 'TRX-' . $tglStr . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

                TransaksiKeuangan::create([
                    'unit_sppg_id' => $unitSppg ? $unitSppg->id : null,
                    'user_id' => $user->id,
                    'nomor_transaksi' => $nomorTrx,
                    'tanggal' => $item['tanggal'],
                    'tanggal_pesanan' => $tanggalPesanan,
                    'kategori' => $item['kategori'],
                    'arus' => $item['arus'] ?? 'Keluar',
                    'nama_transaksi' => $item['nama_transaksi'],
                    'volume' => $volume,
                    'satuan' => $satuan,
                    'nominal' => $item['nominal'],
                    'status' => $item['status'],
                    'perlu_npo' => $perluNpo,
                    'supplier_id' => $supplierId,
                    'work_order_id' => $workOrderId,
                    'vendor' => $vendorName,
                    'keterangan' => $item['keterangan'] ?? null,
                    'metode_bayar' => $item['metode_bayar'] ?? 'Tunai',
                ]);
            }
        });

        $totalInserted = count($validated['items']);
        return back()->with('success', "{$totalInserted} data transaksi berhasil ditambahkan sekaligus.");
    }

    /**
     * Update transaksi manual.
     */
    public function updateTransaksi(Request $request, $id): RedirectResponse
    {
        $trx = TransaksiKeuangan::findOrFail($id);

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'tanggal_pesanan' => ['nullable', 'date'],
            'kategori' => ['required', 'string', 'in:Bahan Baku,Operasional,Insentif Fasilitas,Dana Bantuan Pemerintah'],
            'arus' => ['required', 'string', 'in:Masuk,Keluar'],
            'nama_transaksi' => ['required', 'string', 'max:255'],
            'volume' => ['nullable', 'numeric', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:Disetujui,Ditunda,Ditolak,Dibatalkan'],
            'perlu_npo' => ['nullable', 'boolean'],
            'supplier_id' => ['nullable'],
            'work_order_id' => ['nullable'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'metode_bayar' => ['nullable', 'string', 'max:100'],
        ]);

        $isSimplified = in_array($validated['kategori'], ['Insentif Fasilitas', 'Dana Bantuan Pemerintah']);

        if (!$isSimplified) {
            if (empty($validated['supplier_id'])) {
                return back()->withErrors(['supplier_id' => 'Supplier wajib dipilih untuk kategori Bahan Baku dan Operasional.']);
            }
            if (!isset($validated['volume']) || $validated['volume'] === '' || $validated['volume'] === null) {
                return back()->withErrors(['volume' => 'Volume (jumlah) wajib diisi.']);
            }
            if (empty($validated['satuan'])) {
                return back()->withErrors(['satuan' => 'Satuan wajib dipilih.']);
            }
            if (empty($validated['work_order_id'])) {
                return back()->withErrors(['work_order_id' => 'Sandingkan Work Order wajib dipilih.']);
            }
        }

        if ($isSimplified) {
            $perluNpo = false;
            $tanggalPesanan = $validated['tanggal'];
            $workOrderId = null;
            $volume = 1;
            $satuan = '-';
            $supplierId = null;
            $vendorName = '-';
        } else {
            $perluNpo = isset($validated['perlu_npo']) ? (bool) $validated['perlu_npo'] : true;
            $tanggalPesanan = $perluNpo ? (!empty($validated['tanggal_pesanan']) ? $validated['tanggal_pesanan'] : $validated['tanggal']) : $validated['tanggal'];
            $rawWoId = $validated['work_order_id'] ?? null;
            $workOrderId = (is_numeric($rawWoId) && (int)$rawWoId > 0) ? (int)$rawWoId : null;
            $volume = $validated['volume'] ?? 1;
            $satuan = $validated['satuan'] ?? 'Kg';
            $supplierId = $validated['supplier_id'] ?? null;
            $vendorName = $validated['vendor'] ?? $trx->vendor;
            if (!empty($supplierId)) {
                $sup = Supplier::find($supplierId);
                if ($sup) {
                    $vendorName = $sup->nama_usaha;
                }
            }
        }

        $trx->update([
            'tanggal' => $validated['tanggal'],
            'tanggal_pesanan' => $tanggalPesanan,
            'kategori' => $validated['kategori'],
            'arus' => $validated['arus'],
            'nama_transaksi' => $validated['nama_transaksi'],
            'volume' => $volume,
            'satuan' => $satuan,
            'nominal' => $validated['nominal'],
            'status' => $validated['status'],
            'perlu_npo' => $perluNpo,
            'supplier_id' => $supplierId,
            'work_order_id' => $workOrderId,
            'vendor' => $vendorName,
            'keterangan' => $validated['keterangan'] ?? null,
            'metode_bayar' => $validated['metode_bayar'] ?? null,
        ]);

        return back()->with('success', 'Transaksi berhasil diperbarui.');
    }

    /**
     * Hapus transaksi manual.
     */
    public function destroyTransaksi(Request $request, $id): RedirectResponse
    {
        $trx = TransaksiKeuangan::findOrFail($id);
        $trx->delete();

        return back()->with('success', 'Transaksi berhasil dihapus.');
    }

    /**
     * Perbarui status transaksi langsung (Setujui / Tunda / Tolak / Batalkan).
     */
    public function updateStatusTransaksi(Request $request, $id): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:Disetujui,Ditunda,Ditolak,Dibatalkan'],
        ]);

        $trx = TransaksiKeuangan::findOrFail($id);
        $trx->update([
            'status' => $validated['status'],
        ]);

        $statusMsg = match ($validated['status']) {
            'Disetujui' => "Transaksi {$trx->nomor_transaksi} berhasil disetujui dan kini sah terhitung dalam realisasi keuangan.",
            'Ditunda' => "Status transaksi {$trx->nomor_transaksi} berhasil diubah menjadi Ditunda.",
            'Ditolak' => "Status transaksi {$trx->nomor_transaksi} berhasil diubah menjadi Ditolak.",
            'Dibatalkan' => "Status transaksi {$trx->nomor_transaksi} berhasil diubah menjadi Dibatalkan.",
            default => 'Status transaksi berhasil diperbarui.',
        };

        return back()->with('success', $statusMsg);
    }

    /**
     * Sub-menu 3: Transaksi & Riwayat Pengeluaran/Penerimaan.
     */
    /**
     * Sub-menu Penyesuaian Keuangan & Saldo Awal.
     */
    public function penyesuaian(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'penyesuaian');
    }

    /**
     * Simpan Saldo Awal.
     */
    public function storeSaldoAwal(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        KeuanganSaldoAwal::updateOrCreate(
            ['unit_sppg_id' => $unitSppg->id],
            [
                'user_id' => $user->id,
                'tanggal' => $validated['tanggal'],
                'nominal' => $validated['nominal'],
                'keterangan' => $validated['keterangan'] ?: 'Saldo Awal Kas / Bank Unit SPPG',
                'is_locked' => false,
            ]
        );

        return back()->with('success', 'Saldo Awal berhasil disimpan.');
    }

    /**
     * Perbarui Saldo Awal (dapat diubah kembali).
     */
    public function updateSaldoAwal(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $saldo = KeuanganSaldoAwal::where('unit_sppg_id', $unitSppg->id)->first();
        if ($saldo) {
            $saldo->update([
                'user_id' => $user->id,
                'tanggal' => $validated['tanggal'],
                'nominal' => $validated['nominal'],
                'keterangan' => $validated['keterangan'] ?: 'Saldo Awal Kas / Bank Unit SPPG',
            ]);
        } else {
            KeuanganSaldoAwal::create([
                'unit_sppg_id' => $unitSppg->id,
                'user_id' => $user->id,
                'tanggal' => $validated['tanggal'],
                'nominal' => $validated['nominal'],
                'keterangan' => $validated['keterangan'] ?: 'Saldo Awal Kas / Bank Unit SPPG',
                'is_locked' => false,
            ]);
        }

        return back()->with('success', 'Saldo Awal berhasil diperbarui.');
    }

    /**
     * Tarik / Reset Saldo Awal agar bisa diisi ulang.
     */
    public function destroySaldoAwal(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        KeuanganSaldoAwal::where('unit_sppg_id', $unitSppg->id)->delete();

        return back()->with('success', 'Saldo Awal berhasil ditarik / direset.');
    }

    /**
     * Simpan Penyesuaian Keuangan (Uang Masuk / Keluar).
     */
    public function storePenyesuaian(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'arah' => ['required', 'in:masuk,keluar'],
            'jenis' => ['required', 'string', 'max:100'],
            'jumlah' => ['required', 'numeric', 'min:0.01'],
            'alasan' => ['required', 'string', 'max:1000'],
        ]);

        $tglStr = date('Ymd', strtotime($validated['tanggal']));
        $countToday = KeuanganPenyesuaian::whereDate('created_at', now()->toDateString())->count() + 1;
        $nomorPenyesuaian = 'ADJ-' . $tglStr . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

        KeuanganPenyesuaian::create([
            'unit_sppg_id' => $unitSppg ? $unitSppg->id : null,
            'user_id' => $user->id,
            'nomor_penyesuaian' => $nomorPenyesuaian,
            'tanggal' => $validated['tanggal'],
            'arah' => $validated['arah'],
            'jenis' => $validated['jenis'],
            'jumlah' => $validated['jumlah'],
            'alasan' => $validated['alasan'],
        ]);

        return back()->with('success', 'Penyesuaian keuangan berhasil disimpan.');
    }

    /**
     * Perbarui Penyesuaian Keuangan.
     */
    public function updatePenyesuaian(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        $penyesuaian = KeuanganPenyesuaian::where('id', $id)
            ->when($unitSppg, fn($q) => $q->where('unit_sppg_id', $unitSppg->id))
            ->firstOrFail();

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'arah' => ['required', 'in:masuk,keluar'],
            'jenis' => ['required', 'string', 'max:100'],
            'jumlah' => ['required', 'numeric', 'min:0.01'],
            'alasan' => ['required', 'string', 'max:1000'],
        ]);

        $penyesuaian->update([
            'tanggal' => $validated['tanggal'],
            'arah' => $validated['arah'],
            'jenis' => $validated['jenis'],
            'jumlah' => $validated['jumlah'],
            'alasan' => $validated['alasan'],
        ]);

        return back()->with('success', 'Penyesuaian keuangan berhasil diperbarui.');
    }

    /**
     * Hapus Penyesuaian Keuangan.
     */
    public function destroyPenyesuaian(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        $penyesuaian = KeuanganPenyesuaian::where('id', $id)
            ->when($unitSppg, fn($q) => $q->where('unit_sppg_id', $unitSppg->id))
            ->firstOrFail();

        $penyesuaian->delete();

        return back()->with('success', 'Penyesuaian keuangan berhasil dihapus.');
    }

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
     * Sub-menu: LPDH (Laporan Pertanggungjawaban Dana Harian - Segera Hadir).
     */
    public function lpdh(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'lpdh');
    }

    /**
     * Sub-menu: Neraca (Neraca Keuangan SPPG - Segera Hadir).
     */
    public function neraca(Request $request): Response
    {
        return $this->renderKeuanganView($request, 'neraca');
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
        /**
     * Simpan / Terbitkan Purchase Order Baru (dari Work Order Otomatis atau Manual).
     */
    public function storePo(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $source = $request->input('source', 'work_order'); // 'work_order' atau 'manual'

        if ($source === 'work_order') {
            $validated = $request->validate([
                'work_order_id' => ['required', 'exists:work_orders,id'],
                'nomor_po' => ['required', 'string', 'max:100'],
                'tanggal' => ['required', 'date'],
                'supplier_id' => ['nullable', 'exists:suppliers,id'],
                'jenis_transaksi' => ['nullable', 'string', 'max:100'],
                'status_bayar' => ['nullable', 'string', 'in:Belum Bayar,Lunas'],
                'catatan' => ['nullable', 'string'],
                'items' => ['nullable', 'array'],
            ]);

            $workOrder = WorkOrder::with('items')->where('unit_sppg_id', $unitSppg->id)->findOrFail($validated['work_order_id']);

            if ($workOrder->metode_wo === 'manual') {
                return back()->with('error', 'Work Order manual tidak memiliki rincian bahan baku. Gunakan opsi Tambah Manual PO untuk membuat PO.');
            }

            $existingPoWithNomor = PurchaseOrder::where('nomor_po', $validated['nomor_po'])
                ->where('work_order_id', '!=', $workOrder->id)
                ->first();
            if ($existingPoWithNomor) {
                return back()->with('error', 'Nomor PO ' . $validated['nomor_po'] . ' sudah digunakan oleh Purchase Order lain.');
            }

            DB::transaction(function () use ($unitSppg, $user, $workOrder, $validated) {
                $vendorName = null;
                if (!empty($validated['supplier_id'])) {
                    $sup = Supplier::find($validated['supplier_id']);
                    $vendorName = $sup?->nama_usaha;
                }

                $po = PurchaseOrder::updateOrCreate(
                    ['work_order_id' => $workOrder->id],
                    [
                        'unit_sppg_id' => $unitSppg->id,
                        'nomor_po' => $validated['nomor_po'],
                        'nama_menu' => $workOrder->nama_menu,
                        'tanggal' => $validated['tanggal'],
                        'supplier_id' => $validated['supplier_id'] ?? null,
                        'vendor' => $vendorName,
                        'jenis_transaksi' => $validated['jenis_transaksi'] ?? 'Bahan Baku',
                        'status_po' => 'Terverifikasi',
                        'status_bayar' => $validated['status_bayar'] ?? 'Belum Bayar',
                        'catatan' => $validated['catatan'] ?? ('Diterbitkan dari WO ' . $workOrder->nomor_wo . ' (' . $workOrder->nama_menu . ')'),
                        'diverifikasi_oleh' => $user->id,
                        'diverifikasi_pada' => now(),
                    ]
                );

                if (!empty($validated['items'])) {
                    $po->items()->delete();
                    $totalMaster = 0;
                    $totalAktual = 0;
                    foreach ($validated['items'] as $it) {
                        $gross = (float)($it['gross_kg'] ?? 0);
                        $hargaMaster = (float)($it['harga_master'] ?? 0);
                        $hargaAktual = (float)($it['harga_aktual'] ?? $hargaMaster);
                        $subtotal = $gross * $hargaAktual;

                        $totalMaster += ($gross * $hargaMaster);
                        $totalAktual += $subtotal;

                        PurchaseOrderItem::create([
                            'purchase_order_id' => $po->id,
                            'work_order_item_id' => $it['work_order_item_id'] ?? null,
                            'nama' => $it['nama'],
                            'kategori' => $it['kategori'] ?? 'Lainnya',
                            'tipe' => $it['tipe'] ?? 'Normal',
                            'satuan' => $it['satuan'] ?? 'Kg',
                            'jenis' => $it['jenis'] ?? 'bahan_baku',
                            'gross_kg' => $gross,
                            'qty_beli_po_kg' => $gross,
                            'sumber_pengadaan' => 'Beli PO',
                            'harga_master' => $hargaMaster,
                            'harga_aktual' => $hargaAktual,
                            'subtotal_aktual' => $subtotal,
                            'supplier_id' => $it['supplier_id'] ?? $validated['supplier_id'] ?? null,
                            'jenis_transaksi' => $it['jenis_transaksi'] ?? $validated['jenis_transaksi'] ?? 'Bahan Baku',
                            'keterangan' => $it['keterangan'] ?? null,
                        ]);
                    }
                    $po->update([
                        'items_count' => count($validated['items']),
                        'total_nominal_master' => $totalMaster,
                        'total_nominal_aktual' => $totalAktual,
                    ]);
                } else {
                    self::ensurePoItemsConsolidated($po);
                }

                if (in_array($workOrder->status, ['Draft', 'Diajukan ke Keuangan'])) {
                    $workOrder->update(['status' => 'Siap Produksi']);
                }
            });

            return back()->with('success', 'Purchase Order ' . $validated['nomor_po'] . ' berhasil diterbitkan dari Work Order ' . $workOrder->nomor_wo . '.');
        } else {
            // Source === 'manual'
            $validated = $request->validate([
                'nomor_po' => ['required', 'string', 'max:100', 'unique:purchase_orders,nomor_po'],
                'nama_menu' => ['required', 'string', 'max:255'],
                'tanggal' => ['required', 'date'],
                'supplier_id' => ['nullable', 'exists:suppliers,id'],
                'jenis_transaksi' => ['nullable', 'string', 'max:100'],
                'status_bayar' => ['nullable', 'string', 'in:Belum Bayar,Lunas'],
                'catatan' => ['nullable', 'string'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.nama' => ['required', 'string', 'max:255'],
                'items.*.kategori' => ['nullable', 'string', 'max:100'],
                'items.*.satuan' => ['nullable', 'string', 'max:50'],
                'items.*.gross_kg' => ['required', 'numeric', 'min:0'],
                'items.*.harga_aktual' => ['required', 'numeric', 'min:0'],
                'items.*.supplier_id' => ['nullable', 'exists:suppliers,id'],
                'items.*.jenis_transaksi' => ['nullable', 'string', 'max:100'],
                'items.*.keterangan' => ['nullable', 'string'],
            ], [
                'nomor_po.unique' => 'Nomor PO ini sudah terdaftar.',
                'nama_menu.required' => 'Nama Menu / Keterangan Belanja wajib diisi.',
                'items.required' => 'Minimal satu baris bahan baku/belanja harus diinput.',
                'items.*.nama.required' => 'Nama bahan baku wajib diisi pada setiap baris.',
            ]);

            DB::transaction(function () use ($unitSppg, $user, $validated) {
                $totalNominal = 0;
                foreach ($validated['items'] as $it) {
                    $gross = (float)$it['gross_kg'];
                    $harga = (float)$it['harga_aktual'];
                    $totalNominal += ($gross * $harga);
                }

                $vendorName = null;
                if (!empty($validated['supplier_id'])) {
                    $sup = Supplier::find($validated['supplier_id']);
                    $vendorName = $sup?->nama_usaha;
                }

                $po = PurchaseOrder::create([
                    'unit_sppg_id' => $unitSppg->id,
                    'work_order_id' => null,
                    'nomor_po' => $validated['nomor_po'],
                    'nama_menu' => $validated['nama_menu'],
                    'tanggal' => $validated['tanggal'],
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'vendor' => $vendorName,
                    'jenis_transaksi' => $validated['jenis_transaksi'] ?? 'Bahan Baku',
                    'status_po' => 'Terverifikasi',
                    'status_bayar' => $validated['status_bayar'] ?? 'Belum Bayar',
                    'catatan' => $validated['catatan'] ?? 'Purchase Order Dibuat Manual',
                    'diverifikasi_oleh' => $user->id,
                    'diverifikasi_pada' => now(),
                    'items_count' => count($validated['items']),
                    'total_nominal_master' => $totalNominal,
                    'total_nominal_aktual' => $totalNominal,
                ]);

                foreach ($validated['items'] as $it) {
                    $gross = (float)$it['gross_kg'];
                    $harga = (float)$it['harga_aktual'];
                    $subtotal = $gross * $harga;

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'work_order_item_id' => null,
                        'nama' => $it['nama'],
                        'kategori' => $it['kategori'] ?? 'Lainnya',
                        'tipe' => 'Normal',
                        'satuan' => $it['satuan'] ?? 'Kg',
                        'jenis' => 'bahan_baku',
                        'gross_kg' => $gross,
                        'qty_beli_po_kg' => $gross,
                        'sumber_pengadaan' => 'Beli PO',
                        'harga_master' => $harga,
                        'harga_aktual' => $harga,
                        'subtotal_aktual' => $subtotal,
                        'supplier_id' => $it['supplier_id'] ?? $validated['supplier_id'] ?? null,
                        'jenis_transaksi' => $it['jenis_transaksi'] ?? $validated['jenis_transaksi'] ?? 'Bahan Baku',
                        'keterangan' => $it['keterangan'] ?? null,
                    ]);
                }
            });

            return back()->with('success', 'Purchase Order manual ' . $validated['nomor_po'] . ' berhasil diterbitkan ke Daftar PO resmi.');
        }
    }

    /**
     * Hapus Purchase Order dari Daftar PO resmi.
     */
    public function destroyPo(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->getCachedUnitSppg();

        $po = is_numeric($id)
            ? PurchaseOrder::where('unit_sppg_id', $unitSppg->id)->find($id)
            : PurchaseOrder::where('unit_sppg_id', $unitSppg->id)->where('nomor_po', $id)->first();

        if (!$po) {
            return back()->with('error', 'Purchase Order tidak ditemukan.');
        }

        $nomorPo = $po->nomor_po;
        $po->items()->delete();
        $po->delete();

        return back()->with('success', 'Purchase Order ' . $nomorPo . ' berhasil dihapus.');
    }

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
                ->with(['items.supplier', 'workOrder.items', 'verifikator', 'supplier'])
                ->orderBy('tanggal', 'desc')
                ->get();

            // Pastikan setiap PO memiliki item yang terkelompokan per bahan database pangan (tanpa duplikasi)
            foreach ($allDbPo as $p) {
                self::ensurePoItemsConsolidated($p);
            }

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

                $keyMap = [
                    'sub_menu_1' => 'Sub Menu 1',
                    'sub_menu_2' => 'Sub Menu 2',
                    'sub_menu_3' => 'Sub Menu 3',
                    'sub_menu_4' => 'Sub Menu 4',
                    'sub_menu_5' => 'Sub Menu 5',
                ];

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
                    'menu' => $po->workOrder ? $po->workOrder->nama_menu : ($po->nama_menu ?: 'PO Belanja Manual'),
                    'is_manual_po' => $po->work_order_id === null,
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
                    'items' => $po->items->map(function ($it) use ($po, $keyMap) {
                        $matchingWoItems = collect();
                        if ($po->workOrder && $po->workOrder->items) {
                            $matchingWoItems = $po->workOrder->items->filter(function ($w) use ($it) {
                                return strcasecmp(trim($w->nama_po ?: $w->nama), trim($it->nama)) === 0
                                    || strcasecmp(trim($w->nama), trim($it->nama)) === 0
                                    || $w->id === $it->work_order_item_id;
                            });
                        }

                        $woItem = $matchingWoItems->first() ?: $it->workOrderItem;

                        $subMenusList = [];
                        $totalGramPk = 0;
                        $totalGramPb = 0;
                        $alergenList = [];

                        foreach ($matchingWoItems as $mWo) {
                            $subKey = $mWo->sub_menu_key;
                            if (!$subKey) {
                                $kat = strtolower($mWo->kategori ?? '');
                                $nm = strtolower($mWo->nama ?? '');
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

                            $subNama = $mWo->nama_sub_menu ?: ($po->workOrder ? ($po->workOrder->{$subKey} ?? null) : null);
                            $label = $keyMap[$subKey] ?? 'Sub Menu 1';
                            $isAlergiItem = $mWo->tipe_porsi === 'alergi';

                            $subMenusList[] = [
                                'key' => $subKey,
                                'label' => $isAlergiItem ? "{$label} • Alergi" : $label,
                                'nama' => $subNama && $subNama !== '-' ? $subNama : '',
                                'gross_kg' => (float)$mWo->total_gross_kg,
                                'gram_pk' => (float)$mWo->gram_pk,
                                'gram_pb' => (float)$mWo->gram_pb,
                                'is_alergi' => $isAlergiItem,
                                'badgeClass' => $isAlergiItem ? 'bg-rose-100 text-rose-800 border-rose-300' : 'bg-slate-100 text-slate-800 border-slate-300',
                            ];

                            $totalGramPk += (float)$mWo->gram_pk;
                            $totalGramPb += (float)$mWo->gram_pb;
                            if ($mWo->alergen && !in_array($mWo->alergen, $alergenList)) {
                                $alergenList[] = $mWo->alergen;
                            }
                        }

                        // Deduplicate subMenus by key + nama + is_alergi
                        $uniqueSubMenus = [];
                        foreach ($subMenusList as $sm) {
                            $uKey = $sm['key'] . '|' . $sm['nama'] . '|' . ($sm['is_alergi'] ? '1' : '0');
                            if (!isset($uniqueSubMenus[$uKey])) {
                                $uniqueSubMenus[$uKey] = $sm;
                            } else {
                                $uniqueSubMenus[$uKey]['gross_kg'] += $sm['gross_kg'];
                                $uniqueSubMenus[$uKey]['gram_pk'] += $sm['gram_pk'];
                                $uniqueSubMenus[$uKey]['gram_pb'] += $sm['gram_pb'];
                            }
                        }
                        $uniqueSubMenus = array_values($uniqueSubMenus);

                        $primarySubKey = !empty($uniqueSubMenus) ? $uniqueSubMenus[0]['key'] : ($woItem ? ($woItem->sub_menu_key ?: 'sub_menu_1') : 'sub_menu_1');
                        $allDishNames = array_filter(array_unique(array_column($uniqueSubMenus, 'nama')));
                        $primarySubNama = !empty($allDishNames) ? implode(', ', $allDishNames) : ($woItem ? ($woItem->nama_sub_menu ?: ($po->workOrder ? ($po->workOrder->{$primarySubKey} ?? null) : null)) : null);

                        $gramPkVal = $totalGramPk > 0 ? $totalGramPk : ($woItem ? (float)$woItem->gram_pk : 0);
                        $gramPbVal = $totalGramPb > 0 ? $totalGramPb : ($woItem ? (float)$woItem->gram_pb : 0);

                        $collectedNotes = [];
                        foreach ($matchingWoItems as $mWo) {
                            foreach ([$mWo->keterangan ?? null, $mWo->catatan ?? null, $mWo->spesifikasi ?? null] as $cand) {
                                if ($cand && trim($cand) !== '' && trim($cand) !== '-' && !str_starts_with(strtolower(trim($cand)), 'sub menu') && !str_starts_with(strtolower(trim($cand)), 'sub_menu')) {
                                    $collectedNotes[] = trim($cand);
                                }
                            }
                        }
                        foreach ([$it->keterangan ?? null, $it->catatan ?? null] as $cand) {
                            if ($cand && trim($cand) !== '' && trim($cand) !== '-' && !str_starts_with(strtolower(trim($cand)), 'sub menu') && !str_starts_with(strtolower(trim($cand)), 'sub_menu')) {
                                $collectedNotes[] = trim($cand);
                            }
                        }
                        $collectedNotes = array_values(array_unique($collectedNotes));
                        $finalKeterangan = !empty($collectedNotes) ? implode('; ', $collectedNotes) : '-';

                        return [
                            'id' => $it->id,
                            'nama' => $woItem ? $woItem->nama : $it->nama,
                            'nama_po' => $it->nama,
                            'sub_menu_key' => $primarySubKey,
                            'sub_menu_block_id' => $woItem ? $woItem->sub_menu_block_id : null,
                            'nama_sub_menu' => $primarySubNama,
                            'sub_menus' => $uniqueSubMenus,
                            'satuan' => $it->satuan ?: ($woItem ? ($woItem->satuan ?: 'Kg') : 'Kg'),
                            'jenis' => $it->jenis ?: ($woItem ? ($woItem->jenis ?: 'bahan_baku') : 'bahan_baku'),
                            'kategori' => $it->kategori ?: ($woItem ? $woItem->kategori : 'Lainnya'),
                            'jenis_transaksi' => in_array($it->jenis_transaksi, ['Bahan Baku', 'Operasional', 'Insentif Fasilitas']) ? $it->jenis_transaksi : 'Bahan Baku',
                            'perlu_npo' => $it->perlu_npo !== null ? (bool)$it->perlu_npo : true,
                            'tipe_porsi' => strtolower($it->tipe ?: ($woItem ? $woItem->tipe_porsi : 'normal')),
                            'tipe' => $it->tipe,
                            'jenis_alergi' => $woItem ? ($woItem->jenis_alergi ?: '') : '',
                            'alergen' => !empty($alergenList) ? implode(', ', $alergenList) : ($woItem ? ($woItem->alergen ?: '') : ''),
                            'gram_pk' => (float)$gramPkVal,
                            'gram_pb' => (float)$gramPbVal,
                            'bdd' => $woItem ? (float)($woItem->bdd ?: 100) : 100,
                            'buffer' => $woItem ? (float)($woItem->buffer ?: 0) : 0,
                            'gross_kg' => (float)$it->gross_kg,
                            'stok_digunakan_kg' => (float)($it->stok_digunakan_kg ?? 0),
                            'qty_beli_po_kg' => $it->qty_beli_po_kg !== null ? (float)$it->qty_beli_po_kg : (float)$it->gross_kg,
                            'sumber_pengadaan' => $it->sumber_pengadaan ?: ($it->stok_digunakan_kg > 0 ? ($it->stok_digunakan_kg >= $it->gross_kg ? '100% Dari Stok' : 'Parsial Stok') : 'Beli PO'),
                            'harga_master' => (float)$it->harga_master,
                            'harga_aktual' => (float)($it->harga_aktual ?: $it->harga_master),
                            'subtotal_master' => (float)($it->gross_kg * $it->harga_master),
                            'subtotal_aktual' => (float)($it->subtotal_aktual !== null ? $it->subtotal_aktual : (($it->qty_beli_po_kg !== null ? $it->qty_beli_po_kg : $it->gross_kg) * ($it->harga_aktual ?: $it->harga_master))),
                            'keterangan' => $finalKeterangan,
                            'supplier_id' => $it->supplier_id,
                            'supplier' => $it->supplier ? [
                                'id' => $it->supplier->id,
                                'nama_usaha' => $it->supplier->nama_usaha,
                                'jenis_supplier' => $it->supplier->jenis_supplier,
                                'nama_pemilik' => $it->supplier->nama_pemilik,
                                'no_telp' => $it->supplier->no_telp,
                                'alamat_lengkap' => $it->supplier->alamat_lengkap,
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
                        // 3. Work Order Otomatis yang tersedia untuk dipilih di Daftar PO (hanya WO sistem ber-item)
            $availableWorkOrders = WorkOrder::where('unit_sppg_id', $unitSppg->id)
                ->where(function ($q) {
                    $q->where('metode_wo', 'sistem')
                      ->orWhereNull('metode_wo');
                })
                ->where('metode_wo', '!=', 'manual')
                ->whereHas('items')
                ->with(['items', 'purchaseOrder.items'])
                ->orderBy('tanggal_distribusi', 'desc')
                ->get()
                ->map(function ($wo) {
                    $grouped = [];
                    foreach ($wo->items as $it) {
                        $rawName = $it->nama_po ?: $it->nama;
                        $normName = mb_strtolower(trim($rawName));
                        $normSatuan = mb_strtolower(trim($it->satuan ?? 'kg'));
                        $groupKey = $normName . '|' . $normSatuan;

                        if (!isset($grouped[$groupKey])) {
                            $grouped[$groupKey] = [
                                'work_order_item_id' => $it->id,
                                'nama' => $rawName,
                                'kategori' => $it->kategori ?: 'Lainnya',
                                'satuan' => $it->satuan ?: 'Kg',
                                'tipe' => $it->tipe_porsi === 'alergi' ? 'Alergi' : 'Normal',
                                'gross_kg' => 0,
                                'harga_master' => (float)$it->harga_master,
                                'harga_aktual' => (float)$it->harga_master,
                                'subtotal' => 0,
                                'supplier_id' => null,
                                'jenis_transaksi' => 'Bahan Baku',
                                'keterangan' => '',
                                'keterangan_list' => [],
                            ];
                        }
                        $grouped[$groupKey]['gross_kg'] += (float)$it->total_gross_kg;
                        $grouped[$groupKey]['subtotal'] += ((float)$it->total_gross_kg * (float)$it->harga_master);

                        $rawKet = trim($it->keterangan ?? '');
                        if ($rawKet !== '' && $rawKet !== '-' && !in_array($rawKet, $grouped[$groupKey]['keterangan_list'])) {
                            $grouped[$groupKey]['keterangan_list'][] = $rawKet;
                        }
                    }

                    if ($wo->purchaseOrder && $wo->purchaseOrder->items) {
                        foreach ($wo->purchaseOrder->items as $pItem) {
                            $pNormName = mb_strtolower(trim($pItem->nama));
                            $pNormSatuan = mb_strtolower(trim($pItem->satuan ?? 'kg'));
                            $pKey = $pNormName . '|' . $pNormSatuan;
                            if (isset($grouped[$pKey])) {
                                $pKet = trim($pItem->keterangan ?? '');
                                if ($pKet !== '' && $pKet !== '-' && !in_array($pKet, $grouped[$pKey]['keterangan_list'])) {
                                    $grouped[$pKey]['keterangan_list'][] = $pKet;
                                }
                            }
                        }
                    }

                    foreach ($grouped as &$g) {
                        if (!empty($g['keterangan_list'])) {
                            $g['keterangan'] = implode('; ', $g['keterangan_list']);
                        }
                        unset($g['keterangan_list']);
                    }
                    unset($g);
                    $itemsList = array_values($grouped);
                    $totalNominal = array_sum(array_column($itemsList, 'subtotal'));

                    $hasOfficialPo = $wo->purchaseOrder && in_array($wo->purchaseOrder->status_po, ['Terverifikasi', 'Siap Produksi', 'Selesai']);

                    return [
                        'id' => $wo->id,
                        'nomor_wo' => $wo->nomor_wo,
                        'nama_menu' => $wo->nama_menu,
                        'tanggal_distribusi' => $wo->tanggal_distribusi ? $wo->tanggal_distribusi->format('Y-m-d') : null,
                        'status' => $wo->status,
                        'metode_wo' => $wo->metode_wo ?: 'sistem',
                        'total_pm' => $wo->total_pm ?: 0,
                        'total_pk' => $wo->total_pk ?: 0,
                        'total_pb' => $wo->total_pb ?: 0,
                        'items_count' => count($itemsList),
                        'total_nominal' => $totalNominal,
                        'has_po' => $wo->purchaseOrder !== null,
                        'has_official_po' => $hasOfficialPo,
                        'po_nomor' => $wo->purchaseOrder ? $wo->purchaseOrder->nomor_po : null,
                        'po_status' => $wo->purchaseOrder ? $wo->purchaseOrder->status_po : null,
                        'items' => $itemsList,
                    ];
                })
                ->values()
                ->toArray();

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

        // Work Orders untuk pengaitan transaksi
        $woQuery = WorkOrder::query();
        if ($unitSppg) {
            $woQuery->where('unit_sppg_id', $unitSppg->id);
        }
        $workOrdersList = $woQuery->orderBy('tanggal_distribusi', 'desc')
            ->get(['id', 'nomor_wo', 'nama_menu', 'tanggal_distribusi', 'status', 'metode_wo'])
            ->map(function ($wo) {
                return [
                    'id' => $wo->id,
                    'nomor_wo' => $wo->nomor_wo,
                    'nama_menu' => $wo->nama_menu,
                    'tanggal_distribusi' => $wo->tanggal_distribusi ? $wo->tanggal_distribusi->format('Y-m-d') : null,
                    'status' => $wo->status,
                    'metode_wo' => $wo->metode_wo,
                ];
            })->values()->all();

        // 4. Data Transaksi SPPG (Gabungan PO Rancang Menu Disetujui/Ditolak & Transaksi Manual)
        $manualTransaksi = TransaksiKeuangan::with(['supplier', 'workOrder'])->where(function ($q) use ($unitSppg, $user) {
            if ($unitSppg) {
                $q->where('unit_sppg_id', $unitSppg->id);
            } else {
                $q->where('user_id', $user->id);
            }
        })->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->get();

        $transaksiItems = collect();

        if (isset($allDbPo)) {
            $poForTransaksi = $allDbPo->filter(function ($po) {
                return in_array($po->status_po, ['Disetujui', 'Terverifikasi', 'Siap Produksi', 'Selesai', 'Ditunda', 'Ditolak', 'Dibatalkan']);
            });

            foreach ($poForTransaksi as $po) {
                $statusTrx = 'Disetujui';
                if ($po->status_po === 'Ditolak') {
                    $statusTrx = 'Ditolak';
                } elseif ($po->status_po === 'Ditunda') {
                    $statusTrx = 'Ditunda';
                } elseif ($po->status_po === 'Dibatalkan') {
                    $statusTrx = 'Dibatalkan';
                }
                $tglStr = $po->tanggal ? $po->tanggal->format('Y-m-d') : ($po->created_at ? $po->created_at->format('Y-m-d') : now()->toDateString());
                $menuName = $po->workOrder ? $po->workOrder->nama_menu : ($po->nama_menu ?: 'PO Rancang Menu');
                $vendorName = $po->supplier ? $po->supplier->nama_usaha : ($po->vendor && $po->vendor !== 'Rekanan Pangan SPPG' ? $po->vendor : '-');

                foreach ($po->items as $it) {
                    $subtotal = (float)($it->subtotal_aktual !== null ? $it->subtotal_aktual : (($it->qty_beli_po_kg !== null ? $it->qty_beli_po_kg : $it->gross_kg) * ($it->harga_aktual ?: $it->harga_master)));
                    $kategoriTrx = $it->jenis_transaksi ?: 'Bahan Baku';
                    if (!in_array($kategoriTrx, ['Bahan Baku', 'Operasional', 'Insentif Fasilitas'])) {
                        $kategoriTrx = 'Bahan Baku';
                    }

                    $transaksiItems->push([
                        'id' => 'PO-' . $po->id . '-ITEM-' . $it->id,
                        'source_type' => 'po',
                        'po_id' => $po->id,
                        'po_item_id' => $it->id,
                        'work_order_id' => $po->work_order_id,
                        'work_order' => $po->workOrder ? [
                            'id' => $po->workOrder->id,
                            'nomor_wo' => $po->workOrder->nomor_wo,
                            'nama_menu' => $po->workOrder->nama_menu,
                            'tanggal_distribusi' => $po->workOrder->tanggal_distribusi ? $po->workOrder->tanggal_distribusi->format('Y-m-d') : null,
                        ] : null,
                        'nomor_transaksi' => $po->nomor_po,
                        'tanggal' => $tglStr,
                        'kategori' => $kategoriTrx,
                        'arus' => 'Keluar',
                        'nama_transaksi' => $it->nama . ($menuName ? " ({$menuName})" : ""),
                        'nominal' => $subtotal,
                        'status' => $statusTrx,
                        'perlu_npo' => $it->perlu_npo !== null ? (bool)$it->perlu_npo : true,
                        'vendor' => $it->supplier ? $it->supplier->nama_usaha : $vendorName,
                        'keterangan' => $it->keterangan ?: ($po->catatan ?: '-'),
                        'satuan' => $it->satuan ?: 'Kg',
                        'qty' => (float)($it->qty_beli_po_kg !== null ? $it->qty_beli_po_kg : $it->gross_kg),
                        'harga_satuan' => (float)($it->harga_aktual ?: $it->harga_master),
                        'created_at' => $po->created_at ? $po->created_at->format('Y-m-d H:i:s') : null,
                    ]);
                }
            }
        }

        foreach ($manualTransaksi as $mt) {
            $transaksiItems->push([
                'id' => 'MANUAL-' . $mt->id,
                'source_type' => 'manual',
                'manual_id' => $mt->id,
                'po_id' => null,
                'po_item_id' => null,
                'work_order_id' => $mt->work_order_id,
                'work_order' => $mt->workOrder ? [
                    'id' => $mt->workOrder->id,
                    'nomor_wo' => $mt->workOrder->nomor_wo,
                    'nama_menu' => $mt->workOrder->nama_menu,
                    'tanggal_distribusi' => $mt->workOrder->tanggal_distribusi ? $mt->workOrder->tanggal_distribusi->format('Y-m-d') : null,
                ] : null,
                'nomor_transaksi' => $mt->nomor_transaksi,
                'tanggal' => $mt->tanggal ? $mt->tanggal->format('Y-m-d') : ($mt->created_at ? $mt->created_at->format('Y-m-d') : now()->toDateString()),
                'tanggal_pesanan' => $mt->tanggal_pesanan ? $mt->tanggal_pesanan->format('Y-m-d') : ($mt->tanggal ? $mt->tanggal->format('Y-m-d') : null),
                'kategori' => $mt->kategori ?: 'Operasional',
                'arus' => $mt->arus ?: ($mt->kategori === 'Dana Bantuan Pemerintah' ? 'Masuk' : 'Keluar'),
                'nama_transaksi' => $mt->nama_transaksi,
                'volume' => $mt->volume !== null ? (float)$mt->volume : null,
                'satuan' => $mt->satuan ?: 'Kg',
                'nominal' => (float)$mt->nominal,
                'status' => in_array($mt->status, ['Disetujui', 'Ditunda', 'Ditolak', 'Dibatalkan'], true) ? $mt->status : 'Disetujui',
                'perlu_npo' => (bool)$mt->perlu_npo,
                'supplier_id' => $mt->supplier_id,
                'vendor' => $mt->supplier ? $mt->supplier->nama_usaha : ($mt->vendor ?: '-'),
                'keterangan' => $mt->keterangan ?: '-',
                'qty' => $mt->volume !== null ? (float)$mt->volume : 1,
                'harga_satuan' => (float)$mt->nominal,
                'created_at' => $mt->created_at ? $mt->created_at->format('Y-m-d H:i:s') : null,
            ]);
        }

        $allTransaksiList = $transaksiItems->sortByDesc('tanggal')->values()->all();

        $saldoAwal = null;
        $penyesuaianList = [];

        if ($unitSppg) {
            $dbSaldo = KeuanganSaldoAwal::where('unit_sppg_id', $unitSppg->id)->first();
            if ($dbSaldo) {
                $saldoAwal = [
                    'id' => $dbSaldo->id,
                    'tanggal' => $dbSaldo->tanggal ? $dbSaldo->tanggal->format('Y-m-d') : null,
                    'nominal' => (float)$dbSaldo->nominal,
                    'keterangan' => $dbSaldo->keterangan,
                    'is_locked' => (bool)$dbSaldo->is_locked,
                    'user_name' => $dbSaldo->user?->name ?? 'Admin Keuangan',
                    'created_at' => $dbSaldo->created_at ? $dbSaldo->created_at->format('Y-m-d H:i:s') : null,
                ];
            }

            $penyesuaianList = KeuanganPenyesuaian::where('unit_sppg_id', $unitSppg->id)
                ->with('user:id,name')
                ->orderBy('tanggal', 'desc')
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'nomor_penyesuaian' => $item->nomor_penyesuaian,
                        'tanggal' => $item->tanggal ? $item->tanggal->format('Y-m-d') : null,
                        'arah' => $item->arah,
                        'jenis' => $item->jenis,
                        'jumlah' => (float)$item->jumlah,
                        'alasan' => $item->alasan,
                        'user_name' => $item->user?->name ?? 'Admin Keuangan',
                        'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
                    ];
                })
                ->all();
        }

        return Inertia::render('Keuangan/Index', [
            'transaksiList' => $allTransaksiList,
            'saldoAwal' => $saldoAwal,
            'penyesuaianList' => $penyesuaianList,
            'workOrdersList' => $workOrdersList,
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
            'activeTab' => $activeTab,
            'periodes' => $periodes,
            'verifikasiPoList' => $verifikasiPoList ?? [],
            'poList' => $poList,
            'availableWorkOrders' => $availableWorkOrders ?? [],
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

    /**
     * Memastikan seluruh item dalam Purchase Order terkonsolidasi & dikelompokkan
     * berdasarkan bahan pangan database pangan tanpa duplikasi per sub menu.
     */
    public static function ensurePoItemsConsolidated(PurchaseOrder $po): void
    {
        $items = $po->items;
        if ($items->isEmpty()) {
            return;
        }

        $names = $items->pluck('nama')->map(fn($n) => mb_strtolower(trim($n)));
        if ($names->count() === $names->unique()->count()) {
            return; // Sudah unik / tidak ada duplikasi
        }

        DB::transaction(function () use ($po, $items) {
            $grouped = [];
            foreach ($items as $it) {
                $normName = mb_strtolower(trim($it->nama));
                $normSatuan = mb_strtolower(trim($it->satuan ?? 'kg'));
                $normJenis = mb_strtolower(trim($it->jenis ?? 'bahan_baku'));
                $key = $normName . '|' . $normSatuan . '|' . $normJenis;

                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'primary' => $it,
                        'gross_kg' => 0,
                        'stok_digunakan_kg' => 0,
                        'qty_beli_po_kg' => 0,
                        'tipe' => $it->tipe ?? 'Normal',
                        'keterangan_list' => [],
                        'delete_ids' => [],
                    ];
                } else {
                    $grouped[$key]['delete_ids'][] = $it->id;
                }

                $grouped[$key]['gross_kg'] += (float) $it->gross_kg;
                $grouped[$key]['stok_digunakan_kg'] += (float) $it->stok_digunakan_kg;
                $grouped[$key]['qty_beli_po_kg'] += (float) $it->qty_beli_po_kg;
                if ($it->tipe === 'Alergi') {
                    $grouped[$key]['tipe'] = 'Alergi';
                }
                if ($it->keterangan && $it->keterangan !== '-' && !in_array($it->keterangan, $grouped[$key]['keterangan_list'])) {
                    $grouped[$key]['keterangan_list'][] = $it->keterangan;
                }
            }

            foreach ($grouped as $g) {
                $primary = $g['primary'];
                $hargaAktual = (float) ($primary->harga_aktual ?: $primary->harga_master);
                $gross = round($g['gross_kg'], 4);
                $stok = round($g['stok_digunakan_kg'], 4);
                $qtyBeli = round($g['qty_beli_po_kg'], 4);
                $subtotal = round($qtyBeli * $hargaAktual);

                $primary->update([
                    'gross_kg' => $gross,
                    'stok_digunakan_kg' => $stok,
                    'qty_beli_po_kg' => $qtyBeli,
                    'subtotal_aktual' => $subtotal,
                    'tipe' => $g['tipe'],
                    'keterangan' => !empty($g['keterangan_list']) ? implode('; ', $g['keterangan_list']) : $primary->keterangan,
                ]);

                if (!empty($g['delete_ids'])) {
                    \App\Models\PurchaseOrderItem::whereIn('id', $g['delete_ids'])->delete();
                }
            }

            $po->load('items');
            $newItemCount = $po->items->count();
            $totalNominal = $po->items->sum('subtotal_aktual');
            $po->update([
                'items_count' => $newItemCount,
                'total_nominal_master' => $totalNominal,
                'total_nominal_aktual' => $totalNominal,
            ]);
        });
    }
}

