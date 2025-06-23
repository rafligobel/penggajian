<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Gaji Bulanan - {{ $periode }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            /* Ukuran font diperkecil agar lebih compact */
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .main-table th,
        .main-table td {
            border: 1px solid #ccc;
            padding: 5px 8px;
            /* Padding diperkecil */
            text-align: right;
        }

        .main-table th {
            background-color: #0056b3;
            /* Header tabel lebih modern */
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 8px;
        }

        .main-table .text-left {
            text-align: left;
        }

        .main-table .text-center {
            text-align: center;
        }

        /* Zebra-striping untuk baris tabel agar mudah dibaca */
        .main-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .main-table tfoot .footer-row {
            font-weight: bold;
            background-color: #e9ecef;
            /* Warna footer berbeda */
        }

        /* Styling untuk Kop Surat */
        .header-table {
            width: 100%;
            border: none;
            margin-bottom: 20px;
        }

        .header-table td {
            border: none;
            vertical-align: middle;
            text-align: center;
        }

        .header-table .logo {
            width: 60px;
        }

        .header-table h3,
        .header-table h4,
        .header-table span {
            margin: 0;
        }

        .header-table h3 {
            font-size: 16pt;
            color: #0056b3;
        }

        .header-table h4 {
            font-size: 14pt;
            color: #333;
        }

        .header-table span {
            font-size: 11pt;
            color: #555;
        }

        /* Styling untuk Tanda Tangan */
        .signature-section {
            margin-top: 30px;
        }

        .signature-section td {
            padding: 5px;
            text-align: center;
            border: none;
        }

        .signature-section .signature-line {
            margin-top: 50px;
            border-bottom: 1px solid #333;
        }

        .header-table .logo svg {
            /* Tambahkan style ini */
            width: 60px;
            height: auto;
        }

        .header-table .logo {
            width: 60px;
            /* PASTIKAN ATURAN INI ADA */
        }

        .header-table h3 {
            font-size: 13pt;
            color: #555;
        }
    </style>
</head>

<body>

    {{-- KOP SURAT --}}
    <table class="header">
        <tr>
            <td style="width: 20%; text-align: left;"><img src="{{ $logoAlAzhar }}" alt="Logo" class="logo"></td>
            <td class="title-container">
                <h3>YAYASAN ISLAM AL-AZHAR 43 GORONTALO</h3>
                <h4>SLIP GAJI KARYAWAN</h4>
            </td>
            <td style="width: 20%; text-align: right;"><img src="{{ $logoYayasan }}" alt="Logo" class="logo"></td>
        </tr>
    </table>

    {{-- TABEL UTAMA --}}
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th class="text-left" style="width: 20%;">Nama Karyawan</th>
                <th class="text-left" style="width: 15%;">Jabatan</th>
                <th>Gaji Pokok</th>
                <th>Total Tunjangan</th>
                <th>Lembur & Lainnya</th>
                <th>Potongan</th>
                <th>Gaji Bersih (Diterima)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gajis as $gaji)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-left">{{ $gaji->karyawan->nama }}</td>
                    <td class="text-left">{{ $gaji->karyawan->jabatan }}</td>
                    <td>{{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                    <td>{{ number_format($gaji->total_tunjangan, 0, ',', '.') }}</td>
                    <td>{{ number_format($gaji->pendapatan_lainnya, 0, ',', '.') }}</td>
                    <td>({{ number_format($gaji->potongan, 0, ',', '.') }})</td>
                    <td style="font-weight: bold;">{{ number_format($gaji->gaji_bersih, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">Tidak ada data gaji untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if ($gajis->isNotEmpty())
            <tfoot>
                <tr class="footer-row">
                    <td colspan="3" class="text-center">TOTAL KESELURUHAN</td>
                    <td>{{ number_format($totals->total_gaji_pokok ?? 0, 0, ',', '.') }}</td>
                    <td>{{ number_format($totals->total_semua_tunjangan ?? 0, 0, ',', '.') }}</td>
                    <td>{{ number_format($totals->total_pendapatan_lainnya ?? 0, 0, ',', '.') }}</td>
                    <td>({{ number_format($totals->total_potongan ?? 0, 0, ',', '.') }})</td>
                    <td>{{ number_format($totals->total_gaji_bersih ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- TATA LETAK TANDA TANGAN YANG BARU --}}
    <table class="signature-section">
        <tr>
            <td style="width: 65%;"></td> {{-- Kolom kosong untuk mendorong ke kanan --}}
            <td style="width: 35%;">
                Gorontalo, {{ now()->translatedFormat('d F Y') }}<br>
                Bendahara
                <div class="signature-line"></div>
                <b>( {{ $bendaharaNama ?? '.....................' }} )</b>
            </td>
        </tr>
    </table>

</body>

</html>
