<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

/**
 * Notifikasi yang dikirim setelah Job untuk membuat laporan PDF selesai.
 * Notifikasi ini disimpan ke database.
 */
class ReportGenerated extends Notification
{
    use Queueable;

    private string $filePath;
    private string $originalFilename;
    private string $reportMonth;

    /**
     * Buat instance notifikasi baru.
     *
     * @param string $filePath Path file di dalam storage (misal: 'reports/nama-file.pdf')
     * @param string $originalFilename Nama file yang akan dilihat pengguna saat mengunduh
     * @param string $reportMonth Periode laporan dalam format 'Y-m'
     */
    public function __construct(string $filePath, string $originalFilename, string $reportMonth)
    {
        $this->filePath = $filePath;
        $this->originalFilename = $originalFilename;
        $this->reportMonth = $reportMonth;
    }

    /**
     * Menentukan channel pengiriman notifikasi.
     *
     * @param  object  $notifiable
     * @return array
     */
    public function via(object $notifiable): array
    {
        // 'database' berarti notifikasi akan disimpan di tabel 'notifications'
        return ['database'];
    }

    /**
     * Mendapatkan representasi array dari notifikasi.
     * Data ini akan di-encode sebagai JSON dan disimpan di kolom 'data' pada tabel notifikasi.
     *
     * @param  object  $notifiable
     * @return array
     */
    public function toArray(object $notifiable): array
    {
        // Ubah format 'Y-m' menjadi nama bulan dan tahun yang mudah dibaca (misal: "Juni 2025")
        $periode = Carbon::createFromFormat('Y-m', $this->reportMonth)->translatedFormat('F Y');

        return [
            'message' => 'Laporan gaji untuk periode ' . $periode . ' telah selesai dibuat.',
            'path' => $this->filePath,
            'filename' => $this->originalFilename,
        ];
    }
}
