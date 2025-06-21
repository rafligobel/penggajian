<?php

namespace App\Mail;

use App\Models\Gaji;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment; // <-- Import ini
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage; // <-- Import ini

class SalarySlipMail extends Mailable
{
    use Queueable, SerializesModels;

    public Gaji $gaji;
    public string $pdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct(Gaji $gaji, string $pdfPath)
    {
        $this->gaji = $gaji;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $periode = \Carbon\Carbon::parse($this->gaji->bulan)->translatedFormat('F Y');
        return new Envelope(
            subject: 'Slip Gaji Anda untuk Periode ' . $periode,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.salary.slip', // View untuk isi email
            with: [
                'nama' => $this->gaji->karyawan->nama,
                'periode' => \Carbon\Carbon::parse($this->gaji->bulan)->translatedFormat('F Y'),
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
        // Ambil nama file asli untuk ditampilkan di email
        $originalFilename = 'slip-gaji-' . str_replace(' ', '_', strtolower($this->gaji->karyawan->nama)) . '-' . $this->gaji->bulan . '.pdf';

        return [
            Attachment::fromPath(Storage::disk('public')->path($this->pdfPath))
                ->as($originalFilename)
                ->withMime('application/pdf'),
        ];
    }
}
