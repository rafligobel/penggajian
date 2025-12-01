<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Jobs\GenerateMonthlySalaryReport;
use App\Jobs\SendSlipToEmail;
use App\Jobs\GenerateIndividualReport;
use App\Jobs\SendIndividualReportToEmail;
use App\Jobs\GenerateAttendanceReport;
use App\Jobs\SendAttendanceReportToEmail;
use App\Services\AbsensiService;
use App\Services\SalaryService;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB; // Tambahkan ini

class LaporanController extends Controller
{
    protected $absensiService;
    protected $salaryService;

    public function __construct(AbsensiService $absensiService, SalaryService $salaryService)
    {
        $this->absensiService = $absensiService;
        $this->salaryService = $salaryService;
    }

    // --- LAPORAN GAJI BULANAN ---
    public function gajiBulanan(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        try {
            $tanggal = Carbon::createFromFormat('Y-m', $selectedMonth);
        } catch (\Exception $e) {
            $tanggal = Carbon::now();
        }

        // Eager load relasi penting
        $gajis = Gaji::whereYear('bulan', $tanggal->year)
            ->whereMonth('bulan', $tanggal->month)
            ->with(['karyawan.jabatan', 'tunjanganKehadiran', 'penilaianKinerjas'])
            ->orderBy('karyawan_id')
            ->get();

        // Bulk Fetch Absensi (Ambil semua hitungan absen bulan ini dalam 1 Query)
        $absensi = Absensi::whereYear('tanggal', $tanggal->year)
            ->whereMonth('tanggal', $tanggal->month)
            ->groupBy('karyawan_id')
            ->selectRaw('karyawan_id, count(*) as jumlah_hadir')
            ->pluck('jumlah_hadir', 'karyawan_id');

        // Hitung Hari Kerja (sudah dioptimasi di AbsensiService sebelumnya)
        $totalHariKerja = $this->absensiService->getAttendanceRecap($tanggal)['workingDaysCount'];

        // Loop data di memori saja (Cepat!)
        $gajis->each(function ($gaji) use ($absensi) {
            $jumlahHadir = $absensi->get($gaji->karyawan_id, 0);
            $gaji->jumlah_hadir = $jumlahHadir;

            // [OPTIMASI] Inject data yang sudah ada ke Service
            // Kita tidak perlu Service melakukan query ulang Gaji & Absensi
            $preloadedData = [
                'gaji_record' => $gaji,
                'jumlah_kehadiran' => $jumlahHadir
            ];

            // Panggil service dengan data injeksi
            $detail = $this->salaryService->calculateDetailsForForm(
                $gaji->karyawan,
                $gaji->bulan->format('Y-m-d'),
                $preloadedData
            );

            $gaji->gaji_bersih_perhitungan = $detail['gaji_bersih_numeric'];
        });

        $availableMonths = Gaji::selectRaw('DATE_FORMAT(bulan, "%Y-%m") as bulan_ym')
            ->groupBy('bulan_ym')
            ->orderBy('bulan_ym', 'desc')
            ->pluck('bulan_ym');

        return view('laporan.gaji_bulanan', compact('gajis', 'tanggal', 'selectedMonth', 'availableMonths', 'totalHariKerja'));
    }

    public function cetakGajiBulanan(Request $request)
    {
        $validated = $request->validate([
            'gaji_ids' => 'required|array|min:1',
            'gaji_ids.*' => 'exists:gajis,id',
            'bulan' => 'required|date_format:Y-m',
        ], [
            'gaji_ids.required' => 'Anda harus memilih setidaknya satu karyawan.'
        ]);

        GenerateMonthlySalaryReport::dispatch($validated['bulan'], Auth::user(), $validated['gaji_ids']);
        return redirect()->back()->with('success', 'Laporan sedang diproses. Cek notifikasi beberapa saat lagi.');
    }

    public function kirimEmailGajiTerpilih(Request $request)
    {
        $validated = $request->validate([
            'gaji_ids' => 'required|array|min:1',
            'gaji_ids.*' => 'exists:gajis,id',
        ]);

        SendSlipToEmail::dispatch($validated['gaji_ids'], Auth::id());
        return redirect()->back()->with('success', 'Slip gaji sedang dikirim ke email.');
    }


