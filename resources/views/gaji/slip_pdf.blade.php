<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Slip Gaji</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        .kop {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid black;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .kop img {
            height: 60px;
        }

        .kop .judul {
            text-align: center;
            flex-grow: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            padding: 6px;
            border: 1px solid #000;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="kop">
        <img src="{{ public_path('logo/kiri.png') }}" alt="Logo Kiri">
        <div class="judul">
            <h3>YAYASAN ISLAM AL-AZHAR 43 GORONTALO</h3>
            <p>SLIP GAJI PEGAWAI</p>
        </div>
        <img src="{{ public_path('logo/kanan.png') }}" alt="Logo Kanan">
    </div>

    <p><strong>Nama:</strong> {{ $gaji->karyawan->nama }}</p>
    <p><strong>Tanggal:</strong> {{ $gaji->created_at->format('d-m-Y') }}</p>

    <table>
        <tr>
            <th colspan="2">Rincian Gaji</th>
        </tr>
        <tr>
            <td>Gaji Pokok</td>
            <td class="text-right">Rp {{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Tunjangan Kehadiran</td>
            <td class="text-right">Rp {{ number_format($gaji->tunj_kehadiran, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Tunjangan Anak</td>
            <td class="text-right">Rp {{ number_format($gaji->tunj_anak, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Tunjangan Komunikasi</td>
            <td class="text-right">Rp {{ number_format($gaji->tunj_komunikasi, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Tunjangan Pengabdian</td>
            <td class="text-right">Rp {{ number_format($gaji->tunj_pengabdian, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Tunjangan Jabatan</td>
            <td class="text-right">Rp {{ number_format($gaji->tunj_jabatan, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Tunjangan Kinerja</td>
            <td class="text-right">Rp {{ number_format($gaji->tunj_kinerja, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Lembur</td>
            <td class="text-right">Rp {{ number_format($gaji->lembur, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Kelebihan Jam</td>
            <td class="text-right">Rp {{ number_format($gaji->kelebihan_jam, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Potongan</td>
            <td class="text-right">Rp {{ number_format($gaji->potongan, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Total Gaji Bersih</th>
            <th class="text-right">Rp
                {{ number_format(
                    $gaji->gaji_pokok +
                        $gaji->tunj_kehadiran +
                        $gaji->tunj_anak +
                        $gaji->tunj_komunikasi +
                        $gaji->tunj_pengabdian +
                        $gaji->tunj_jabatan +
                        $gaji->tunj_kinerja +
                        $gaji->lembur +
                        $gaji->kelebihan_jam -
                        $gaji->potongan,
                    0,
                    ',',
                    '.',
                ) }}
            </th>
        </tr>
    </table>

    <br><br>
    <div style="text-align: right">
        <p>Gorontalo, {{ now()->format('d-m-Y') }}</p>
        <p><strong>Bendahara</strong></p><br><br>
        <p>( _____________________ )</p>
    </div>

</body>

</html>
