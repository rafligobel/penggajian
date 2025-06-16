<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\User;
use App\Mail\SalarySlipMail;
use App\Notifications\SlipGenerated;
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
use Illuminate\Support\Facades\Log; // <-- TAMBAHKAN BARIS INI

class SendSlipToEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected Gaji $gaji;
    protected User $user; // User yang melakukan aksi (bendahara)

    /**
     * Create a new job instance.
     */
    public function __construct(Gaji $gaji, User $user)
    {
        $this->gaji = $gaji;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $gaji = $this->gaji;
        $karyawan = $gaji->karyawan;

        if (empty($karyawan->email)) {
            Log::warning("Attempted to send salary slip to employee without an email.", ['karyawan_id' => $karyawan->id]);
            return;
        }

        try {
            // 1. Generate PDF
            $logoAlAzhar = $this->encodeImageToBase64(public_path('logo/logoalazhar.png'));
            $logoYayasan = $this->encodeImageToBase64(public_path('logo/logoyayasan.png'));
            $pdf = Pdf::loadView('gaji.slip_pdf', compact('gaji', 'logoAlAzhar', 'logoYayasan'));
            $pdf->setPaper('A4', 'portrait');

            $safeFilename = str_replace(' ', '_', strtolower($karyawan->nama));
            $filename = 'slips/slip-gaji-' . $safeFilename . '-' . $gaji->bulan . '-' . uniqid() . '.pdf';
            Storage::disk('public')->put($filename, $pdf->output());

            // 2. Kirim Email
            Mail::to($karyawan->email)->send(new SalarySlipMail($gaji, $filename));

            // 3. Notifikasi ke bendahara (opsional, bisa diganti notif sukses)
            $this->user->notify(new SlipGenerated($filename, basename($filename), $gaji));
        } catch (Throwable $e) {
            // Jika gagal, catat error ke log
            Log::error("Gagal mengirim email slip gaji untuk karyawan ID: " . $karyawan->id, [
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Opsional: kirim notifikasi kegagalan ke bendahara
        }
    }
}
