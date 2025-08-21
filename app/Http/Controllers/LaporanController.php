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
use App\Jobs\SendSlipToEmail; // Pastikan use statement ini ada
use App\Jobs\GenerateIndividualReport;
use App\Jobs\SendIndividualReportToEmail;
use App\Jobs\GenerateIndividualSlip;
use App\Models\SesiAbsensi;
use App\Jobs\GenerateAttendanceReport;
use App\Jobs\SendAttendanceReportToEmail;

class LaporanController extends Controller
{
    use ManagesImageEncoding;

    public function index()
    {
        return redirect()->route('laporan.gaji.bulanan');
    }

    public function gajiBulanan(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        $gajis = Gaji::with('karyawan')->where('bulan', $selectedMonth)->get();
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
            $totalHariKerja = $startOfMonth->diffInWeekdays($endOfMonth);
            $laporanData = [
                'gajis' => $gajis,
                'absensi_summary' => [
                    'hadir' => $absensi->count(),
                    'alpha' => $totalHariKerja - $absensi->count(),
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

    /**
     * Menangani permintaan untuk mengirim laporan per karyawan ke email di latar belakang.
     */
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

    /**
     * METODE YANG HILANG, SEKARANG DITAMBAHKAN KEMBALI
     * Menangani permintaan untuk mengirim slip gaji terpilih ke email masing-masing karyawan.
     */
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
            // Hanya kirim jika karyawan punya email
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


    //laporan absensi
    public function rekapAbsensi(Request $request)
    {
        $periode = $request->input('periode', date('Y-m'));
        $date = Carbon::createFromFormat('Y-m', $periode);
        $bulan = $date->format('m');
        $tahun = $date->format('Y');

        $karyawanData = Karyawan::where('status_aktif', true)
            ->with(['absensi' => function ($query) use ($bulan, $tahun) {
                $query->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
            }])
            ->get();

        $sesiAbsensi = SesiAbsensi::all()->keyBy('id');
        $jumlahHariKerja = $date->daysInMonth;

        $rekapData = $karyawanData->map(function ($karyawan) use ($sesiAbsensi, $jumlahHariKerja) {
            $summary = [
                'total_hadir' => $karyawan->absensi->count(),
                'total_alpha' => $jumlahHariKerja - $karyawan->absensi->count(),
                'sesi' => []
            ];

            foreach ($sesiAbsensi as $sesi) {
                $summary['sesi'][$sesi->id] = ['nama' => $sesi->nama, 'hadir' => $karyawan->absensi->where('sesi_absensi_id', $sesi->id)->count()];
            }

            return (object)['id' => $karyawan->id, 'nip' => $karyawan->nip, 'nama' => $karyawan->nama, 'email' => $karyawan->email, 'summary' => $summary];
        });

        return view('laporan.laporan_absensi', compact('rekapData', 'bulan', 'tahun', 'sesiAbsensi'));
    }

    /**
     * Menangani permintaan cetak PDF untuk rekap absensi terpilih.
     */
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

    /**
     * Menangani permintaan kirim email untuk rekap absensi terpilih.
     */
    public function kirimEmailRekapAbsensi(Request $request)
    {
        $validated = $request->validate([
            'karyawan_ids' => 'required|array|min:1',
            'periode' => 'required|date_format:Y-m',
        ]);

        $date = Carbon::createFromFormat('Y-m', $validated['periode']);

        // ======================================================
        // FUNGSI SEKARANG BERJALAN: Memanggil Job untuk setiap karyawan
        // ======================================================
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
