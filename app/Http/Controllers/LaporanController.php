<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Traits\ManagesImageEncoding;
use App\Jobs\GenerateMonthlySalaryReport;
use App\Jobs\SendSlipToEmail;
use App\Jobs\GenerateIndividualReport;
use App\Jobs\SendIndividualReportToEmail;
use App\Jobs\GenerateIndividualSlip;
use App\Models\SesiAbsensi;
use App\Jobs\GenerateAttendanceReport;
use App\Jobs\SendAttendanceReportToEmail;
use App\Services\AbsensiService; // Import AbsensiService

class LaporanController extends Controller
{
    use ManagesImageEncoding;

    protected $absensiService;

    public function __construct(AbsensiService $absensiService)
    {
        $this->absensiService = $absensiService;
    }

    public function index()
    {
        return redirect()->route('laporan.gaji.bulanan');
    }

    public function gajiBulanan(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        // [PERBAIKAN] Eager load relasi karyawan beserta jabatan
        $gajis = Gaji::with('karyawan.jabatan')->where('bulan', $selectedMonth)->get();
        $statistik = [
            'total_pengeluaran' => $gajis->sum('gaji_bersih'),
            'gaji_tertinggi' => $gajis->max('gaji_bersih'),
            'gaji_rata_rata' => $gajis->avg('gaji_bersih'),
            'jumlah_penerima' => $gajis->count(),
        ];
        return view('laporan.gaji_bulanan', compact('gajis', 'selectedMonth', 'statistik'));
    }

    public function cetakGajiBulanan(Request $request)
    {
        $request->validate([
            'bulan' => 'required|date_format:Y-m',
            'gaji_ids' => 'nullable|array',
            'gaji_ids.*' => 'exists:gajis,id',
        ]);
        $gajiIds = $request->input('gaji_ids');
        $selectedMonth = $request->input('bulan');
        $user = Auth::user();
        if (empty($gajiIds)) {
            return redirect()->back()->withErrors(['gaji_ids' => 'Silakan pilih setidaknya satu karyawan untuk dicetak.']);
        }
        GenerateMonthlySalaryReport::dispatch($selectedMonth, $user, $gajiIds);
        return redirect()->route('laporan.gaji.bulanan', ['bulan' => $selectedMonth])
            ->with('success', 'Permintaan laporan diterima! Laporan sedang diproses dan akan muncul di notifikasi jika sudah siap.');
    }

