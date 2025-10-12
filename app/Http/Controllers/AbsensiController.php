<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\SesiAbsensi;
use App\Services\AbsensiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class AbsensiController extends Controller
{
    protected $absensiService;

    public function __construct(AbsensiService $absensiService)
    {
        $this->absensiService = $absensiService;
    }

    public function index()
    {
        $today = today();
        $statusInfo = $this->absensiService->getSessionStatus($today);
        $isSesiDibuka = false;
        $sesiHariIni = null;
        $pesanSesi = $statusInfo['keterangan'] ?? $statusInfo['status'];

        if ($statusInfo['is_active']) {
            $now = now();
            $waktuMulai = Carbon::parse($statusInfo['waktu_mulai']);
            $waktuSelesai = Carbon::parse($statusInfo['waktu_selesai']);

            if ($now->between($waktuMulai, $waktuSelesai)) {
                $isSesiDibuka = true;
                $pesanSesi = 'Sesi absensi sedang dibuka.';
            } elseif ($now->isAfter($waktuSelesai)) {
                $pesanSesi = 'Sesi absensi hari ini sudah ditutup.';
            } else {
                $pesanSesi = 'Sesi absensi hari ini belum dibuka.';
            }


            $sesiHariIni = (object) [
                'waktu_mulai' => $waktuMulai->format('H:i'),
                'waktu_selesai' => $waktuSelesai->format('H:i'),
            ];
        }

        return view('absensi.index', compact('sesiHariIni', 'isSesiDibuka', 'pesanSesi'));
    }

    public function store(Request $request)
    {
        $request->validate(['identifier' => 'required|string']);

        $karyawan = Karyawan::where('nip', $request->identifier)
            ->orWhere('nama', 'like', '%' . $request->identifier . '%')
            ->first();

        if (!$karyawan) {
            return redirect()->back()
                ->withErrors(['identifier' => 'NIP atau Nama tidak ditemukan.'])
                ->withInput();
        }

        $now = now();
        $today = $now->copy()->startOfDay();
        $statusInfo = $this->absensiService->getSessionStatus($today);

        if (!$statusInfo['is_active']) {
            return redirect()->back()->with('info', 'Sesi absensi untuk hari ini tidak aktif. Keterangan: ' . ($statusInfo['keterangan'] ?? $statusInfo['status']));
        }

        $waktuMulai = Carbon::parse($statusInfo['waktu_mulai']);
        $waktuSelesai = Carbon::parse($statusInfo['waktu_selesai']);

        if (!$now->between($waktuMulai, $waktuSelesai)) {
            return redirect()->back()->with('info', 'Sesi absensi sedang ditutup. Sesi berlaku dari jam ' . $waktuMulai->format('H:i') . ' hingga ' . $waktuSelesai->format('H:i') . '.');
        }

        // Logika pengambilan ID Sesi yang sudah diperbaiki
        $sesiAbsensi = SesiAbsensi::where('tanggal', $today->toDateString())->where('is_default', false)->where('tipe', 'aktif')->first();
        if (!$sesiAbsensi) {
            $sesiAbsensi = SesiAbsensi::where('is_default', true)->first();
        }

        if (!$sesiAbsensi) {
            return redirect()->back()->with('info', 'FATAL: Sistem tidak dapat menemukan ID sesi absensi yang valid.');
        }

        $sudahAbsen = Absensi::where('nip', $karyawan->nip)
            ->whereDate('tanggal', $today)
            ->exists();

        if ($sudahAbsen) {
            return redirect()->back()->with('info', 'Anda (' . $karyawan->nama . ') sudah melakukan absensi hari ini.');
        }

        Absensi::create([
            'sesi_absensi_id' => $sesiAbsensi->id,
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

    public function fetchRekapData(Request $request)
    {
        try {
            $request->validate(['bulan' => 'required|date_format:Y-m']);
            $selectedMonth = Carbon::createFromFormat('Y-m', $request->bulan);

            $rekap = $this->absensiService->getAttendanceRecap($selectedMonth);

            return response()->json([
                'rekap' => $rekap['rekapData'],
                'nama_bulan' => $selectedMonth->translatedFormat('F Y'),
                'total_hari_kerja' => $rekap['workingDaysCount'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat memuat rekap.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
