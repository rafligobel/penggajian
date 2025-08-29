<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Gaji Bulanan - {{ \Carbon\Carbon::parse($periode)->translatedFormat('F Y') }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8pt;
            color: #333;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 10px;
        }

        .header .logo {
            width: 50px;
            vertical-align: middle;
        }

        .header h3 {
            font-size: 14pt;
            margin: 0;
        }

        .header h4 {
            font-size: 12pt;
            margin: 0;
            color: #333;
        }

        .main-table th,
        .main-table td {
            border: 1px solid #ccc;
            padding: 4px 5px;
            text-align: right;
            /* Default alignment for numbers */
        }

        .main-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
        }

        .main-table .text-left {
            text-align: left;
        }

        .main-table .text-center {
            text-align: center;
        }

        .main-table tfoot .footer-row {
            font-weight: bold;
            background-color: #e9ecef;
        }

        .signature-section {
            margin-top: 30px;
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
                <td style="width: 15%; text-align: left;"><img src="{{ $logoAlAzhar }}" alt="Logo" class="logo"></td>
                <td>
                    <h3>YAYASAN ISLAM AL-AZHAR 43 GORONTALO</h3>
                    <h4>LAPORAN GAJI TENAGA KERJA</h4>
                    <span>Periode: {{ \Carbon\Carbon::parse($periode)->translatedFormat('F Y') }}</span>
                </td>
                <td style="width: 15%; text-align: right;"><img src="{{ $logoYayasan }}" alt="Logo" class="logo">
                </td>
            </tr>
        </table>

        {{-- TABEL GAJI UTAMA --}}
        <table class="main-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 3%;">No</th>
                    <th rowspan="2" class="text-left" style="width: 15%;">Nama Karyawan</th>
                    {{-- KOLOM JABATAN BARU --}}
                    <th rowspan="2" class="text-left" style="width: 12%;">Jabatan</th>
                    <th rowspan="2">Kehadiran</th>
                    <th rowspan="2">Gaji Pokok</th>
                    <th colspan="5">Tunjangan</th>
                    <th rowspan="2">Lainnya</th>
                    <th rowspan="2">Potongan</th>
                    <th rowspan="2">Gaji Bersih</th>
                </tr>
                <tr>
                    <th>Jabatan</th>
                    <th>Anak</th>
                    <th>Komunikasi</th>
                    <th>Pengabdian</th>
                    <th>Kinerja</th>
                </tr>
            </thead>
            <tbody>
                @forelse($gajis as $gaji)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="text-left">{{ $gaji->karyawan->nama }}</td>
                        {{-- DATA JABATAN BARU --}}
                        <td class="text-left">{{ $gaji->karyawan->jabatan->nama_jabatan ?? '-' }}</td>
                        <td class="text-center">
                            {{ $kehadiranData[$gaji->karyawan_id]->total_hadir ?? 0 }} Hari
                        </td>
                        <td>{{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                        <td>{{ number_format($gaji->tunj_jabatan, 0, ',', '.') }}</td>
                        <td>{{ number_format($gaji->tunj_anak, 0, ',', '.') }}</td>
                        <td>{{ number_format($gaji->tunj_komunikasi, 0, ',', '.') }}</td>
                        <td>{{ number_format($gaji->tunj_pengabdian, 0, ',', '.') }}</td>
                        <td>{{ number_format($gaji->tunj_kinerja, 0, ',', '.') }}</td>
                        <td>{{ number_format($gaji->pendapatan_lainnya, 0, ',', '.') }}</td>
                        <td>({{ number_format($gaji->potongan, 0, ',', '.') }})</td>
                        <td style="font-weight: bold;">{{ number_format($gaji->gaji_bersih, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        {{-- Colspan disesuaikan menjadi 13 --}}
                        <td colspan="13" class="text-center" style="padding: 20px;">Tidak ada data gaji untuk periode
                            ini.</td>
                    </tr>
                @endforelse
            </tbody>

            @if ($gajis->isNotEmpty())
                <tfoot>
                    <tr class="footer-row">
                        {{-- Colspan disesuaikan menjadi 4 --}}
                        <td colspan="4" class="text-center">TOTAL (Rp)</td>
                        <td>{{ number_format($totals->total_gaji_pokok, 0, ',', '.') }}</td>
                        <td>{{ number_format($totals->total_tunj_jabatan, 0, ',', '.') }}</td>
                        <td>{{ number_format($totals->total_tunj_anak, 0, ',', '.') }}</td>
                        <td>{{ number_format($totals->total_tunj_komunikasi, 0, ',', '.') }}</td>
                        <td>{{ number_format($totals->total_tunj_pengabdian, 0, ',', '.') }}</td>
                        <td>{{ number_format($totals->total_tunj_kinerja, 0, ',', '.') }}</td>
                        <td>{{ number_format($totals->total_pendapatan_lainnya, 0, ',', '.') }}</td>
                        <td>({{ number_format($totals->total_potongan, 0, ',', '.') }})</td>
                        <td>{{ number_format($totals->total_gaji_bersih, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>

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
