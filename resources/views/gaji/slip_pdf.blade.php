<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $gaji->karyawan->nama }} - {{ \Carbon\Carbon::parse($gaji->bulan)->translatedFormat('F Y') }}
    </title>
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9.5pt;
            /* Sedikit diperkecil untuk lebih compact */
            color: #333;
            margin: 0;
        }

        .container {
            width: 18cm;
            margin: 1cm auto;
            /* Margin atas/bawah diperkecil */
            border: 1px solid #e0e0e0;
            padding: 0.8cm;
            /* Padding diperkecil */
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header {
            padding-bottom: 8px;
            /* Diperkecil */
            margin-bottom: 15px;
            /* Diperkecil */
            border-bottom: 2px solid #0056b3;
            text-align: center;
        }

        .header .logo {
            width: 55px;
            /* Sedikit diperkecil */
            height: 55px;
            vertical-align: middle;
        }

        .header .title-container {
            vertical-align: middle;
        }

        .header h3,
        .header h4 {
            margin: 0;
            padding: 0;
            color: #0056b3;
        }

        .header h3 {
            font-size: 15pt;
        }

        .header h4 {
            font-size: 13pt;
            color: #555;
        }

        .employee-details table td {
            padding: 3px 0;
            /* Jarak diperkecil */
            font-size: 9.5pt;
        }

        .salary-details {
            margin-top: 20px;
            /* Diperkecil */
        }

        .salary-details th,
        .salary-details td {
            padding: 8px;
            /* Padding sel diperkecil */
            border-bottom: 1px solid #e9e9e9;
        }

        .salary-details th {
            background-color: #f8f9fa;
            text-align: left;
            font-weight: bold;
            color: #333;
            font-size: 9pt;
        }

        .salary-details .text-right {
            text-align: right;
        }

        .salary-details .section-header td {
            /* Diperbarui agar lebih jelas */
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
            /* Kelas baru untuk baris Gaji Bersih */
            font-weight: bold;
            font-size: 11pt;
            background-color: #d1e7dd;
            color: #0a3622;
        }

        /* Tata Letak Tanda Tangan yang Baru */
        .signature-section {
            margin-top: 35px;
            /* Jarak diperkecil */
        }

        .signature-section td {
            padding: 5px;
            text-align: center;
        }

        .signature-section .signature-line {
            margin-top: 50px;
            border-bottom: 1px solid #333;
        }
    </style>
</head>

<body>
    <div class="container">
        {{-- KOP SURAT (Tidak ada perubahan struktur) --}}
        <table class="header">
            <tr>
                <td style="width: 20%; text-align: left;"><img src="{{ $logoAlAzhar }}" alt="Logo" class="logo"></td>
                <td class="title-container">
                    <h3>YAYASAN ISLAM AL-AZHAR 43 GORONTALO</h3>
                    <h4>SLIP GAJI KARYAWAN</h4>
                </td>
                <td style="width: 20%; text-align: right;"><img src="{{ $logoYayasan }}" alt="Logo" class="logo">
                </td>
            </tr>
        </table>

        {{-- DETAIL KARYAWAN (Tidak ada perubahan struktur) --}}
        <div class="employee-details">
            <table>
                <tr>
                    <td width="15%"><strong>NAMA</strong></td>
                    <td width="35%">: {{ $gaji->karyawan->nama }}</td>
                    <td width="15%"><strong>PERIODE</strong></td>
                    <td width="35%">: {{ \Carbon\Carbon::parse($gaji->bulan)->translatedFormat('F Y') }}</td>
                </tr>
                <tr>
                    <td><strong>NIP</strong></td>
                    <td>: {{ $gaji->karyawan->nip }}</td>
                    <td><strong>JABATAN</strong></td>
                    <td>: {{ $gaji->karyawan->jabatan }}</td>
                </tr>
            </table>
        </div>

        {{-- RINCIAN GAJI (Struktur sama, kelas CSS diperbarui) --}}
        <table class="salary-details">
            <thead>
                <tr>
                    <th width="70%">KETERANGAN</th>
                    <th class="text-right">JUMLAH (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="section-header">
                    <td colspan="2">A. PENDAPATAN</td>
                </tr>
                <tr>
                    <td>Gaji Pokok</td>
                    <td class="text-right">{{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Kehadiran</td>
                    <td class="text-right">{{ number_format($gaji->tunj_kehadiran, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Jabatan</td>
                    <td class="text-right">{{ number_format($gaji->tunj_jabatan, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Kinerja</td>
                    <td class="text-right">{{ number_format($gaji->tunj_kinerja, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Anak</td>
                    <td class="text-right">{{ number_format($gaji->tunj_anak, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Komunikasi</td>
                    <td class="text-right">{{ number_format($gaji->tunj_komunikasi, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Pengabdian</td>
                    <td class="text-right">{{ number_format($gaji->tunj_pengabdian, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Lembur</td>
                    <td class="text-right">{{ number_format($gaji->lembur, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Kelebihan Jam</td>
                    <td class="text-right">{{ number_format($gaji->kelebihan_jam, 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td class="text-right">TOTAL PENDAPATAN</td>
                    <td class="text-right">{{ number_format($gaji->total_pendapatan, 0, ',', '.') }}</td>
                </tr>

                <tr class="section-header">
                    <td colspan="2">B. POTONGAN</td>
                </tr>
                <tr>
                    <td>Potongan Lain-lain</td>
                    <td class="text-right">{{ number_format($gaji->potongan, 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td class="text-right">TOTAL POTONGAN</td>
                    <td class="text-right">{{ number_format($gaji->potongan, 0, ',', '.') }}</td>
                </tr>

                <tr class="grand-total-row">
                    <td class="text-right">GAJI BERSIH (A - B)</td>
                    <td class="text-right">Rp {{ number_format($gaji->gaji_bersih, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        {{-- TATA LETAK TANDA TANGAN YANG BARU --}}
        <table class="signature-section">
            <tr>
                <td style="width: 65%;"></td> {{-- Kolom kosong untuk mendorong ke kanan --}}
                <td style="width: 35%;">
                    Gorontalo, {{ now()->translatedFormat('d F Y') }}<br>
                    Bendahara
                    <div class="signature-line"></div>
                    ( {{ $bendaharaNama ?? '.....................' }} )
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
