<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\User;
use App\Models\TandaTangan;
use App\Notifications\ReportGenerated;
use App\Traits\ManagesImageEncoding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Carbon\Carbon;
use App\Services\SalaryService;

class GenerateMonthlySalaryReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected $selectedMonth;
    protected $user;
    protected $gajiIds;

    public function __construct(string $selectedMonth, User $user, ?array $gajiIds = null)
    {
        $this->selectedMonth = $selectedMonth;
        $this->user = $user;
        $this->gajiIds = $gajiIds;
    }

    public function handle(SalaryService $salaryService): void
    {
        try {
            $query = Gaji::with(['karyawan.jabatan', 'tunjanganKehadiran'])->where('bulan', $this->selectedMonth);

            if ($this->gajiIds) {
                $query->whereIn('id', $this->gajiIds);
            }

            $gajis = $query->get()->sortBy('karyawan.nama');

            $kehadiranData = [];
            foreach ($gajis as $gaji) {
                $detailGaji = $salaryService->calculateDetailsForForm($gaji->karyawan, $this->selectedMonth);
                $kehadiranData[$gaji->id] = $detailGaji['jumlah_kehadiran'];
            }

            $totals = [
                'gaji_pokok' => $gajis->sum('gaji_pokok'),
                'tunj_jabatan' => $gajis->sum(fn($g) => $g->karyawan->jabatan->tunj_jabatan ?? 0),
                'tunj_kehadiran' => $gajis->sum(fn($g) => ($kehadiranData[$g->id] ?? 0) * ($g->tunjanganKehadiran->jumlah_tunjangan ?? 0)),
                'tunj_anak' => $gajis->sum('tunj_anak'),
                'tunj_komunikasi' => $gajis->sum('tunj_komunikasi'),
                'tunj_pengabdian' => $gajis->sum('tunj_pengabdian'),
                'tunj_kinerja' => $gajis->sum('tunj_kinerja'),
                'lembur' => $gajis->sum('lembur'),
                'potongan' => $gajis->sum('potongan'),
            ];

            // Kalkulasi total tunjangan dan pendapatan
            $total_semua_tunjangan = $totals['tunj_jabatan'] + $totals['tunj_kehadiran'] + $totals['tunj_anak'] + $totals['tunj_komunikasi'] + $totals['tunj_pengabdian'] + $totals['tunj_kinerja'] + $totals['lembur'];
            $totals['total_tunjangan'] = $total_semua_tunjangan;
            $totals['gaji_bersih'] = ($totals['gaji_pokok'] + $total_semua_tunjangan) - $totals['potongan'];


            // [PERBAIKAN] Mengganti 'is_active' menjadi pencarian berdasarkan 'key'
            $tandaTanganBendahara = '';
            $pengaturanTtd = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            if ($pengaturanTtd && Storage::disk('public')->exists($pengaturanTtd->value)) {
                $tandaTanganBendahara = $this->getImageAsBase64DataUri(storage_path('app/public/' . $pengaturanTtd->value));
            }
            $bendahara = User::where('role', 'bendahara')->first();
            $bendaharaNama = $bendahara ? $bendahara->name : 'Bendahara Umum';

            $data = [
                'gajis' => $gajis,
                'periode' => $this->selectedMonth,
                'totals' => $totals,
                'bendaharaNama' => $bendaharaNama,
                'logoAlAzhar' => $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png')),
                'logoYayasan' => $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png')),
                'tandaTanganBendahara' => $tandaTanganBendahara,
                'kehadiranData' => $kehadiranData,
            ];

            $pdf = Pdf::loadView('gaji.cetak_semua', $data);
            $pdf->setPaper('A4', 'landscape');

            $safeMonth = str_replace('-', '', $this->selectedMonth);
            $filename = 'reports/laporan_gaji_' . $safeMonth . '_' . uniqid() . '.pdf';

            Storage::disk('public')->put($filename, $pdf->output());

            $this->user->notify(new ReportGenerated(
                $filename,
                'Laporan Gaji ' . $safeMonth . '.pdf',
                $this->selectedMonth,
                'Laporan Gaji Bulanan telah selesai dibuat.'
            ));
        } catch (Throwable $e) {
            Log::error('Gagal membuat Laporan Gaji Bulanan: ' . $e->getMessage(), ['exception' => $e]);
            $this->user->notify(new ReportGenerated(
                '',
                'Gagal Membuat Laporan Gaji',
                $this->selectedMonth,
                'Terjadi kesalahan teknis saat membuat Laporan Gaji Bulanan. Error: ' . $e->getMessage(),
                true
            ));
        }
    }
}
