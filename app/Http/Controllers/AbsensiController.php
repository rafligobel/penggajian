<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\SesiAbsensi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception; // <-- Tambahkan ini

class AbsensiController extends Controller
{
    // ... method index, showAbsensiForm, store, rekapPerBulan tidak berubah ...
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

    public function showAbsensiForm()
    {
        return $this->index();
    }

    public function store(Request $request)
    {
        $request->validate(['identifier' => 'required|string']);
        $identifier = $request->identifier;
        $karyawan = Karyawan::where('nip', $identifier)
            ->orWhere('nama', $identifier)
            ->first();
        if (!$karyawan) {
            return redirect()->back()
                ->withErrors(['identifier' => 'NIP atau Nama tidak ditemukan. Pastikan penulisan sudah benar.'])
                ->withInput();
        }
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $sesiAktif = SesiAbsensi::where('tanggal', $today)->where('is_active', true)->first();
        if (!$sesiAktif) {
            return redirect()->back()->with('info', 'Sesi absensi untuk hari ini belum dibuka.');
        }
        $waktuMulai = Carbon::parse($today->format('Y-m-d') . ' ' . $sesiAktif->waktu_mulai);
        $waktuSelesai = Carbon::parse($today->format('Y-m-d') . ' ' . $sesiAktif->waktu_selesai);
        if (!$now->between($waktuMulai, $waktuSelesai)) {
            return redirect()->back()->with('info', 'Sesi absensi sedang ditutup. Sesi berlaku dari jam ' . $waktuMulai->format('H:i') . ' hingga ' . $waktuSelesai->format('H:i') . '.');
        }
        $sudahAbsen = Absensi::where('nip', $karyawan->nip)
            ->whereDate('tanggal', $today)
            ->exists();
        if ($sudahAbsen) {
            return redirect()->back()->with('info', 'Anda (' . $karyawan->nama . ') sudah melakukan absensi hari ini.');
        }
        Absensi::create([
            'nip' => $karyawan->nip,
            'nama' => $karyawan->nama,
            'tanggal' => $now->toDateString(),
            'jam' => $now->toTimeString(),
        ]);
        return redirect()->back()->with('success', 'Absensi untuk ' . $karyawan->nama . ' berhasil dicatat. Terima kasih!');
    }

    public function rekapPerBulan(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        return view('absensi.rekap', compact('selectedMonth'));
    }

    /**
     * Mengambil data rekap untuk ditampilkan via AJAX/Fetch.
     * DIBUNGKUS DENGAN TRY-CATCH UNTUK MENANGANI ERROR
     */
    public function fetchRekapData(Request $request)
    {
        try {
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
                        // Pastikan tanggal tidak null sebelum di-parse
                        if (empty($item->tanggal)) return false;
                        return \Carbon\Carbon::parse($item->tanggal)->day == $day;
                    });
                    $harian[$day] = [
                        'status' => $absenPadaHariIni ? 'H' : 'A',
                        'jam' => $absenPadaHariIni && $absenPadaHariIni->jam ? \Carbon\Carbon::parse($absenPadaHariIni->jam)->format('H:i') : '-',
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
        } catch (Exception $e) {
            // Jika terjadi error, kirim respons JSON dengan pesan error
            return response()->json([
                'error' => 'Terjadi kesalahan di server.',
                'message' => $e->getMessage() // Pesan error asli untuk debugging
            ], 500);
        }
    }
}
