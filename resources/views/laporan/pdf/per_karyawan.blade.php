<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Rincian Karyawan</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 10px;
            color: #333;
        }

        .header-table,
        .main-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            padding: 5px;
            vertical-align: middle;
        }

        .logo {
            width: 60px;
        }

        .kop-surat {
            text-align: center;
        }

        .kop-surat h3 {
            font-size: 16px;
            margin: 0;
        }

        .kop-surat p {
            font-size: 11px;
            margin: 2px 0;
        }

        hr {
            border: 0;
            border-top: 2px solid #333;
            margin: 15px 0;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 3px;
        }

        .main-table th,
        .main-table td {
            border: 1px solid #ccc;
            padding: 6px;
        }

        .main-table th {
            background-color: #f2f2f2;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .fw-bold {
            font-weight: bold;
        }

        .signature-section {
            margin-top: 30px;
            width: 100%;
        }

        .signature-section td {
            border: none;
            text-align: center;
            padding: 10px;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td style="width: 15%;"><img src="{{ $logoYayasan }}" alt="Logo Yayasan" class="logo"></td>
            <td style="width: 70%;" class="kop-surat">
                <h3>SEKOLAH ISLAM AL AZHAR 43 GORONTALO</h3>
                <p>LAPORAN GAJI PEGAWAI</p>
            </td>
            <td style="width: 15%; text-align: right;"><img src="{{ $logoAlAzhar }}" alt="Logo Al-Azhar" class="logo">
            </td>
        </tr>
    </table>
    <hr>
    <table class="info-table">
        <tr>
            <td style="width: 15%;"><strong>Nama Pegawai</strong></td>
            <td style="width: 2%;">:</td>
            <td>{{ $karyawan->nama }}</td>
        </tr>
        <tr>
            <td><strong>NP</strong></td>
            <td>:</td>
            <td>{{ $karyawan->nip }}</td>
        </tr>
        <tr>
            <td><strong>Jabatan</strong></td>
            <td>:</td>
            <td>{{ $karyawan->jabatan?->nama_jabatan ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Periode Laporan</strong></td>
            <td>:</td>
            <td>{{ $tanggalMulai->translatedFormat('F Y') }} s.d. {{ $tanggalSelesai->translatedFormat('F Y') }}</td>
        </tr>
    </table>

    <h4>Riwayat Gaji & Absensi Bulanan</h4>
    <table class="main-table">
        <thead>
            <tr>
                <th>Periode Gaji</th>
                <th>Hadir</th>
                <th>Alpha</th>
                <th>Gaji Pokok</th>
                <th>Total Tunjangan</th>
                <th>Potongan</th>
                <th class="fw-bold">Gaji Bersih</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($gajis as $gaji)
                <tr>
                    <td class="text-center">{{ \Carbon\Carbon::parse($gaji->bulan)->translatedFormat('F Y') }}</td>
                    <td class="text-center">{{ $gaji->hadir ?? 0 }} Hari</td>
                    <td class="text-center">{{ $gaji->alpha ?? 0 }} Hari</td>
                    <td class="text-right">Rp {{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($gaji->total_tunjangan, 0, ',', '.') }}</td>
                    <td class="text-right">(Rp {{ number_format($gaji->potongan, 0, ',', '.') }})</td>
                    <td class="text-right fw-bold">Rp {{ number_format($gaji->gaji_bersih, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data gaji pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signature-section">
        <tr>
            <td style="width: 65%;"></td>
            <td style="width: 35%;">
                Gorontalo, {{ now()->translatedFormat('d F Y') }}<br>
                Bendahara
                <div style="height: 60px; margin: 5px 0;">
                    @if (!empty($tandaTanganBendahara))
                        <img src="{{ $tandaTanganBendahara }}" alt="Tanda Tangan" style="height: 100%; width: auto;">
                    @endif
                </div>
                <b style="text-decoration: underline;">{{ $bendaharaNama }}</b>
            </td>
        </tr>
    </table>
</body>

</html>
