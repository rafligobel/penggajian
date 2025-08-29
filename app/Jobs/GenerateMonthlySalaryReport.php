<?php

namespace App\Jobs;

use App\Models\Gaji;
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
use Throwable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateMonthlySalaryReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected $selectedMonth;
    protected $user;
    protected $gajiIds;

    public function __construct(string $selectedMonth, User $user, ?array $gajiIds = null)
    {
        $this->selectedMonth = $selectedMonth;
        $this->user = $user;
        $this->gajiIds = $gajiIds;
    }

    public function handle(): void
    {
        try {
            // Eager load relasi karyawan untuk efisiensi
            $query = Gaji::with('karyawan')->where('bulan', $this->selectedMonth);

            if (!empty($this->gajiIds)) {
                $query->whereIn('id', $this->gajiIds);
            }

            $gajis = $query->get();

            if ($gajis->isEmpty()) {
                throw new \Exception('Tidak ada data gaji yang valid untuk diproses.');
            }

            // ====================================================================
            // PERBAIKAN: Menggunakan Relasi Eloquent untuk mengambil total kehadiran
            // ====================================================================
            $periode = Carbon::createFromFormat('Y-m', $this->selectedMonth);
            $kehadiranData = [];

            foreach ($gajis as $gaji) {
                if ($gaji->karyawan) {
                    // Menggunakan relasi 'absensi' yang ada di model Karyawan
                    $totalHadir = $gaji->karyawan->absensi()
                        ->whereYear('tanggal', $periode->year)
                        ->whereMonth('tanggal', $periode->month)
                        ->count();

                    // Membuat objek sederhana untuk konsistensi dengan view
                    $kehadiranData[$gaji->karyawan_id] = (object)['total_hadir' => $totalHadir];
                }
            }
            // ====================================================================
            // AKHIR DARI PERBAIKAN
            // ====================================================================

            $bendahara = User::where('role', 'bendahara')->first();
            $bendaharaNama = $bendahara ? $bendahara->name : 'Bendahara Umum';

            $totals = (object)[
                'total_gaji_pokok' => $gajis->sum('gaji_pokok'),
                'total_tunj_jabatan' => $gajis->sum('tunj_jabatan'),
                'total_tunj_anak' => $gajis->sum('tunj_anak'),
                'total_tunj_komunikasi' => $gajis->sum('tunj_komunikasi'),
                'total_tunj_pengabdian' => $gajis->sum('tunj_pengabdian'),
                'total_tunj_kinerja' => $gajis->sum('tunj_kinerja'),
                'total_pendapatan_lainnya' => $gajis->sum('pendapatan_lainnya'),
                'total_potongan' => $gajis->sum('potongan'),
                'total_gaji_bersih' => $gajis->sum('gaji_bersih'),
            ];

            $tandaTanganBendahara = '';
            $pengaturanTtd = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            if ($pengaturanTtd && Storage::disk('public')->exists($pengaturanTtd->value)) {
                $tandaTanganBendahara = $this->getImageAsBase64DataUri(storage_path('app/public/' . $pengaturanTtd->value));
            }

            $data = [
                'gajis' => $gajis,
                'periode' => $this->selectedMonth,
                'totals' => $totals,
                'bendaharaNama' => $bendaharaNama,
                'logoAlAzhar' => $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png')),
                'logoYayasan' => $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png')),
                'tandaTanganBendahara' => $tandaTanganBendahara,
                'kehadiranData' => $kehadiranData,
            ];

            $pdf = Pdf::loadView('gaji.cetak_semua', $data);
            $pdf->setPaper('A4', 'landscape');

            $safeMonth = str_replace('-', '', $this->selectedMonth);
            $filename = 'laporan_gaji_' . $safeMonth . '_' . uniqid() . '.pdf';
            $path = 'reports/' . $filename;

            Storage::disk('public')->put($path, $pdf->output());

            $this->user->notify(new ReportGenerated(
                $path,
                'Laporan Gaji ' . $safeMonth . '.pdf',
                $this->selectedMonth,
                'Laporan Gaji Bulanan telah selesai dibuat.'
            ));
        } catch (Throwable $e) {
            Log::error('Gagal membuat Laporan Gaji Bulanan: ' . $e->getMessage(), ['exception' => $e]);
            $this->user->notify(new ReportGenerated('', '', $this->selectedMonth, 'Gagal membuat Laporan Gaji Bulanan: ' . $e->getMessage(), true));
        }
    }
}
