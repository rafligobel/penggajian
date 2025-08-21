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
    public string $attachmentPath;
    public string $attachmentFilename;

    public function __construct(string $subject, string $path, string $filename)
    {
        $this->customSubject = $subject;
        $this->attachmentPath = $path;
        $this->attachmentFilename = $filename;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->customSubject);
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>Salam,</p><p>Laporan yang Anda minta telah berhasil dibuat dan terlampir dalam email ini.</p><p>Terima kasih.</p>');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('public', $this->attachmentPath)
                ->as($this->attachmentFilename)
                ->withMime('application/pdf'),
        ];
    }
}