    // --- LAPORAN ABSENSI ---
    public function rekapAbsensi(Request $request)
    {
        $selectedMonth = $request->input('periode', Carbon::now()->format('Y-m'));
        try {
            $tanggal = Carbon::createFromFormat('Y-m', $selectedMonth);
        } catch (\Exception $e) {
            $tanggal = Carbon::now();
        }

        // Service ini sudah kita optimasi di Tahap 1 (Eager Loading)
        $result = $this->absensiService->getAttendanceRecap($tanggal);

        $rekapData = $result['rekapData'];
        $daysInMonth = $result['daysInMonth'];

        $availableMonths = Absensi::selectRaw('DATE_FORMAT(tanggal, "%Y-%m") as bulan_ym')
            ->groupBy('bulan_ym')
            ->orderBy('bulan_ym', 'desc')
            ->pluck('bulan_ym');

        return view('laporan.laporan_absensi', compact('rekapData', 'tanggal', 'selectedMonth', 'availableMonths', 'daysInMonth'));
    }

    public function cetakRekapAbsensi(Request $request)
    {
        $validated = $request->validate([
            'karyawan_ids' => 'required|array|min:1',
            'karyawan_ids.*' => 'exists:karyawans,id',
            'periode' => 'required|date_format:Y-m',
        ]);

        $date = Carbon::createFromFormat('Y-m', $validated['periode']);

        GenerateAttendanceReport::dispatch(
            $validated['karyawan_ids'],
            $date->format('m'),
            $date->format('Y'),
            Auth::id()
        );

        return redirect()->back()->with('success', 'Laporan absensi sedang diproses.');
    }

    public function kirimEmailRekapAbsensi(Request $request)
    {
        $validated = $request->validate([
            'karyawan_ids' => 'required|array|min:1',
            'karyawan_ids.*' => 'exists:karyawans,id',
            'periode' => 'required|date_format:Y-m',
        ]);

        $date = Carbon::createFromFormat('Y-m', $validated['periode']);

        foreach ($validated['karyawan_ids'] as $karyawanId) {
            SendAttendanceReportToEmail::dispatch(
                $karyawanId,
                $date->format('m'),
                $date->format('Y'),
                Auth::id()
            );
        }

        return redirect()->back()->with('success', 'Laporan absensi sedang dikirim ke email.');
    }


    // --- LAPORAN PER KARYAWAN ---
    public function perKaryawan(Request $request)
    {
        $karyawans = Karyawan::orderBy('nama')->get();
        $selectedKaryawanId = $request->input('karyawan_id');

        $karyawan = null;
        $laporanData = null;

        $availableMonths = Gaji::selectRaw('DATE_FORMAT(bulan, "%Y-%m") as bulan_ym')
            ->groupBy('bulan_ym')
            ->orderBy('bulan_ym', 'desc')
            ->pluck('bulan_ym');

        $bulanMulai = $request->input('bulan_mulai', $availableMonths->first() ?? Carbon::now()->format('Y-m'));
        $bulanSelesai = $request->input('bulan_selesai', Carbon::now()->format('Y-m'));

        if ($selectedKaryawanId) {
            $karyawan = Karyawan::findOrFail($selectedKaryawanId);
            // Panggil fungsi yang sudah dioptimasi
            $laporanData = $this->getLaporanData($karyawan, $bulanMulai, $bulanSelesai);
        }

        return view('laporan.per_karyawan', compact('karyawans', 'karyawan', 'laporanData', 'bulanMulai', 'bulanSelesai', 'availableMonths', 'selectedKaryawanId'));
    }

    /**
     * [OPTIMASI BESAR] Mengambil data laporan tanpa N+1 Query.
     */
    private function getLaporanData(Karyawan $karyawan, string $bulanMulai, string $bulanSelesai): array
    {
        $start = Carbon::createFromFormat('Y-m', $bulanMulai)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $bulanSelesai)->endOfMonth();

