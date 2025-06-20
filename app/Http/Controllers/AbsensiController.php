<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\SesiAbsensi;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    // ... method index() tidak berubah ...
    public function index()
    {
        $today = today();
        $now = Carbon::now();
        $sesiHariIni = SesiAbsensi::where('tanggal', $today)->where('is_active', true)->first();
        $isSesiDibuka = false;
        if ($sesiHariIni) {
            $waktuMulai = Carbon::parse($today->format('Y-m-d') . ' ' . $sesiHariIni->waktu_mulai);
            $waktuSelesai = Carbon::parse($today->format('Y-m-d') . ' ' . $sesiHariIni->waktu_selesai);
            if ($now->between($waktuMulai, $waktuSelesai)) {
                $isSesiDibuka = true;
            }
        }
        return view('absensi.index', compact('sesiHariIni', 'isSesiDibuka'));
    }


    // Menyimpan data absensi dari karyawan
    public function store(Request $request)
    {
        $request->validate(['nip' => 'required|exists:karyawans,nip']);

        $now = Carbon::now();
        $today = $now->copy()->startOfDay();

        // 1. Cek sesi aktif
        $sesiAktif = SesiAbsensi::where('tanggal', $today)->where('is_active', true)->first();
        if (!$sesiAktif) {
            return redirect()->back()->with('info', 'Sesi absensi untuk hari ini belum dibuka.');
        }

        // 2. Cek rentang waktu sesi
        $waktuMulai = Carbon::parse($today->format('Y-m-d') . ' ' . $sesiAktif->waktu_mulai);
        $waktuSelesai = Carbon::parse($today->format('Y-m-d') . ' ' . $sesiAktif->waktu_selesai);
        if (!$now->between($waktuMulai, $waktuSelesai)) {
            return redirect()->back()->with('info', 'Sesi absensi sedang ditutup. Sesi berlaku dari jam ' . $waktuMulai->format('H:i') . ' hingga ' . $waktuSelesai->format('H:i') . '.');
        }

        // 3. Cek apakah sudah absen hari ini
        $karyawan = Karyawan::where('nip', $request->nip)->first();
        $sudahAbsen = Absensi::where('nip', $karyawan->nip)
            ->whereDate('tanggal', $today)
            ->exists();
        if ($sudahAbsen) {
            return redirect()->back()->with('info', 'Anda sudah melakukan absensi hari ini.');
        }

        // 4. Jika semua validasi lolos, catat absensi
        Absensi::create([
            'nip' => $karyawan->nip,
            'nama' => $karyawan->nama,
            'tanggal' => $now->toDateString(),
            // 'status' => 'Hadir', // <-- INI DIHAPUS
            'jam' => $now->toTimeString(),
        ]);

        return redirect()->back()->with('success', 'Absensi berhasil dicatat. Terima kasih!');
    }

    // ... method rekapPerBulan() dan fetchRekapData() tidak berubah ...
    public function rekapPerBulan(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        return view('absensi.rekap', compact('selectedMonth'));
    }
    public function fetchRekapData(Request $request)
    {
        $request->validate(['bulan' => 'required|date_format:Y-m']);
        $selectedMonth = \Carbon\Carbon::createFromFormat('Y-m', $request->bulan);
        $namaBulan = $selectedMonth->translatedFormat('F Y');
        $daysInMonth = $selectedMonth->daysInMonth;
        $karyawans = Karyawan::where('status_aktif', true)->orderBy('nama')->get();
        $absensiBulanIniGrouped = Absensi::whereYear('tanggal', $selectedMonth->year)
            ->whereMonth('tanggal', $selectedMonth->month)
            ->get()
            ->groupBy('nip');
        $rekapData = [];
        foreach ($karyawans as $karyawan) {
            $karyawanAbsensi = $absensiBulanIniGrouped->get($karyawan->nip, collect());
            $totalHadir = $karyawanAbsensi->count();
            $totalAlpha = $daysInMonth - $totalHadir;
            $harian = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $absenPadaHariIni = $karyawanAbsensi->firstWhere(function ($item) use ($day) {
                    return \Carbon\Carbon::parse($item->tanggal)->day == $day;
                });
                $harian[$day] = [
                    'status' => $absenPadaHariIni ? 'H' : 'A',
                    'jam' => $absenPadaHariIni ? \Carbon\Carbon::parse($absenPadaHariIni->jam)->format('H:i') : '-',
                ];
            }
            $rekapData[] = [
                'nip' => $karyawan->nip,
                'nama' => $karyawan->nama,
                'ringkasan' => [
                    'hadir' => $totalHadir,
                    'sakit' => 0,
                    'izin' => 0,
                    'alpha' => $totalAlpha,
                ],
                'detail' => $harian,
            ];
        }
        return response()->json([
            'rekap' => $rekapData,
            'nama_bulan' => $namaBulan,
        ]);
    }
}
