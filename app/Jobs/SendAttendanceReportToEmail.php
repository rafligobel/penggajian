<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Karyawan;
use App\Models\User;
use App\Models\TandaTangan;
use App\Mail\AttendanceReportMail;
use App\Notifications\ReportGenerated; // [TAMBAHKAN]
use App\Traits\ManagesImageEncoding;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAttendanceReportToEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected $karyawanId;
    protected $bulan;
    protected $tahun;
    protected $userId;

    public function __construct(int $karyawanId, string $bulan, string $tahun, int $userId)
    {
        $this->karyawanId = $karyawanId;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $karyawan = Karyawan::find($this->karyawanId);
        $periode = Carbon::create($this->tahun, $this->bulan);

        try {
            if (empty($karyawan->email)) {
                Log::warning("Batal kirim: Karyawan {$karyawan->nama} (ID: {$karyawan->id}) tidak memiliki alamat email.");
                // [TAMBAHKAN] Notifikasi jika email tidak ada
                $notifMessage = "Batal kirim laporan absensi: {$karyawan->nama} tidak memiliki email.";
                $user->notify(new ReportGenerated('', '', $periode->format('Y-m'), $notifMessage, true));
                return;
            }

            // ... (logika pembuatan PDF tetap sama)
            $daysInMonth = $periode->daysInMonth;
            // ... (sisa logika)
            $data = [ /* ... */];

            $pdf = Pdf::loadView('laporan.pdf.rekap_absensi', $data)->setPaper('a4', 'landscape');
            $filename = 'laporan_absensi_' . str_replace(' ', '_', $karyawan->nama) . '_' . $periode->format('F_Y') . '.pdf';

            Mail::to($karyawan->email)->send(new AttendanceReportMail($karyawan, $periode, $pdf->output(), $filename));

            Log::info("Email laporan absensi untuk {$karyawan->nama} berhasil dikirim.");

            // [TAMBAHKAN] Notifikasi konfirmasi keberhasilan
            $notifMessage = "Laporan absensi {$karyawan->nama} periode {$periode->translatedFormat('F Y')} berhasil dikirim.";
            $user->notify(new ReportGenerated('', $filename, $periode->format('Y-m'), $notifMessage, false));
        } catch (Throwable $e) {
            Log::error("Gagal mengirim email laporan absensi ke {$karyawan->nama}: " . $e->getMessage(), ['exception' => $e]);
            // [TAMBAHKAN] Notifikasi jika terjadi error
            if ($user) {
                $notifMessage = "Gagal mengirim email laporan absensi ke {$karyawan->nama}.";
                $user->notify(new ReportGenerated('', '', $periode->format('Y-m'), $notifMessage, true));
            }
        }
    }
}
