<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\User;
use App\Notifications\ReportGenerated;
use App\Traits\ManagesImageEncoding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Pengaturan;
use Throwable;

class GenerateIndividualSlip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected Gaji $gaji;
    protected User $user;

    public function __construct(Gaji $gaji, User $user)
    {
        $this->gaji = $gaji;
        $this->user = $user;
    }

    public function handle(): void
    {
        try {
            $gaji = $this->gaji;
            $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
            $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));

            $bendaharaUser = User::where('role', 'bendahara')->first();
            $bendaharaNama = $bendaharaUser ? $bendaharaUser->name : 'Bendahara Umum';

            $tandaTanganBendahara = '';
            $pengaturanTtd = Pengaturan::where('key', 'tanda_tangan_bendahara')->first();
            if ($pengaturanTtd && Storage::disk('public')->exists($pengaturanTtd->value)) {
                $tandaTanganBendahara = $this->getImageAsBase64DataUri(storage_path('app/public/' . $pengaturanTtd->value));
            }

            // ====================================================================
            // INI BAGIAN YANG DIPERBAIKI
            // ====================================================================
            $pdf = Pdf::loadView('gaji.slip_pdf', compact(
                'gaji',
                'logoAlAzhar',
                'logoYayasan',
                'bendaharaNama',
                'tandaTanganBendahara' // Variabel ini sekarang ditambahkan
            ));
            // ====================================================================

            $pdf->setPaper('A4', 'portrait');

            $safeFilename = str_replace(' ', '_', strtolower($gaji->karyawan->nama));
            $filename = 'slip-gaji-' . $safeFilename . '-' . $gaji->bulan . '-' . uniqid() . '.pdf';
            $path = 'slips/' . $filename;
            Storage::disk('public')->put($path, $pdf->output());

            $notif = new ReportGenerated(
                $path,
                'slip-gaji-' . $safeFilename . '-' . $gaji->bulan . '.pdf',
                $gaji->bulan,
                'Slip gaji untuk ' . $gaji->karyawan->nama . ' telah selesai dibuat.'
            );
            $this->user->notify($notif);
        } catch (Throwable $e) {
            Log::error('Gagal membuat slip PDF individual: ' . $e->getMessage(), ['exception' => $e]);
            $this->user->notify(new ReportGenerated(
                '',
                '',
                $this->gaji->bulan ?? 'N/A',
                'Gagal membuat slip untuk ' . ($this->gaji->karyawan->nama ?? 'N/A') . '.',
                true
            ));
        }
    }
}
