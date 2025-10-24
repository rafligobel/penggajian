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
use App\Services\SalaryService; // [TAMBAHKAN] Import SalaryService
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;



class LaporanController extends Controller
{
    protected $absensiService;
    protected $salaryService; // [TAMBAHKAN] Deklarasi properti

    // [MODIFIKASI] Inject SalaryService di constructor
    public function __construct(AbsensiService $absensiService, SalaryService $salaryService)
    {
        $this->absensiService = $absensiService;
        $this->salaryService = $salaryService; // [TAMBAHKAN] Inisialisasi service
    }

    // --- LAPORAN GAJI BULANAN ---
    public function gajiBulanan(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        $tanggal = Carbon::createFromFormat('Y-m', $selectedMonth);

        $gajis = Gaji::whereYear('bulan', $tanggal->year)
            ->whereMonth('bulan', $tanggal->month)
            ->with('karyawan.jabatan', 'tunjanganKehadiran')
            ->orderBy('karyawan_id')
            ->get();

        $absensi = Absensi::whereYear('tanggal', $tanggal->year)
            ->whereMonth('tanggal', $tanggal->month)
            // Menggunakan karyawan_id untuk grouping dan select
            ->groupBy('karyawan_id')
            ->selectRaw('karyawan_id, count(*) as jumlah_hadir')
            ->pluck('jumlah_hadir', 'karyawan_id');

        $totalHariKerja = $this->absensiService->getAttendanceRecap($tanggal)['workingDaysCount'];

        $gajis->each(function ($gaji) use ($absensi, $totalHariKerja) {
            $gaji->jumlah_hadir = $absensi->get($gaji->karyawan_id, 0);
            $gaji->gaji_bersih_perhitungan = $this->salaryService->calculateDetailsForForm($gaji->karyawan, $gaji->bulan->format('Y-m-d'))['gaji_bersih_numeric'];
        });

        // PERBAIKAN: Mengganti DISTINCT dan ORDER BY yang bermasalah dengan GROUP BY
        $availableMonths = Gaji::selectRaw('DATE_FORMAT(bulan, "%Y-%m") as bulan_ym')
            ->groupBy('bulan_ym')
            ->orderBy('bulan_ym', 'desc')
            ->pluck('bulan_ym');

        return view('laporan.gaji_bulanan', compact('gajis', 'tanggal', 'selectedMonth', 'availableMonths', 'totalHariKerja'));
    }


    public function cetakGajiBulanan(Request $request)
    {
        // [PERBAIKAN] Validasi yang lebih ketat dengan pesan kustom
        $validated = $request->validate([
            'gaji_ids' => 'required|array|min:1',
            'gaji_ids.*' => 'exists:gajis,id',
            'bulan' => 'required|date_format:Y-m',
        ], [
            'gaji_ids.required' => 'Anda harus memilih setidaknya satu karyawan untuk mencetak laporan gaji.'
        ]);

        GenerateMonthlySalaryReport::dispatch($validated['bulan'], Auth::user(), $validated['gaji_ids']);
        return redirect()->back()->with('success', 'Permintaan cetak laporan gaji bulanan sedang diproses.');
    }
    public function kirimEmailGajiTerpilih(Request $request)
    {
        // [PERBAIKAN] Validasi yang lebih ketat dengan pesan kustom
        $validated = $request->validate([
            'gaji_ids' => 'required|array|min:1',
            'gaji_ids.*' => 'exists:gajis,id',
        ], [
            'gaji_ids.required' => 'Anda harus memilih setidaknya satu karyawan untuk mengirim slip gaji.'
        ]);

        SendSlipToEmail::dispatch($validated['gaji_ids'], Auth::id());
        return redirect()->back()->with('success', 'Proses pengiriman slip gaji ke email sedang berjalan.');
    }


    // --- LAPORAN ABSENSI ---
    public function rekapAbsensi(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        $tanggal = Carbon::createFromFormat('Y-m', $selectedMonth);

        // PERBAIKAN KRITIS A: Panggil service dan simpan hasilnya
        $result = $this->absensiService->getAttendanceRecap($tanggal);

        // PERBAIKAN KRITIS B: Ekstrak data yang dibutuhkan view
        $rekapData = $result['rekapData'];
        $daysInMonth = $result['daysInMonth'];

        $availableMonths = Absensi::selectRaw('DATE_FORMAT(tanggal, "%Y-%m") as bulan_ym')
            ->groupBy('bulan_ym')
            ->orderBy('bulan_ym', 'desc')
            ->pluck('bulan_ym');

        // PERBAIKAN KRITIS C: Leewatkan semua variabel penting ke view
        return view('laporan.laporan_absensi', compact('rekapData', 'tanggal', 'selectedMonth', 'availableMonths', 'daysInMonth'));
    }

