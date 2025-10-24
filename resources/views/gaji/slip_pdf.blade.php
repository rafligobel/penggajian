<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $gaji->karyawan->nama ?? 'N/A' }} -
        {{ $gaji->bulan->translatedFormat('F Y') }}</title>
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
                    <h3>SEKOLAH ISLAM AL AZHAR 43 GORONTALO</h3>
                    <h4>SLIP GAJI PEGAWAI</h4>
                </td>
                <td style="width: 20%; text-align: right;"><img src="{{ $logoYayasan }}" alt="Logo" class="logo">
                </td>
            </tr>
        </table>

        <div class="employee-details">
            <table class="info-section">
                <tr>
                    <td style="width: 35%;">Periode Gaji</td>
                    <td style="width: 65%;">: {{ $gaji->bulan->translatedFormat('F Y') }}</td>
                </tr>
                <tr>
                    <td>Nama Karyawan</td>
                    <td style="width: 65%;">: {{ $gaji->karyawan->nama ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>NP</td>
                    <td style="width: 65%;">: {{ $gaji->karyawan->nip ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Jabatan</td>
                    <td style="width: 65%;">: {{ $gaji->karyawan->jabatan->nama_jabatan ?? 'N/A' }}</td>
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
                    <td class="text-right">{{ $data['gaji_pokok_string'] }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Kehadiran ({{ $data['total_kehadiran'] }} hari)</td>
                    <td class="text-right">{{ $data['total_tunjangan_kehadiran_string'] }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Jabatan</td>
                    <td class="text-right">{{ $data['tunj_jabatan_string'] }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Kinerja</td>
                    <td class="text-right">{{ $data['tunj_kinerja_string'] }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Anak</td>
                    <td class="text-right">{{ $data['tunj_anak_string'] }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Komunikasi</td>
                    <td class="text-right">{{ $data['tunj_komunikasi_string'] }}</td>
                </tr>
                <tr>
                    <td>Tunjangan Pengabdian</td>
                    <td class="text-right">{{ $data['tunj_pengabdian_string'] }}</td>
                </tr>
                <tr>
                    <td>Lembur</td>
                    <td class="text-right">{{ $data['lembur_string'] }}</td>
                </tr>

                @php
                    // PERBAIKAN KRITIS: MENGHITUNG GROSS SALARY DARI KOMPONEN NUMERIK
                    // TOTAL PENDAPATAN (Gaji Kotor) = Semua Komponen Positif
                    $totalPendapatan =
                        (float) $data['gaji_pokok'] +
                        (float) $data['tunj_jabatan'] +
                        (float) $data['tunj_kehadiran'] +
                        (float) $data['tunj_anak'] +
                        (float) $data['tunj_komunikasi'] +
                        (float) $data['tunj_pengabdian'] +
                        (float) $data['tunj_kinerja'] +
                        (float) $data['lembur'];
                @endphp
                <tr class="total-row">
                    <td class="text-right">TOTAL PENDAPATAN</td>
                    {{-- Tampilkan hasil perhitungan, lalu format --}}
                    <td class="text-right">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                </tr>

                <tr class="section-header">
                    <td colspan="2">B. POTONGAN</td>
                </tr>
                <tr>
                    <td>Potongan Lain-lain</td>
                    {{-- Menggunakan kunci string yang benar --}}
                    <td class="text-right">{{ $data['potongan_string'] }}</td>
                </tr>
                <tr class="total-row">
                    <td class="text-right">TOTAL POTONGAN</td>
                    {{-- Menggunakan kunci string yang benar --}}
                    <td class="text-right">{{ $data['potongan_string'] }}</td>
                </tr>

                <tr class="grand-total-row">
                    <td class="text-right">GAJI BERSIH</td>
                    {{-- TAMPILKAN STRING FINAL YANG SUDAH DIFORMAT --}}
                    <td class="text-right">{{ $data['gaji_bersih_string'] }}</td>
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
