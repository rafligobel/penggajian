<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\User;
use App\Models\TandaTangan;
use App\Notifications\ReportGenerated;
use App\Services\SalaryService;
use App\Traits\ManagesImageEncoding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Throwable;

class GenerateIndividualReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected $karyawanId;
    protected $tanggalMulai;
    protected $tanggalSelesai;
    protected $userId;

    public function __construct(int $karyawanId, string $tanggalMulai, string $tanggalSelesai, int $userId)
    {
        $this->karyawanId = $karyawanId;
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->userId = $userId;
    }

    public function handle(SalaryService $salaryService): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            Log::error('User tidak ditemukan untuk notifikasi Laporan Individual.', ['userId' => $this->userId]);
            return;
        }

        try {
            $selectedKaryawan = Karyawan::with('jabatan')->findOrFail($this->karyawanId);

            $gajis = Gaji::with('karyawan.jabatan', 'tunjanganKehadiran')
                ->where('karyawan_id', $this->karyawanId)
                ->whereBetween('bulan', [$this->tanggalMulai, $this->tanggalSelesai])
                ->orderBy('bulan', 'asc')->get();

            $totalTunjanganKehadiran = $gajis->sum(function ($gaji) use ($salaryService) {
                $detailGajiBulanIni = $salaryService->calculateDetailsForForm($gaji->karyawan, $gaji->bulan);
                return $detailGajiBulanIni['tunj_kehadiran'];
            });

            $totalPerTunjangan = [
                'Tunjangan Jabatan' => $gajis->sum(fn($g) => $g->karyawan->jabatan->tunj_jabatan ?? 0),
                'Tunjangan Kehadiran' => $totalTunjanganKehadiran,
                'Tunjangan Anak' => $gajis->sum('tunj_anak'),
                'Tunjangan Komunikasi' => $gajis->sum('tunj_komunikasi'),
                'Tunjangan Pengabdian' => $gajis->sum('tunj_pengabdian'),
                'Tunjangan Kinerja' => $gajis->sum('tunj_kinerja'),
                'Lembur' => $gajis->sum('lembur'),
            ];

            $totalGajiPokok = $gajis->sum('gaji_pokok');
            $totalSemuaTunjangan = collect($totalPerTunjangan)->sum();
            $totalPotongan = $gajis->sum('potongan');
            $totalGajiBersih = $totalGajiPokok + $totalSemuaTunjangan - $totalPotongan;

            // [PERBAIKAN] Mengambil tanda tangan berdasarkan 'key', bukan 'status' atau 'is_active'
            $tandaTanganBendahara = '';
            $pengaturanTtd = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            if ($pengaturanTtd && Storage::disk('public')->exists($pengaturanTtd->value)) {
                $tandaTanganBendahara = $this->getImageAsBase64DataUri(storage_path('app/public/' . $pengaturanTtd->value));
            }
            $bendahara = User::where('role', 'bendahara')->first();
            $bendaharaNama = $bendahara ? $bendahara->name : 'Bendahara Umum';

            $data = [
                'selectedKaryawan' => $selectedKaryawan,
                'gajis' => $gajis,
                'tanggalMulai' => Carbon::createFromFormat('Y-m', $this->tanggalMulai)->translatedFormat('F Y'),
                'tanggalSelesai' => Carbon::createFromFormat('Y-m', $this->tanggalSelesai)->translatedFormat('F Y'),
                'logoAlAzhar' => $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png')),
                'logoYayasan' => $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png')),
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara,
                'totalGajiPokok' => $totalGajiPokok,
                'totalPerTunjangan' => $totalPerTunjangan,
                'totalSemuaTunjangan' => $totalSemuaTunjangan,
                'totalPotongan' => $totalPotongan,
                'totalGajiBersih' => $totalGajiBersih,
            ];

            $pdf = Pdf::loadView('laporan.pdf.per_karyawan', $data);
            $pdf->setPaper('A4', 'portrait');

            $safeFilename = str_replace(' ', '_', strtolower($selectedKaryawan->nama));
            $filename = 'reports/' . $safeFilename . '_' . $this->tanggalMulai . '_sd_' . $this->tanggalSelesai . '_' . uniqid() . '.pdf';

            Storage::disk('public')->put($filename, $pdf->output());

            $user->notify(new ReportGenerated(
                $filename,
                'Laporan Karyawan ' . $selectedKaryawan->nama . '.pdf',
                $this->tanggalMulai,
                'Laporan Rincian Karyawan untuk ' . $selectedKaryawan->nama . ' telah selesai dibuat.'
            ));
        } catch (Throwable $e) {
            Log::error('Gagal membuat Laporan Rincian Karyawan: ' . $e->getMessage(), ['exception' => $e]);
            $user->notify(new ReportGenerated(
                '',
                'Gagal Membuat Laporan',
                $this->tanggalMulai,
                'Terjadi kesalahan teknis saat membuat laporan karyawan. Error: ' . $e->getMessage(),
                true
            ));
        }
    }
}
