<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\User;
use App\Mail\SalarySlipMail;
use App\Notifications\ReportGenerated;
use App\Traits\ManagesImageEncoding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Throwable;
use Illuminate\Support\Facades\Log;

class SendSlipToEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected int $gajiId;
    protected int $userId;

    public function __construct(int $gajiId, int $userId)
    {
        $this->gajiId = $gajiId;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        Log::info("MEMULAI JOB SendSlipToEmail untuk Gaji ID: {$this->gajiId}");
        $user = null;

        try {
            Log::info("[Checkpoint 1] Mencari user dengan ID: {$this->userId}");
            $user = User::findOrFail($this->userId);

            Log::info("[Checkpoint 2] Mencari gaji dengan ID: {$this->gajiId}");
            $gaji = Gaji::with('karyawan')->findOrFail($this->gajiId);

            Log::info("[Checkpoint 3] Memeriksa relasi karyawan.");
            if (!$gaji->karyawan) {
                throw new \Exception("Data Karyawan tidak ditemukan untuk Gaji ID: {$this->gajiId}.");
            }
            $karyawan = $gaji->karyawan;
            Log::info("--> Karyawan ditemukan: {$karyawan->nama}");

            Log::info("[Checkpoint 4] Memeriksa email karyawan.");
            if (empty($karyawan->email)) {
                Log::warning("--> Karyawan {$karyawan->nama} tidak punya email. Job dihentikan.");
                $user->notify(new ReportGenerated('', '', $gaji->bulan, 'Batal kirim: ' . $karyawan->nama . ' tidak memiliki email.', true));
                return;
            }
            Log::info("--> Email ditemukan: {$karyawan->email}");

            Log::info("[Checkpoint 5] Menyiapkan data untuk PDF.");
            $logoAlAzhar = $this->encodeImageToBase64(public_path('logo/logoalazhar.png'));
            $logoYayasan = $this->encodeImageToBase64(public_path('logo/logoyayasan.png'));
            Log::info("--> Gambar logo selesai di-encode.");

            Log::info("[Checkpoint 6] Membuat PDF.");
            $pdf = Pdf::loadView('gaji.slip_pdf', compact('gaji', 'logoAlAzhar', 'logoYayasan'));
            $pdf->setPaper('A4', 'portrait');
            Log::info("--> Mesin PDF berhasil di-load.");

            Log::info("[Checkpoint 7] Menyimpan PDF ke storage.");
            $safeFilename = str_replace(' ', '_', strtolower($karyawan->nama));
            $path = 'slips/slip-gaji-' . $safeFilename . '-' . $gaji->bulan . '-' . uniqid() . '.pdf';
            Storage::disk('public')->put($path, $pdf->output());
            Log::info("--> PDF disimpan di: {$path}");

            Log::info("[Checkpoint 8] Mengirim email.");
            Mail::to($karyawan->email)->send(new SalarySlipMail($gaji, $path));
            Log::info("--> Email berhasil dikirim ke Mailer.");

            Log::info("[Checkpoint 9] Mengirim notifikasi sukses.");
            $user->notify(new ReportGenerated(
                $path,
                'slip-gaji-' . $safeFilename . '.pdf',
                $gaji->bulan,
                'Slip gaji berhasil dikirim ke email ' . $karyawan->nama,
                false
            ));
            Log::info("JOB SELESAI DENGAN SUKSES.");
        } catch (Throwable $e) {
            // Ini akan menangkap APAPUN error yang terjadi di blok try
            Log::error("GAGAL PADA JOB SendSlipToEmail ID: {$this->gajiId}", [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'trace'         => $e->getTraceAsString() // Trace lengkap
            ]);

            if ($user) {
                // Kirim notifikasi kegagalan yang lebih detail
                $user->notify(new ReportGenerated(
                    '',
                    '',
                    'N/A',
                    'Error Gaji ID ' . $this->gajiId . ': ' . $e->getMessage(),
                    true
                ));
            }
        }
    }
}
