<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\User;
use App\Models\TandaTangan;
use App\Mail\SalarySlipMail;
use App\Notifications\ReportGenerated;
use App\Services\SalaryService;
use App\Traits\ManagesImageEncoding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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

    public function handle(SalaryService $salaryService): void
    {
        $user = User::find($this->userId);
        try {
            $gaji = Gaji::with('karyawan.jabatan')->findOrFail($this->gajiId);
            $karyawan = $gaji->karyawan;

            if (empty($karyawan->email)) {
                Log::warning("--> Karyawan {$karyawan->nama} tidak punya email. Job dihentikan.");
                $user->notify(new ReportGenerated('', '', $gaji->bulan, 'Batal kirim: ' . $karyawan->nama . ' tidak memiliki email.', true));
                return;
            }

            $data = $salaryService->calculateDetailsForForm($karyawan, $gaji->bulan);

            $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
            $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));
            $bendaharaUser = User::where('role', 'bendahara')->first();
            $bendaharaNama = $bendaharaUser ? $bendaharaUser->name : 'Bendahara Umum';
            $tandaTanganBendahara = '';
            $pengaturanTtd = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            if ($pengaturanTtd && Storage::disk('public')->exists($pengaturanTtd->value)) {
                $tandaTanganBendahara = $this->getImageAsBase64DataUri(storage_path('app/public/' . $pengaturanTtd->value));
            }

            $pdf = Pdf::loadView('gaji.slip_pdf', [
                'data' => $data,
                'gaji' => $gaji,
                'logoAlAzhar' => $logoAlAzhar,
                'logoYayasan' => $logoYayasan,
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara
            ]);
            $pdf->setPaper('A4', 'portrait');

            $pdfOutput = $pdf->output();
            $safeFilename = str_replace(' ', '_', strtolower($karyawan->nama));
            $filename = 'slip-gaji-' . $safeFilename . '-' . $gaji->bulan . '.pdf';

            Mail::to($karyawan->email)->send(new SalarySlipMail($gaji, $pdfOutput, $filename));

            $notifMessage = 'Slip gaji berhasil dikirim ke email ' . $karyawan->nama;
            $user->notify(new ReportGenerated('', $filename, $gaji->bulan, $notifMessage, false));
        } catch (Throwable $e) {
            Log::error('Gagal mengirim slip gaji ke email: ' . $e->getMessage(), ['exception' => $e]);
            if ($user) {
                $gaji = Gaji::find($this->gajiId);
                $notifMessage = 'Gagal mengirim slip ke email ' . (optional($gaji->karyawan)->nama ?? 'N/A') . '.';
                $user->notify(new ReportGenerated('', '', optional($gaji)->bulan ?? 'N/A', $notifMessage, true));
            }
        }
    }
}
