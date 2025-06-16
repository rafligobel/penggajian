<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Gaji Bulanan - {{ $periode }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px;
            text-align: right;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .kop-table {
            width: 100%;
            border: none;
            margin-bottom: 20px;
        }

        .kop-table td {
            border: none;
            vertical-align: middle;
            text-align: center;
        }

        .kop-table .logo {
            width: 70px;
        }

        .kop-table h3,
        .kop-table h4 {
            margin: 0;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            font-weight: bold;
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>

    <table class="kop-table">
        <tr>
            <td style="width: 20%; text-align: left;"><img src="{{ $logoKiri }}" class="logo"></td>
            <td style="width: 60%;">
                <h3>YAYASAN AL-AZHAR 43 GORONTALO</h3>
                <h4>DAFTAR REKAPITULASI GAJI PEGAWAI</h4>
                <span>Periode: {{ $periode }}</span>
            </td>
            <td style="width: 20%; text-align: right;"><img src="{{ $logoKanan }}" class="logo"></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th class="text-left">Nama Karyawan</th>
                <th class="text-left">Jabatan</th>
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
                    <td class="footer">{{ number_format($gaji->gaji_bersih, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data gaji untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>

        @if ($gajis->isNotEmpty())
            <tfoot>
                <tr class="footer">
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

</body>

</html>
