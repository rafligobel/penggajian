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
}
