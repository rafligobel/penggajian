<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class ReportGenerated extends Notification
{
    use Queueable;

    private string $filePath;
    private string $originalFilename;
    private string $reportMonth;
    private string $customMessage; // <-- Tambahkan
    private bool $isError; // <-- Tambahkan

    public function __construct(string $filePath, string $originalFilename, string $reportMonth, string $customMessage = '', bool $isError = false)
    {
        $this->filePath = $filePath;
        $this->originalFilename = $originalFilename;
        $this->reportMonth = $reportMonth;
        $this->isError = $isError;
        // Atur pesan default jika pesan kustom kosong
        $this->customMessage = $customMessage ?: 'Laporan gaji untuk periode ' . Carbon::createFromFormat('Y-m', $this->reportMonth)->translatedFormat('F Y') . ' telah selesai.';
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->customMessage,
            'path' => $this->filePath,
            'filename' => $this->originalFilename,
            'is_error' => $this->isError, // <-- Tambahkan
        ];
    }
}
