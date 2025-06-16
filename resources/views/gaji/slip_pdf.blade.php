<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $gaji->karyawan->nama }} - {{ \Carbon\Carbon::parse($gaji->bulan)->translatedFormat('F Y') }}
    </title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
        }

        .container {
            width: 100%;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 10px;
            display: table;
            width: 100%;
        }

        .header .logo {
            width: 65px;
            display: table-cell;
            vertical-align: middle;
        }

        .header .title {
            display: table-cell;
            vertical-align: middle;
        }

        .header h3,
        .header h4 {
            margin: 0;
        }

        .employee-details {
            margin-bottom: 20px;
            width: 100%;
        }

        .employee-details td {
            padding: 3px;
        }

        .salary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .salary-table th,
        .salary-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .salary-table th {
            background-color: #f2f2f2;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
        }

        .footer {
            margin-top: 40px;
            width: 100%;
        }

        .footer .signature {
            float: right;
            width: 200px;
            text-align: center;
        }

        .footer .signature-line {
            border-bottom: 1px solid #333;
            height: 60px;
        }
    </style>
</head>

<body>
    <div class="container">
        {{-- BAGIAN KOP SURAT --}}
        <div class="header">
            <div class="logo">
                <img src="{{ $logoAlAzhar }}" alt="Logo Al Azhar" style="width: 100%;">
            </div>
            <div class="title">
                <h3>YAYASAN ISLAM AL-AZHAR 43 GORONTALO</h3>
                <h4>SLIP GAJI PEGAWAI</h4>
            </div>
            <div class="logo">
                <img src="{{ $logoYayasan }}" alt="Logo Yayasan" style="width: 100%;">
            </div>
        </div>

        {{-- BAGIAN DETAIL KARYAWAN --}}
        <table class="employee-details">
            <tr>
                <td width="15%"><strong>NAMA</td>
                <td width="35%">: {{ $gaji->karyawan->nama }}</td>
                <td width="15%"><strong>PERIODE</td>
                <td width="35%">: {{ \Carbon\Carbon::parse($gaji->bulan)->translatedFormat('F Y') }}</td>
            </tr>
            <tr>
                <td><strong>NIP</td>
                <td>: {{ $gaji->karyawan->nip }}</td>
                <td><strong>JABATAN</td>
                <td>: {{ $gaji->karyawan->jabatan }}</td>
            </tr>
        </table>

        {{-- BAGIAN RINCIAN GAJI --}}
        <table class="salary-table">
            <thead>
                <tr>
                    <th width="50%">PENERIMAAN</th>
                    <th width="50%">POTONGAN</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    {{-- Kolom Penerimaan --}}
                    <td style="vertical-align: top;">
                        <table>
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
                        </table>
                    </td>
                    {{-- Kolom Potongan --}}
                    <td style="vertical-align: top;">
                        <table>
                            <tr>
                                <td>Potongan Lain-lain</td>
                                <td class="text-right">{{ number_format($gaji->potongan, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                {{-- Baris Total --}}
                <tr class="total-row">
                    <td>
                        <strong>TOTAL PENERIMAAN</strong>
                        @php
                            $totalPenerimaan = $gaji->gaji_bersih + $gaji->potongan;
                        @endphp
                        <strong style="float: right;">{{ number_format($totalPenerimaan, 0, ',', '.') }}</strong>
                    </td>
                    <td>
                        <strong>TOTAL POTONGAN</strong>
                        <strong style="float: right;">{{ number_format($gaji->potongan, 0, ',', '.') }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Baris Gaji Bersih --}}
        <table class="salary-table total-row" style="margin-top: 0px;">
            <tr>
                <td width="50%">GAJI BERSIH (DITERIMA)</td>
                <td width="50%" class="text-right">{{ number_format($gaji->gaji_bersih, 0, ',', '.') }}</td>
            </tr>
        </table>

        {{-- BAGIAN TANDA TANGAN --}}
        <div class="footer">
            <div class="signature">
                Gorontalo, {{ now()->translatedFormat('d F Y') }}
                <br>
                Bendahara
                <div class="signature-line"></div>
                ( _____________________ )
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>
</body>

</html>
