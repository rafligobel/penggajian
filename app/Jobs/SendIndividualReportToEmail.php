<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\User;
use App\Models\TandaTangan;
// --- HAPUS 'USE' YANG TIDAK DIPAKAI ---
// use App\Mail\SalarySlipMail; 
// --- TAMBAHKAN 'USE' YANG BARU ---
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
        $user = null;
        try {
            $user = User::findOrFail($this->userId);
            $karyawan = Karyawan::findOrFail($this->karyawanId);

            if (empty($karyawan->email)) {
                Log::warning("--> Karyawan {$karyawan->nama} tidak punya email. Job dihentikan.");
                $user->notify(new ReportGenerated('', '', $this->tanggalMulai, 'Batal kirim: ' . $karyawan->nama . ' tidak memiliki email.', true));
                return;
            }

            // Generate the PDF content first
            $gajis = Gaji::where('karyawan_id', $this->karyawanId)
                ->whereBetween('bulan', [$this->tanggalMulai, $this->tanggalSelesai])
                ->orderBy('bulan', 'asc')->get();

            $startOfMonth = Carbon::createFromFormat('Y-m', $this->tanggalMulai)->startOfMonth();
            $endOfMonth = Carbon::createFromFormat('Y-m', $this->tanggalSelesai)->endOfMonth();

            $absensi = Absensi::where('nip', $karyawan->nip)
                ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                ->get();

            $totalHariKerja = $startOfMonth->diffInWeekdays($endOfMonth);
            $absensiSummary = [
                'hadir' => $absensi->count(),
                'alpha' => $totalHariKerja - $absensi->count(),
            ];

            $bendahara = User::where('role', 'bendahara')->first();
            $bendaharaNama = $bendahara ? $bendahara->name : 'Bendahara Umum';

            $tandaTanganBendahara = '';
            $pengaturanTtd = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            if ($pengaturanTtd && Storage::disk('public')->exists($pengaturanTtd->value)) {
                $tandaTanganBendahara = $this->getImageAsBase64DataUri(storage_path('app/public/' . $pengaturanTtd->value));
            }

            $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
            $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));

            $data = [
                'selectedKaryawan' => $karyawan,
                'tanggalMulai' => $this->tanggalMulai,
                'tanggalSelesai' => $this->tanggalSelesai,
                'absensiSummary' => $absensiSummary,
                'gajis' => $gajis,
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara,
                'logoAlAzhar' => $logoAlAzhar,
                'logoYayasan' => $logoYayasan,
            ];

            $pdf = Pdf::loadView('laporan.pdf.per_karyawan', $data);
            $pdf->setPaper('A4', 'portrait');

            $safeFilename = str_replace(' ', '_', strtolower($karyawan->nama));
            $filename = 'reports/' . 'laporan_' . $safeFilename . '_' . $this->tanggalMulai . '_sd_' . $this->tanggalSelesai . '_' . uniqid() . '.pdf';

            Storage::disk('public')->put($filename, $pdf->output());

            // --- GANTI CARA PENGIRIMAN EMAIL ---
            $mailable = new IndividualReportMail($karyawan, $filename, $this->tanggalMulai, $this->tanggalSelesai);
            Mail::to($karyawan->email)->send($mailable);
            // --- AKHIR PERUBAHAN ---

            Log::info("--> Email laporan rincian berhasil dikirim ke Mailer untuk {$karyawan->email}.");

            $user->notify(new ReportGenerated(
                $filename,
                'Laporan Rincian Karyawan ' . $karyawan->nama . '.pdf',
                $this->tanggalMulai,
                'Laporan Rincian Karyawan berhasil dikirim ke email ' . $karyawan->nama,
                false
            ));
        } catch (Throwable $e) {
            Log::error('Gagal mengirim Laporan Rincian Karyawan: ' . $e->getMessage(), ['exception' => $e]);
            if ($user) {
                $user->notify(new ReportGenerated('', '', $this->tanggalMulai, 'Gagal mengirim email Laporan Rincian Karyawan: ' . $e->getMessage(), true));
            }
        }
    }
}
