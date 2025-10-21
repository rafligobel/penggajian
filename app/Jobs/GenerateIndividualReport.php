<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\User;
use App\Models\TandaTangan;
use App\Models\Absensi;
use App\Notifications\ReportGenerated;
use App\Traits\ManagesImageEncoding;
use App\Services\SalaryService; // [PERBAIKAN] Tambahkan use statement
use App\Services\AbsensiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Throwable;

class GenerateIndividualReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected int $karyawanId;
    protected string $tanggalMulai;
    protected string $tanggalSelesai;
    protected int $userId;

    public function __construct(int $karyawanId, string $tanggalMulai, string $tanggalSelesai, int $userId)
    {
        $this->karyawanId = $karyawanId;
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->userId = $userId;
    }

    // [PERBAIKAN] Inject SalaryService
    public function handle(AbsensiService $absensiService, SalaryService $salaryService): void
    {
        $user = User::find($this->userId);
        $karyawan = Karyawan::findOrFail($this->karyawanId);

        try {
            $startDate = Carbon::createFromFormat('Y-m', $this->tanggalMulai)->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $this->tanggalSelesai)->endOfMonth();

            $gajis = Gaji::with('karyawan.jabatan', 'tunjanganKehadiran')
                ->where('karyawan_id', $karyawan->id)
                ->whereBetween('bulan', [$startDate, $endDate])
                ->orderBy('bulan', 'asc')->get();

            // [PERBAIKAN UTAMA] Gunakan Service untuk kalkulasi (DRY)
            $gajis->each(function ($gaji) use ($absensiService, $salaryService) {
                $bulanGaji = $gaji->bulan;

                // 1. Panggil SalaryService untuk semua detail gaji & kehadiran
                $detailGaji = $salaryService->calculateDetailsForForm($gaji->karyawan, $bulanGaji->format('Y-m'));

                // 2. Lampirkan data absensi
                $gaji->hadir = $detailGaji['total_kehadiran'];

                // 3. Hitung hari kerja & alpha (Logika spesifik dari AbsensiService)
                $workingDaysCount = 0;
                $period = \Carbon\CarbonPeriod::create($bulanGaji->copy()->startOfMonth(), $bulanGaji->copy()->endOfMonth());
                foreach ($period as $date) {
                    if ($absensiService->getSessionStatus($date)['is_active']) {
                        $workingDaysCount++;
                    }
                }
                $alphaBulanIni = $workingDaysCount - $gaji->hadir;
                $gaji->alpha = $alphaBulanIni > 0 ? $alphaBulanIni : 0;

                // 4. Lampirkan rincian gaji dari service
                $gaji->total_tunjangan = ($detailGaji['tunj_jabatan'] ?? 0) +
                    ($detailGaji['tunj_kehadiran'] ?? 0) +
                    ($detailGaji['tunj_anak'] ?? 0) +
                    ($detailGaji['tunj_komunikasi'] ?? 0) +
                    ($detailGaji['tunj_pengabdian'] ?? 0) +
                    ($detailGaji['tunj_kinerja'] ?? 0) +
                    ($detailGaji['lembur'] ?? 0);
                $gaji->gaji_bersih = $detailGaji['gaji_bersih_numeric'] ?? 0;
            });

            // Ambil data tanda tangan & logo
            $bendahara = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            $bendaharaNama = $bendahara ? $bendahara->nama : 'Bendahara Belum Diset';
            $tandaTanganBendahara = $bendahara && Storage::disk('public')->exists($bendahara->path)
                ? $this->getImageAsBase64DataUri(storage_path('app/public/' . $bendahara->path))
                : null;

            $data = [
                'karyawan' => $karyawan,
                'gajis' => $gajis,
                'tanggalMulai' => $startDate,
                'tanggalSelesai' => $endDate,
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara,
                'logoAlAzhar' => $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png')),
                'logoYayasan' => $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png')),
            ];

            $pdf = Pdf::loadView('laporan.pdf.per_karyawan', $data)->setPaper('A4', 'portrait');

            $safeName = Str::slug($karyawan->nama, '_');
            $downloadFilename = "laporan_rinci_{$safeName}_{$this->tanggalMulai}_sd_{$this->tanggalSelesai}.pdf";
            $storageFilename = uniqid() . "_{$downloadFilename}";
            $path = 'laporan/individual/' . $storageFilename;

            Storage::disk('local')->put($path, $pdf->output());

            $notifMessage = 'Laporan rincian untuk ' . $karyawan->nama . ' telah selesai dibuat.';
            $user->notify(new ReportGenerated($path, $downloadFilename, $this->tanggalMulai, $notifMessage));
        } catch (Throwable $e) {
            Log::error('Gagal membuat Laporan Rincian Karyawan: ' . $e->getMessage(), ['exception' => $e]);
            $user->notify(new ReportGenerated('', '', $this->tanggalMulai, 'Gagal membuat laporan rincian untuk ' . $karyawan->nama . '.', true));
        }
    }
}
