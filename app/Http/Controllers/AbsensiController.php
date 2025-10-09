<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\SesiAbsensi; // Ditambahkan untuk mendapatkan ID Sesi
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
        $pesanSesi = $statusInfo['status'];

        if ($statusInfo['is_active']) {
            $now = now();
            $waktuMulai = Carbon::parse($statusInfo['waktu_mulai']);
            $waktuSelesai = Carbon::parse($statusInfo['waktu_selesai']);

            if ($now->between($waktuMulai, $waktuSelesai)) {
                $isSesiDibuka = true;
                $pesanSesi = 'Sesi absensi sedang dibuka.';
            } else {
                $pesanSesi = 'Sesi absensi hari ini sudah ditutup.';
            }

            $sesiHariIni = (object) [
                'waktu_mulai' => $waktuMulai->format('H:i'),
                'waktu_selesai' => $waktuSelesai->format('H:i'),
            ];
        }

        return view('absensi.index', compact('sesiHariIni', 'isSesiDibuka', 'pesanSesi'));
    }

    /**
     * Menyimpan absensi dengan menyertakan sesi_absensi_id.
     */
    public function store(Request $request)
    {
        $request->validate(['identifier' => 'required|string']);

        $karyawan = Karyawan::where('nip', $request->identifier)
            ->orWhere('nama', $request->identifier)
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

        // --- [PERBAIKAN UTAMA] ---
        // 1. Ambil ID Sesi Absensi yang sedang berjalan
        $sesiAbsensi = SesiAbsensi::where('tanggal', $today->format('Y-m-d'))->first();
        if (!$sesiAbsensi) {
            // Jika tidak ada sesi khusus hari ini, cari sesi default
            $sesiAbsensi = SesiAbsensi::where('is_default', true)->first();
        }

        // Jika sesi tetap tidak ditemukan, berikan error
        if (!$sesiAbsensi) {
            return redirect()->back()->with('info', 'Sistem tidak dapat menemukan sesi absensi yang aktif saat ini.');
        }
        // --- [AKHIR PERBAIKAN UTAMA] ---

        $sudahAbsen = Absensi::where('nip', $karyawan->nip)
            ->whereDate('tanggal', $today)
            ->exists();

        if ($sudahAbsen) {
            return redirect()->back()->with('info', 'Anda (' . $karyawan->nama . ') sudah melakukan absensi hari ini.');
        }

        Absensi::create([
            'sesi_absensi_id' => $sesiAbsensi->id, // 2. Sertakan ID Sesi saat create
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

            // [PERBAIKAN] Panggil logika terpusat dari service
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
