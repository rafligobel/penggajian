<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware; // Pastikan ini di-import jika belum
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\GajiController;
use App\Http\Controllers\SimulasiGajiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AbsensiController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Bendahara specific routes
    Route::middleware(['role:bendahara'])->group(function () {
        Route::resource('gaji', GajiController::class);
        Route::get('gaji', [GajiController::class, 'index'])->name('gaji.index');
        Route::post('gaji/save', [GajiController::class, 'saveOrUpdate'])->name('gaji.save'); // RUTE BARU
        Route::get('/gaji/{id}/cetak', [GajiController::class, 'cetakPDF'])->name('gaji.cetak');

        Route::get('/aturan-gaji', [GajiController::class, 'aturan'])->name('aturan.index');
        Route::get('/laporan/absensi', [AbsensiController::class, 'rekapPerBulan'])->name('laporan.absensi.index');
        Route::get('/laporan/absensi/data', [AbsensiController::class, 'fetchRekapData'])->name('laporan.absensi.data');

        // ---- TAMBAHKAN RUTE INI ----
        Route::resource('sesi-absensi', App\Http\Controllers\SesiAbsensiController::class)->except(['show']);
    });

    // Admin specific routes for Karyawan Management (CRUD except index and show)
    Route::middleware(['role:admin'])->group(function () {
        // Memberikan akses ke create, store, edit, update, destroy untuk KaryawanController
        Route::resource('/karyawan', KaryawanController::class)->except(['index', 'show']);
    });

    // Admin and Bendahara can view Karyawan list and details
    Route::middleware(['role:admin,bendahara'])->group(function () {
        Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
        Route::get('/karyawan/{karyawan}', [KaryawanController::class, 'show'])->name('karyawan.show');
    });
});


// Simulasi Gaji (tanpa login)
Route::get('/simulasi', [SimulasiGajiController::class, 'index'])->name('simulasi.index');
Route::post('/simulasi/hitung', [SimulasiGajiController::class, 'hitung'])->name('simulasi.hitung');

// Absensi (tanpa login)
Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.form'); // Menggunakan index method untuk menampilkan form
Route::post('/absensi', [AbsensiController::class, 'store'])->name('absensi.store');


require __DIR__ . '/auth.php';
