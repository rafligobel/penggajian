<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\User;
use App\Models\TandaTangan; // [PERBAIKAN] Tambahkan use statement
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
use Illuminate\Support\Facades\Storage;
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

        // [PERBAIKAN] Ambil Aset (TTD & Logo) di luar loop untuk efisiensi
        $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
        $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));

        // [PERBAIKAN WAJIB] Logika TTD yang sebelumnya hilang, ditambahkan
        $bendahara = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
        $bendaharaNama = $bendahara ? $bendahara->nama : 'Bendahara Belum Diset';
        $tandaTanganBendahara = $bendahara && Storage::disk('public')->exists($bendahara->path)
            ? $this->getImageAsBase64DataUri(storage_path('app/public/' . $bendahara->path))
            : null;

        foreach ($gajis as $gaji) {
            $karyawan = $gaji->karyawan;
            $periode = $gaji->bulan;

            try {
                if (!$karyawan || empty($karyawan->email)) {
                    throw new \Exception("Karyawan tidak ditemukan atau tidak memiliki email.");
                }

                // [FIXED] Panggilan ini sekarang mengembalikan key yang benar
                $dataSlip = $salaryService->calculateDetailsForForm($karyawan, $periode->format('Y-m'));

                $dataSlip['jumlah_kehadiran'] = $dataSlip['total_kehadiran'] ?? 0;
                $dataSlip['gaji_pokok'] = $dataSlip['gaji_pokok_numeric'] ?? 0;
                $dataSlip['tunj_kehadiran'] = $dataSlip['tunj_kehadiran'] ?? 0; // Tambahkan ini untuk mengatasi error terbaru
                $dataSlip['gaji_bersih'] = $dataSlip['gaji_bersih_numeric'] ?? 0; // <<< FIX TERBARU
                // [FIXED] View ini sekarang akan me-render tanpa error
                $pdf = Pdf::loadView('gaji.slip_pdf', [
                    'data' => $dataSlip,
                    'gaji' => $gaji,
                    'logoAlAzhar' => $logoAlAzhar,
                    'logoYayasan' => $logoYayasan,
                    'bendaharaNama' => $bendaharaNama,
                    'tandaTanganBendahara' => $tandaTanganBendahara
                ]);
                $pdf->setPaper('A4', 'portrait');

                $safeName = Str::slug($karyawan->nama, '_');
                $filename = "slip_gaji_{$safeName}_{$periode->format('Y-m')}.pdf";

                $pdfOutput = $pdf->output();

                $path = 'sent_slips/' . $periode->format('Y-m') . '/' . uniqid() . '_' . $filename;
                Storage::disk('local')->put($path, $pdfOutput);

                Mail::to($karyawan->email)->send(new SlipGajiMail($gaji, $pdfOutput, $filename));

                if ($user) {
                    $notifMessage = "Slip gaji {$karyawan->nama} ({$periode->translatedFormat('F Y')}) berhasil dikirim.";
                    $user->notify(new ReportGenerated($path, $filename, $periode->format('Y-m'), $notifMessage, false));
                }
            } catch (Throwable $e) {
                $errorMessage = optional($karyawan)->nama ?? "Karyawan (Gaji ID: {$gaji->id})";
                Log::error("Gagal mengirim slip gaji ke {$errorMessage}: " . $e->getMessage());

                if ($user) {
                    $notifMessage = "Gagal mengirim slip gaji ke {$errorMessage} ({$periode->translatedFormat('F Y')}).";
                    $user->notify(new ReportGenerated('', '', $periode->format('Y-m'), $notifMessage, true));
                }
            }
        }
    }
}
