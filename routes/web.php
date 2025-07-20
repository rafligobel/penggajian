<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\SesiAbsensiController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\GajiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SimulasiGajiController;
use App\Http\Controllers\UserController; // Diasumsikan ada untuk manajemen user oleh Admin

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Di sini adalah tempat Anda dapat mendaftarkan rute web untuk aplikasi Anda.
| Rute-rute ini dimuat oleh RouteServiceProvider dan semuanya akan
| ditugaskan ke grup middleware "web". Buat sesuatu yang hebat!
|
*/

//========================================================================
// RUTE PUBLIK (Untuk Tenaga Kerja - Tanpa Autentikasi)
//========================================================================
Route::get('/', function () {
    return view('welcome');
});

// Fitur Simulasi Gaji dapat diakses publik
Route::get('/simulasi-gaji', [SimulasiGajiController::class, 'index'])->name('simulasi.index');
Route::post('/simulasi-gaji', [SimulasiGajiController::class, 'calculate'])->name('simulasi.calculate');

// Fitur Absensi dapat diakses publik
// CATATAN: Ini memerlukan method dan view baru di AbsensiController yang dirancang untuk publik.
// Misalnya, halaman di mana karyawan memilih nama mereka dari daftar dan mencatat kehadiran.
Route::get('/absensi-karyawan', [AbsensiController::class, 'createPublic'])->name('absensi.public.create');
Route::post('/absensi-karyawan', [AbsensiController::class, 'storePublic'])->name('absensi.public.store');


//========================================================================
// RUTE YANG MEMERLUKAN AUTENTIKASI (Untuk Admin & Bendahara)
//========================================================================
Route::middleware('auth')->group(function () {
    // Rute Umum setelah login
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Rute Profil Pengguna (bisa diakses Admin & Bendahara)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //-------------------------------------------------
    // RUTE KHUSUS BENDAHARA
    //-------------------------------------------------
    Route::middleware('role:bendahara')->prefix('bendahara')->name('bendahara.')->group(function () {
        Route::resource('karyawan', KaryawanController::class);
        Route::resource('sesi-absensi', SesiAbsensiController::class);
        Route::get('absensi/{sesi_absensi_id}/rekap', [AbsensiController::class, 'rekap'])->name('absensi.rekap');
        Route::resource('absensi', AbsensiController::class);
        Route::post('gaji/generate-all', [GajiController::class, 'generateAllSalaries'])->name('gaji.generate_all');
        Route::get('gaji/cetak-semua', [GajiController::class, 'cetakSemuaSlip'])->name('gaji.cetak_semua');
        Route::post('gaji/send-all-slips', [GajiController::class, 'sendAllSlips'])->name('gaji.send_all_slips');
        Route::resource('gaji', GajiController::class);
        Route::get('laporan/gaji-bulanan', [LaporanController::class, 'gajiBulanan'])->name('laporan.gaji_bulanan');
        Route::get('laporan/per-karyawan', [LaporanController::class, 'perKaryawan'])->name('laporan.per_karyawan');
    });

    //-------------------------------------------------
    // RUTE KHUSUS ADMIN
    //-------------------------------------------------
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        // Contoh: Admin dapat mengelola akun pengguna (termasuk akun Bendahara)
        // CATATAN: Anda perlu membuat UserController untuk fungsionalitas ini.
        Route::resource('users', UserController::class);
        Route::resource('notifications', NotificationController::class)->only(['index']);
    });
});

require __DIR__.'/auth.php';