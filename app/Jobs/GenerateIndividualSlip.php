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
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $gaji = $this->gaji;
        $logoAlAzhar = $this->encodeImageToBase64(public_path('logo/logoalazhar.png'));
        $logoYayasan = $this->encodeImageToBase64(public_path('logo/logoyayasan.png'));

        $pdf = Pdf::loadView('gaji.slip_pdf', compact('gaji', 'logoAlAzhar', 'logoYayasan'));
        $pdf->setPaper('A4', 'portrait');

        $safeFilename = str_replace(' ', '_', strtolower($gaji->karyawan->nama));
        $filename = 'slip-gaji-' . $safeFilename . '-' . $gaji->bulan . '-' . uniqid() . '.pdf';
        $path = 'slips/' . $filename;
        Storage::disk('public')->put($path, $pdf->output());

        $this->user->notify(new SlipGenerated($path, $filename, $gaji));
    }
}
