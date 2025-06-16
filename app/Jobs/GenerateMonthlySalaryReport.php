<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\User;
use App\Traits\ManagesImageEncoding;
use App\Notifications\ReportGenerated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateMonthlySalaryReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected string $selectedMonth;
    protected User $user;
    protected ?array $gajiIds; // Bisa null

    /**
     * Create a new job instance.
     * @param string $selectedMonth
     * @param User $user
     * @param array|null $gajiIds ID Gaji yang akan diproses, null untuk semua
     */
    public function __construct(string $selectedMonth, User $user, ?array $gajiIds = null)
    {
        $this->selectedMonth = $selectedMonth;
        $this->user = $user;
        $this->gajiIds = $gajiIds;
    }

    public function handle(): void
    {
        $date = Carbon::createFromFormat('Y-m', $this->selectedMonth);

        $gajiQuery = Gaji::with('karyawan')->where('bulan', $this->selectedMonth);

        // Jika ada ID yang diberikan, filter berdasarkan ID tersebut
        if (!empty($this->gajiIds)) {
            $gajiQuery->whereIn('id', $this->gajiIds);
        }

        $gajis = $gajiQuery->get();

        // Jika tidak ada data yang ditemukan, hentikan proses
        if ($gajis->isEmpty()) {
            return;
        }

        $totals = (object) [
            'total_gaji_pokok' => $gajis->sum('gaji_pokok'),
            'total_gaji_bersih' => $gajis->sum('gaji_bersih'),
            'total_potongan' => $gajis->sum('potongan'),
            'total_semua_tunjangan' => $gajis->sum('total_tunjangan'),
            'total_pendapatan_lainnya' => $gajis->sum('pendapatan_lainnya'),
        ];

        $logoKiri = $this->encodeImageToBase64(public_path('logo/logoalazhar.png'));
        $logoKanan = $this->encodeImageToBase64(public_path('logo/logoyayasan.png'));

        $data = [
            'gajis' => $gajis,
            'totals' => $totals,
            'periode' => $date->translatedFormat('F Y'),
            'logoKiri' => $logoKiri,
            'logoKanan' => $logoKanan,
        ];

        $pdf = Pdf::loadView('gaji.cetak_semua', $data)->setPaper('a4', 'landscape');

        $filename = 'laporan-gaji-bulanan-' . $this->selectedMonth . '-' . uniqid() . '.pdf';
        $path = 'reports/' . $filename;
        Storage::disk('public')->put($path, $pdf->output());

        $this->user->notify(new ReportGenerated(
            $path,
            'laporan-gaji-' . $this->selectedMonth . '.pdf',
            $this->selectedMonth
        ));
    }
}
