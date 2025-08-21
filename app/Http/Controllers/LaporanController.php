<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\User;
use App\Models\TandaTangan;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Traits\ManagesImageEncoding;
use App\Jobs\SendLaporanToEmail;
use App\Jobs\GenerateMonthlySalaryReport;
use App\Jobs\SendSlipToEmail;

class LaporanController extends Controller
{
    use ManagesImageEncoding;

    public function gajiBulanan(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        $gajis = Gaji::with('karyawan')->where('bulan', $selectedMonth)->get();
        return view('laporan.gaji_bulanan', compact('gajis', 'selectedMonth'));
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
            $absensi = Absensi::where('nip', $selectedKaryawan->nip)->whereBetween('tanggal', [$startOfMonth, $endOfMonth])->get();
            $totalHariKerja = $startOfMonth->diffInWeekdays($endOfMonth);

            $laporanData = [
                'gajis' => $gajis,
                'absensi_summary' => ['hadir' => $absensi->count(), 'alpha' => $totalHariKerja - $absensi->count()],
            ];
        }
        return view('laporan.per_karyawan', compact('karyawans', 'selectedKaryawanId', 'tanggalMulai', 'tanggalSelesai', 'laporanData', 'selectedKaryawan'));
    }

    public function rekapAbsensi(Request $request)
    {
        $bulan = $request->input('bulan', Carbon::now()->format('Y-m'));
        $rekap = Absensi::selectRaw('karyawan_id, COUNT(*) as jumlah_hadir')
            ->whereYear('tanggal', Carbon::parse($bulan)->year)
            ->whereMonth('tanggal', Carbon::parse($bulan)->month)
            ->groupBy('karyawan_id')->with('karyawan')->get();
        return view('absensi.rekap', compact('rekap', 'bulan'));
    }

    public function cetakGajiBulanan(Request $request)
    {
        $request->validate(['gaji_ids' => 'required|array|min:1'], ['gaji_ids.required' => 'Pilih setidaknya satu karyawan untuk dicetak.']);
        GenerateMonthlySalaryReport::dispatch($request->input('bulan'), Auth::user(), $request->input('gaji_ids'));
        return back()->with('success', 'Permintaan cetak PDF sedang diproses.');
    }

    public function kirimEmailGajiTerpilih(Request $request)
    {
        $request->validate(['gaji_ids' => 'required|array|min:1'], ['gaji_ids.required' => 'Pilih setidaknya satu karyawan untuk dikirim email.']);
        $gajiIds = $request->input('gaji_ids');
        $user = Auth::user();
        $daftarGaji = Gaji::with('karyawan')->whereIn('id', $gajiIds)->get();
        $karyawanDikirimi = 0;
        foreach ($daftarGaji as $gaji) {
            if (!empty($gaji->karyawan->email)) {
                SendSlipToEmail::dispatch($gaji->id, $user->id);
                $karyawanDikirimi++;
            }
        }
        return back()->with('success', "Permintaan kirim slip gaji ke {$karyawanDikirimi} karyawan sedang diproses.");
    }

    public function cetakLaporanPdf(Request $request)
    {
        $tipeLaporan = $request->input('tipe');
        $viewPath = '';
        $data = [];
        $filename = 'laporan.pdf';
        $paperOrientation = 'portrait';
        $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
        $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));
        $bendaharaNama = User::where('role', 'bendahara')->first()->name ?? 'Bendahara';
        $tandaTanganBendahara = '';
        $pengaturanTtd = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
        if ($pengaturanTtd && Storage::disk('public')->exists($pengaturanTtd->value)) {
            $tandaTanganBendahara = $this->getImageAsBase64DataUri(storage_path('app/public/' . $pengaturanTtd->value));
        }
        $commonData = compact('logoAlAzhar', 'logoYayasan', 'bendaharaNama', 'tandaTanganBendahara');

        switch ($tipeLaporan) {
            case 'gaji_bulanan_semua':
                $bulan = $request->input('bulan');
                $gajis = Gaji::with('karyawan')->where('bulan', $bulan)->get();
                $totals = (object)[
                    'total_gaji_pokok' => $gajis->sum('gaji_pokok'),
                    'total_tunj_jabatan' => $gajis->sum('tunj_jabatan'),
                    'total_tunj_anak' => $gajis->sum('tunj_anak'),
                    'total_tunj_komunikasi' => $gajis->sum('tunj_komunikasi'),
                    'total_tunj_pengabdian' => $gajis->sum('tunj_pengabdian'),
                    'total_tunj_kinerja' => $gajis->sum('tunj_kinerja'),
                    'total_pendapatan_lainnya' => $gajis->sum('pendapatan_lainnya'),
                    'total_potongan' => $gajis->sum('potongan'),
                    'total_gaji_bersih' => $gajis->sum('gaji_bersih'),
                ];
                $viewPath = 'gaji.cetak_semua';
                $data = array_merge($commonData, ['gajis' => $gajis, 'periode' => $bulan, 'totals' => $totals]);
                $filename = 'laporan-gaji-bulanan-' . $bulan . '.pdf';
                $paperOrientation = 'landscape';
                break;
            case 'per_karyawan':
                $karyawanId = $request->input('karyawan_id');
                if (!$karyawanId) return back()->with('error', 'Silakan pilih karyawan.');
                $selectedKaryawan = Karyawan::findOrFail($karyawanId);
                $tanggalMulai = $request->input('tanggal_mulai');
                $tanggalSelesai = $request->input('tanggal_selesai');
                $gajis = Gaji::where('karyawan_id', $karyawanId)
                    ->whereBetween('bulan', [$tanggalMulai, $tanggalSelesai])
                    ->orderBy('bulan', 'asc')->get();
                $startOfMonth = Carbon::createFromFormat('Y-m', $tanggalMulai)->startOfMonth();
                $endOfMonth = Carbon::createFromFormat('Y-m', $tanggalSelesai)->endOfMonth();
                $absensi = Absensi::where('nip', $selectedKaryawan->nip)->whereBetween('tanggal', [$startOfMonth, $endOfMonth])->get();
                $totalHariKerja = $startOfMonth->diffInWeekdays($endOfMonth);
                $absensiSummary = ['hadir' => $absensi->count(), 'alpha' => $totalHariKerja - $absensi->count()];
                $viewPath = 'laporan.pdf.per_karyawan';
                $data = array_merge($commonData, compact('gajis', 'selectedKaryawan', 'tanggalMulai', 'tanggalSelesai', 'absensiSummary'));
                $filename = 'laporan-gaji-' . \Illuminate\Support\Str::slug($selectedKaryawan->nama) . '.pdf';
                break;
            default:
                return back()->with('error', 'Tipe laporan tidak valid.');
        }
        $pdf = Pdf::loadView($viewPath, $data)->setPaper('a4', $paperOrientation);
        return $pdf->stream($filename);
    }

    public function kirimLaporanEmail(Request $request)
    {
        $request->validate(['tipe' => 'required|string']);
        $tipeLaporan = $request->input('tipe');
        $payload = $request->except(['_token', 'tipe']);
        $user = Auth::user();
        SendLaporanToEmail::dispatch($tipeLaporan, $payload, $user);
        return back()->with('success', 'Permintaan pengiriman laporan ke email sedang diproses.');
    }
}
