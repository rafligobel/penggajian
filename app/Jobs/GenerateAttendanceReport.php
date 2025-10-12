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
use App\Notifications\ReportGenerated;
use App\Traits\ManagesImageEncoding;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateAttendanceReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ManagesImageEncoding;

    protected $karyawanIds;
    protected $bulan;
    protected $tahun;
    protected $userId;

    public function __construct(array $karyawanIds, string $bulan, string $tahun, int $userId)
    {
        $this->karyawanIds = $karyawanIds;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $periode = Carbon::create($this->tahun, $this->bulan);

        try {
            $karyawans = Karyawan::whereIn('id', $this->karyawanIds)
                ->with(['absensi' => function ($query) {
                    $query->whereMonth('tanggal', $this->bulan)->whereYear('tanggal', $this->tahun);
                }])
                ->get();

            $daysInMonth = $periode->daysInMonth;
            $detailAbsensi = [];

            foreach ($karyawans as $karyawan) {
                $dailyData = [];
                $totalHadir = 0;

                $absensiHarian = $karyawan->absensi->keyBy(function ($item) {
                    return Carbon::parse($item->tanggal)->format('j');
                });

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    if (isset($absensiHarian[$day])) {
                        $dailyData[$day] = 'H'; // Hadir
                        $totalHadir++;
                    } else {
                        $dailyData[$day] = 'A'; // Alpha
                    }
                }

                $detailAbsensi[] = (object)[
                    'nip' => $karyawan->nip,
                    'nama' => $karyawan->nama,
                    'daily_data' => $dailyData,
                    'total_hadir' => $totalHadir,
                    'total_alpha' => $daysInMonth - $totalHadir,
                ];
            }

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
                'detailAbsensi' => $detailAbsensi,
                'daysInMonth' => $daysInMonth,
                'periode' => $periode,
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara,
                'logoAlAzhar' => $logoAlAzhar,
                'logoYayasan' => $logoYayasan,
            ];

            $pdf = Pdf::loadView('laporan.pdf.rekap_absensi', $data)->setPaper('a4', 'landscape');

            $filename = 'laporan-absensi-' . $periode->format('Y-m') . '-' . uniqid() . '.pdf';
            // [PERBAIKAN] Path penyimpanan dibuat lebih terstruktur
            $path = 'laporan/absensi/' . $filename;

            // [PERBAIKAN] Menyimpan ke disk 'local' untuk keamanan
            Storage::disk('local')->put($path, $pdf->output());

            $notifMessage = "Laporan absensi periode {$periode->translatedFormat('F Y')} telah selesai dibuat.";
            $user->notify(new ReportGenerated($path, "Laporan Absensi {$periode->translatedFormat('F Y')}.pdf", $periode->format('Y-m'), $notifMessage));
        } catch (Throwable $e) {
            Log::error('Gagal membuat Laporan Rincian Absensi: ' . $e->getMessage(), ['exception' => $e]);
            if ($user) {
                $notifMessage = "Gagal membuat laporan absensi untuk periode {$periode->translatedFormat('F Y')}.";
                $user->notify(new ReportGenerated('', '', $periode->format('Y-m'), $notifMessage, true));
            }
        }
    }
}
