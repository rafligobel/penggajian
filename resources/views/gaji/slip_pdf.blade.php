<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $data['karyawan']->nama }} -
        {{ \Carbon\Carbon::parse($data['bulan'])->translatedFormat('F Y') }}</title>
    <style>
        /* CSS styles (tidak ada perubahan signifikan, hanya memastikan konsistensi) */
        @page {
            margin: 0;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9.5pt;
            color: #333;
            margin: 0;
        }

        .container {
            width: 18cm;
            margin: 1cm auto;
            border: 1px solid #e0e0e0;
            padding: 0.8cm;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header {
            padding-bottom: 8px;
            margin-bottom: 15px;
            border-bottom: 2px solid #0056b3;
            text-align: center;
        }

        .header .logo {
            width: 55px;
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
            font-size: 9.5pt;
        }

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
            color: #333;
            font-size: 9pt;
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

        .signature-section {
            margin-top: 35px;
        }

        .signature-section td {
            padding: 5px;
            text-align: center;
        }

        .signature-space {
            height: 60px;
            margin-top: 5px;
            margin-bottom: 5px;
            position: relative;
        }

        .signature-img {
            width: 80px;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }
    </style>
</head>

<body>
    <div class="container">
        <table class="header">
            <tr>
                <td style="width: 20%; text-align: left;"><img src="{{ $logoAlAzhar }}" alt="Logo" class="logo"></td>
                <td class="title-container">
                    <h3>YAYASAN ISLAM AL-AZHAR 43 GORONTALO</h3>
                    <h4>SLIP GAJI TENAGA KERJA</h4>
                </td>
                <td style="width: 20%; text-align: right;"><img src="{{ $logoYayasan }}" alt="Logo" class="logo">
                </td>
            </tr>
        </table>

        <div class="employee-details">
            <table>
                <tr>
                    <td width="15%"><strong>NAMA</strong></td>
                    <td width="35%">: {{ $data['karyawan']->nama }}</td>
                    <td width="15%"><strong>PERIODE</strong></td>
                    <td width="35%">: {{ \Carbon\Carbon::parse($data['bulan'])->translatedFormat('F Y') }}</td>
                </tr>
                <tr>
                    <td><strong>NIP</strong></td>
                    <td>: {{ $data['karyawan']->nip }}</td>
                    <td><strong>JABATAN</strong></td>
                    <td>: {{ $data['karyawan']->jabatan?->nama_jabatan ?? 'Jabatan Belum Diatur' }}</td>
                </tr>
            </table>
        </div>

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
                    <td class="text-right">{{ number_format($data['gaji_pokok'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Kehadiran ({{ $data['jumlah_kehadiran'] }} hari)</td>
                    <td class="text-right">{{ number_format($data['tunj_kehadiran'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Jabatan</td>
                    <td class="text-right">{{ number_format($data['tunj_jabatan'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Kinerja</td>
                    <td class="text-right">{{ number_format($data['tunj_kinerja'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Anak</td>
                    <td class="text-right">{{ number_format($data['tunj_anak'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Komunikasi</td>
                    <td class="text-right">{{ number_format($data['tunj_komunikasi'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Pengabdian</td>
                    <td class="text-right">{{ number_format($data['tunj_pengabdian'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Lembur</td>
                    <td class="text-right">{{ number_format($data['lembur'], 0, ',', '.') }}</td>
                </tr>

                @php
                    // Total pendapatan dihitung dari gaji bersih + potongan untuk akurasi
                    $totalPendapatan = $data['gaji_bersih'] + $data['potongan'];
                @endphp
                <tr class="total-row">
                    <td class="text-right">TOTAL PENDAPATAN</td>
                    <td class="text-right">{{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                </tr>

                <tr class="section-header">
                    <td colspan="2">B. POTONGAN</td>
                </tr>
                <tr>
                    <td>Potongan Lain-lain</td>
                    <td class="text-right">{{ number_format($data['potongan'], 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td class="text-right">TOTAL POTONGAN</td>
                    <td class="text-right">{{ number_format($data['potongan'], 0, ',', '.') }}</td>
                </tr>

                <tr class="grand-total-row">
                    <td class="text-right">GAJI BERSIH</td>
                    <td class="text-right">Rp {{ number_format($data['gaji_bersih'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <table class="signature-section">
            <tr>
                <td style="width: 65%;"></td>
                <td style="width: 35%;">
                    Gorontalo, {{ now()->translatedFormat('d F Y') }}<br>
                    Bendahara
                    <div class="signature-space">
                        @if (!empty($tandaTanganBendahara))
                            <img src="{{ $tandaTanganBendahara }}" alt="Tanda Tangan" class="signature-img">
                        @endif
                    </div>
                    <b>( {{ $bendaharaNama ?? '.....................' }} )</b>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
