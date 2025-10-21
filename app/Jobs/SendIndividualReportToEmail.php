<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\User;
use App\Models\TandaTangan;
use App\Mail\IndividualReportMail;
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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Throwable;

class SendIndividualReportToEmail implements ShouldQueue
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

        $cleanKaryawanNama = mb_convert_encoding($karyawan->nama, 'UTF-8', 'UTF-8');
        $notifMessage = 'Gagal membuat laporan rincian untuk ' . $cleanKaryawanNama . '.';
        $periodeNotif = $this->tanggalMulai;

        try {
            if (empty($karyawan->email)) {
                throw new \Exception("Karyawan {$cleanKaryawanNama} (ID: {$karyawan->id}) tidak memiliki alamat email.");
            }

            $startDate = Carbon::createFromFormat('Y-m', $this->tanggalMulai)->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $this->tanggalSelesai)->endOfMonth();
            $periodeNotif = $startDate->format('Y-m');

            $gajis = Gaji::with('karyawan.jabatan', 'tunjanganKehadiran')
                ->where('karyawan_id', $karyawan->id)
                ->whereBetween('bulan', [$startDate, $endDate])
                ->orderBy('bulan', 'asc')->get();

            if ($gajis->isEmpty()) {
                throw new \Exception("Tidak ditemukan data gaji untuk {$cleanKaryawanNama} pada periode {$startDate->translatedFormat('F Y')} s/d {$endDate->translatedFormat('F Y')}.");
            }

            // [PERBAIKAN UTAMA] Gunakan Service untuk kalkulasi (DRY)
            $gajis->each(function ($gaji) use ($absensiService, $salaryService) {
                $bulanGaji = $gaji->bulan;

                // 1. Panggil SalaryService untuk semua detail gaji & kehadiran
                $detailGaji = $salaryService->calculateDetailsForForm($gaji->karyawan, $bulanGaji->format('Y-m'));

                // 2. Lampirkan data absensi
                $gaji->hadir = $detailGaji['total_kehadiran'];

                // 3. Hitung hari kerja & alpha
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
                    ($detailGaji['tunj_kehadiran'] ?? 0) + // [PERBAIKAN ROBUSTNESS - FIX UNDEFINED ARRAY KEY]
                    ($detailGaji['tunj_anak'] ?? 0) +
                    ($detailGaji['tunj_komunikasi'] ?? 0) +
                    ($detailGaji['tunj_pengabdian'] ?? 0) +
                    ($detailGaji['tunj_kinerja'] ?? 0) +
                    ($detailGaji['lembur'] ?? 0);
                $gaji->gaji_bersih = $detailGaji['gaji_bersih_numeric'];
            });

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
            $pdfOutput = $pdf->output();

            $safeName = Str::slug($cleanKaryawanNama, '_');
            $downloadFilename = "laporan_rinci_{$safeName}_{$this->tanggalMulai}_sd_{$this->tanggalSelesai}.pdf";
            $storageFilename = uniqid() . "_{$downloadFilename}";
            $path = 'laporan/individual/' . $storageFilename;

            Storage::disk('local')->put($path, $pdfOutput);

            // [PERBAIKAN FOKUS 4] Urutan argumen disesuaikan dengan Mailable yang telah diperbaiki:
            // Urutan baru: (Karyawan, filePath, pdfFilename, startDate, endDate)
            Mail::to($karyawan->email)->send(new IndividualReportMail(
                $karyawan, // Argumen #1: Karyawan (object)
                $gajis, // Argumen #2: Gajis (Collection) -> INI PERBAIKAN TIPE DATA UTAMA
                $path, // Argumen #3: File Path (string)
                $downloadFilename, // Argumen #4: File Name (string)
                $startDate, // Argumen #5: Tanggal Mulai (Carbon)
                $endDate, // Argumen #6: Tanggal Selesai (Carbon)
                $data['logoYayasan'], // Argumen #7: Logo Yayasan (base64)
                $data['logoAlAzhar'], // Argumen #8: Logo Al Azhar (base64)
                $bendaharaNama, // Argumen #9: Nama Bendahara (string)
                $tandaTanganBendahara // Argumen #10: Tanda Tangan (base64/null)
            ));

            Log::info("Email Laporan Rincian Karyawan berhasil dikirim ke: {$karyawan->email}");

            $notifMessage = 'Laporan rincian ' . $cleanKaryawanNama . ' berhasil dikirim ke email.';
            $user->notify(new ReportGenerated($path, $downloadFilename, $periodeNotif, $notifMessage, false));
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage();
            $cleanErrorMessage = mb_convert_encoding($errorMessage, 'UTF-8', 'UTF-8');
            Log::error('Gagal mengirim Laporan Rincian Karyawan: ' . $cleanErrorMessage, ['exception' => $e]);
            $user->notify(new ReportGenerated('', '', $periodeNotif, $cleanErrorMessage, true));
        }
    }
}
