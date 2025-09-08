<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Services\AbsensiService; // Ganti SesiAbsensi dengan service kita
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class AbsensiController extends Controller
{
    protected $absensiService;

    // 1. Inject AbsensiService melalui constructor
    public function __construct(AbsensiService $absensiService)
    {
        $this->absensiService = $absensiService;
    }

    /**
     * 2. Menampilkan halaman absensi utama dengan logika baru.
     */
    public function index()
    {
        $today = today();
        $statusInfo = $this->absensiService->getSessionStatus($today);
        $isSesiDibuka = false;
        $sesiHariIni = null;
        $pesanSesi = $statusInfo['status']; // Pesan default dari service

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

            // Buat objek sementara agar view tidak perlu diubah banyak
            $sesiHariIni = (object) [
                'waktu_mulai' => $waktuMulai->format('H:i'),
                'waktu_selesai' => $waktuSelesai->format('H:i'),
            ];
        }

        return view('absensi.index', compact('sesiHariIni', 'isSesiDibuka', 'pesanSesi'));
    }

    public function showAbsensiForm()
    {
        return $this->index();
    }

    /**
     * 3. Menyimpan absensi dengan validasi sesi dari service.
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
     * 4. Mengambil data rekap yang sudah disesuaikan dengan hari kerja efektif.
     */
    public function fetchRekapData(Request $request)
    {
        try {
            $request->validate(['bulan' => 'required|date_format:Y-m']);
            $selectedMonth = Carbon::createFromFormat('Y-m', $request->bulan);
            $daysInMonth = $selectedMonth->daysInMonth;

            // Hitung hari kerja efektif dalam sebulan
            $workingDaysCount = 0;
            $workingDaysMap = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = $selectedMonth->copy()->setDay($day);
                $statusInfo = $this->absensiService->getSessionStatus($currentDate);
                if ($statusInfo['is_active']) {
                    $workingDaysCount++;
                    $workingDaysMap[$day] = true;
                } else {
                    $workingDaysMap[$day] = false;
                }
            }

            $karyawans = Karyawan::where('status_aktif', true)->orderBy('nama')->get();
            $absensiBulanIniGrouped = Absensi::whereYear('tanggal', $selectedMonth->year)
                ->whereMonth('tanggal', $selectedMonth->month)
                ->get()
                ->groupBy('nip');

            $rekapData = [];
            foreach ($karyawans as $karyawan) {
                $karyawanAbsensi = $absensiBulanIniGrouped->get($karyawan->nip, collect());
                $totalHadir = $karyawanAbsensi->count();
                $totalAlpha = $workingDaysCount - $totalHadir; // Alpha dihitung dari hari kerja efektif

                $harian = [];
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $absenPadaHariIni = $karyawanAbsensi->firstWhere(fn($item) => Carbon::parse($item->tanggal)->day == $day);
                    $status = '-'; // Default untuk hari libur

                    if ($workingDaysMap[$day]) { // Jika ini adalah hari kerja
                        $status = $absenPadaHariIni ? 'H' : 'A';
                    }

                    $harian[$day] = [
                        'status' => $status,
                        'jam' => $absenPadaHariIni ? Carbon::parse($absenPadaHariIni->jam)->format('H:i') : '-',
                    ];
                }

                $rekapData[] = [
                    'nip' => $karyawan->nip,
                    'nama' => $karyawan->nama,
                    'ringkasan' => [
                        'hadir' => $totalHadir,
                        'sakit' => 0, // Placeholder
                        'izin' => 0, // Placeholder
                        'alpha' => $totalAlpha < 0 ? 0 : $totalAlpha,
                    ],
                    'detail' => $harian,
                ];
            }

            return response()->json([
                'rekap' => $rekapData,
                'nama_bulan' => $selectedMonth->translatedFormat('F Y'),
                'total_hari_kerja' => $workingDaysCount, // Kirim info ini ke frontend
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat memuat rekap.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
