<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\GajiController;
use App\Http\Controllers\SimulasiGajiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AbsensiController;

// Route::get('/dashboard', [DashboardController::class, 'index'])
//     ->middleware(['auth'])
//     ->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware(['auth', 'role:bendahara'])->group(function () {
    Route::resource('gaji', GajiController::class);
});
Route::get('/gaji/{id}/cetak', [GajiController::class, 'cetakPDF'])->name('gaji.cetak');
Route::get('/gaji/cetak-semua', [GajiController::class, 'cetakSemua'])->name('gaji.cetak.semua');
Route::get('/aturan-gaji', [GajiController::class, 'aturan'])->name('aturan.index');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('/karyawan', KaryawanController::class);
});

Route::middleware(['auth', 'role:admin, bendahara'])->group(function () {});
Route::get('/', function () {
    return view('welcome');
});

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/simulasi', [SimulasiGajiController::class, 'index'])->name('simulasi.index');
Route::post('/simulasi/hitung', [SimulasiGajiController::class, 'hitung'])->name('simulasi.hitung');


Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.form');
Route::post('/absensi', [AbsensiController::class, 'store'])->name('absensi.store');

require __DIR__ . '/auth.php';
