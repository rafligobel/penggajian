<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Karyawan;
use App\Models\User;
use App\Models\TandaTangan; // Tambahkan ini
use App\Mail\AttendanceReportMail;
use App\Notifications\ReportGenerated;
use App\Services\AbsensiService; // Tambahkan ini
use App\Traits\ManagesImageEncoding;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage; // Tambahkan ini
use Illuminate\Support\Str;
use Throwable;

class SendAttendanceReportToEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected int $karyawanId;
    protected string $bulan;
    protected string $tahun;
    protected int $userId;

    public function __construct(int $karyawanId, string $bulan, string $tahun, int $userId)
    {
        $this->karyawanId = $karyawanId;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->userId = $userId;
    }

    public function handle(AbsensiService $absensiService): void
    {
        $user = User::find($this->userId);
        $karyawan = Karyawan::find($this->karyawanId);
        // [PERBAIKAN] Nama variabel disesuaikan menjadi $periode
        $periode = Carbon::create($this->tahun, $this->bulan, 1);

        if (!$karyawan || !$karyawan->email) {
            $errorMessage = "Karyawan ID {$this->karyawanId} tidak ditemukan atau tidak memiliki email.";
            Log::warning($errorMessage, ['job' => self::class]);
            if ($user) {
                $user->notify(new ReportGenerated('', '', $periode->format('Y-m'), "Gagal mengirim laporan ke " . ($karyawan->nama ?? 'N/A') . ": Karyawan tidak ditemukan atau tidak memiliki email.", true));
            }
            return;
        }

        try {
            // Ambil data rekap HANYA untuk satu karyawan ini
            $rekap = $absensiService->getAttendanceRecap($periode, [$this->karyawanId]);

            if (empty($rekap['rekapData'])) {
                throw new \Exception("Tidak ada data absensi untuk {$karyawan->nama} pada periode ini.");
            }

            // [PERBAIKAN UTAMA] Transformasi struktur data agar cocok dengan view
            $detailAbsensi = [];
            foreach ($rekap['rekapData'] as $dataKaryawan) {
                $item = new \stdClass();
                $item->nama = $dataKaryawan['nama'];
                $item->nip = $dataKaryawan['nip'];
                $item->total_hadir = $dataKaryawan['summary']['total_hadir'];
                $item->total_alpha = $dataKaryawan['summary']['total_alpha'];
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

            // [PERBAIKAN] Kumpulkan semua data yang dibutuhkan oleh view
            $data = [
                'periode' => $periode,
                'daysInMonth' => $rekap['daysInMonth'],
                'detailAbsensi' => $detailAbsensi,
                'logoAlAzhar' => $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png')),
                'logoYayasan' => $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png')),
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara,
            ];

            $pdf = Pdf::loadView('laporan.pdf.rekap_absensi', $data)->setPaper('a4', 'landscape');
            $pdfOutput = $pdf->output(); // Ambil konten PDF sekali saja

            $safeName = Str::slug($karyawan->nama, '_');
            $filename = "laporan_absensi_{$safeName}_{$periode->format('Y-m')}.pdf";

            // 1. Simpan salinan PDF ke disk agar bisa diunduh oleh admin
            $path = 'sent_attendance_reports/' . $periode->format('Y-m') . '/' . uniqid() . '_' . $filename;
            Storage::disk('local')->put($path, $pdfOutput);

            // 2. Kirim email ke karyawan dengan konten PDF dari memori
            Mail::to($karyawan->email)->send(new AttendanceReportMail($karyawan, $periode, $pdfOutput, $filename));

            Log::info("Email laporan absensi untuk {$karyawan->nama} berhasil dikirim.");

            // 3. Kirim notifikasi sukses ke admin dengan PATH file yang sudah disimpan
            $notifMessage = "Laporan absensi {$karyawan->nama} periode {$periode->translatedFormat('F Y')} berhasil dikirim.";
            $user->notify(new ReportGenerated($path, $filename, $periode->format('Y-m'), $notifMessage, false));
        } catch (Throwable $e) {
            Log::error("Gagal mengirim email laporan absensi ke {$karyawan->nama}: " . $e->getMessage(), ['exception' => $e]);
            if ($user) {
                $notifMessage = "Gagal mengirim laporan absensi ke email {$karyawan->nama}.";
                $user->notify(new ReportGenerated('', '', $periode->format('Y-m'), $notifMessage, true));
            }
        }
    }
}
