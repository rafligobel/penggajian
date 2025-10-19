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
                    <h3>SEKOLAH ISLAM AL AZHAR 43 GORONTALO</h3>
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
                    {{-- [PERUBAIKAN] colspan diubah --}}
                    <th rowspan="2" style="width: 3%;">No</th>
                    <th rowspan="2" class="text-left" style="width: 15%;">Nama Karyawan</th>
                    <th rowspan="2" class="text-left" style="width: 10%;">NIP</th>
                    <th rowspan="2">Kehadiran</th>
                    <th rowspan="2">Gaji Pokok</th>
                    <th colspan="7">Tunjangan</th>
                    <th rowspan="2">Potongan</th>
                    <th rowspan="2">Gaji Bersih</th>
                </tr>
                <tr>
                    <th>Jabatan</th>
                    <th>Kehadiran</th>
                    <th>Anak</th>
                    <th>Komunikasi</th>
                    <th>Pengabdian</th>
                    <th>Kinerja</th>
                    <th style="font-weight: bold; background-color: #d1ecf1;">Total Tunjangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($gajis as $gaji)
                    @php
                        // Hitung total tunjangan per karyawan
                        $tunjanganJabatan = $gaji->karyawan->jabatan->tunj_jabatan ?? 0;
                        $tunjanganKehadiran =
                            ($kehadiranData[$gaji->id] ?? 0) * ($gaji->tunjanganKehadiran->jumlah_tunjangan ?? 0);
                        $totalTunjangan =
                            $tunjanganJabatan +
                            $tunjanganKehadiran +
                            $gaji->tunj_anak +
                            $gaji->tunj_komunikasi +
                            $gaji->tunj_pengabdian +
                            $gaji->tunj_kinerja +
                            $gaji->lembur;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="text-left">{{ $gaji->karyawan->nama }}</td>
                        {{-- [PERUBAIKAN] Menampilkan NIP --}}
                        <td class="text-left">{{ $gaji->karyawan->nip ?? '-' }}</td>
                        <td class="text-center">{{ $kehadiranData[$gaji->id] ?? 0 }} Hari</td>
                        <td>{{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                        <td>{{ number_format($tunjanganJabatan, 0, ',', '.') }}</td>
                        <td>{{ number_format($tunjanganKehadiran, 0, ',', '.') }}</td>
                        <td>{{ number_format($gaji->tunj_anak, 0, ',', '.') }}</td>
                        <td>{{ number_format($gaji->tunj_komunikasi, 0, ',', '.') }}</td>
                        <td>{{ number_format($gaji->tunj_pengabdian, 0, ',', '.') }}</td>
                        <td>{{ number_format($gaji->tunj_kinerja, 0, ',', '.') }}</td>
                        {{-- [PERUBAIKAN] Menampilkan Total Tunjangan per karyawan --}}
                        <td style="font-weight: bold; background-color: #f8f9fa;">
                            {{ number_format($totalTunjangan, 0, ',', '.') }}</td>
                        <td>({{ number_format($gaji->potongan, 0, ',', '.') }})</td>
                        <td style="font-weight: bold;">{{ number_format($gaji->gaji_bersih, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center" style="padding: 20px;">Tidak ada data gaji untuk
                            dipilih.</td>
                    </tr>
                @endforelse
            </tbody>

            @if ($gajis->isNotEmpty())
                <tfoot>
                    <tr class="footer-row">
                        <td colspan="4" class="text-center">TOTAL (Rp)</td>
                        <td>{{ number_format($totals['gaji_pokok'], 0, ',', '.') }}</td>
                        <td>{{ number_format($totals['tunj_jabatan'], 0, ',', '.') }}</td>
                        <td>{{ number_format($totals['tunj_kehadiran'], 0, ',', '.') }}</td>
                        <td>{{ number_format($totals['tunj_anak'], 0, ',', '.') }}</td>
                        <td>{{ number_format($totals['tunj_komunikasi'], 0, ',', '.') }}</td>
                        <td>{{ number_format($totals['tunj_pengabdian'], 0, ',', '.') }}</td>
                        <td>{{ number_format($totals['tunj_kinerja'], 0, ',', '.') }}</td>
                        {{-- [PERUBAIKAN] Menampilkan Grand Total Tunjangan --}}
                        <td style="background-color: #d1ecf1;">
                            {{ number_format($totals['total_tunjangan'], 0, ',', '.') }}</td>
                        <td>({{ number_format($totals['potongan'], 0, ',', '.') }})</td>
                        <td>{{ number_format($totals['gaji_bersih'], 0, ',', '.') }}</td>
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
