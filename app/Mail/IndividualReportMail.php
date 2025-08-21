<?php

namespace App\Mail;

use App\Models\Karyawan;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class IndividualReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $karyawan;
    public $tanggalMulai;
    public $tanggalSelesai;
    protected $pdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct(Karyawan $karyawan, string $pdfPath, string $tanggalMulai, string $tanggalSelesai)
    {
        $this->karyawan = $karyawan;
        $this->pdfPath = $pdfPath;
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $periodeMulai = Carbon::parse($this->tanggalMulai)->translatedFormat('F Y');
        $periodeSelesai = Carbon::parse($this->tanggalSelesai)->translatedFormat('F Y');

        return new Envelope(
            subject: 'Laporan Rincian Karyawan Periode ' . $periodeMulai . ' - ' . $periodeSelesai,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $periodeMulai = Carbon::parse($this->tanggalMulai)->translatedFormat('F Y');
        $periodeSelesai = Carbon::parse($this->tanggalSelesai)->translatedFormat('F Y');

        return new Content(
            markdown: 'emails.salary.slip', // Kita bisa pakai ulang template email yang ada
            with: [
                'nama' => $this->karyawan->nama,
                'periode' => "{$periodeMulai} - {$periodeSelesai}. (Ini adalah laporan rincian Anda)",
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
            Attachment::fromStorageDisk('public', $this->pdfPath)
                ->as('laporan-rincian-' . str_replace(' ', '_', strtolower($this->karyawan->nama)) . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
