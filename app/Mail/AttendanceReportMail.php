<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use App\Models\Karyawan;
use Carbon\Carbon;

class AttendanceReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $karyawan;
    public $periode;
    protected $pdfData;
    protected $pdfFilename;

    /**
     * Create a new message instance.
     */
    public function __construct(Karyawan $karyawan, Carbon $periode, $pdfData, $pdfFilename)
    {
        $this->karyawan = $karyawan;
        $this->periode = $periode;
        $this->pdfData = $pdfData;
        $this->pdfFilename = $pdfFilename;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Laporan Rincian Absensi Periode ' . $this->periode->translatedFormat('F Y'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.salary.slip', // Kita bisa pakai ulang template email yang ada
            with: [
                'nama' => $this->karyawan->nama,
                'periode' => "{$this->periode->translatedFormat('F Y')}. (Ini adalah laporan rincian absensi Anda)",
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn() => $this->pdfData, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
