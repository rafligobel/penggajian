<?php

namespace App\Jobs;

use App\Models\Karyawan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\TandaTangan; // Tambahkan ini
use App\Notifications\ReportGenerated;
use App\Services\AbsensiService;
use App\Traits\ManagesImageEncoding;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage; // Tambahkan ini
use Throwable;

class GenerateAttendanceReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected array $karyawanIds;
    protected string $bulan;
    protected string $tahun;
    protected int $userId;

    public function __construct(array $karyawanIds, string $bulan, string $tahun, int $userId)
    {
        $this->karyawanIds = $karyawanIds;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->userId = $userId;
    }

    public function handle(AbsensiService $absensiService): void
    {
        $user = User::find($this->userId);
        $karyawan = Karyawan::find($this->karyawanIds);
        $periode = Carbon::create($this->tahun, $this->bulan, 1);


        try {
            $rekap = $absensiService->getAttendanceRecap($periode, $this->karyawanIds);

            if (empty($rekap['rekapData'])) {
                throw new \Exception('Tidak ada data absensi untuk karyawan yang dipilih pada periode ini.');
            }

            // [PERBAIKAN UTAMA] Transformasi struktur data agar cocok dengan view
            $detailAbsensi = [];
            foreach ($rekap['rekapData'] as $dataKaryawan) {
                $item = new \stdClass();
                $item->nama = $dataKaryawan['nama'];
                $item->nip = $dataKaryawan['nip'];
                $item->total_hadir = $dataKaryawan['summary']['total_hadir'];
                $item->total_alpha = $dataKaryawan['summary']['total_alpha'];

                // Ubah struktur 'detail' menjadi 'daily_data'
                $dailyData = [];
                foreach ($dataKaryawan['detail'] as $day => $statusData) {
                    $dailyData[$day] = $statusData['status'];
                }
                $item->daily_data = $dailyData;
                $detailAbsensi[] = $item;
            }

            // [PERBAIKAN] Ambil data Tanda Tangan
            $bendahara = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            $bendaharaNama = $bendahara ? $bendahara->nama : 'Bendahara Belum Diset';
            $tandaTanganBendahara = $bendahara && Storage::disk('public')->exists($bendahara->path)
                ? $this->getImageAsBase64DataUri(storage_path('app/public/' . $bendahara->path))
                : null;

            // [PERBAIKAN] Kumpulkan semua data yang dibutuhkan oleh view baru
            $data = [
                'periode' => $periode,
                'daysInMonth' => $rekap['daysInMonth'],
                'detailAbsensi' => $detailAbsensi, // Menggunakan data yang sudah ditransformasi
                'logoAlAzhar' => $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png')),
                'logoYayasan' => $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png')),
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara,
            ];

            $pdf = Pdf::loadView('laporan.pdf.rekap_absensi', $data)->setPaper('a4', 'landscape');

            $periodeFormatted = $periode->format('Y-m');
            $filename = "laporan_absensi_{$periodeFormatted}_" . time() . ".pdf";
            $downloadFilename = "laporan_absensi_{$periodeFormatted}.pdf";
            $path = 'laporan/absensi/' . $filename;

            Storage::disk('local')->put($path, $pdf->output());

            $notifMessage = 'Laporan rekap absensi periode ' . $periode->translatedFormat('F Y') . ' telah selesai dibuat.';
            $user->notify(new ReportGenerated($path, $downloadFilename, $periodeFormatted, $notifMessage, false));
        } catch (Throwable $e) {
            Log::error("Gagal membuat PDF Laporan Absensi: " . $e->getMessage(), ['exception' => $e]);
            if ($user) {
                $notifMessage = 'Gagal memproses laporan rekap absensi untuk periode ' . $periode->translatedFormat('F Y') . '.';
                $user->notify(new ReportGenerated('', '', $periode->format('Y-m'), $notifMessage, true));
            }
        }
    }
}
