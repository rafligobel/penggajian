<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Rincian Absensi - {{ $periode->translatedFormat('F Y') }}</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            /* PERUBAHAN: Ukuran font diperkecil */
            font-size: 7px;
        }

        .header-table {
            width: 100%;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }

        .header-table td {
            vertical-align: middle;
        }

        .header-img {
            /* PERUBAHAN: Ukuran logo diperkecil */
            width: 55px;
        }

        .header-text {
            text-align: center;
        }

        .header-text h3 {
            /* PERUBAHAN: Ukuran font header diperkecil */
            font-size: 14px;
            margin: 0;
        }

        .header-text h4 {
            font-size: 12px;
            margin: 0;
            font-weight: normal;
        }

        .header-text p {
            font-size: 9px;
            margin: 4px 0 0 0;
        }

        .content-table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 15px;
            table-layout: auto;
        }

        .content-table th,
        .content-table td {
            border: 1px solid #000;
            /* PERUBAHAN: Padding diperkecil */
            padding: 3px;
            text-align: center;
            word-wrap: break-word;
        }

        .content-table th {
            background-color: #e9ecef;
        }

        .content-table .karyawan-name {
            text-align: left;
        }

        .text-success {
            color: green;
        }

        .text-danger {
            color: red;
        }

        .fw-bold {
            font-weight: bold;
        }

        .footer {
            width: 250px;
            /* Lebar dikurangi */
            margin-left: auto;
            margin-right: 0;
            margin-top: 15px;
            /* Margin atas dikurangi */
            text-align: center;
            font-size: 10px;
            /* Font diperkecil */
        }

        .signature-space {
            height: 45px;
            /* Ruang TTD dikurangi */
            position: relative;
        }

        .signature-img {
            width: 60px;
            /* Lebar gambar TTD dikurangi */
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            top: -5px;
        }
    </style>
</head>

<body>
    {{-- KOP SURAT YANG SUDAH DIRAPIKAN --}}
    <table class="header-table">
        <tr>
            <td style="width: 20%; text-align: center;">
                <img src="{{ $logoAlAzhar }}" alt="Logo Al-Azhar" class="header-img">
            </td>
            <td style="width: 60%;" class="header-text">
                <h3>YAYASAN AL AZHAR GORONTALO</h3>
                <h4>LAPORAN RINCIAN ABSENSI KARYAWAN</h4>
                <p>Periode: {{ $periode->translatedFormat('F Y') }}</p>
            </td>
            <td style="width: 20%; text-align: center;">
                <img src="{{ $logoYayasan }}" alt="Logo Yayasan" class="header-img">
            </td>
        </tr>
    </table>

    {{-- TABEL KONTEN --}}
    <table class="content-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 3%;">No</th>
                <th rowspan="2" style="width: 15%;">Nama Karyawan</th>
                {{-- PENAMBAHAN KOLOM JABATAN --}}
                <th rowspan="2" class="w-nip">NIP</th>
                <th colspan="{{ $daysInMonth }}">Tanggal</th>
                <th colspan="2">Total</th>
            </tr>
            <tr>
                @for ($day = 1; $day <= $daysInMonth; $day++)
                    <th>{{ $day }}</th>
                @endfor
                <th style="width: 5%;">Hadir</th>
                <th style="width: 5%;">Alpha</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detailAbsensi as $index => $data)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="karyawan-name">{{ $data->nama }}</td>
                    {{-- PENAMBAHAN DATA JABATAN --}}
                    <td class="karyawan-name">{{ $data->nip ?? '-' }}</td>
                    @for ($day = 1; $day <= $daysInMonth; $day++)
                        <td class="{{ $data->daily_data[$day] === 'H' ? 'text-success' : 'text-danger' }}">
                            {{ $data->daily_data[$day] }}
                        </td>
                    @endfor
                    <td class="fw-bold text-success">{{ $data->total_hadir }}</td>
                    <td class="fw-bold text-danger">{{ $data->total_alpha }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TANDA TANGAN YANG SUDAH DIRAPIKAN --}}
    <div class="footer">
        <p>Gorontalo, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
        <p>Bendahara Umum,</p>
        <div class="signature-space">
            @if ($tandaTanganBendahara)
                <img src="{{ $tandaTanganBendahara }}" class="signature-img">
            @endif
        </div>
        <p class="fw-bold" style="text-decoration: underline;">{{ $bendaharaNama }}</p>
    </div>
</body>

</html>
