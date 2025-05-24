<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Absensi;
use Carbon\Carbon;

class AbsensiController extends Controller
{
    public function index()
    {
        return view('absensi.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nip' => 'required|exists:karyawans,nip',
        ]);

        $karyawan = Karyawan::where('nip', $request->nip)->first();

        // Cek apakah sudah absen hari ini
        $sudahAbsen = Absensi::where('karyawan_id', $karyawan->id)
            ->where('tanggal', Carbon::now()->toDateString())
            ->exists();

        if ($sudahAbsen) {
            return redirect()->back()->with('info', 'Anda sudah absen hari ini.');
        }

        Absensi::create([
            'karyawan_id' => $karyawan->id,
            'tanggal' => Carbon::now()->toDateString(),
            'status' => 'Hadir',
        ]);

        return redirect()->back()->with('success', 'Absensi berhasil dicatat untuk hari ini!');
    }
}
