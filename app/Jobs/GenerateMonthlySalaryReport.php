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

    protected $selectedMonth;
    protected $user;

    public function __construct(string $selectedMonth, User $user)
    {
        $this->selectedMonth = $selectedMonth;
        $this->user = $user;
    }

    public function handle(): void
    {
        // Logika inti dipindahkan ke sini
        $date = Carbon::createFromFormat('Y-m', $this->selectedMonth);
        $gajis = Gaji::with('karyawan')->where('bulan', $this->selectedMonth)->get();

        // Kueri total yang efisien
        $totals = Gaji::where('bulan', $this->selectedMonth)->select(
            DB::raw('SUM(gaji_pokok) as total_gaji_pokok'),
            DB::raw('SUM(gaji_bersih) as total_gaji_bersih'),
            DB::raw('SUM(potongan) as total_potongan'),
            DB::raw('SUM(tunj_kehadiran + tunj_anak + tunj_komunikasi + tunj_pengabdian + tunj_jabatan + tunj_kinerja) as total_semua_tunjangan'),
            DB::raw('SUM(lembur + kelebihan_jam) as total_pendapatan_lainnya')
        )->first();

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

        $filename = 'laporan-gaji-' . $this->selectedMonth . '-' . uniqid() . '.pdf';
        $path = 'reports/' . $filename;
        Storage::disk('public')->put($path, $pdf->output());

        // Memberi tahu user via notifikasi database
        $this->user->notify(new ReportGenerated(
            $path,
            'laporan-gaji-' . $this->selectedMonth . '.pdf',
            $this->selectedMonth
        ));
    }
}
