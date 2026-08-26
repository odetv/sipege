<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GiziController;
use App\Http\Controllers\KelompokPenerimaManfaatController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\ProfileController;
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

    // Gizi SPPG (dengan sub-menu TKPI, Analisa PM, Buat Menu, Kalender Menu)
    Route::prefix('gizi')->name('gizi.')->group(function () {
        Route::get('/', [GiziController::class, 'index'])->name('index');
        Route::get('/tkpi', [GiziController::class, 'tkpi'])->name('tkpi');
        Route::get('/analisa-pm', [GiziController::class, 'analisaPm'])->name('analisa-pm');
        Route::get('/buat-menu', [GiziController::class, 'buatMenu'])->name('buat-menu');
        Route::get('/kalender-menu', [GiziController::class, 'kalenderMenu'])->name('kalender-menu');
    });

    // Keuangan SPPG
    Route::get('/keuangan', [KeuanganController::class, 'index'])->name('keuangan.index');

    // Label SPPG
    Route::get('/label', [LabelController::class, 'index'])->name('label.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
