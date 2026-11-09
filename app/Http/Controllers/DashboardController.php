<?php

namespace App\Http\Controllers;

use App\Models\UnitSppg;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the application dashboard.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Jika data profil / Unit SPPG belum diinisialisasi, arahkan ke onboarding untuk melengkapi data
        if (!$user->isProfileCompleted()) {
            return redirect()->route('onboarding.index');
        }

        $unitSppg = $user->getCachedUnitSppg();

        $kelompokList = [];
        $workOrders = [];
        if ($unitSppg) {
            $kelompokList = \App\Models\KelompokPenerimaManfaat::getCachedListForUnit($unitSppg->id);

            $totalUnitPk = $kelompokList->sum('total_porsi_kecil');
            $totalUnitPb = $kelompokList->sum('total_porsi_besar');

            $workOrders = \App\Models\WorkOrder::where('unit_sppg_id', $unitSppg->id)
                ->with(['purchaseOrder'])
                ->withCount('items')
                ->orderBy('tanggal_distribusi', 'desc')
                ->take(15)
                ->get()
                ->map(function ($wo) use ($totalUnitPk, $totalUnitPb) {
                    $pk = $wo->total_pk ?: $totalUnitPk;
                    $pb = $wo->total_pb ?: $totalUnitPb;
                    $tot = (int)$pk + (int)$pb;

                    $hasAkg = false;
                    if (!empty($wo->akg_pk) && is_array($wo->akg_pk)) {
                        $numericValues = array_filter($wo->akg_pk, fn($v) => is_numeric($v) && (float)$v > 0);
                        $hasAkg = count($numericValues) > 0;
                    }

                    $subMenus = $wo->sub_menus ?: array_values(array_filter([
                        $wo->sub_menu_1,
                        $wo->sub_menu_2,
                        $wo->sub_menu_3,
                        $wo->sub_menu_4,
                        $wo->sub_menu_5,
                    ]));

                    return [
                        'id' => $wo->nomor_wo,
                        'db_id' => $wo->id,
                        'uuid' => $wo->uuid,
                        'nama' => $wo->nama_menu,
                        'metode_wo' => $wo->metode_wo ?: 'sistem',
                        'has_akg' => $hasAkg,
                        'sub_menus' => $subMenus,
                        'tanggal' => $wo->tanggal_distribusi ? (is_string($wo->tanggal_distribusi) ? substr($wo->tanggal_distribusi, 0, 10) : $wo->tanggal_distribusi->format('Y-m-d')) : null,
                        'status' => $wo->status,
                        'current_step' => (int)($wo->current_step ?: 1),
                        'total_porsi' => $tot,
                        'porsi_pk' => (int)$pk,
                        'porsi_pb' => (int)$pb,
                        'items_count' => (int)($wo->items_count ?? 0),
                        'created_at' => $wo->created_at ? $wo->created_at->format('Y-m-d H:i:s') : null,
                        'diajukan_pada' => $wo->diajukan_pada ? (is_string($wo->diajukan_pada) ? $wo->diajukan_pada : $wo->diajukan_pada->format('Y-m-d H:i:s')) : null,
                        'disetujui_pada' => $wo->disetujui_pada ? (is_string($wo->disetujui_pada) ? $wo->disetujui_pada : $wo->disetujui_pada->format('Y-m-d H:i:s')) : null,
                        'ditolak_pada' => $wo->ditolak_pada ? (is_string($wo->ditolak_pada) ? $wo->ditolak_pada : $wo->ditolak_pada->format('Y-m-d H:i:s')) : null,
                        'catatan_keuangan' => $wo->catatan_keuangan,
                        'po' => $wo->purchaseOrder ? [
                            'id' => $wo->purchaseOrder->nomor_po,
                            'status_po' => $wo->purchaseOrder->status_po,
                            'total_nominal' => $wo->purchaseOrder->total_nominal_aktual ?: $wo->purchaseOrder->total_nominal_master,
                        ] : null,
                    ];
                });
        }

        $pengawasGizi = null;
        $pengawasKeuangan = null;
        if ($unitSppg) {
            $pengawasGizi = \App\Models\Petugas::where('unit_sppg_id', $unitSppg->id)
                ->where(function ($q) {
                    $q->where('jabatan', 'like', '%Pengawas Gizi%')
                      ->orWhere('jabatan', 'like', '%PLOG%');
                })
                ->orderByRaw("CASE WHEN status = 'Aktif' THEN 0 ELSE 1 END")
                ->first();

            $pengawasKeuangan = \App\Models\Petugas::where('unit_sppg_id', $unitSppg->id)
                ->where(function ($q) {
                    $q->where('jabatan', 'like', '%Pengawas Keuangan%')
                      ->orWhere('jabatan', 'like', '%PLOK%');
                })
                ->orderByRaw("CASE WHEN status = 'Aktif' THEN 0 ELSE 1 END")
                ->first();
        }

        // Ringkasan Keuangan: Saldo Akhir, Uang Masuk Hari Ini, Uang Keluar Hari Ini, dan Transaksi Tertunda
        $todayStr = now()->toDateString();
        $keuanganSummary = [
            'saldo_akhir' => 0,
            'saldo_awal' => 0,
            'has_saldo_awal' => false,
            'uang_masuk_hari_ini' => 0,
            'uang_keluar_hari_ini' => 0,
            'net_hari_ini' => 0,
            'tanggal_hari_ini' => $todayStr,
            'jumlah_tertunda' => 0,
            'nominal_tertunda' => 0,
        ];

        if ($unitSppg) {
            $saldoAwalRow = \App\Models\KeuanganSaldoAwal::where('unit_sppg_id', $unitSppg->id)->first();
            $saldoAwalNominal = $saldoAwalRow ? (float)$saldoAwalRow->nominal : 0;

            // Masuk hari ini (Penyesuaian Masuk + Transaksi Masuk)
            $masukHariIni = (float)\App\Models\KeuanganPenyesuaian::where('unit_sppg_id', $unitSppg->id)
                ->whereDate('tanggal', $todayStr)
                ->where('arah', 'masuk')
                ->sum('jumlah')
                + (float)\App\Models\TransaksiKeuangan::where('unit_sppg_id', $unitSppg->id)
                ->whereDate('tanggal', $todayStr)
                ->where('status', 'Disetujui')
                ->where(function ($q) {
                    $q->where('arus', 'Masuk')
                      ->orWhere(function ($sq) {
                          $sq->whereNull('arus')->where('kategori', 'Dana Bantuan Pemerintah');
                      });
                })
                ->sum('nominal');

            // Keluar hari ini (Penyesuaian Keluar + Transaksi Keluar + PO Terverifikasi hari ini)
            $keluarHariIni = (float)\App\Models\KeuanganPenyesuaian::where('unit_sppg_id', $unitSppg->id)
                ->whereDate('tanggal', $todayStr)
                ->where('arah', 'keluar')
                ->sum('jumlah')
                + (float)\App\Models\TransaksiKeuangan::where('unit_sppg_id', $unitSppg->id)
                ->whereDate('tanggal', $todayStr)
                ->where('status', 'Disetujui')
                ->where(function ($q) {
                    $q->where('arus', 'Keluar')
                      ->orWhere(function ($sq) {
                          $sq->whereNull('arus')->where('kategori', '!=', 'Dana Bantuan Pemerintah');
                      });
                })
                ->sum('nominal')
                + (float)\App\Models\PurchaseOrderItem::whereHas('purchaseOrder', function ($q) use ($unitSppg, $todayStr) {
                    $q->where('unit_sppg_id', $unitSppg->id)
                        ->whereDate('tanggal', $todayStr)
                        ->whereIn('status_po', ['Terverifikasi', 'Siap Produksi', 'Selesai']);
                })->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(subtotal_aktual, COALESCE(qty_beli_po_kg, gross_kg) * COALESCE(harga_aktual, harga_master))'));

            // Total Seluruh Uang Masuk Akumulasi
            $totalMasuk = (float)\App\Models\KeuanganPenyesuaian::where('unit_sppg_id', $unitSppg->id)
                ->where('arah', 'masuk')
                ->sum('jumlah')
                + (float)\App\Models\TransaksiKeuangan::where('unit_sppg_id', $unitSppg->id)
                ->where('status', 'Disetujui')
                ->where(function ($q) {
                    $q->where('arus', 'Masuk')
                      ->orWhere(function ($sq) {
                          $sq->whereNull('arus')->where('kategori', 'Dana Bantuan Pemerintah');
                      });
                })
                ->sum('nominal');

            // Total Seluruh Uang Keluar Akumulasi
            $totalKeluar = (float)\App\Models\KeuanganPenyesuaian::where('unit_sppg_id', $unitSppg->id)
                ->where('arah', 'keluar')
                ->sum('jumlah')
                + (float)\App\Models\TransaksiKeuangan::where('unit_sppg_id', $unitSppg->id)
                ->where('status', 'Disetujui')
                ->where(function ($q) {
                    $q->where('arus', 'Keluar')
                      ->orWhere(function ($sq) {
                          $sq->whereNull('arus')->where('kategori', '!=', 'Dana Bantuan Pemerintah');
                      });
                })
                ->sum('nominal')
                + (float)\App\Models\PurchaseOrderItem::whereHas('purchaseOrder', function ($q) use ($unitSppg) {
                    $q->where('unit_sppg_id', $unitSppg->id)
                        ->whereIn('status_po', ['Terverifikasi', 'Siap Produksi', 'Selesai']);
                })->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(subtotal_aktual, COALESCE(qty_beli_po_kg, gross_kg) * COALESCE(harga_aktual, harga_master))'));

            $saldoAkhir = $saldoAwalNominal + $totalMasuk - $totalKeluar;

            // Transaksi Tertunda (Status Ditunda yang perlu verifikasi/persetujuan)
            $trxTertundaQuery = \App\Models\TransaksiKeuangan::where('unit_sppg_id', $unitSppg->id)
                ->where('status', 'Ditunda');
            $jumlahTertunda = (int) $trxTertundaQuery->count();
            $nominalTertunda = (float) $trxTertundaQuery->sum('nominal');

            $keuanganSummary = [
                'saldo_akhir' => (float)$saldoAkhir,
                'saldo_awal' => (float)$saldoAwalNominal,
                'has_saldo_awal' => (bool)$saldoAwalRow,
                'uang_masuk_hari_ini' => (float)$masukHariIni,
                'uang_keluar_hari_ini' => (float)$keluarHariIni,
                'net_hari_ini' => (float)($masukHariIni - $keluarHariIni),
                'tanggal_hari_ini' => $todayStr,
                'jumlah_tertunda' => $jumlahTertunda,
                'nominal_tertunda' => $nominalTertunda,
            ];
        }

        return Inertia::render('Dashboard/Index', [
            'user' => $user,
            'unitSppg' => $unitSppg,
            'kelompokList' => $kelompokList,
            'workOrders' => $workOrders,
            'pengawasGizi' => $pengawasGizi,
            'pengawasKeuangan' => $pengawasKeuangan,
            'keuanganSummary' => $keuanganSummary,
            'needs_onboarding' => !$user->isProfileCompleted(),
        ]);
    }

    /**
     * Update user profile information (Kepala SPPG).
     * Note: Email cannot be modified by user.
     */
    public function updateUserProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nik' => ['required', 'digits:16', Rule::unique('users', 'nik')->ignore($user->id)],
            'nip' => ['nullable', 'numeric'],
            'nama' => ['required', 'string', 'max:255'],
            'gelar_depan' => ['nullable', 'string', 'max:50'],
            'gelar_belakang' => ['nullable', 'string', 'max:50'],
            'agama' => ['required', 'string', 'max:50'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],
            'jenjang_pendidikan' => ['required', 'string', 'max:50'],
            'bidang_pendidikan' => ['required', 'string', 'max:100'],
            'status_kawin' => ['required', 'in:Belum Menikah,Menikah,Janda,Duda'],
            'provinsi_ktp' => ['required', 'string', 'max:100'],
            'kabupaten_ktp' => ['required', 'string', 'max:100'],
            'kecamatan_ktp' => ['required', 'string', 'max:100'],
            'desa_kelurahan_ktp' => ['required', 'string', 'max:100'],
            'kode_pos_ktp' => ['required', 'numeric', 'digits:5'],
            'alamat_lengkap_ktp' => ['required', 'string'],
            'provinsi_domisili' => ['required', 'string', 'max:100'],
            'kabupaten_domisili' => ['required', 'string', 'max:100'],
            'kecamatan_domisili' => ['required', 'string', 'max:100'],
            'desa_kelurahan_domisili' => ['required', 'string', 'max:100'],
            'kode_pos_domisili' => ['required', 'numeric', 'digits:5'],
            'alamat_lengkap_domisili' => ['required', 'string'],
            'latitude_domisili' => ['required', 'numeric'],
            'longitude_domisili' => ['required', 'numeric'],
            'telepon' => ['required', 'string', 'regex:/^62[0-9]{8,15}$/'],
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus berjumlah tepat 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar di sistem.',
            'nama.required' => 'Nama lengkap wajib diisi.',
            'agama.required' => 'Pilih salah satu agama.',
            'jenis_kelamin.required' => 'Pilih jenis kelamin.',
            'tempat_lahir.required' => 'Tempat lahir wajib diisi.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'jenjang_pendidikan.required' => 'Pilih jenjang pendidikan.',
            'bidang_pendidikan.required' => 'Bidang pendidikan / jurusan wajib diisi.',
            'status_kawin.required' => 'Pilih status perkawinan.',
            'telepon.required' => 'Nomor telepon wajib diisi.',
            'telepon.regex' => 'Format nomor telepon tidak valid. Pastikan diawali format Indonesia (62...).',
            'kode_pos_ktp.digits' => 'Kode pos KTP harus berupa 5 digit angka.',
            'kode_pos_domisili.digits' => 'Kode pos Domisili harus berupa 5 digit angka.',
            'latitude_domisili.required' => 'Titik koordinat domisili wajib ditentukan.',
            'longitude_domisili.required' => 'Titik koordinat domisili wajib ditentukan.',
        ]);

        // Exclude email from mass assignment to ensure it cannot be modified
        unset($validated['email']);

        $user->fill($validated);
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Data Profil Pengguna berhasil diperbarui.');
    }

    /**
     * Update unit SPPG information.
     */
    public function updateUnitSppg(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return redirect()->route('dashboard')->with('error', 'Data Unit SPPG tidak ditemukan.');
        }

        $validated = $request->validate([
            'id_sppg' => ['required', 'string', 'size:8', 'alpha_num', Rule::unique('unit_sppg', 'id_sppg')->ignore($unitSppg->id)],
            'kode_sppg' => ['required', 'string', 'max:100'],
            'nama' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Belum Operasional,Operasional,Suspend Ringan,Suspend Sedang,Suspend Berat,Suspend Permanen'],
            'tanggal_operasional' => ['nullable', 'date'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kabupaten' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'desa_kelurahan' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'kode_pos' => ['required', 'numeric', 'digits:5'],
            'alamat_lengkap' => ['required', 'string'],
        ], [
            'id_sppg.required' => 'ID SPPG wajib diisi.',
            'id_sppg.size' => 'ID SPPG harus tepat 8 digit karakter alfanumerik.',
            'id_sppg.unique' => 'ID SPPG ini sudah digunakan oleh unit lain.',
            'kode_sppg.required' => 'Kode unit SPPG wajib diisi.',
            'nama.required' => 'Nama unit SPPG wajib diisi.',
            'status.required' => 'Status operasional wajib dipilih.',
            'kode_pos.digits' => 'Kode pos Unit SPPG harus berupa 5 digit angka.',
            'latitude.required' => 'Titik koordinat unit SPPG wajib ditentukan.',
            'longitude.required' => 'Titik koordinat unit SPPG wajib ditentukan.',
            'alamat_lengkap.required' => 'Alamat lengkap unit SPPG wajib diisi.',
        ]);

        // Ensure id_sppg is stored in uppercase
        $validated['id_sppg'] = strtoupper($validated['id_sppg']);

        $unitSppg->fill($validated);
        $unitSppg->save();

        \Illuminate\Support\Facades\Cache::forget("unit_sppg_user_{$user->id}");

        return redirect()->route('dashboard')->with('success', 'Data Unit SPPG berhasil diperbarui.');
    }
}
