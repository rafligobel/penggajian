<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\TunjanganKehadiranController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\GajiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\SesiAbsensiController;
use App\Http\Controllers\TandaTanganController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TenagaKerjaController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AturanTunjanganAnakController;
use App\Http\Controllers\AturanTunjanganPengabdianController;
use App\Http\Controllers\PengaturanKinerjaController;
use App\Http\Controllers\TunjanganKomunikasiController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- RUTE PUBLIK ---
Route::get('/', function () {
    return view('welcome');
});


// --- RUTE UNTUK SEMUA USER YANG SUDAH LOGIN ---
Route::middleware('auth')->group(function () {
    // Hanya satu definisi untuk route 'dashboard' utama
    Route::get('/dashboard', function () {

        // Cek dulu apakah user benar-benar ada (meski di dalam middleware 'auth')
        if (!Auth::check()) {
            // Seharusnya tidak tercapai, tapi ini validasi ekstra
            return redirect('/login');
        }

        // Ambil User object melalui Facade
        $user = Auth::user();

        // TIDAK PERLU DocBlock jika IDE Helper sudah dijalankan,
        // tapi jika ingin menambahkan untuk kepastian, gunakan:
        /** @var User $user */
        // $user = Auth::user();

        $role = $user->role;

        switch ($role) {
            case 'superadmin':
            case 'admin':
            case 'bendahara':
                return redirect()->route('dashboard.main');
            case 'tenaga_kerja':
                return redirect()->route('tenaga_kerja.dashboard');
            default:
                abort(403, 'Akses ditolak. Peran pengguna tidak valid.');
        }
    })->name('dashboard');
    // Profile & Notifikasi
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::delete('/notifications/delete-selected', [NotificationController::class, 'deleteSelected'])->name('notifications.deleteSelected');
    Route::delete('/notifications/delete-all', [NotificationController::class, 'deleteAll'])->name('notifications.deleteAll');
    Route::get('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::get('/notifications/mark-as-read/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
});


// --- RUTE KHUSUS ADMIN & SUPERADMIN ---
Route::middleware(['auth', 'role:superadmin,admin'])->group(function () {


    Route::resource('users', UserController::class);
    Route::resource('jabatan', JabatanController::class);
    Route::resource('tunjangan-kehadiran', TunjanganKehadiranController::class)->except(['create', 'edit', 'show']);
    Route::resource('tunjangan-komunikasi', TunjanganKomunikasiController::class)->except(['create', 'edit', 'show']);

    // Resource Karyawan sekarang aman di dalam middleware admin
    Route::get('karyawan/create', [KaryawanController::class, 'create'])->name('karyawan.create');
    Route::post('karyawan', [KaryawanController::class, 'store'])->name('karyawan.store');
    Route::get('karyawan/{karyawan}/edit', [KaryawanController::class, 'edit'])->name('karyawan.edit');
    Route::put('karyawan/{karyawan}', [KaryawanController::class, 'update'])->name('karyawan.update');
    Route::delete('karyawan/{karyawan}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');

    Route::get('aturan-anak', [AturanTunjanganAnakController::class, 'index'])->name('aturan-anak.index'); // Diubah dari edit ke index
    Route::put('aturan-anak', [AturanTunjanganAnakController::class, 'update'])->name('aturan-anak.update');

    // Rute untuk Tunjangan Pengabdian (Hapus 'create' dan 'edit')
    Route::resource('aturan-pengabdian', AturanTunjanganPengabdianController::class)->except(['show', 'create', 'edit']);

    Route::get('pengaturan-kinerja', [PengaturanKinerjaController::class, 'index'])->name('pengaturan-kinerja.index');
    // Rute untuk update Aturan (Nilai Maks)
    Route::put('pengaturan-kinerja/aturan', [PengaturanKinerjaController::class, 'updateAturan'])->name('pengaturan-kinerja.aturan.update');
    // Rute untuk CRUD Indikator
    Route::post('pengaturan-kinerja/indikator', [PengaturanKinerjaController::class, 'storeIndikator'])->name('pengaturan-kinerja.indikator.store');
    Route::put('pengaturan-kinerja/indikator/{indikator}', [PengaturanKinerjaController::class, 'updateIndikator'])->name('pengaturan-kinerja.indikator.update');
    Route::delete('pengaturan-kinerja/indikator/{indikator}', [PengaturanKinerjaController::class, 'destroyIndikator'])->name('pengaturan-kinerja.indikator.destroy');
});

Route::middleware(['auth', 'role:superadmin,admin,bendahara'])->group(function () {
    Route::get('karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
    Route::get('karyawan/{karyawan}', [KaryawanController::class, 'show'])->name('karyawan.show');
    Route::get('/dashboard-main', [DashboardController::class, 'index'])->name('dashboard.main');
});

// --- RUTE KHUSUS BENDAHARA ---
Route::middleware(['auth', 'role:bendahara'])->group(function () {
    // Kelola Gaji
    Route::get('gaji', [GajiController::class, 'index'])->name('gaji.index');
    Route::post('gaji/save', [GajiController::class, 'saveOrUpdate'])->name('gaji.save');
    Route::post('/gaji/{gaji}/download-slip', [GajiController::class, 'downloadSlip'])->name('gaji.download-slip');
    Route::post('/gaji/{gaji}/send-email', [GajiController::class, 'sendEmail'])->name('gaji.send-email');


    // Laporan
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/gaji-bulanan', [LaporanController::class, 'gajiBulanan'])->name('laporan.gaji.bulanan');
    Route::post('/laporan/gaji-bulanan/cetak', [LaporanController::class, 'cetakGajiBulanan'])->name('laporan.gaji.cetak');
    Route::post('/laporan/gaji/kirim-email-terpilih', [LaporanController::class, 'kirimEmailGajiTerpilih'])->name('laporan.gaji.kirim-email-terpilih');
    Route::get('/laporan/per-karyawan', [LaporanController::class, 'perKaryawan'])->name('laporan.per.karyawan');
    Route::post('/laporan/per-karyawan/cetak', [LaporanController::class, 'cetakLaporanPerKaryawan'])->name('laporan.per.karyawan.cetak');
    Route::post('/laporan/per-karyawan/kirim-email', [LaporanController::class, 'kirimEmailLaporanPerKaryawan'])->name('laporan.per.karyawan.kirim-email');
    Route::get('/laporan/absensi', [LaporanController::class, 'rekapAbsensi'])->name('laporan.absensi');
    Route::post('/laporan/absensi/cetak', [LaporanController::class, 'cetakRekapAbsensi'])->name('laporan.absensi.cetak');
    Route::post('/laporan/absensi/kirim-email', [LaporanController::class, 'kirimEmailRekapAbsensi'])->name('laporan.absensi.kirim-email');
    Route::get('/laporan/absensi/data', [AbsensiController::class, 'fetchRekapData'])->name('laporan.absensi.data');

    // Absensi
    Route::get('/rekap-absensi', [AbsensiController::class, 'rekapPerBulan'])->name('absensi.rekap');
    Route::get('/sesi-absensi', [SesiAbsensiController::class, 'index'])->name('sesi_absensi.index');
    Route::post('sesi-absensi', [SesiAbsensiController::class, 'storeOrUpdate'])->name('sesi_absensi.storeOrUpdate');
    Route::get('sesi-absensi/calendar-events', [SesiAbsensiController::class, 'getCalendarEvents'])->name('sesi_absensi.calendar-events');
    Route::delete('sesi-absensi/{sesi_absensi}', [SesiAbsensiController::class, 'destroy'])->name('sesi_absensi.destroy');

    Route::get('/laporan/absensi/data', [App\Http\Controllers\AbsensiController::class, 'fetchRekapData'])->name('absensi.rekap.data');


    // Pengaturan Tanda Tangan
    Route::get('/tanda-tangan', [TandaTanganController::class, 'index'])->name('tanda_tangan.index');
    Route::post('/tanda-tangan', [TandaTanganController::class, 'update'])->name('tanda_tangan.update');
});


// --- RUTE KHUSUS TENAGA KERJA ---
Route::middleware(['auth', 'role:tenaga_kerja'])->prefix('tenaga-kerja')->name('tenaga_kerja.')->group(function () {
    // Cukup satu route dashboard
    Route::get('/dashboard', [TenagaKerjaController::class, 'dashboard'])->name('dashboard');
    Route::get('/absensi', [TenagaKerjaController::class, 'formAbsensi'])->name('absensi.form');
    Route::post('/absensi', [TenagaKerjaController::class, 'prosesAbsensi'])->name('absensi.store');

    // Rute untuk fungsionalitas di dashboard tenaga kerja
    Route::post('/simulasi/hitung', [TenagaKerjaController::class, 'hitungSimulasi'])->name('simulasi.hitung');
    Route::get('/laporan-gaji', [TenagaKerjaController::class, 'laporanGaji'])->name('laporan_gaji');
    // Route::post('/slip-gaji/download', [TenagaKerjaController::class, 'downloadSlipGaji'])->name('slip_gaji.download');

    // Rute untuk konten modal AJAX (INI YANG PALING PENTING UNTUK FIX ERROR)
    Route::get('/modal/laporan-gaji', [TenagaKerjaController::class, 'getModalLaporanGaji'])->name('modal.laporan_gaji');
    Route::get('/modal/slip-gaji', [TenagaKerjaController::class, 'getModalSlipGaji'])->name('modal.slip_gaji');

    Route::get('/slip-gaji/download', [TenagaKerjaController::class, 'downloadSlipGaji'])
        ->name('slip_gaji.download'); // <-- Nama route yang benar: 'tenaga_kerja.slip_gaji.download'
    Route::get('/laporan-gaji/{gaji}/cetak', [TenagaKerjaController::class, 'cetakLaporanGaji'])
        ->name('laporan_gaji.cetak');

    Route::get('/data-saya', [TenagaKerjaController::class, 'editDataSaya'])->name('data_saya.edit');
    Route::put('/data-saya', [TenagaKerjaController::class, 'updateDataSaya'])->name('data_saya.update');
});


// --- AUTHENTICATION ROUTES ---
require __DIR__ . '/auth.php';
