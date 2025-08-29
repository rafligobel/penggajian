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

        /* INFO KARYAWAN */
        .employee-details td {
            padding: 3px 0;
        }

        /* TABEL GAJI (GAYA SLIP) */
        .salary-details {
            margin-top: 20px;
        }

        .salary-details th,
        .salary-details td {
            padding: 8px;
            border-bottom: 1px solid #e9e9e9;
        }

        .salary-details th {
            background-color: #f8f9fa;
            text-align: left;
            font-weight: bold;
        }

        .salary-details .text-right {
            text-align: right;
        }

        .salary-details .section-header td {
            background-color: #e9f5ff;
            font-weight: bold;
            padding: 6px 8px;
        }

        .total-row td {
            font-weight: bold;
            font-size: 10pt;
            background-color: #f8f9fa;
        }

        .grand-total-row td {
            font-weight: bold;
            font-size: 11pt;
            background-color: #d1e7dd;
            color: #0a3622;
        }

        /* TANDA TANGAN */
        .signature-section {
            margin-top: 35px;
        }

        .signature-section td {
            padding: 5px;
            text-align: center;
            border: none;
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

        <hr style="margin-top: 20px; border: 0; border-top: 1px solid #ccc;">

        {{-- TABEL REKAP GAJI DENGAN GAYA SLIP --}}
        @if ($gajis->isNotEmpty())
            <h4 style="margin-top: 20px; margin-bottom: 10px; text-align:center;">Rekapitulasi Gaji Selama Periode</h4>
            <table class="salary-details">
                <thead>
                    <tr>
                        <th width="70%">KETERANGAN</th>
                        <th class="text-right">TOTAL JUMLAH (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="section-header">
                        <td colspan="2">A. PENDAPATAN</td>
                    </tr>
                    <tr>
                        <td>Gaji Pokok</td>
                        <td class="text-right">{{ number_format($gajis->sum('gaji_pokok'), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Tunjangan Kehadiran ({{ $absensiSummary['hadir'] ?? 0 }} hari)</td>
                        <td class="text-right">{{ number_format($gajis->sum('tunj_kehadiran'), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Tunjangan Jabatan</td>
                        <td class="text-right">{{ number_format($gajis->sum('tunj_jabatan'), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Tunjangan Kinerja</td>
                        <td class="text-right">{{ number_format($gajis->sum('tunj_kinerja'), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Tunjangan Anak</td>
                        <td class="text-right">{{ number_format($gajis->sum('tunj_anak'), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Tunjangan Komunikasi</td>
                        <td class="text-right">{{ number_format($gajis->sum('tunj_komunikasi'), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Tunjangan Pengabdian</td>
                        <td class="text-right">{{ number_format($gajis->sum('tunj_pengabdian'), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Lembur</td>
                        <td class="text-right">{{ number_format($gajis->sum('lembur'), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Kelebihan Jam</td>
                        <td class="text-right">{{ number_format($gajis->sum('kelebihan_jam'), 0, ',', '.') }}</td>
                    </tr>
                    <tr class="total-row">
                        <td class="text-right">TOTAL PENDAPATAN</td>
                        <td class="text-right">{{ number_format($gajis->sum('total_pendapatan'), 0, ',', '.') }}</td>
                    </tr>

                    <tr class="section-header">
                        <td colspan="2">B. POTONGAN</td>
                    </tr>
                    <tr>
                        <td>Potongan Lain-lain</td>
                        <td class="text-right">{{ number_format($gajis->sum('potongan'), 0, ',', '.') }}</td>
                    </tr>
                    <tr class="total-row">
                        <td class="text-right">TOTAL POTONGAN</td>
                        <td class="text-right">{{ number_format($gajis->sum('potongan'), 0, ',', '.') }}</td>
                    </tr>

                    <tr class="grand-total-row">
                        <td class="text-right">TOTAL GAJI BERSIH DITERIMA</td>
                        <td class="text-right">Rp {{ number_format($gajis->sum('gaji_bersih'), 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <div style="text-align: center; padding: 20px; border: 1px dashed #ccc; margin-top: 20px;">
                Tidak ada data riwayat gaji pada periode yang dipilih.
            </div>
        @endif


        {{-- TANDA TANGAN --}}
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
