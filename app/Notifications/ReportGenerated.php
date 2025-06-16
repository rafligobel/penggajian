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

    public function __construct(string $filePath, string $originalFilename, string $reportMonth)
    {
        $this->filePath = $filePath;
        $this->originalFilename = $originalFilename;
        $this->reportMonth = $reportMonth;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $periode = Carbon::createFromFormat('Y-m', $this->reportMonth)->translatedFormat('F Y');
        return [
            'message' => 'Laporan gaji untuk periode ' . $periode . ' telah selesai.',
            'path' => $this->filePath,
            'filename' => $this->originalFilename,
        ];
    }
}
