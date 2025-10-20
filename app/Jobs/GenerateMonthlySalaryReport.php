<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\User;
use App\Models\Absensi;
use App\Models\TandaTangan;
use App\Notifications\ReportGenerated;
use App\Traits\ManagesImageEncoding;
use App\Services\SalaryService; // [PERBAIKAN] Tambahkan use statement
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

class GenerateMonthlySalaryReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected string $bulan;
    protected User $user;
    protected array $gajiIds;

    public function __construct(string $bulan, User $user, array $gajiIds)
    {
        $this->bulan = $bulan;
        $this->user = $user;
        $this->gajiIds = $gajiIds;
    }

    // [PERBAIKAN] Inject SalaryService untuk konsistensi data
    public function handle(SalaryService $salaryService): void
    {
        $user = $this->user;
        $periode = null;

        try {
            $periode = Carbon::createFromFormat('Y-m', $this->bulan);

            $gajis = Gaji::with(['karyawan.jabatan', 'tunjanganKehadiran'])
                ->whereIn('id', $this->gajiIds)
                ->get();

            if ($gajis->isEmpty()) {
                throw new \Exception('Tidak ada data gaji yang valid untuk diproses.');
            }

            $kehadiranData = [];
            $totals = [
                'gaji_pokok' => 0,
                'tunj_jabatan' => 0,
                'tunj_kehadiran' => 0,
                'tunj_anak' => 0,
                'tunj_komunikasi' => 0,
                'tunj_pengabdian' => 0,
                'tunj_kinerja' => 0,
                'lembur' => 0, // [PERBAIKAN] Tambahkan lembur
                'total_tunjangan' => 0,
                'potongan' => 0,
                'gaji_bersih' => 0,
            ];

            // [PERBAIKAN UTAMA] Gunakan SalaryService, hapus kalkulasi manual
            foreach ($gajis as $gaji) {
                // Panggil service untuk menghitung detail
                $detailGaji = $salaryService->calculateDetailsForForm($gaji->karyawan, $gaji->bulan->format('Y-m'));

                // Ambil data yang sudah dihitung oleh service
                $kehadiranData[$gaji->id] = $detailGaji['jumlah_kehadiran'];

                $totalTunjangan = $detailGaji['tunj_jabatan'] +
                    $detailGaji['tunj_kehadiran'] +
                    $detailGaji['tunj_anak'] +
                    $detailGaji['tunj_komunikasi'] +
                    $detailGaji['tunj_pengabdian'] +
                    $detailGaji['tunj_kinerja'] +
                    $detailGaji['lembur'];

                // Akumulasi total dari data service
                $totals['gaji_pokok'] += $detailGaji['gaji_pokok_numeric'];
                $totals['tunj_jabatan'] += $detailGaji['tunj_jabatan'];
                $totals['tunj_kehadiran'] += $detailGaji['tunj_kehadiran'];
                $totals['tunj_anak'] += $detailGaji['tunj_anak'];
                $totals['tunj_komunikasi'] += $detailGaji['tunj_komunikasi'];
                $totals['tunj_pengabdian'] += $detailGaji['tunj_pengabdian'];
                $totals['tunj_kinerja'] += $detailGaji['tunj_kinerja'];
                $totals['lembur'] += $detailGaji['lembur'];
                $totals['total_tunjangan'] += $totalTunjangan;
                $totals['potongan'] += $detailGaji['potongan'];
                $totals['gaji_bersih'] += $detailGaji['gaji_bersih_numeric'];

                $gaji->gaji_bersih = $detailGaji['gaji_bersih_numeric'];
            }

            // Logika aset (logo & ttd) sudah benar
            $bendahara = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            $bendaharaNama = $bendahara ? $bendahara->nama : 'Bendahara Belum Diset';
            $tandaTanganBendahara = $bendahara && Storage::disk('public')->exists($bendahara->path)
                ? $this->getImageAsBase64DataUri(storage_path('app/public/' . $bendahara->path))
                : null;
            $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
            $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));

            $data = [
                'gajis' => $gajis,
                'periode' => $this->bulan,
                'kehadiranData' => $kehadiranData,
                'totals' => $totals,
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara,
                'logoAlAzhar' => $logoAlAzhar,
                'logoYayasan' => $logoYayasan,
            ];

            $pdf = Pdf::loadView('gaji.cetak_semua', $data)->setPaper('A4', 'landscape');

            $periodeFormatted = $periode->format('Y-m');
            $downloadFilename = "laporan_gaji_bulanan_{$periodeFormatted}.pdf";
            $storageFilename = "laporan_gaji_bulanan_{$periodeFormatted}_" . time() . ".pdf";
            $path = 'reports/' . $storageFilename;

            Storage::disk('local')->put($path, $pdf->output());

            $notifMessage = 'Laporan Gaji Bulanan periode ' . $periode->translatedFormat('F Y') . ' telah selesai dibuat.';
            $user->notify(new ReportGenerated($path, $downloadFilename, $this->bulan, $notifMessage));
        } catch (Throwable $e) {
            Log::error('Gagal membuat Laporan Gaji Bulanan: ' . $e->getMessage(), ['exception' => $e]);
            $notifMessage = 'Gagal memproses laporan gaji bulanan.';
            $user->notify(new ReportGenerated('', '', $this->bulan, $notifMessage, true));
        }
    }
}
