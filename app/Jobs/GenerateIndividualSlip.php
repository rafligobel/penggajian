<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\User;
use App\Notifications\SlipGenerated;
use App\Traits\ManagesImageEncoding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Notifications\ReportGenerated;

class GenerateIndividualSlip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected Gaji $gaji;
    protected User $user;

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
        try {
            $gaji = $this->gaji;
            $logoAlAzhar = $this->encodeImageToBase64(public_path('logo/logoalazhar.png'));
            $logoYayasan = $this->encodeImageToBase64(public_path('logo/logoyayasan.png'));

            $pdf = Pdf::loadView('gaji.slip_pdf', compact('gaji', 'logoAlAzhar', 'logoYayasan'));
            $pdf->setPaper('A4', 'portrait');

            $safeFilename = str_replace(' ', '_', strtolower($gaji->karyawan->nama));
            $filename = 'slip-gaji-' . $safeFilename . '-' . $gaji->bulan . '-' . uniqid() . '.pdf';
            $path = 'slips/' . $filename;
            Storage::disk('public')->put($path, $pdf->output());

            // Ganti notifikasi SlipGenerated ke ReportGenerated agar seragam
            $notif = new ReportGenerated(
                $path,
                'slip-gaji-' . $safeFilename . '-' . $gaji->bulan . '.pdf', // Nama file yang lebih ramah
                $gaji->bulan,
                'Slip gaji untuk ' . $gaji->karyawan->nama . ' telah selesai dibuat.' // Pesan kustom
            );
            $this->user->notify($notif);
        } catch (Throwable $e) {
            Log::error('Gagal membuat slip PDF individual: ' . $e->getMessage());
            // Kirim notifikasi kegagalan kepada user yang meminta
            $this->user->notify(new ReportGenerated(
                '',
                '',
                $this->gaji->bulan,
                'Gagal membuat slip untuk ' . $this->gaji->karyawan->nama . '.',
                true // Tandai sebagai error
            ));
        }
    }
}
