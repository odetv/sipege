<?php

use App\Http\Controllers\AsetDigitalController;
use App\Http\Controllers\BackupRestoreController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GiziController;
use App\Http\Controllers\KategoriKpmController;
use App\Http\Controllers\KelompokPenerimaManfaatController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PeriodeController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\PetunjukController;
use App\Http\Controllers\ResiduController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

// API Proxy Wilayah Indonesia (cahyadsn/wilayah via wilayah.id / fallback)
Route::prefix('api/wilayah')->group(function () {
    Route::get('/provinces', [WilayahController::class, 'provinces']);
    Route::get('/regencies/{provinceCode}', [WilayahController::class, 'regencies']);
    Route::get('/districts/{regencyCode}', [WilayahController::class, 'districts']);
    Route::get('/villages/{districtCode}', [WilayahController::class, 'villages']);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::put('/dashboard/user-profile', [DashboardController::class, 'updateUserProfile'])->name('dashboard.user.update');
    Route::put('/dashboard/unit-sppg', [DashboardController::class, 'updateUnitSppg'])->name('dashboard.unit.update');

    // Kelompok Penerima Manfaat (Daftar KPM, Kategori KPM, Rekap Distribusi, Pembayaran Insentif)
    Route::prefix('penerima-manfaat')->name('penerima-manfaat.')->group(function () {
        Route::get('/daftar-kpm', [KelompokPenerimaManfaatController::class, 'index'])->name('daftar-kpm');
        Route::get('/daftar-pm', fn() => redirect()->route('penerima-manfaat.daftar-kpm'))->name('daftar-pm');
        Route::get('/rekap-distribusi', [KelompokPenerimaManfaatController::class, 'rekapDistribusi'])->name('rekap-distribusi');
        Route::post('/rekap-distribusi/simpan', [KelompokPenerimaManfaatController::class, 'simpanDistribusi'])->name('rekap-distribusi.simpan');
        Route::get('/pembayaran-insentif', [KelompokPenerimaManfaatController::class, 'pembayaranInsentif'])->name('pembayaran-insentif');
        Route::post('/pembayaran-insentif/generate-csv', [KelompokPenerimaManfaatController::class, 'generateInsentifCsv'])->name('pembayaran-insentif.generate-csv');

        // Kategori KPM
        Route::prefix('kategori-kpm')->name('kategori-kpm.')->group(function () {
            Route::get('/', [KategoriKpmController::class, 'index'])->name('index');
            Route::post('/', [KategoriKpmController::class, 'store'])->name('store');
            Route::put('/{id}', [KategoriKpmController::class, 'update'])->name('update');
            Route::delete('/{id}', [KategoriKpmController::class, 'destroy'])->name('destroy');
            Route::post('/reset', [KategoriKpmController::class, 'resetDefaults'])->name('reset');
        });
    });
    Route::resource('penerima-manfaat', KelompokPenerimaManfaatController::class);

    // Work Order SPPG (Menu Khusus Buat WO & Manajemen WO)
    Route::prefix('work-order')->name('work-order.')->group(function () {
        Route::get('/', [WorkOrderController::class, 'index'])->name('index');
        Route::get('/buat', [WorkOrderController::class, 'perencanaan'])->name('buat');
        Route::get('/buat-wo', [WorkOrderController::class, 'perencanaan'])->name('buat-wo');
        Route::get('/edit-manual', [WorkOrderController::class, 'perencanaan'])->name('edit-manual');
        Route::get('/edit-manual/{id}', function (\Illuminate\Http\Request $request, $id) {
            return redirect()->route('work-order.edit-manual', array_merge(['id' => $id], $request->query()));
        });
        Route::get('/perencanaan', fn(\Illuminate\Http\Request $request) => redirect()->route('work-order.buat', $request->query()))->name('perencanaan');
        Route::get('/daftar', [WorkOrderController::class, 'daftar'])->name('daftar');
        Route::get('/daftar-wo', [WorkOrderController::class, 'daftar'])->name('daftar-wo');
        Route::post('/', [WorkOrderController::class, 'store'])->name('store');
        Route::put('/{id}/manual', [WorkOrderController::class, 'updateManual'])->name('update-manual');
        Route::match(['post', 'patch'], '/bulk-status', [WorkOrderController::class, 'bulkUpdateStatus'])->name('bulk-status');
        Route::match(['post', 'patch'], '/{id}/status', [WorkOrderController::class, 'updateStatus'])->name('update-status');
        Route::post('/{id}/ajukan', [WorkOrderController::class, 'ajukanKeuangan'])->name('ajukan');
        Route::delete('/bulk-delete', [WorkOrderController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::delete('/{id}', [WorkOrderController::class, 'destroy'])->name('destroy');
    });

    // Gizi SPPG (dengan sub-menu Database Pangan, Analisa PM, Daftar Menu, Buat Menu)
    Route::prefix('gizi')->name('gizi.')->group(function () {
        Route::get('/', [GiziController::class, 'index'])->name('index');
        Route::get('/database-pangan', [GiziController::class, 'databasePangan'])->name('database-pangan');
        Route::get('/tkpi', [GiziController::class, 'databasePangan'])->name('tkpi');
        Route::get('/analisa-pm', [GiziController::class, 'analisaPm'])->name('analisa-pm');
        Route::get('/daftar-menu', [GiziController::class, 'daftarMenu'])->name('daftar-menu');
        Route::get('/rancang-menu', [GiziController::class, 'rancangMenu'])->name('rancang-menu');
        Route::get('/buat-menu', [GiziController::class, 'rancangMenu'])->name('buat-menu');
        Route::get('/kalender-menu', [GiziController::class, 'kalenderMenu'])->name('kalender-menu');
        Route::get('/resep-makanan', [GiziController::class, 'resep'])->name('resep-makanan');
        Route::get('/resep', [GiziController::class, 'resep'])->name('resep');

        // Resep API Actions
        Route::get('/api/resep', [GiziController::class, 'getResepList'])->name('api.resep.index');
        Route::post('/api/resep', [GiziController::class, 'storeResep'])->name('api.resep.store');
        Route::put('/api/resep/{id}', [GiziController::class, 'updateResep'])->name('api.resep.update');
        Route::delete('/api/resep/{id}', [GiziController::class, 'destroyResep'])->name('api.resep.destroy');

        // Work Order Actions
        Route::post('/work-order', [GiziController::class, 'storeWorkOrder'])->name('work-order.store');
        Route::post('/work-order/{id}/duplicate', [GiziController::class, 'duplicateWorkOrder'])->name('work-order.duplicate');
        Route::put('/work-order/{id}', [GiziController::class, 'updateWorkOrder'])->name('work-order.update');
        Route::delete('/work-order/{id}', [GiziController::class, 'destroyWorkOrder'])->name('work-order.destroy');

        // FatSecret API Proxy & Search
        Route::get('/api/fatsecret/search', [GiziController::class, 'searchFatSecret'])->name('fatsecret.search');
        Route::get('/api/fatsecret/food/{id}', [GiziController::class, 'getFatSecretFood'])->name('fatsecret.food');

        // NutriSurvey Dynamic Database API
        Route::get('/api/nutrisurvey/data', [GiziController::class, 'getNutrisurveyData'])->name('nutrisurvey.data');
    });

    // Keuangan SPPG (Sub-menu Lengkap)
    Route::prefix('keuangan')->name('keuangan.')->group(function () {
        Route::get('/', [KeuanganController::class, 'anggaran'])->name('index');
        Route::get('/anggaran', [KeuanganController::class, 'anggaran'])->name('anggaran');
        Route::get('/verifikasi-po', [KeuanganController::class, 'verifikasiPoIndex'])->name('verifikasi-po');
        Route::get('/verifikasi_po', [KeuanganController::class, 'verifikasiPoIndex'])->name('verifikasi_po');
        Route::get('/daftar-po', [KeuanganController::class, 'daftarPo'])->name('daftar-po');
        Route::get('/daftar_po', [KeuanganController::class, 'daftarPo'])->name('daftar_po');
        Route::get('/transaksi', [KeuanganController::class, 'transaksi'])->name('transaksi');
        Route::get('/bku', [KeuanganController::class, 'bku'])->name('bku');
        Route::get('/bp-bank', [KeuanganController::class, 'bpBank'])->name('bp-bank');
        Route::get('/bp-petty-cash', [KeuanganController::class, 'bpPettyCash'])->name('bp-petty-cash');
        Route::get('/bp-bahan-baku', [KeuanganController::class, 'bpBahanBaku'])->name('bp-bahan-baku');
        Route::get('/bp-operasional', [KeuanganController::class, 'bpOperasional'])->name('bp-operasional');
        Route::get('/bp-fasilitas', [KeuanganController::class, 'bpFasilitas'])->name('bp-fasilitas');
        Route::get('/lpa', [KeuanganController::class, 'lpa'])->name('lpa');
        Route::get('/sptj', [KeuanganController::class, 'sptj'])->name('sptj');
        Route::get('/bapsd', [KeuanganController::class, 'bapsd'])->name('bapsd');
        Route::get('/stok', [KeuanganController::class, 'stok'])->name('stok');
        Route::get('/laporan-harian', [KeuanganController::class, 'laporanHarian'])->name('laporan-harian');
        Route::get('/laporan-periodik', [KeuanganController::class, 'laporanPeriodik'])->name('laporan-periodik');
        Route::get('/survei-harga-pasar', [KeuanganController::class, 'surveiHarga'])->name('survei-harga-pasar');
        Route::get('/survei-harga', [KeuanganController::class, 'surveiHarga'])->name('survei-harga');
        Route::post('/survei-harga-pasar', [KeuanganController::class, 'storeSurveiHarga'])->name('survei-harga-pasar.store');
        Route::put('/survei-harga-pasar/{id}', [KeuanganController::class, 'updateSurveiHarga'])->name('survei-harga-pasar.update');
        Route::delete('/survei-harga-pasar/{id}', [KeuanganController::class, 'destroySurveiHarga'])->name('survei-harga-pasar.destroy');
        Route::post('/survei-harga', [KeuanganController::class, 'storeSurveiHarga'])->name('survei-harga.store');
        Route::put('/survei-harga/{id}', [KeuanganController::class, 'updateSurveiHarga'])->name('survei-harga.update');
        Route::delete('/survei-harga/{id}', [KeuanganController::class, 'destroySurveiHarga'])->name('survei-harga.destroy');

        // PO Actions
        Route::post('/po/{id}/verifikasi', [KeuanganController::class, 'verifikasiPo'])->name('po.verifikasi');
        Route::put('/po/{id}/supplier', [KeuanganController::class, 'updatePoSupplier'])->name('po.update-supplier');
        Route::post('/po/{id}/supplier', [KeuanganController::class, 'updatePoSupplier'])->name('po.update-supplier-post');
    });

    // Supplier Rekanan SPPG
    Route::resource('supplier', SupplierController::class);

    // Petugas SPPG (Daftar Petugas, Rekap Kehadiran, Rekap Bonus, Rekap Asuransi & Pembayaran Gaji BNI Direct)
    Route::get('/petugas/daftar-petugas', fn() => redirect()->route('petugas.index'))->name('petugas.daftar-petugas');
    Route::get('/petugas/shifting', [PetugasController::class, 'shifting'])->name('petugas.shifting');
    Route::post('/petugas/shifting/simpan', [PetugasController::class, 'simpanShifting'])->name('petugas.shifting.simpan');
    Route::get('/petugas/rekap-kehadiran', [PetugasController::class, 'rekapKehadiran'])->name('petugas.rekap-kehadiran');
    Route::post('/petugas/rekap-kehadiran/simpan', [PetugasController::class, 'simpanPresensi'])->name('petugas.rekap-kehadiran.simpan');
    Route::get('/petugas/kehadiran', [PetugasController::class, 'rekapKehadiran'])->name('petugas.kehadiran');
    Route::get('/petugas/rekap-bonus', [PetugasController::class, 'rekapBonus'])->name('petugas.rekap-bonus');
    Route::get('/petugas/rekap-asuransi', [PetugasController::class, 'rekapBpjsTk'])->name('petugas.rekap-asuransi');
    Route::post('/petugas/rekap-asuransi/update-iuran', [PetugasController::class, 'updateIuranBpjsTk'])->name('petugas.rekap-asuransi.update-iuran');
    Route::get('/petugas/rekap-bpjs-tk', [PetugasController::class, 'rekapBpjsTk'])->name('petugas.rekap-bpjs-tk');
    Route::post('/petugas/rekap-bpjs-tk/update-iuran', [PetugasController::class, 'updateIuranBpjsTk'])->name('petugas.rekap-bpjs-tk.update-iuran');
    Route::get('/petugas/pembayaran-gaji', [PetugasController::class, 'pembayaranGaji'])->name('petugas.pembayaran-gaji');
    Route::post('/petugas/pembayaran-gaji/generate-bni-csv', [PetugasController::class, 'generateBniDirectCsv'])->name('petugas.pembayaran-gaji.generate-csv');
    Route::post('/petugas/pembayaran-gaji/download-xls', [PetugasController::class, 'downloadBniDirectXls'])->name('petugas.pembayaran-gaji.download-xls');
    Route::resource('petugas', PetugasController::class);

    // Label SPPG (Menu Langsung)
    Route::prefix('label')->name('label.')->group(function () {
        Route::get('/', [LabelController::class, 'index'])->name('index');
        Route::get('/buat', [LabelController::class, 'buat'])->name('buat');
        Route::get('/daftar', [LabelController::class, 'daftar'])->name('daftar');
    });

    // Menu Residu (Pencatatan Limbah Harian SPPG)
    Route::prefix('residu')->name('residu.')->group(function () {
        Route::get('/', [ResiduController::class, 'index'])->name('index');
        Route::post('/', [ResiduController::class, 'store'])->name('store');
        Route::post('/ai-parse', [ResiduController::class, 'aiParse'])->name('ai-parse');
        Route::post('/bulk-store', [ResiduController::class, 'bulkStore'])->name('bulk-store');
        Route::post('/bulk-destroy', [ResiduController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::put('/{id}', [ResiduController::class, 'update'])->name('update');
        Route::delete('/{id}', [ResiduController::class, 'destroy'])->name('destroy');
    });

    // Menu Laporan SPPG
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [LaporanController::class, 'harianLintasSektor'])->name('index');
        Route::get('/laporan-harian', [LaporanController::class, 'harianLintasSektor'])->name('laporan-harian');
        Route::get('/harian', [LaporanController::class, 'harianLintasSektor'])->name('harian');
        Route::get('/harian-lintas-sektor', [LaporanController::class, 'harianLintasSektor'])->name('harian-lintas-sektor');
    });

    // Menu Pengaturan SPPG (Periode & Kategori KPM)
    Route::prefix('pengaturan')->group(function () {
        Route::resource('periode', PeriodeController::class)
            ->names('periode')
            ->only(['index', 'store', 'update', 'destroy']);

        Route::prefix('kategori-kpm')->name('pengaturan.kategori-kpm.')->group(function () {
            Route::get('/', [KategoriKpmController::class, 'index'])->name('index');
            Route::post('/', [KategoriKpmController::class, 'store'])->name('store');
            Route::put('/{id}', [KategoriKpmController::class, 'update'])->name('update');
            Route::delete('/{id}', [KategoriKpmController::class, 'destroy'])->name('destroy');
            Route::post('/reset', [KategoriKpmController::class, 'resetDefaults'])->name('reset');
        });

        Route::get('/backup-restore', [BackupRestoreController::class, 'index'])->name('pengaturan.backup-restore.index');

        // API Key Platform AI (Dapat diakses via /pengaturan/api-key maupun /pengaturan/kunci-api)
        Route::prefix('api-key')->name('pengaturan.api-key.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ApiKeyController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\ApiKeyController::class, 'update'])->name('update');
            Route::post('/test-koneksi', [\App\Http\Controllers\ApiKeyController::class, 'testKoneksi'])->name('test-koneksi');
        });

        Route::prefix('kunci-api')->name('pengaturan.kunci-api.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ApiKeyController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\ApiKeyController::class, 'update'])->name('update');
            Route::post('/test-koneksi', [\App\Http\Controllers\ApiKeyController::class, 'testKoneksi'])->name('test-koneksi');
        });

        Route::redirect('/kunci-api', '/pengaturan/kunci-api');
    });

    // Redirect dari /periode ke /pengaturan/periode
    Route::redirect('/periode', '/pengaturan/periode');

    // Menu Petunjuk (SOP, Juknis & Pedoman) SPPG
    Route::prefix('petunjuk')->name('petunjuk.')->group(function () {
        Route::get('/', [PetunjukController::class, 'index'])->name('index');
        Route::get('/sop', [PetunjukController::class, 'sop'])->name('sop');
        Route::get('/juknis', [PetunjukController::class, 'juknis'])->name('juknis');
        Route::get('/pedoman', [PetunjukController::class, 'pedoman'])->name('pedoman');
        Route::get('/stream/{type}/{filename}', [PetunjukController::class, 'stream'])->name('stream');
        Route::get('/download/{type}/{filename}', [PetunjukController::class, 'download'])->name('download');
    });

    // Menu Aset Digital (Logo, Denah, Plang Ruangan, Poster, Kop Dokumen)
    Route::prefix('aset-digital')->name('aset-digital.')->group(function () {
        Route::get('/', [AsetDigitalController::class, 'index'])->name('index');
        Route::get('/logo', [AsetDigitalController::class, 'logo'])->name('logo');
        Route::get('/denah', [AsetDigitalController::class, 'denah'])->name('denah');
        Route::get('/struktur', [AsetDigitalController::class, 'struktur'])->name('struktur');
        Route::get('/plang-ruangan', [AsetDigitalController::class, 'plangRuangan'])->name('plang-ruangan');
        Route::get('/poster', [AsetDigitalController::class, 'poster'])->name('poster');
        Route::get('/kop-dokumen', [AsetDigitalController::class, 'kopDokumen'])->name('kop-dokumen');
        Route::post('/kop-dokumen', [AsetDigitalController::class, 'saveKopDokumen'])->name('kop-dokumen.save');
        Route::post('/kop-dokumen/upload-logo', [AsetDigitalController::class, 'uploadKopLogo'])->name('kop-dokumen.upload-logo');
        Route::get('/kop-dokumen/template/{format}', [AsetDigitalController::class, 'downloadKopTemplate'])->name('kop-dokumen.download-template');
        Route::get('/stream/{category}/{filename}', [AsetDigitalController::class, 'stream'])->name('stream');
        Route::get('/download/{category}/{filename}', [AsetDigitalController::class, 'download'])->name('download');
    });

    // Backward-compatibility / shortcut routes untuk SOP
    Route::prefix('sop')->name('sop.')->group(function () {
        Route::get('/', [PetunjukController::class, 'sop'])->name('index');
        Route::get('/stream/{filename}', fn($filename) => redirect()->route('petunjuk.stream', ['type' => 'sop', 'filename' => $filename]))->name('stream');
        Route::get('/download/{filename}', fn($filename) => redirect()->route('petunjuk.download', ['type' => 'sop', 'filename' => $filename]))->name('download');
    });
});

require __DIR__ . '/auth.php';
