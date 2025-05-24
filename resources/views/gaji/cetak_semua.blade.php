<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        .kop { text-align: center; }
        .logo { width: 80px; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        h4, h5 { margin: 4px 0; }
    </style>
</head>
<body>

<div class="kop">
    <table>
        <tr>
            <td width="15%"><img src="{{ public_path('logo/kiri.png') }}" class="logo"></td>
            <td>
                <h4>YAYASAN AL-AZHAR 43 GORONTALO</h4>
                <h5><u>DAFTAR GAJI PEGAWAI</u></h5>
                <p>Periode: {{ now()->format('F Y') }}</p>
            </td>
            <td width="15%"><img src="{{ public_path('logo/kanan.png') }}" class="logo"></td>
        </tr>
    </table>
</div>

<table>
    <thead>
        <tr>
            <th rowspan="2">No</th>
            <th rowspan="2">Nama</th>
            <th rowspan="2">Jabatan</th>
            <th colspan="2">Gaji Pokok</th>
            <th colspan="6">Tunjangan</th>
            <th colspan="2">Tambahan</th>
            <th rowspan="2">Potongan</th>
            <th rowspan="2">Total</th>
        </tr>
        <tr>
            <th>Pokok</th>
            <th>Subtotal</th>
            <th>Kehadiran</th>
            <th>Anak</th>
            <th>Komunikasi</th>
            <th>Pengabdian</th>
            <th>Jabatan</th>
            <th>Kinerja</th>
            <th>Lembur</th>
            <th>Kelebihan Jam</th>
        </tr>
    </thead>
    <tbody>
        @php
            $totalKeseluruhan = 0;
        @endphp

        @foreach($karyawans as $no => $karyawan)
            @php
                $tunjangan = 
                    ($karyawan->tunj_kehadiran ?? 0) +
                    ($karyawan->tunj_anak ?? 0) +
                    ($karyawan->tunj_komunikasi ?? 0) +
                    ($karyawan->tunj_pengabdian ?? 0) +
                    ($karyawan->tunj_jabatan ?? 0) +
                    ($karyawan->tunj_kinerja ?? 0);

                $tambahan = ($karyawan->lembur ?? 0) + ($karyawan->kelebihan_jam ?? 0);
                $potongan = $karyawan->potongan ?? 0;

                $total = ($karyawan->gaji_pokok ?? 0) + $tunjangan + $tambahan - $potongan;
                $totalKeseluruhan += $total;
            @endphp
            <tr>
                <td>{{ $no + 1 }}</td>
                <td class="text-left">{{ $karyawan->nama }}</td>
                <td class="text-left">{{ $karyawan->jabatan ?? '-' }}</td>
                <td class="text-right">{{ number_format($karyawan->gaji_pokok ?? 0) }}</td>
                <td class="text-right">{{ number_format(($karyawan->gaji_pokok ?? 0)) }}</td>
                <td class="text-right">{{ number_format($karyawan->tunj_kehadiran ?? 0) }}</td>
                <td class="text-right">{{ number_format($karyawan->tunj_anak ?? 0) }}</td>
                <td class="text-right">{{ number_format($karyawan->tunj_komunikasi ?? 0) }}</td>
                <td class="text-right">{{ number_format($karyawan->tunj_pengabdian ?? 0) }}</td>
                <td class="text-right">{{ number_format($karyawan->tunj_jabatan ?? 0) }}</td>
                <td class="text-right">{{ number_format($karyawan->tunj_kinerja ?? 0) }}</td>
                <td class="text-right">{{ number_format($karyawan->lembur ?? 0) }}</td>
                <td class="text-right">{{ number_format($karyawan->kelebihan_jam ?? 0) }}</td>
                <td class="text-right">{{ number_format($potongan) }}</td>
                <td class="text-right"><strong>{{ number_format($total) }}</strong></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="14" class="text-right"><strong>Total Keseluruhan</strong></td>
            <td class="text-right"><strong>{{ number_format($totalKeseluruhan) }}</strong></td>
        </tr>
    </tfoot>
</table>

</body>
</html>
