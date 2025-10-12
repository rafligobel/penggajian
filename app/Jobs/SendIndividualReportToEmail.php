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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Throwable;

class SendIndividualReportToEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected $karyawanId;
    protected $tanggalMulai;
    protected $tanggalSelesai;
    protected $userId;

    public function __construct(int $karyawanId, string $tanggalMulai, string $tanggalSelesai, int $userId)
    {
        $this->karyawanId = $karyawanId;
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        try {
            $karyawan = Karyawan::findOrFail($this->karyawanId);

            if (empty($karyawan->email)) {
                Log::warning("--> Karyawan {$karyawan->nama} tidak punya email. Job dihentikan.");
                $user->notify(new ReportGenerated('', '', $this->tanggalMulai, 'Batal kirim: ' . $karyawan->nama . ' tidak memiliki email.', true));
                return;
            }

            // [PERBAIKAN] Logika pembuatan PDF tetap sama, namun tidak akan disimpan ke disk
            $gajis = Gaji::where('karyawan_id', $this->karyawanId)
                ->whereBetween('bulan', [$this->tanggalMulai, $this->tanggalSelesai])
                ->orderBy('bulan', 'asc')->get();

            // ... (logika lainnya untuk mengambil data PDF)
            $bendahara = User::where('role', 'bendahara')->first();
            $bendaharaNama = $bendahara ? $bendahara->name : 'Bendahara Umum';

            $tandaTanganBendahara = '';
            $pengaturanTtd = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            if ($pengaturanTtd && Storage::disk('public')->exists($pengaturanTtd->value)) {
                $tandaTanganBendahara = $this->getImageAsBase64DataUri(storage_path('app/public/' . $pengaturanTtd->value));
            }

            $data = [
                'selectedKaryawan' => $karyawan,
                // ... isi data lainnya untuk PDF ...
                'gajis' => $gajis,
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara,
                // ...
            ];

            $pdf = Pdf::loadView('laporan.pdf.per_karyawan', $data)->setPaper('A4', 'portrait');

            // [PERBAIKAN] Hapus penyimpanan ke disk, langsung gunakan outputnya
            $pdfOutput = $pdf->output();
            $safeFilename = str_replace(' ', '_', strtolower($karyawan->nama));
            $filename = 'laporan-individual-' . $safeFilename . '-' . $this->tanggalMulai . '-sd-' . $this->tanggalSelesai . '.pdf';

            // [PERBAIKAN] Kirim email dengan data PDF dari memori
            Mail::to($karyawan->email)->send(new IndividualReportMail($karyawan, $pdfOutput, $filename, $this->tanggalMulai, $this->tanggalSelesai));

            Log::info("--> Email laporan rincian berhasil dikirim untuk {$karyawan->email}.");

            // [PERBAIKAN] Kirim notifikasi konfirmasi tanpa path file
            $notifMessage = 'Laporan Rincian Karyawan berhasil dikirim ke email ' . $karyawan->nama;
            $user->notify(new ReportGenerated(
                '', // Path dikosongkan
                $filename,
                $this->tanggalMulai,
                $notifMessage,
                false
            ));
        } catch (Throwable $e) {
            Log::error('Gagal mengirim Laporan Rincian Karyawan: ' . $e->getMessage(), ['exception' => $e]);
            if ($user) {
                $notifMessage = 'Gagal mengirim email Laporan Rincian Karyawan: ' . $e->getMessage();
                $user->notify(new ReportGenerated('', '', $this->tanggalMulai, $notifMessage, true));
            }
        }
    }
}
