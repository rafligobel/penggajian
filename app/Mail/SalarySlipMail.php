<?php

namespace App\Mail;

use App\Models\Gaji;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SalarySlipMail extends Mailable
{
    use Queueable, SerializesModels;

    public Gaji $gaji;
    public $pdfData;
    public string $pdfFilename;

    public function __construct(Gaji $gaji, $pdfData, string $pdfFilename)
    {
        $this->gaji = $gaji;
        $this->pdfData = $pdfData;
        $this->pdfFilename = $pdfFilename;
    }

    public function envelope(): Envelope
    {
        $periode = \Carbon\Carbon::parse($this->gaji->bulan)->translatedFormat('F Y');
        return new Envelope(
            subject: 'Slip Gaji Anda untuk Periode ' . $periode,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.salary.slip',
            with: [
                'nama' => $this->gaji->karyawan->nama,
                'periode' => \Carbon\Carbon::parse($this->gaji->bulan)->translatedFormat('F Y'),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn() => $this->pdfData, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
