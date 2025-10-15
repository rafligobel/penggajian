<?php

namespace App\Mail;

use App\Models\Karyawan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IndividualReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public Karyawan $karyawan;
    public $pdfData;
    public string $pdfFilename;
    public string $tanggalMulai;
    public string $tanggalSelesai;

    public function __construct(Karyawan $karyawan, $pdfData, string $pdfFilename, string $tanggalMulai, string $tanggalSelesai)
    {
        $this->karyawan = $karyawan;
        $this->pdfData = $pdfData;
        $this->pdfFilename = $pdfFilename;
        $this->tanggalMulai = \Carbon\Carbon::parse($tanggalMulai)->translatedFormat('F Y');
        $this->tanggalSelesai = \Carbon\Carbon::parse($tanggalSelesai)->translatedFormat('F Y');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Laporan Rincian Karyawan Periode ' . $this->tanggalMulai . ' - ' . $this->tanggalSelesai,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reports.individual', // Gunakan view
            with: [
                'nama' => $this->karyawan->nama,
                'periode' => $this->tanggalMulai . ' s/d ' . $this->tanggalSelesai,
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
