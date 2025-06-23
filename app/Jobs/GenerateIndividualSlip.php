<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\User;
use App\Notifications\ReportGenerated; // Pastikan ini ada
use App\Traits\ManagesImageEncoding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Throwable;

class GenerateIndividualSlip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected Gaji $gaji;
    protected User $user;

    public function __construct(Gaji $gaji, User $user)
    {
        $this->gaji = $gaji;
        $this->user = $user; // Tetap simpan user yang trigger, untuk log atau notifikasi
    }

    public function handle(): void
    {
        try {
            $gaji = $this->gaji;
            $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
            $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));

            // --- PERUBAHAN LOGIKA DI SINI ---
            // 1. Cari pengguna dengan peran 'bendahara' di database.
            $bendaharaUser = User::where('role', 'bendahara')->first();

            // 2. Ambil namanya. Jika tidak ada, gunakan nama default.
            $bendaharaNama = $bendaharaUser ? $bendaharaUser->name : 'Bendahara Umum';
            // --- AKHIR PERUBAHAN ---

            // Teruskan nama bendahara yang benar ke view
            $pdf = Pdf::loadView('gaji.slip_pdf', compact('gaji', 'logoAlAzhar', 'logoYayasan', 'bendaharaNama'));
            $pdf->setPaper('A4', 'portrait');

            $safeFilename = str_replace(' ', '_', strtolower($gaji->karyawan->nama));
            $filename = 'slip-gaji-' . $safeFilename . '-' . $gaji->bulan . '-' . uniqid() . '.pdf';
            $path = 'slips/' . $filename;
            Storage::disk('public')->put($path, $pdf->output());

            // Notifikasi tetap dikirim ke pengguna yang menekan tombol (misal: admin)
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
