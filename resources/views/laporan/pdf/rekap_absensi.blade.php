<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Rincian Absensi - {{ $periode->translatedFormat('F Y') }}</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 8px;
        }

        /* --- PERBAIKAN KOMPONEN HEADER (KOP SURAT & LOGO) --- */
        .header-table {
            width: 100%;
            border-bottom: 1px solid #000;
            /* Menambahkan garis bawah kop surat */
            padding-bottom: 8px;
        }

        .header-table td {
            vertical-align: middle;
            /* Memastikan logo dan teks sejajar di tengah */
        }

        .header-img {
            width: 65px;
            /* Sedikit menyesuaikan ukuran logo */
        }

        .header-text {
            text-align: center;
        }

        .header-text h3 {
            font-size: 16px;
            margin: 0;
        }

        .header-text h4 {
            font-size: 14px;
            margin: 0;
            font-weight: normal;
        }

        .header-text p {
            font-size: 10px;
            margin: 5px 0 0 0;
        }

        /* --- AKHIR PERBAIKAN HEADER --- */

        .content-table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            table-layout: auto;
        }

        .content-table th,
        .content-table td {
            border: 1px solid #000;
            padding: 4px;
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

        /* --- PERBAIKAN KOMPONEN FOOTER (TANDA TANGAN) --- */
        .footer {
            width: 300px;
            /* Memberi lebar tetap agar tidak terlalu ke kanan */
            margin-left: auto;
            /* Mendorong blok ke kanan */
            margin-right: 0;
            margin-top: 20px;
            text-align: center;
            /* Pusatkan teks di dalam blok footer */
            font-size: 11px;
        }

        .signature-space {
            height: 55px;
            /* Ruang untuk tanda tangan */
            position: relative;
            /* Diperlukan agar gambar TTD bisa diposisikan */
        }

        .signature-img {
            width: 70px;
            /* Lebar gambar dikurangi agar lebih pas */
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            top: -5px;
            /* Posisi disesuaikan agar pas di tengah ruang */
        }

        /* --- AKHIR PERBAIKAN FOOTER --- */
    </style>
</head>

<body>
    {{-- KOP SURAT YANG SUDAH DIRAPIKAN --}}
    <table class="header-table">
        <tr>
            <td style="width: 20%; text-align: center;">
                <img src="{{ $logoYayasan }}" alt="Logo Yayasan" class="header-img">
            </td>
            <td style="width: 60%;" class="header-text">
                <h3>YAYASAN AL AZHAR GORONTALO</h3>
                <h4>LAPORAN RINCIAN ABSENSI KARYAWAN</h4>
                <p>Periode: {{ $periode->translatedFormat('F Y') }}</p>
            </td>
            <td style="width: 20%; text-align: center;">
                <img src="{{ $logoAlAzhar }}" alt="Logo Al-Azhar" class="header-img">
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
                <th rowspan="2" style="width: 15%;">Jabatan</th>
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
                    <td class="karyawan-name">{{ $data->jabatan?->nama_jabatan ?? 'N/A' }}</td>
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
