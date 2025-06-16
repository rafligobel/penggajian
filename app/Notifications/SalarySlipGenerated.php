<?php

namespace App\Notifications;

use App\Models\Gaji;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SalarySlipGenerated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $gaji;
    protected $pdfData;
    protected $pdfFilename;

    /**
     * Create a new notification instance.
     */
    public function __construct(Gaji $gaji, $pdfData, $pdfFilename)
    {
        $this->gaji = $gaji;
        $this->pdfData = $pdfData;
        $this->pdfFilename = $pdfFilename;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $periode = \Carbon\Carbon::parse($this->gaji->bulan)->translatedFormat('F Y');

        return (new MailMessage)
            ->subject('Slip Gaji Periode ' . $periode)
            ->greeting('Assalamualaikum, ' . $this->gaji->karyawan->nama . '!')
            ->line('Berikut terlampir slip gaji Anda untuk periode ' . $periode . '.')
            ->line('Harap simpan email ini sebagai arsip digital Anda.')
            ->line('Terima kasih.')
            ->attachData($this->pdfData, $this->pdfFilename, [
                'mime' => 'application/pdf',
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
