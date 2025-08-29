<?php

namespace App\Jobs;

use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\Absensi;
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
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Throwable;

class GenerateIndividualReport implements ShouldQueue
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
        try {
            $user = User::findOrFail($this->userId);
            $selectedKaryawan = Karyawan::findOrFail($this->karyawanId);

            $gajis = Gaji::where('karyawan_id', $this->karyawanId)
                ->whereBetween('bulan', [$this->tanggalMulai, $this->tanggalSelesai])
                ->orderBy('bulan', 'asc')->get();

            // === AWAL PERUBAHAN ===
            // Menghitung total dari koleksi $gajis
            $totalGajiPokok = $gajis->sum('gaji_pokok');
            $totalTunjangan = $gajis->sum('tunjangan');
            $totalPotongan = $gajis->sum('potongan');
            $totalGajiBersih = $gajis->sum('gaji_bersih');
            // === AKHIR PERUBAHAN ===

            $startOfMonth = Carbon::createFromFormat('Y-m', $this->tanggalMulai)->startOfMonth();
            $endOfMonth = Carbon::createFromFormat('Y-m', $this->tanggalSelesai)->endOfMonth();

            $absensi = Absensi::where('nip', $selectedKaryawan->nip)
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
                'selectedKaryawan' => $selectedKaryawan,
                'tanggalMulai' => $this->tanggalMulai,
                'tanggalSelesai' => $this->tanggalSelesai,
                'absensiSummary' => $absensiSummary,
                'gajis' => $gajis,
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara,
                'logoAlAzhar' => $logoAlAzhar,
                'logoYayasan' => $logoYayasan,

                // === AWAL PERUBAHAN ===
                // Mengirim variabel total ke view
                'totalGajiPokok' => $totalGajiPokok,
                'totalTunjangan' => $totalTunjangan,
                'totalPotongan' => $totalPotongan,
                'totalGajiBersih' => $totalGajiBersih,
                // === AKHIR PERUBAHAN ===
            ];

            $pdf = Pdf::loadView('laporan.pdf.per_karyawan', $data);
            $pdf->setPaper('A4', 'portrait');

            $safeFilename = str_replace(' ', '_', strtolower($selectedKaryawan->nama));
            $filename = 'laporan_' . $safeFilename . '_' . $this->tanggalMulai . '_sd_' . $this->tanggalSelesai . '_' . uniqid() . '.pdf';
            $path = 'reports/' . $filename;

            Storage::disk('public')->put($path, $pdf->output());

            $user->notify(new ReportGenerated(
                $path,
                'Laporan Karyawan ' . $selectedKaryawan->nama . '.pdf',
                $this->tanggalMulai,
                'Laporan Rincian Karyawan untuk ' . $selectedKaryawan->nama . ' telah selesai dibuat.'
            ));
        } catch (Throwable $e) {
            Log::error('Gagal membuat Laporan Rincian Karyawan: ' . $e->getMessage(), ['exception' => $e]);
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new ReportGenerated('', '', $this->tanggalMulai, 'Gagal membuat Laporan Rincian Karyawan: ' . $e->getMessage(), true));
            }
        }
    }
}
