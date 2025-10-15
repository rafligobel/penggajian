<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\User;
use App\Mail\SlipGajiMail;
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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage; // Pastikan facade Storage di-import
use Illuminate\Support\Str;
use Throwable;

class SendSlipToEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected array $gajiIds;
    protected int $userId;

    public function __construct(array $gajiIds, int $userId)
    {
        $this->gajiIds = $gajiIds;
        $this->userId = $userId;
    }

    public function handle(SalaryService $salaryService): void
    {
        $user = User::find($this->userId);
        $gajis = Gaji::with('karyawan.jabatan')->find($this->gajiIds);

        foreach ($gajis as $gaji) {
            $karyawan = $gaji->karyawan;
            $periode = $gaji->bulan; // Ini adalah objek Carbon

            try {
                if (!$karyawan || empty($karyawan->email)) {
                    throw new \Exception("Karyawan tidak ditemukan atau tidak memiliki email.");
                }

                $dataSlip = $salaryService->calculateDetailsForForm($karyawan, $periode->format('Y-m'));

                $pdf = Pdf::loadView('gaji.slip_pdf', [
                    'data' => $dataSlip,
                    'gaji' => $gaji,
                    'logoAlAzhar' => $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png')),
                    'logoYayasan' => $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png')),
                ]);
                $pdf->setPaper('A4', 'portrait');

                $safeName = Str::slug($karyawan->nama, '_');
                $filename = "slip_gaji_{$safeName}_{$periode->format('Y-m')}.pdf";

                // Ambil konten PDF sekali saja untuk efisiensi
                $pdfOutput = $pdf->output();

                // [PERBAIKAN UTAMA] Simpan salinan PDF ke disk agar bisa diunduh oleh admin
                $path = 'sent_slips/' . $periode->format('Y-m') . '/' . uniqid() . '_' . $filename;
                Storage::disk('local')->put($path, $pdfOutput);

                // Kirim email ke karyawan dengan konten PDF dari memori
                Mail::to($karyawan->email)->send(new SlipGajiMail($gaji, $pdfOutput, $filename));

                // Kirim notifikasi sukses ke admin dengan PATH file yang sudah disimpan
                if ($user) {
                    $notifMessage = "Slip gaji {$karyawan->nama} ({$periode->translatedFormat('F Y')}) berhasil dikirim.";

                    // [PERBAIKAN UTAMA] Kirim path dan filename yang benar ke notifikasi
                    $user->notify(new ReportGenerated($path, $filename, $periode->format('Y-m'), $notifMessage, false));
                }
            } catch (Throwable $e) {
                $errorMessage = optional($karyawan)->nama ?? "Karyawan (Gaji ID: {$gaji->id})";
                Log::error("Gagal mengirim slip gaji ke {$errorMessage}: " . $e->getMessage());

                // Kirim notifikasi gagal ke admin (tanpa path file)
                if ($user) {
                    $notifMessage = "Gagal mengirim slip gaji ke {$errorMessage} ({$periode->translatedFormat('F Y')}).";
                    $user->notify(new ReportGenerated('', '', $periode->format('Y-m'), $notifMessage, true));
                }
            }
        }
    }
}
