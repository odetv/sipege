<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GiziController;
use App\Http\Controllers\KelompokPenerimaManfaatController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\PeriodeController;
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

    // Gizi SPPG (dengan sub-menu TKPI, Analisa PM, Daftar Menu, Buat Menu)
    Route::prefix('gizi')->name('gizi.')->group(function () {
        Route::get('/', [GiziController::class, 'index'])->name('index');
        Route::get('/tkpi', [GiziController::class, 'tkpi'])->name('tkpi');
        Route::get('/analisa-pm', [GiziController::class, 'analisaPm'])->name('analisa-pm');
        Route::get('/daftar-menu', [GiziController::class, 'daftarMenu'])->name('daftar-menu');
        Route::get('/rancang-menu', [GiziController::class, 'rancangMenu'])->name('rancang-menu');
        Route::get('/buat-menu', [GiziController::class, 'rancangMenu'])->name('buat-menu');
        Route::get('/kalender-menu', [GiziController::class, 'kalenderMenu'])->name('kalender-menu');
    });

    // Keuangan SPPG (12 Sub-menu Lengkap)
    Route::prefix('keuangan')->name('keuangan.')->group(function () {
        Route::get('/', [KeuanganController::class, 'anggaran'])->name('index');
        Route::get('/anggaran', [KeuanganController::class, 'anggaran'])->name('anggaran');
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
    });

    // Label SPPG
    Route::get('/label', [LabelController::class, 'index'])->name('label.index');

    // Periode Operasional SPPG
    Route::resource('periode', PeriodeController::class)->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__ . '/auth.php';
