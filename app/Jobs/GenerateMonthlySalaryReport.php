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

            $total_semua_tunjangan = $totals['tunj_jabatan'] + $totals['tunj_kehadiran'] + $totals['tunj_anak'] + $totals['tunj_komunikasi'] + $totals['tunj_pengabdian'] + $totals['tunj_kinerja'] + $totals['lembur'];
            $totals['total_tunjangan'] = $total_semua_tunjangan;
            $totals['gaji_bersih'] = ($totals['gaji_pokok'] + $total_semua_tunjangan) - $totals['potongan'];

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

            $periode = Carbon::parse($this->selectedMonth);
            $filename = 'laporan-gaji-bulanan-' . $periode->format('Y-m') . '_' . uniqid() . '.pdf';
            // [PERBAIKAN] Path penyimpanan dibuat lebih terstruktur
            $path = 'laporan/gaji_bulanan/' . $filename;

            // [PERBAIKAN] Menyimpan ke disk 'local' untuk keamanan
            Storage::disk('local')->put($path, $pdf->output());

            $notifMessage = 'Laporan Gaji Bulanan periode ' . $periode->translatedFormat('F Y') . ' telah selesai dibuat.';
            $this->user->notify(new ReportGenerated(
                $path,
                'Laporan Gaji ' . $periode->translatedFormat('F Y') . '.pdf',
                $this->selectedMonth,
                $notifMessage
            ));
        } catch (Throwable $e) {
            Log::error('Gagal membuat Laporan Gaji Bulanan: ' . $e->getMessage(), ['exception' => $e]);
            $this->user->notify(new ReportGenerated(
                '',
                '',
                $this->selectedMonth,
                'Gagal membuat Laporan Gaji Bulanan. Error: ' . $e->getMessage(),
                true
            ));
        }
    }
}
