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

        // [PERBAIKAN LOGIKA UTAMA]
        // 1. Ambil semua data gaji yang sudah tersimpan untuk bulan yang dipilih.
        $gajisTersimpan = Gaji::with('karyawan.jabatan') // [FIX] Eager load relasi karyawan & jabatan
            ->whereYear('bulan', $tanggal->year)
            ->whereMonth('bulan', $tanggal->month)
            ->get();

        // 2. Gunakan SalaryService untuk menghitung ulang detail setiap gaji.
        //    Ini akan menghasilkan 'gaji_bersih' dan data lengkap lainnya.
        $laporanGaji = [];
        foreach ($gajisTersimpan as $gaji) {
            // Service akan mengambil data tersimpan dan menghitung ulang semua tunjangan & gaji bersih.
            $detailKalkulasi = $this->salaryService->calculateDetailsForForm($gaji->karyawan, $selectedMonth);

            // [PERBAIKAN UTAMA] Di sinilah letak bug-nya.
            // View Anda (gaji_bulanan.blade.php) mengharapkan struktur data $item['karyawan']->nama
            // dan $item['gaji']->id, tapi Anda hanya mengirim $detailKalkulasi.
            // Kita bungkus datanya agar sesuai ekspektasi View:
            $laporanGaji[] = [
                'gaji' => $gaji, // Mengirim objek Gaji (untuk $item['gaji']->id)
                'karyawan' => $gaji->karyawan, // Mengirim objek Karyawan (untuk $item['karyawan']->nama)
                'gaji_bersih' => $detailKalkulasi['gaji_bersih_numeric'] // Mengirim gaji bersih (untuk number_format)
            ];
        }

        // 3. Kirim data yang sudah dihitung ulang ke view.
        return view('laporan.gaji_bulanan', compact('laporanGaji', 'selectedMonth'));
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
        $selectedMonthStr = $request->input('periode', Carbon::now()->format('Y-m'));
        $selectedMonth = Carbon::parse($selectedMonthStr);

        // [ASUMSI] Nama method di service adalah getAttendanceRecap
        $rekap = $this->absensiService->getAttendanceRecap($selectedMonth);
        $rekapData = $rekap['rekapData'];
        $daysInMonth = $rekap['daysInMonth'];

        return view('laporan.laporan_absensi', compact('rekapData', 'selectedMonth', 'daysInMonth'));
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
        $selectedKaryawanId = $request->input('karyawan_id');
        $selectedKaryawan = null;
        $laporanData = null;

        $tanggalMulai = $request->input('tanggal_mulai', Carbon::now()->subMonths(2)->format('Y-m'));
        $tanggalSelesai = $request->input('tanggal_selesai', Carbon::now()->format('Y-m'));

        if ($selectedKaryawanId) {
            $selectedKaryawan = Karyawan::findOrFail($selectedKaryawanId);
            $laporanData = $this->getLaporanData($selectedKaryawan, $tanggalMulai, $tanggalSelesai);
        }

        return view('laporan.per_karyawan', compact('karyawans', 'selectedKaryawanId', 'selectedKaryawan', 'tanggalMulai', 'tanggalSelesai', 'laporanData'));
    }

    private function getLaporanData(Karyawan $karyawan, string $startMonth, string $endMonth)
    {
        $startDate = Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $endMonth)->endOfMonth();

        $gajis = Gaji::with('karyawan.jabatan', 'tunjanganKehadiran')
            ->where('karyawan_id', $karyawan->id)
            ->whereBetween('bulan', [$startDate, $endDate])
            ->orderBy('bulan', 'asc')
            ->get();

        // [CATATAN] Logika ini sudah benar, karena ini untuk laporan per karyawan
        // dan BEDA dengan SalaryService. Ini tidak masalah.
        $gajis->each(function ($gaji) {
            $kehadiranBulanIni = Absensi::where('nip', $gaji->karyawan->nip)
                ->whereYear('tanggal', $gaji->bulan->year)
                ->whereMonth('tanggal', $gaji->bulan->month)
                ->count();

            // Menggunakan 'tunj_jabatan' sesuai temuan di SalaryService
            $tunjanganJabatan = $gaji->karyawan->jabatan->tunj_jabatan ?? 0;
            $tunjanganKehadiran = $kehadiranBulanIni * ($gaji->tunjanganKehadiran->jumlah_tunjangan ?? 0);
            $totalTunjangan = $tunjanganJabatan + $tunjanganKehadiran + $gaji->tunj_anak + $gaji->tunj_komunikasi + $gaji->tunj_pengabdian + $gaji->tunj_kinerja + $gaji->lembur;
            $gaji->total_tunjangan = $totalTunjangan;
            $gaji->gaji_bersih = ($gaji->gaji_pokok + $totalTunjangan) - $gaji->potongan;
        });

        // [PERBAIKAN UTAMA] Logika baru untuk menghitung absensi
        // 1. Hitung total kehadiran (ini sudah benar)
        $totalHadir = Absensi::where('nip', $karyawan->nip)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->count();

        // 2. Hitung total hari kerja pada periode tersebut menggunakan AbsensiService
        // [CATATAN] Kode ini ASLI dari Anda, dan logikanya sudah benar (meski agak lambat).
        // Kita tidak akan mengubahnya agar tidak menimbulkan bug baru.
        $workingDaysCount = 0;
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            if ($this->absensiService->getSessionStatus($date)['is_active']) {
                $workingDaysCount++;
            }
        }

        // 3. Hitung alpha
        $totalAlpha = $workingDaysCount - $totalHadir;

        return [
            'gajis' => $gajis,
            'absensi_summary' => [
                'hadir' => $totalHadir,
                'alpha' => $totalAlpha > 0 ? $totalAlpha : 0, // Pastikan tidak negatif
            ],
        ];
    }

    public function cetakLaporanPerKaryawan(Request $request)
    {
        $validated = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'tanggal_mulai' => 'required|date_format:Y-m',
            'tanggal_selesai' => 'required|date_format:Y-m|after_or_equal:tanggal_mulai',
        ]);

        GenerateIndividualReport::dispatch(
            $validated['karyawan_id'],
            $validated['tanggal_mulai'],
            $validated['tanggal_selesai'],
            Auth::id()
        );

        return redirect()->back()->with('success', 'Permintaan cetak laporan rincian sedang diproses. Anda akan dinotifikasi jika sudah siap.');
    }

    public function kirimEmailLaporanPerKaryawan(Request $request)
    {
        $validated = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'tanggal_mulai' => 'required|date_format:Y-m',
            'tanggal_selesai' => 'required|date_format:Y-m|after_or_equal:tanggal_mulai',
        ]);

        SendIndividualReportToEmail::dispatch(
            $validated['karyawan_id'],
            $validated['tanggal_mulai'],
            $validated['tanggal_selesai'],
            Auth::id()
        );

        return redirect()->back()->with('success', 'Laporan rincian karyawan sedang dalam proses pengiriman ke email.');
    }
}
