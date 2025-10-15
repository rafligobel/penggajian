<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\User;
use App\Models\Absensi;
use App\Models\TandaTangan;
use App\Notifications\ReportGenerated;
use App\Traits\ManagesImageEncoding; // DIbutuhkan untuk logo & ttd
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
    // [PERBAIKAN] Tambahkan trait untuk mengubah gambar menjadi base64
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

    public function handle(): void
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
                'total_tunjangan' => 0,
                'potongan' => 0,
                'gaji_bersih' => 0,
            ];

            foreach ($gajis as $gaji) {
                $jumlahKehadiran = Absensi::where('nip', $gaji->karyawan->nip)
                    ->whereYear('tanggal', $periode->year)
                    ->whereMonth('tanggal', $periode->month)
                    ->count();
                $kehadiranData[$gaji->id] = $jumlahKehadiran;

                $tunjanganJabatan = $gaji->karyawan->jabatan->tunj_jabatan ?? 0;
                $tunjanganKehadiran = $jumlahKehadiran * ($gaji->tunjanganKehadiran->jumlah_tunjangan ?? 0);
                $totalTunjangan = $tunjanganJabatan + $tunjanganKehadiran + $gaji->tunj_anak + $gaji->tunj_komunikasi + $gaji->tunj_pengabdian + $gaji->tunj_kinerja + $gaji->lembur;
                $gajiBersih = ($gaji->gaji_pokok + $totalTunjangan) - $gaji->potongan;

                $totals['gaji_pokok'] += $gaji->gaji_pokok;
                $totals['tunj_jabatan'] += $tunjanganJabatan;
                $totals['tunj_kehadiran'] += $tunjanganKehadiran;
                $totals['tunj_anak'] += $gaji->tunj_anak;
                $totals['tunj_komunikasi'] += $gaji->tunj_komunikasi;
                $totals['tunj_pengabdian'] += $gaji->tunj_pengabdian;
                $totals['tunj_kinerja'] += $gaji->tunj_kinerja;
                $totals['total_tunjangan'] += $totalTunjangan;
                $totals['potongan'] += $gaji->potongan;
                $totals['gaji_bersih'] += $gajiBersih;

                $gaji->gaji_bersih = $gajiBersih;
            }

            // [PERBAIKAN UTAMA DI SINI]
            // Menggunakan kolom 'key' bukan 'jabatan'
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
