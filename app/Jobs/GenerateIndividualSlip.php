<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\User;
use App\Models\TandaTangan;
use App\Notifications\ReportGenerated;
use App\Services\SalaryService;
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
            $data = $salaryService->calculateDetailsForForm($gaji->karyawan, $gaji->bulan);

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
                'data' => $data, // Data hasil kalkulasi
                'gaji' => $gaji, // Data mentah dari Eloquent
                'logoAlAzhar' => $logoAlAzhar,
                'logoYayasan' => $logoYayasan,
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara
            ]);
            $pdf->setPaper('A4', 'portrait');

            $safeFilename = str_replace(' ', '_', strtolower($gaji->karyawan->nama));
            $filename = 'slip-gaji-' . $safeFilename . '-' . $gaji->bulan . '-' . uniqid() . '.pdf';
            $path = 'slips/' . $filename;
            Storage::disk('public')->put($path, $pdf->output());

            $notif = new ReportGenerated($path, 'slip-gaji-' . $safeFilename . '-' . $gaji->bulan . '.pdf', $gaji->bulan, 'Slip gaji untuk ' . $gaji->karyawan->nama . ' telah selesai dibuat.');
            $user->notify($notif);
        } catch (Throwable $e) {
            Log::error('Gagal membuat slip PDF individual: ' . $e->getMessage(), ['exception' => $e]);
            if ($user) {
                $gaji = Gaji::find($this->gajiId);
                $user->notify(new ReportGenerated('', '', optional($gaji)->bulan ?? 'N/A', 'Gagal membuat slip untuk ' . (optional($gaji->karyawan)->nama ?? 'N/A') . '.', true));
            }
        }
    }
}
