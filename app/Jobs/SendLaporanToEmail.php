<?php

namespace App\Jobs;

use App\Mail\LaporanMail;
use App\Models\Absensi;
use App\Models\Gaji;
use App\Models\Karyawan;
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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Throwable;

class SendLaporanToEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected string $tipeLaporan;
    protected array $payload;
    protected User $user;

    public function __construct(string $tipeLaporan, array $payload, User $user)
    {
        $this->tipeLaporan = $tipeLaporan;
        $this->payload = $payload;
        $this->user = $user;
    }

    public function handle(): void
    {
        try {
            $viewPath = '';
            $data = [];
            $filename = 'laporan.pdf';
            $subject = 'Laporan Anda Telah Siap';
            $paperOrientation = 'portrait';

            $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
            $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));
            $bendaharaNama = User::where('role', 'bendahara')->first()->name ?? 'Bendahara';
            $tandaTanganBendahara = '';
            $pengaturanTtd = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            if ($pengaturanTtd && Storage::disk('public')->exists($pengaturanTtd->value)) {
                $tandaTanganBendahara = $this->getImageAsBase64DataUri(storage_path('app/public/' . $pengaturanTtd->value));
            }
            $commonData = compact('logoAlAzhar', 'logoYayasan', 'bendaharaNama', 'tandaTanganBendahara');

            switch ($this->tipeLaporan) {
                case 'gaji_bulanan':
                    $bulan = $this->payload['bulan'] ?? now()->format('Y-m');
                    $gajis = Gaji::with('karyawan')->where('bulan', $bulan)->get();
                    $viewPath = 'laporan.pdf.gaji_bulanan'; // Pastikan view ini ada
                    $data = array_merge($commonData, compact('gajis', 'bulan'));
                    $filename = 'laporan-gaji-bulanan-' . $bulan . '.pdf';
                    $subject = 'Laporan Gaji Bulanan - ' . Carbon::parse($bulan)->translatedFormat('F Y');
                    $paperOrientation = 'landscape';
                    break;

                case 'per_karyawan':
                    $karyawanId = $this->payload['karyawan_id'];
                    $selectedKaryawan = Karyawan::findOrFail($karyawanId);
                    $tanggalMulai = $this->payload['tanggal_mulai'];
                    $tanggalSelesai = $this->payload['tanggal_selesai'];
                    $gajis = Gaji::where('karyawan_id', $karyawanId)->whereBetween('bulan', [$tanggalMulai, $tanggalSelesai])->orderBy('bulan', 'asc')->get();
                    $startOfMonth = Carbon::createFromFormat('Y-m', $tanggalMulai)->startOfMonth();
                    $endOfMonth = Carbon::createFromFormat('Y-m', $tanggalSelesai)->endOfMonth();
                    $absensi = Absensi::where('nip', $selectedKaryawan->nip)->whereBetween('tanggal', [$startOfMonth, $endOfMonth])->get();
                    $totalHariKerja = $startOfMonth->diffInWeekdays($endOfMonth);
                    $absensiSummary = ['hadir' => $absensi->count(), 'alpha' => $totalHariKerja - $absensi->count()];
                    $viewPath = 'laporan.pdf.per_karyawan';
                    $data = array_merge($commonData, compact('gajis', 'selectedKaryawan', 'tanggalMulai', 'tanggalSelesai', 'absensiSummary'));
                    $filename = 'laporan-gaji-' . \Illuminate\Support\Str::slug($selectedKaryawan->nama) . '.pdf';
                    $subject = 'Laporan Gaji Karyawan - ' . $selectedKaryawan->nama;
                    break;
            }

            if (empty($viewPath)) throw new \Exception('Tipe laporan tidak valid untuk email.');

            $pdf = Pdf::loadView($viewPath, $data)->setPaper('a4', $paperOrientation);
            $path = 'temp_reports/' . uniqid() . '.pdf';
            Storage::disk('public')->put($path, $pdf->output());

            Mail::to($this->user->email)->send(new LaporanMail($subject, $path, $filename));

            Storage::disk('public')->delete($path);

            $this->user->notify(new ReportGenerated('', '', '', 'Laporan (' . str_replace('_', ' ', $this->tipeLaporan) . ') telah berhasil dikirim ke email Anda.'));
        } catch (Throwable $e) {
            Log::error('Gagal mengirim email laporan: ' . $e->getMessage(), ['exception' => $e]);
            $this->user->notify(new ReportGenerated('', '', '', 'Gagal mengirim email laporan: ' . $e->getMessage(), true));
        }
    }
}
