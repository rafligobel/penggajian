<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Rincian - {{ $selectedKaryawan->nama }}</title>
    <style>
        /* ... CSS tidak ada perubahan ... */
        body {
            font-family: 'Helvetica', sans-serif;
            color: #333;
            font-size: 12px;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        .header-table,
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .header-text {
            text-align: center;
        }

        .header-text h3,
        .header-text h4 {
            margin: 0;
        }

        .header-text p {
            margin: 5px 0;
            font-size: 10px;
        }

        .logo {
            width: 60px;
            height: auto;
        }

        .text-right {
            text-align: right;
        }

        .salary-details {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }

        .salary-details th,
        .salary-details td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .salary-details th {
            background-color: #f8f9fa;
            text-align: left;
        }

        .section-header td {
            background-color: #e9ecef;
            font-weight: bold;
        }

        .total-row td,
        .grand-total-row td {
            font-weight: bold;
        }

        .grand-total-row {
            background-color: #d1ecf1;
        }

        .footer {
            margin-top: 40px;
            width: 100%;
        }

        .signature-section {
            float: right;
            width: 250px;
            text-align: center;
        }

        .signature-section .signature-space {
            height: 60px;
            border-bottom: 1px solid #333;
            margin-bottom: 5px;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>

<body>
    <div class="container">
        {{-- KOP SURAT (Tidak ada perubahan) --}}
        <table class="header-table">
            <tr>
                <td style="width: 15%; text-align: center;"><img src="{{ $logoYayasan }}" alt="Logo Yayasan"
                        class="logo"></td>
                <td style="width: 70%;" class="header-text">
                    <h3>YAYASAN PENDIDIKAN AL-AZHAR GORONTALO</h3>
                    <h4>LAPORAN RINCIAN GAJI KARYAWAN</h4>
                    <p>Jl. Dr. H. Zainal Umar Sidiki, S.H, Kota Gorontalo</p>
                </td>
                <td style="width: 15%; text-align: center;"><img src="{{ $logoAlAzhar }}" alt="Logo Al-Azhar"
                        class="logo"></td>
            </tr>
        </table>
        <hr style="border: 0; border-top: 2px solid #333; margin-top: 10px;">

        {{-- INFO KARYAWAN (Tidak ada perubahan) --}}
        <table class="info-table" style="margin-top: 20px;">
            <tr>
                <td style="width: 15%;"><strong>Nama Karyawan</strong></td>
                <td style="width: 35%;">: {{ $selectedKaryawan->nama }}</td>
                <td style="width: 15%;"><strong>Periode Laporan</strong></td>
                <td style="width: 35%;">: {{ $tanggalMulai }} s/d {{ $tanggalSelesai }}</td>
            </tr>
            <tr>
                <td><strong>NIP</strong></td>
                <td>: {{ $selectedKaryawan->nip }}</td>
                <td><strong>Jabatan</strong></td>
                <td>: {{ $selectedKaryawan->jabatan->nama_jabatan ?? 'N/A' }}</td>
            </tr>
        </table>

        <hr style="margin-top: 20px; border: 0; border-top: 1px solid #ccc;">

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
                        <td>Total Gaji Pokok</td>
                        <td class="text-right">{{ number_format($totalGajiPokok, 0, ',', '.') }}</td>
                    </tr>

                    {{-- === [PERBAIKAN DIMULAI DI SINI] === --}}
                    {{-- Loop untuk menampilkan setiap jenis tunjangan, termasuk yang bernilai 0 --}}
                    @foreach ($totalPerTunjangan as $namaTunjangan => $total)
                        <tr>
                            <td style="padding-left: 20px;">{{ $namaTunjangan }}</td>
                            <td class="text-right">{{ number_format($total, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    {{-- === [PERBAIKAN SELESAI] === --}}

                    <tr class="total-row">
                        <td class="text-right">TOTAL PENDAPATAN</td>
                        <td class="text-right">{{ number_format($totalGajiPokok + $totalSemuaTunjangan, 0, ',', '.') }}
                        </td>
                    </tr>

                    <tr class="section-header">
                        <td colspan="2">B. POTONGAN</td>
                    </tr>
                    <tr>
                        <td>Total Potongan Lain-lain</td>
                        <td class="text-right">({{ number_format($totalPotongan, 0, ',', '.') }})</td>
                    </tr>
                    <tr class="total-row">
                        <td class="text-right">TOTAL POTONGAN</td>
                        <td class="text-right">({{ number_format($totalPotongan, 0, ',', '.') }})</td>
                    </tr>

                    <tr class="grand-total-row">
                        <td class="text-right">TOTAL GAJI BERSIH DITERIMA</td>
                        <td class="text-right">Rp {{ number_format($totalGajiBersih, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <p style="text-align:center; margin-top: 30px; font-style: italic;">Tidak ada data gaji untuk ditampilkan
                pada periode ini.</p>
        @endif

        {{-- TANDA TANGAN (Tidak ada perubahan) --}}
        <div class="footer clearfix">
            <div class="signature-section">
                <p>Gorontalo, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
                <p>Bendahara Yayasan</p>
                @if ($tandaTanganBendahara)
                    <img src="{{ $tandaTanganBendahara }}" alt="Tanda Tangan" style="height: 60px; width: auto;">
                @else
                    <div class="signature-space"></div>
                @endif
                <p style="font-weight: bold; text-decoration: underline;">{{ $bendaharaNama }}</p>
            </div>
        </div>
    </div>
</body>

</html>
