<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\User;
use App\Models\TandaTangan;
use App\Mail\IndividualReportMail;
use App\Notifications\ReportGenerated;
use App\Traits\ManagesImageEncoding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Throwable;
use App\Services\AbsensiService;


class SendIndividualReportToEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected int $karyawanId;
    protected string $tanggalMulai;
    protected string $tanggalSelesai;
    protected int $userId;

    public function __construct(int $karyawanId, string $tanggalMulai, string $tanggalSelesai, int $userId)
    {
        $this->karyawanId = $karyawanId;
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->userId = $userId;
    }


    public function handle(AbsensiService $absensiService): void
    {
        $user = User::find($this->userId);
        $karyawan = Karyawan::findOrFail($this->karyawanId);

        try {
            $startDate = Carbon::createFromFormat('Y-m', $this->tanggalMulai)->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $this->tanggalSelesai)->endOfMonth();

            $gajis = Gaji::with('karyawan.jabatan', 'tunjanganKehadiran')
                ->where('karyawan_id', $karyawan->id)
                ->whereBetween('bulan', [$startDate, $endDate])
                ->orderBy('bulan', 'asc')->get();

            // [PERBAIKAN UTAMA] Hitung absensi & gaji per bulan
            $gajis->each(function ($gaji) use ($absensiService) {
                $bulanGaji = $gaji->bulan;

                // Hitung kehadiran per bulan
                $kehadiranBulanIni = Absensi::where('nip', $gaji->karyawan->nip)
                    ->whereYear('tanggal', $bulanGaji->year)
                    ->whereMonth('tanggal', $bulanGaji->month)
                    ->count();

                // Hitung hari kerja per bulan
                $workingDaysCount = 0;
                $period = \Carbon\CarbonPeriod::create($bulanGaji->copy()->startOfMonth(), $bulanGaji->copy()->endOfMonth());
                foreach ($period as $date) {
                    if ($absensiService->getSessionStatus($date)['is_active']) {
                        $workingDaysCount++;
                    }
                }
                $alphaBulanIni = $workingDaysCount - $kehadiranBulanIni;

                // Lampirkan data absensi ke objek gaji
                $gaji->hadir = $kehadiranBulanIni;
                $gaji->alpha = $alphaBulanIni > 0 ? $alphaBulanIni : 0;

                // Hitung rincian gaji
                $tunjanganJabatan = $gaji->karyawan->jabatan->tunj_jabatan ?? 0;
                $tunjanganKehadiran = $kehadiranBulanIni * ($gaji->tunjanganKehadiran->jumlah_tunjangan ?? 0);
                $totalTunjangan = $tunjanganJabatan + $tunjanganKehadiran + $gaji->tunj_anak + $gaji->tunj_komunikasi + $gaji->tunj_pengabdian + $gaji->tunj_kinerja + $gaji->lembur;
                $gaji->total_tunjangan = $totalTunjangan;
                $gaji->gaji_bersih = ($gaji->gaji_pokok + $totalTunjangan) - $gaji->potongan;
            });

            // Ambil data tanda tangan & logo
            $bendahara = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            $bendaharaNama = $bendahara ? $bendahara->nama : 'Bendahara Belum Diset';
            $tandaTanganBendahara = $bendahara && Storage::disk('public')->exists($bendahara->path)
                ? $this->getImageAsBase64DataUri(storage_path('app/public/' . $bendahara->path))
                : null;

            // Kumpulkan semua data untuk PDF
            $data = [
                'karyawan' => $karyawan,
                'gajis' => $gajis, // Data gaji kini sudah lengkap dengan absensi bulanan
                'tanggalMulai' => $startDate,
                'tanggalSelesai' => $endDate,
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara,
                'logoAlAzhar' => $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png')),
                'logoYayasan' => $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png')),
            ];

            $pdf = Pdf::loadView('laporan.pdf.per_karyawan', $data)->setPaper('A4', 'portrait');

            $safeName = Str::slug($karyawan->nama, '_');
            $downloadFilename = "laporan_rinci_{$safeName}_{$this->tanggalMulai}_sd_{$this->tanggalSelesai}.pdf";
            $storageFilename = uniqid() . "_{$downloadFilename}";
            $path = 'laporan/individual/' . $storageFilename;

            Storage::disk('local')->put($path, $pdf->output());

            $notifMessage = 'Laporan rincian untuk ' . $karyawan->nama . ' telah selesai dibuat.';
            $user->notify(new ReportGenerated($path, $downloadFilename, $this->tanggalMulai, $notifMessage));
        } catch (Throwable $e) {
            Log::error('Gagal membuat Laporan Rincian Karyawan: ' . $e->getMessage(), ['exception' => $e]);
            $user->notify(new ReportGenerated('', '', $this->tanggalMulai, 'Gagal membuat laporan rincian untuk ' . $karyawan->nama . '.', true));
        }
    }
}
