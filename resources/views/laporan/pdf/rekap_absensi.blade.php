{{-- resources/views/laporan/pdf/rekap_absensi.blade.php --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Rincian Absensi - {{ $periode->translatedFormat('F Y') }}</title>
    <style>
        /* ... (style Anda sudah benar, tidak perlu diubah) ... */
        body {
            font-family: 'Helvetica', sans-serif;
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
            width: 55px;
        }

        .header-text {
            text-align: center;
        }

        .header-text h3 {
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
            margin-left: auto;
            margin-right: 0;
            margin-top: 15px;
            text-align: center;
            font-size: 10px;
        }

        .signature-space {
            height: 45px;
            position: relative;
        }

        .signature-img {
            width: 60px;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            top: -5px;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td style="width: 20%; text-align: center;"><img src="{{ $logoAlAzhar }}" alt="Logo Al-Azhar"
                    class="header-img"></td>
            <td style="width: 60%;" class="header-text">
                <h3>SEKOLAH ISLAM AL AZHAR 43 GORONTALO</h3>
                <h4>LAPORAN RINCIAN ABSENSI PEGAWAI</h4>
                <p>Periode: {{ $periode->translatedFormat('F Y') }}</p>
            </td>
            <td style="width: 20%; text-align: center;"><img src="{{ $logoYayasan }}" alt="Logo Yayasan"
                    class="header-img"></td>
        </tr>
    </table>

    <table class="content-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 3%;">No</th>
                <th rowspan="2" style="width: 15%;" class="karyawan-name">Nama Pegawai</th>
                <th rowspan="2" style="width: 10%;">NP</th>
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
            {{-- [PERBAIKAN] Mengganti $data-> (objek) menjadi $data[...] (array) agar sesuai struktur data AbsensiService --}}
            @foreach ($detailAbsensi as $index => $data)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    {{-- [PERBAIKAN] Menampilkan nama karyawan dari array --}}
                    <td class="karyawan-name">{{ $data['nama'] ?? '-' }}</td>
                    {{-- [PERBAIKAN] Menampilkan NIP dari array --}}
                    <td>{{ $data['nip'] ?? '-' }}</td>

                    @for ($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            // [PERBAIKAN] Mengambil status dari array 'detail' berdasarkan key hari, dan 'status' di dalamnya
                            $status = $data['detail'][$day]['status'] ?? 'L'; // Default ke 'Libur' jika tidak ada data
                        @endphp
                        <td class="{{ $status === 'H' ? 'text-success' : 'text-danger' }}">
                            {{ $status }}
                        </td>
                    @endfor

                    {{-- [PERBAIKAN] Mengambil total dari array 'summary' --}}
                    <td class="fw-bold text-success">{{ $data['summary']['total_hadir'] }}</td>
                    <td class="fw-bold text-danger">{{ $data['summary']['total_alpha'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        {{-- ... (Bagian footer Anda sudah benar) ... --}}
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
