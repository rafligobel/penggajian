<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Gaji;
use App\Models\SesiAbsensi;
use Carbon\Carbon;
use App\Services\SalaryService; // Menggunakan SalaryService untuk konsistensi

class DashboardController extends Controller
{
    protected $salaryService;

    public function __construct(SalaryService $salaryService)
    {
        $this->salaryService = $salaryService;
    }

    public function index()
    {
        $now = Carbon::now();
        $bulanIni = $now->format('Y-m');
        $bulanLalu = $now->subMonth()->format('Y-m');

        // Statistik Karyawan
        $totalKaryawanAktif = Karyawan::where('status_aktif', true)->count();
        $karyawanBaruBulanIni = Karyawan::where('status_aktif', true)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();
            
        // Statistik Gaji
        $gajiBulanIni = Gaji::where('bulan', $bulanIni)->get();
        $gajiBulanLalu = Gaji::where('bulan', $bulanLalu)->get();

        $totalGajiBulanIni = $gajiBulanIni->sum('gaji_bersih');
        $totalGajiBulanLalu = $gajiBulanLalu->sum('gaji_bersih');
        
        // Menghindari pembagian dengan nol
        $perbandinganGaji = $totalGajiBulanLalu > 0
            ? (($totalGajiBulanIni - $totalGajiBulanLalu) / $totalGajiBulanLalu) * 100
            : 0;

        // Statistik Absensi
        $sesiHariIni = SesiAbsensi::where('tanggal', today())->first();
        $statusSesi = 'Belum Dibuat';
        if ($sesiHariIni) {
            $statusSesi = $sesiHariIni->is_active ? 'Dibuka' : 'Ditutup';
        }

        return view('dashboard.index', [
            'totalKaryawanAktif' => $totalKaryawanAktif,
            'karyawanBaruBulanIni' => $karyawanBaruBulanIni,
            'totalGajiBulanIni' => $totalGajiBulanIni,
            'gajiDiproses' => $gajiBulanIni->count(),
            'perbandinganGaji' => $perbandinganGaji,
            'statusSesi' => $statusSesi,
        ]);
    }
}