    public function perKaryawan(Request $request)
    {
        $karyawans = Karyawan::where('status_aktif', true)->orderBy('nama')->get();
        $selectedKaryawanId = $request->input('karyawan_id');
        $tanggalMulai = $request->input('tanggal_mulai', Carbon::now()->subMonths(5)->format('Y-m'));
        $tanggalSelesai = $request->input('tanggal_selesai', Carbon::now()->format('Y-m'));
        $laporanData = [];
        $selectedKaryawan = null;
        if ($selectedKaryawanId) {
            $selectedKaryawan = Karyawan::find($selectedKaryawanId);
            $gajis = Gaji::where('karyawan_id', $selectedKaryawanId)
                ->whereBetween('bulan', [$tanggalMulai, $tanggalSelesai])
                ->orderBy('bulan', 'asc')->get();
            $startOfMonth = Carbon::createFromFormat('Y-m', $tanggalMulai)->startOfMonth();
            $endOfMonth = Carbon::createFromFormat('Y-m', $tanggalSelesai)->endOfMonth();
            $absensi = Absensi::where('nip', $selectedKaryawan->nip)
                ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                ->get();

            // [PERBAIKAN] Hitung hari kerja efektif
            $jumlahHariKerjaEfektif = 0;
            for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
                if ($this->absensiService->getSessionStatus($date)['is_active']) {
                    $jumlahHariKerjaEfektif++;
                }
            }

            $laporanData = [
                'gajis' => $gajis,
                'absensi_summary' => [
                    'hadir' => $absensi->count(),
                    'alpha' => $jumlahHariKerjaEfektif - $absensi->count(),
                ],
            ];
        }
        return view('laporan.per_karyawan', compact('karyawans', 'selectedKaryawanId', 'tanggalMulai', 'tanggalSelesai', 'laporanData', 'selectedKaryawan'));
    }
    public function cetakLaporanPerKaryawan(Request $request)
    {
        $validated = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'tanggal_mulai' => 'required|date_format:Y-m',
            'tanggal_selesai' => 'required|date_format:Y-m',
        ]);

        GenerateIndividualReport::dispatch(
            $validated['karyawan_id'],
            $validated['tanggal_mulai'],
            $validated['tanggal_selesai'],
            Auth::id()
        );

        return redirect()->back()->with('success', 'Permintaan cetak PDF untuk laporan karyawan sedang diproses. Anda akan dinotifikasi jika sudah siap.');
    }

    public function kirimEmailLaporanPerKaryawan(Request $request)
    {
        $validated = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'tanggal_mulai' => 'required|date_format:Y-m',
            'tanggal_selesai' => 'required|date_format:Y-m',
        ]);

        $karyawan = Karyawan::findOrFail($validated['karyawan_id']);

        if (empty($karyawan->email)) {
            return redirect()->back()->with('error', 'Gagal. Karyawan ini tidak memiliki alamat email.');
        }

        SendIndividualReportToEmail::dispatch(
            $validated['karyawan_id'],
            $validated['tanggal_mulai'],
            $validated['tanggal_selesai'],
            Auth::id()
        );

        return redirect()->back()->with('success', "Permintaan pengiriman email untuk {$karyawan->nama} sedang diproses. Anda akan dinotifikasi jika sudah siap.");
    }

    public function kirimEmailGajiTerpilih(Request $request)
    {
        $validated = $request->validate([
            'gaji_ids' => 'required|array|min:1',
            'gaji_ids.*' => 'exists:gajis,id',
        ], [
            'gaji_ids.required' => 'Silakan pilih setidaknya satu karyawan untuk dikirim email.',
            'gaji_ids.min' => 'Silakan pilih setidaknya satu karyawan untuk dikirim email.',
        ]);

        $user = Auth::user();
        $gajiIds = $validated['gaji_ids'];
        $karyawanDikirimi = 0;

        $daftarGaji = Gaji::with('karyawan')->whereIn('id', $gajiIds)->get();

        foreach ($daftarGaji as $gaji) {
            if (!empty($gaji->karyawan->email)) {
                SendSlipToEmail::dispatch($gaji->id, $user->id);
                $karyawanDikirimi++;
            }
        }

        if ($karyawanDikirimi > 0) {
            return back()->with('success', "Permintaan pengiriman email untuk {$karyawanDikirimi} karyawan sedang diproses. Anda akan dinotifikasi jika selesai.");
        } else {
            return back()->with('error', 'Tidak ada karyawan terpilih yang memiliki alamat email.');
        }
    }

    // app/Http/Controllers/LaporanController.php

    public function rekapAbsensi(Request $request)
    {
        $periode = $request->input('periode', date('Y-m'));
        $selectedMonth = Carbon::createFromFormat('Y-m', $periode);
        $daysInMonth = $selectedMonth->daysInMonth;

        // Menghitung hari kerja efektif dalam sebulan menggunakan AbsensiService
        $workingDaysCount = 0;
        $workingDaysMap = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $selectedMonth->copy()->setDay($day);
            if ($this->absensiService->getSessionStatus($currentDate)['is_active']) {
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
            $totalAlpha = $workingDaysCount - $totalHadir;

            // Membuat detail absensi harian untuk kalender
            $harian = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $absenPadaHariIni = $karyawanAbsensi->firstWhere(fn($item) => Carbon::parse($item->tanggal)->day == $day);
                $status = '-'; // Default untuk hari libur atau non-aktif

                if ($workingDaysMap[$day]) { // Jika ini adalah hari kerja
                    $status = $absenPadaHariIni ? 'H' : 'A';
                }

                $harian[$day] = [
                    'status' => $status,
                    'jam' => $absenPadaHariIni ? Carbon::parse($absenPadaHariIni->jam)->format('H:i') : '-',
                ];
            }

            $rekapData[] = (object)[
                'id' => $karyawan->id,
                'nip' => $karyawan->nip,
                'nama' => $karyawan->nama,
                'email' => $karyawan->email,
                'summary' => [
                    'total_hadir' => $totalHadir,
                    'total_alpha' => $totalAlpha < 0 ? 0 : $totalAlpha,
                ],
                'detail' => $harian, // Mengirim data harian ke view
            ];
        }

        // Mengirim variabel yang dibutuhkan oleh view baru
        return view('laporan.laporan_absensi', compact('rekapData', 'selectedMonth', 'daysInMonth'));
    }

    public function cetakRekapAbsensi(Request $request)
    {
        $validated = $request->validate([
            'karyawan_ids' => 'required|array|min:1',
            'periode' => 'required|date_format:Y-m',
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
        $validated = $request->validate([
            'karyawan_ids' => 'required|array|min:1',
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

        return redirect()->back()->with('success', 'Permintaan kirim email rekap absensi untuk karyawan terpilih sedang diproses.');
    }
}