        // 1. Ambil Data Gaji
        $gajis = Gaji::where('karyawan_id', $karyawan->id)
            ->whereBetween('bulan', [$start, $end])
            ->with(['tunjanganKehadiran', 'karyawan.jabatan']) // Eager load jabatan juga
            ->orderBy('bulan', 'asc')
            ->get();

        // 2. Hitung Total Kehadiran Periode (1 Query)
        $totalKehadiranPeriode = Absensi::where('karyawan_id', $karyawan->id)
            ->whereBetween('tanggal', [$start, $end])
            ->count();

        // 3. [OPTIMASI] Ambil Data Absensi Per Bulan SEKALIGUS (Group By Year-Month)
        // Ini menggantikan query count() di dalam loop
        $absensiPerBulan = Absensi::where('karyawan_id', $karyawan->id)
            ->whereBetween('tanggal', [$start, $end])
            ->selectRaw('YEAR(tanggal) as year, MONTH(tanggal) as month, count(*) as total')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(function ($item) {
                // Buat key unik: "2025-10"
                return $item->year . '-' . $item->month;
            });

        // 4. Map Data Gaji
        $gajis->each(function ($gaji) use ($absensiPerBulan) {
            $bulan = $gaji->bulan->month;
            $tahun = $gaji->bulan->year;
            $key = $tahun . '-' . $bulan;

            // Ambil jumlah kehadiran dari Collection (Memori), bukan DB
            $jumlahKehadiran = isset($absensiPerBulan[$key]) ? $absensiPerBulan[$key]->total : 0;

            $tunjanganPerKehadiran = $gaji->tunjanganKehadiran->jumlah_tunjangan ?? 0;
            // Gunakan tunjangan jabatan SNAPSHOT dari tabel gaji jika ada, 
            // fallback ke master jabatan (untuk data lama)
            $tunjanganJabatan = $gaji->tunj_jabatan > 0
                ? $gaji->tunj_jabatan
                : ($gaji->karyawan->jabatan->tunj_jabatan ?? 0);

            $totalTunjanganKehadiran = $jumlahKehadiran * $tunjanganPerKehadiran;

            $totalTunjangan = $totalTunjanganKehadiran + $tunjanganJabatan + $gaji->tunj_anak + $gaji->tunj_komunikasi + $gaji->tunj_pengabdian + $gaji->tunj_kinerja + $gaji->lembur;
            $totalPotongan = $gaji->potongan;

            $gaji->total_tunjangan_custom = $totalTunjangan;
            $gaji->total_potongan_custom = $totalPotongan;
            $gaji->gaji_bersih = ($gaji->gaji_pokok + $totalTunjangan) - $totalPotongan;
            $gaji->jumlah_hadir = $jumlahKehadiran;
        });

        return [
            'gajis' => $gajis,
            'absensi_summary' => [
                'total_hadir_periode' => $totalKehadiranPeriode
            ]
        ];
    }

    public function cetakLaporanPerKaryawan(Request $request)
    {
        $validated = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'bulan_mulai' => 'required|date_format:Y-m',
            'bulan_selesai' => 'required|date_format:Y-m|after_or_equal:bulan_mulai',
        ]);

        GenerateIndividualReport::dispatch(
            $validated['karyawan_id'],
            $validated['bulan_mulai'],
            $validated['bulan_selesai'],
            Auth::id()
        );

        return redirect()->back()->with('success', 'Laporan rincian sedang diproses.');
    }

    public function kirimEmailLaporanPerKaryawan(Request $request)
    {
        $validated = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'bulan_mulai' => 'required|date_format:Y-m',
            'bulan_selesai' => 'required|date_format:Y-m|after_or_equal:bulan_mulai',
        ]);

        SendIndividualReportToEmail::dispatch(
            $validated['karyawan_id'],
            $validated['bulan_mulai'],
            $validated['bulan_selesai'],
            Auth::id()
        );

        return redirect()->back()->with('success', 'Laporan rincian sedang dikirim.');
    }
}
