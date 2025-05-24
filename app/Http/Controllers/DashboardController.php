<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Gaji;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // public function index()
    // {
    //     $totalKaryawan = Karyawan::count();

    //     $totalGaji = Gaji::sum('total_gaji');

    //     $totalGajiBulanIni = Gaji::whereMonth('created_at', Carbon::now()->month)
    //         ->whereYear('created_at', Carbon::now()->year)
    //         ->sum('total_gaji');

    //     return view('dashboard.index', [
    //         'totalKaryawan' => $totalKaryawan,
    //         'totalGaji' => $totalGaji,
    //         'totalGajiBulanIni' => $totalGajiBulanIni,
    //     ]);
    // }

    public function index()
    {
        $totalKaryawan = Karyawan::count();

        // Hitung total gaji secara manual dari semua data
        $totalGaji = Gaji::all()->sum(function ($gaji) {
            return
                $gaji->gaji_pokok +
                $gaji->tunj_kehadiran +
                $gaji->tunj_anak +
                $gaji->tunj_komunikasi +
                $gaji->tunj_pengabdian +
                $gaji->tunj_jabatan +
                $gaji->tunj_kinerja +
                $gaji->lembur +
                $gaji->kelebihan_jam -
                $gaji->potongan;
        });

        // Hitung total gaji bulan ini
        $totalGajiBulanIni = Gaji::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->get()
            ->sum(function ($gaji) {
                return
                    $gaji->gaji_pokok +
                    $gaji->tunj_kehadiran +
                    $gaji->tunj_anak +
                    $gaji->tunj_komunikasi +
                    $gaji->tunj_pengabdian +
                    $gaji->tunj_jabatan +
                    $gaji->tunj_kinerja +
                    $gaji->lembur +
                    $gaji->kelebihan_jam -
                    $gaji->potongan;
            });

        return view('dashboard.index', [
            'totalKaryawan' => $totalKaryawan,
            'totalGaji' => $totalGaji,
            'totalGajiBulanIni' => $totalGajiBulanIni,
        ]);
    }
}