    public function cetakRekapAbsensi(Request $request)
    {
        // [PERBAIKAN] Validasi yang lebih baik dengan pesan kustom
        $validated = $request->validate([
            'karyawan_ids' => 'required|array|min:1',
            'karyawan_ids.*' => 'exists:karyawans,id',
            'periode' => 'required|date_format:Y-m',
        ], [
            'karyawan_ids.required' => 'Anda harus memilih setidaknya satu karyawan untuk mencetak laporan.',
        ]);

        $date = Carbon::createFromFormat('Y-m', $validated['periode']);

        GenerateAttendanceReport::dispatch(
            $validated['karyawan_ids'],
            $date->format('m'),
            $date->format('Y'),
            Auth::id()
        );

        return redirect()->back()->with('success', 'Permintaan cetak PDF rekap absensi sedang diproses. Anda akan dinotifikasi jika sudah siap.');
    }

    public function kirimEmailRekapAbsensi(Request $request)
    {
        // [PERBAIKAN] Validasi yang lebih baik dengan pesan kustom
        $validated = $request->validate([
            'karyawan_ids' => 'required|array|min:1',
            'karyawan_ids.*' => 'exists:karyawans,id',
            'periode' => 'required|date_format:Y-m',
        ], [
            'karyawan_ids.required' => 'Anda harus memilih setidaknya satu karyawan untuk mengirim email laporan.',
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

        return redirect()->back()->with('success', 'Proses pengiriman laporan absensi ke email karyawan terpilih sedang berjalan.');
    }


    // --- LAPORAN PER KARYAWAN ---
    public function perKaryawan(Request $request)
    {
        $karyawans = Karyawan::orderBy('nama')->get();

        // PERBAIKAN: Mendefinisikan variabel ini yang dibutuhkan View
        $selectedKaryawanId = $request->input('karyawan_id');

        $karyawan = null;
        $laporanData = null;

        $availableMonths = Gaji::selectRaw('DATE_FORMAT(bulan, "%Y-%m") as bulan_ym')
            ->groupBy('bulan_ym') // PERBAIKAN: Mengganti DISTINCT
            ->orderBy('bulan_ym', 'desc')
            ->pluck('bulan_ym');

        $bulanMulai = $request->input('bulan_mulai', $availableMonths->first() ?? Carbon::now()->format('Y-m'));
        $bulanSelesai = $request->input('bulan_selesai', Carbon::now()->format('Y-m'));


        if ($selectedKaryawanId) {
            $karyawan = Karyawan::findOrFail($selectedKaryawanId);
            $laporanData = $this->getLaporanData($karyawan, $bulanMulai, $bulanSelesai);
        }

        // PERBAIKAN: Melewatkan $selectedKaryawanId ke view
        return view('laporan.per_karyawan', compact('karyawans', 'karyawan', 'laporanData', 'bulanMulai', 'bulanSelesai', 'availableMonths', 'selectedKaryawanId'));
    }
    private function getLaporanData(Karyawan $karyawan, string $bulanMulai, string $bulanSelesai): array
    {
        $start = Carbon::createFromFormat('Y-m', $bulanMulai)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $bulanSelesai)->endOfMonth();

        $gajis = Gaji::where('karyawan_id', $karyawan->id)
            ->whereBetween('bulan', [$start, $end])
            ->with('tunjanganKehadiran')
            ->orderBy('bulan', 'asc')
            ->get();

        // Mengambil Total Kehadiran untuk seluruh periode
        $totalKehadiranPeriode = Absensi::where('karyawan_id', $karyawan->id)
            ->whereBetween('tanggal', [$start, $end])
            ->count();


        $gajis->each(function ($gaji) {
            $bulan = $gaji->bulan->month;
            $tahun = $gaji->bulan->year;

            // PERBAIKAN KRITIS (1): Mengganti 'nip' dengan 'karyawan_id'
            $jumlahKehadiran = Absensi::where('karyawan_id', $gaji->karyawan_id) // Menggunakan variabel yang ada ($gaji->karyawan_id)
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan)
                ->count();

            $tunjanganPerKehadiran = $gaji->tunjanganKehadiran->jumlah_tunjangan ?? 0;
            $tunjanganJabatan = $gaji->karyawan->jabatan->tunj_jabatan ?? 0;

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
            // PERBAIKAN KRITIS: Menambahkan kunci yang dibutuhkan View
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
            $validated['bulan_mulai'], // Menggunakan bulan_mulai
            $validated['bulan_selesai'], // Menggunakan bulan_selesai
            Auth::id()
        );

        return redirect()->back()->with('success', 'Permintaan cetak laporan rincian sedang diproses. Anda akan dinotifikasi jika sudah siap.');
    }

    public function kirimEmailLaporanPerKaryawan(Request $request)
    {
        $validated = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            // PERBAIKAN KRITIS: Mengganti 'tanggal_mulai' menjadi 'bulan_mulai'
            'bulan_mulai' => 'required|date_format:Y-m',
            // PERBAIKAN KRITIS: Mengganti 'tanggal_selesai' menjadi 'bulan_selesai'
            'bulan_selesai' => 'required|date_format:Y-m|after_or_equal:bulan_mulai',
        ]);

        SendIndividualReportToEmail::dispatch(
            $validated['karyawan_id'],
            $validated['bulan_mulai'], // Menggunakan bulan_mulai
            $validated['bulan_selesai'], // Menggunakan bulan_selesai
            Auth::id()
        );

        return redirect()->back()->with('success', 'Laporan rincian karyawan sedang dalam proses pengiriman ke email.');
    }
}
