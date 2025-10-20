<?php

namespace App\Mail;

use App\Models\Karyawan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Support\Collection; // Tambahkan ini untuk tiping Collection

class IndividualReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public Karyawan $karyawan;
    public Collection $gajis; // Tambahkan properti gajis
    public string $filePath;
    public string $pdfFilename;
    public Carbon $tanggalMulai; // Simpan Carbon object untuk akses penuh
    public Carbon $tanggalSelesai; // Simpan Carbon object untuk akses penuh

    // Properti tambahan yang dibutuhkan oleh view
    public $logoYayasan;
    public $logoAlAzhar;
    public $bendaharaNama;
    public $tandaTanganBendahara;

    /**
     * Create a new message instance.
     *
     * @param Karyawan $karyawan Data karyawan
     * @param Collection $gajis Data riwayat gaji
     * @param string $filePath Path file PDF yang di-generate
     * @param string $pdfFilename Nama file PDF
     * @param Carbon $startDate Tanggal mulai periode laporan
     * @param Carbon $endDate Tanggal selesai periode laporan
     */
    public function __construct(
        Karyawan $karyawan,
        Collection $gajis, // Tambahkan ini di constructor
        string $filePath,
        string $pdfFilename,
        Carbon $startDate,
        Carbon $endDate,
        $logoYayasan,
        $logoAlAzhar,
        $bendaharaNama,
        $tandaTanganBendahara
    ) {
        $this->karyawan = $karyawan;
        $this->gajis = $gajis; // Set properti gajis
        $this->filePath = $filePath;
        $this->pdfFilename = $pdfFilename;
        $this->tanggalMulai = $startDate; // Simpan Carbon object
        $this->tanggalSelesai = $endDate; // Simpan Carbon object
        $this->logoYayasan = $logoYayasan;
        $this->logoAlAzhar = $logoAlAzhar;
        $this->bendaharaNama = $bendaharaNama;
        $this->tandaTanganBendahara = $tandaTanganBendahara;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $tanggalMulaiFormatted = $this->tanggalMulai->translatedFormat('F Y');
        $tanggalSelesaiFormatted = $this->tanggalSelesai->translatedFormat('F Y');

        return new Envelope(
            subject: 'Laporan Rincian Gaji Pegawai: ' . $this->karyawan->nama . ' Periode ' . $tanggalMulaiFormatted,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            // GANTI: Menggunakan view 'laporan.pdf.per_karyawan' sesuai permintaan
            view: 'laporan.pdf.per_karyawan', 
            with: [
                'karyawan' => $this->karyawan,
                'gajis' => $this->gajis,
                'tanggalMulai' => $this->tanggalMulai,
                'tanggalSelesai' => $this->tanggalSelesai,
                'logoYayasan' => $this->logoYayasan,
                'logoAlAzhar' => $this->logoAlAzhar,
                'bendaharaNama' => $this->bendaharaNama,
                'tandaTanganBendahara' => $this->tandaTanganBendahara,
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
        // Asumsi file PDF yang akan dikirim sudah di-generate dan disimpan ke disk 'local'
        return [
            Attachment::fromStorageDisk('local', $this->filePath)
                ->as($this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
