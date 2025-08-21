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
use App\Notifications\ReportGenerated;
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

        try {
            if (empty($karyawan->email)) {
                Log::warning("Batal kirim: Karyawan {$karyawan->nama} (ID: {$karyawan->id}) tidak memiliki alamat email.");
                return;
            }

            $periode = Carbon::create($this->tahun, $this->bulan);

            $daysInMonth = $periode->daysInMonth;
            $absensiHarian = $karyawan->absensi()
                ->whereMonth('tanggal', $this->bulan)
                ->whereYear('tanggal', $this->tahun)
                ->get()
                ->keyBy(function ($item) {
                    return Carbon::parse($item->tanggal)->format('j');
                });

            $dailyData = [];
            $totalHadir = 0;
            for ($day = 1; $day <= $daysInMonth; $day++) {
                if (isset($absensiHarian[$day])) {
                    $dailyData[$day] = 'H';
                    $totalHadir++;
                } else {
                    $dailyData[$day] = 'A';
                }
            }

            $detailAbsensi = [(object)[
                'nama' => $karyawan->nama,
                'daily_data' => $dailyData,
                'total_hadir' => $totalHadir,
                'total_alpha' => $daysInMonth - $totalHadir,
            ]];

            $bendahara = User::where('role', 'bendahara')->first();
            $bendaharaNama = $bendahara ? $bendahara->name : 'Bendahara Umum';

            // ========================================================================
            // PERBAIKAN LOGIKA PENGAMBILAN TANDA TANGAN
            // ========================================================================
            $tandaTanganBendahara = '';
            $pengaturanTtd = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            if ($pengaturanTtd && Storage::disk('public')->exists($pengaturanTtd->value)) {
                $tandaTanganBendahara = $this->getImageAsBase64DataUri(storage_path('app/public/' . $pengaturanTtd->value));
            }

            $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
            $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));

            $data = [
                'detailAbsensi' => $detailAbsensi,
                'daysInMonth' => $daysInMonth,
                'periode' => $periode,
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara,
                'logoAlAzhar' => $logoAlAzhar,
                'logoYayasan' => $logoYayasan,
            ];

            $pdf = Pdf::loadView('laporan.pdf.rekap_absensi', $data)->setPaper('a4', 'landscape');
            $filename = 'laporan_absensi_' . str_replace(' ', '_', $karyawan->nama) . '_' . $periode->format('F_Y') . '.pdf';

            Mail::to($karyawan->email)->send(new AttendanceReportMail($karyawan, $periode, $pdf->output(), $filename));

            Log::info("Email laporan absensi untuk {$karyawan->nama} berhasil dikirim.");
        } catch (Throwable $e) {
            Log::error("Gagal mengirim email laporan absensi ke {$karyawan->nama}: " . $e->getMessage(), ['exception' => $e]);
        }
    }
}
