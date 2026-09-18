<?php

use App\Http\Controllers\AsetDigitalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GiziController;
use App\Http\Controllers\KelompokPenerimaManfaatController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\PeriodeController;
use App\Http\Controllers\PetunjukController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WilayahController;
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

    // Kelompok Penerima Manfaat
    Route::resource('penerima-manfaat', KelompokPenerimaManfaatController::class);

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

        // Work Order Actions
        Route::post('/work-order', [GiziController::class, 'storeWorkOrder'])->name('work-order.store');
        Route::put('/work-order/{id}', [GiziController::class, 'updateWorkOrder'])->name('work-order.update');
        Route::delete('/work-order/{id}', [GiziController::class, 'destroyWorkOrder'])->name('work-order.destroy');
    });

    // Keuangan SPPG (12 Sub-menu Lengkap)
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
        Route::get('/survei-harga', [KeuanganController::class, 'surveiHarga'])->name('survei-harga');
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

    // Label SPPG
    Route::prefix('label')->name('label.')->group(function () {
        Route::get('/', [LabelController::class, 'index'])->name('index');
        Route::get('/buat', [LabelController::class, 'buat'])->name('buat');
        Route::get('/daftar', [LabelController::class, 'daftar'])->name('daftar');
        Route::post('/', [LabelController::class, 'store'])->name('store');
        Route::put('/{id}', [LabelController::class, 'update'])->name('update');
        Route::delete('/{id}', [LabelController::class, 'destroy'])->name('destroy');
    });

    // Periode Operasional SPPG
    Route::resource('periode', PeriodeController::class)->only(['index', 'store', 'update', 'destroy']);

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
