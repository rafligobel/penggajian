<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Rincian - {{ $selectedKaryawan->nama }}</title>
    <style>
        @page {
            margin: 0.8cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9.5pt;
            color: #333;
        }

        .container {
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* KOP SURAT */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 10px;
        }

        .header .logo {
            width: 60px;
            vertical-align: middle;
        }

        .header h3,
        .header h4 {
            margin: 0;
            padding: 0;
            color: #0056b3;
        }

        .header h3 {
            font-size: 16pt;
        }

        .header h4 {
            font-size: 14pt;
            color: #333;
        }

        /* INFO KARYAWAN & ABSENSI */
        .employee-details td {
            padding: 2px 0;
        }

        .summary-box {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .summary-box h6 {
            margin: 0 0 5px 0;
            font-size: 10pt;
            color: #555;
        }

        .summary-box p {
            margin: 0;
            font-size: 14pt;
            font-weight: bold;
        }

        /* TABEL GAJI (GAYA SLIP) */
        .salary-details {
            margin-top: 15px;
        }

        .salary-details th,
        .salary-details td {
            padding: 7px;
            border-bottom: 1px solid #e9e9e9;
        }

        .salary-details th {
            background-color: #f8f9fa;
            text-align: left;
            font-weight: bold;
        }

        .salary-details .section-header td {
            background-color: #e9f5ff;
            font-weight: bold;
        }

        .salary-details .total-row td {
            font-weight: bold;
            background-color: #f8f9fa;
        }

        .salary-details .grand-total-row td {
            font-weight: bold;
            font-size: 11pt;
            background-color: #d1e7dd;
            color: #0a3622;
        }

        .text-end {
            text-align: right;
        }

        /* TANDA TANGAN */
        .signature-section {
            margin-top: 40px;
        }

        .signature-section td {
            padding: 5px;
            text-align: center;
            border: none;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <div class="container">
        {{-- KOP SURAT --}}
        <table class="header">
            <tr>
                <td style="width: 20%; text-align: left;"><img src="{{ $logoAlAzhar }}" alt="Logo" class="logo"></td>
                <td>
                    <h3>YAYASAN ISLAM AL-AZHAR 43 GORONTALO</h3>
                    <h4>LAPORAN RINCIAN KARYAWAN</h4>
                </td>
                <td style="width: 20%; text-align: right;"><img src="{{ $logoYayasan }}" alt="Logo" class="logo">
                </td>
            </tr>
        </table>

        {{-- INFO KARYAWAN --}}
        <table class="employee-details">
            <tr>
                <td width="20%"><strong>NAMA KARYAWAN</strong></td>
                <td>: {{ $selectedKaryawan->nama }}</td>
            </tr>
            <tr>
                <td><strong>NIP</strong></td>
                <td>: {{ $selectedKaryawan->nip }}</td>
            </tr>
            {{-- PENAMBAHAN BARIS JABATAN --}}
            <tr>
                <td><strong>JABATAN</strong></td>
                <td>: {{ $selectedKaryawan->jabatan?->nama_jabatan ?? 'Jabatan Belum Diatur' }}</td>
            </tr>
            <tr>
                <td><strong>PERIODE LAPORAN</strong></td>
                <td>: {{ \Carbon\Carbon::parse($tanggalMulai)->translatedFormat('F Y') }} s.d.
                    {{ \Carbon\Carbon::parse($tanggalSelesai)->translatedFormat('F Y') }}</td>
            </tr>
        </table>

        {{-- RINGKASAN ABSENSI --}}
        <h4 style="margin-top: 25px; margin-bottom: 10px;">Ringkasan Absensi Selama Periode</h4>
        <table style="width: 60%;">
            <tr>
                <td style="padding: 0 5px 0 0;">
                    <div class="summary-box" style="background-color: #e9f5ff;">
                        <h6>Total Kehadiran</h6>
                        <p style="color: #0056b3;">{{ $absensiSummary['hadir'] }} Hari</p>
                    </div>
                </td>
                <td style="padding: 0 0 0 5px;">
                    <div class="summary-box" style="background-color: #ffe9e9;">
                        <h6>Total Alpha</h6>
                        <p style="color: #b30000;">{{ $absensiSummary['alpha'] }} Hari</p>
                    </div>
                </td>
            </tr>
        </table>

        <hr style="margin-top: 25px; border: 0; border-top: 1px solid #ccc;">

        {{-- LOOP UNTUK SETIAP SLIP GAJI --}}
        @forelse ($gajis as $gaji)
            <div class="slip-wrapper">
                <h4
                    style="text-align: center; background-color: #f2f2f2; padding: 5px; margin-top: 25px; margin-bottom: 15px;">
                    Rincian Gaji Periode: {{ \Carbon\Carbon::parse($gaji->bulan)->translatedFormat('F Y') }}
                </h4>

                <table class="salary-details">
                    <thead>
                        <tr>
                            <th width="70%">KETERANGAN</th>
                            <th class="text-end">JUMLAH (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="section-header">
                            <td colspan="2">A. PENDAPATAN</td>
                        </tr>
                        <tr>
                            <td>Gaji Pokok</td>
                            <td class="text-end">{{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan Kehadiran {{ $absensiSummary['hadir'] }} hari</td>
                            <td class="text-end">{{ number_format($gaji->tunj_kehadiran, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan Jabatan</td>
                            <td class="text-end">{{ number_format($gaji->tunj_jabatan, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan Kinerja</td>
                            <td class="text-end">{{ number_format($gaji->tunj_kinerja, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan Anak</td>
                            <td class="text-end">{{ number_format($gaji->tunj_anak, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan Komunikasi</td>
                            <td class="text-end">{{ number_format($gaji->tunj_komunikasi, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tunjangan Pengabdian</td>
                            <td class="text-end">{{ number_format($gaji->tunj_pengabdian, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Lembur</td>
                            <td class="text-end">{{ number_format($gaji->lembur, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Kelebihan Jam</td>
                            <td class="text-end">{{ number_format($gaji->kelebihan_jam, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="text-end">TOTAL PENDAPATAN</td>
                            <td class="text-end">{{ number_format($gaji->total_pendapatan, 0, ',', '.') }}</td>
                        </tr>

                        <tr class="section-header">
                            <td colspan="2">B. POTONGAN</td>
                        </tr>
                        <tr>
                            <td>Potongan Lain-lain</td>
                            <td class="text-end">{{ number_format($gaji->potongan, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="text-end">TOTAL POTONGAN</td>
                            <td class="text-end">{{ number_format($gaji->potongan, 0, ',', '.') }}</td>
                        </tr>

                        <tr class="grand-total-row">
                            <td class="text-end">GAJI BERSIH</td>
                            <td class="text-end">Rp {{ number_format($gaji->gaji_bersih, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Memberi jeda antar slip, kecuali untuk yang terakhir --}}
            @if (!$loop->last)
                <div class="page-break"></div>
            @endif
        @empty
            <div style="text-align: center; padding: 20px; border: 1px dashed #ccc; margin-top: 20px;">
                Tidak ada data riwayat gaji pada periode yang dipilih.
            </div>
        @endforelse

        {{-- TANDA TANGAN (Hanya muncul di halaman terakhir) --}}
        <table class="signature-section">
            <tr>
                <td style="width: 65%;"></td>
                <td style="width: 35%;">
                    Gorontalo, {{ now()->translatedFormat('d F Y') }}<br>
                    Bendahara
                    <div style="height: 60px; margin-top: 5px; margin-bottom: 5px;">
                        @if (!empty($tandaTanganBendahara))
                            <img src="{{ $tandaTanganBendahara }}" alt="Tanda Tangan"
                                style="height: 100%; width: auto;">
                        @endif
                    </div>
                    <b>( {{ $bendaharaNama ?? '.....................' }} )</b>
                </td>
            </tr>
        </table>

    </div>
</body>

</html>
