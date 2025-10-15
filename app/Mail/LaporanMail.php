<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class LaporanMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $customSubject;
    public string $attachmentFilename;
    public $pdfData;

    // PERBAIKAN: Menerima data PDF mentah, bukan path
    public function __construct(string $subject, $pdfData, string $filename)
    {
        $this->customSubject = $subject;
        $this->pdfData = $pdfData;
        $this->attachmentFilename = $filename;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->customSubject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reports.general', // Sebaiknya gunakan view
            with: [
                'subject' => $this->customSubject
            ]
        );
    }

    public function attachments(): array
    {
        // PERBAIKAN: Melampirkan dari data mentah
        return [
            Attachment::fromData(fn() => $this->pdfData, $this->attachmentFilename)
                ->withMime('application/pdf'),
        ];
    }
}

// Anda perlu membuat view blade sederhana untuk email ini, contohnya di:
// resources/views/emails/reports/general.blade.php
/*
@component('mail::message')
# Laporan Telah Dibuat

Salam,

Laporan yang Anda minta ({{ $subject }}) telah berhasil dibuat dan terlampir dalam email ini.

Terima kasih,<br>
{{ config('app.name') }}
@endcomponent
*/