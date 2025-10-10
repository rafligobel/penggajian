<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Karyawan;
use App\Models\Gaji;
use App\Models\Absensi;
use App\Models\SesiAbsensi;
use App\Models\Jabatan;
use App\Models\TunjanganKehadiran;
use Carbon\Carbon;
use Exception;

class ImportGajiCommand extends Command
{
    protected $signature = 'import:gaji {bulan} {tahun} {--file=} {--update-karyawan}';
    protected $description = 'Import data karyawan, absensi, dan gaji MENTAH dari file CSV.';

    public function handle()
    {
        $this->info('>>> MENJALANKAN SCRIPT VERSI AKHIR (MODEL FIXED) <<<');

        $bulanInput = $this->argument('bulan');
        $tahun = $this->argument('tahun');
        $filePath = $this->option('file');
        $shouldUpdateKaryawan = $this->option('update-karyawan');

        if (!$filePath || !file_exists($filePath)) {
            $this->error('File tidak ditemukan! Sertakan path yang benar: --file=<path_ke_file>');
            return 1;
        }

        $bulanAngka = $this->getBulanAngka($bulanInput);
        if (is_null($bulanAngka)) {
            $this->error("Nama atau nomor bulan '{$bulanInput}' tidak valid.");
            return 1;
        }

        $bulanString = Carbon::create($tahun, $bulanAngka, 1)->format('Y-m');
        $this->info("Memulai proses import untuk bulan: {$bulanString} dari file: {$filePath}");

        $dataCsv = $this->readCsv($filePath);
        if (empty($dataCsv)) {
            $this->error('File CSV kosong atau tidak bisa dibaca.');
            return 1;
        }

        $defaultTunjangan = TunjanganKehadiran::firstOrCreate(
            ['id' => 1],
            ['jenis_tunjangan' => 'Standar (Otomatis)', 'jumlah_tunjangan' => 25000]
        );
        $defaultTunjanganId = $defaultTunjangan->id;
        $this->info("-> Menggunakan Tunjangan Kehadiran Default (ID: {$defaultTunjanganId})");

        DB::beginTransaction();
        try {
            foreach ($dataCsv as $row) {
                $tunjanganJabatanValue = (int) $this->cleanNumeric($row['TJ. Jabatan'] ?? 0);
                $jabatan = Jabatan::firstOrCreate(
                    ['tunj_jabatan' => $tunjanganJabatanValue],
                    ['nama_jabatan' => 'Jabatan Otomatis ' . number_format($tunjanganJabatanValue)]
                );

                $karyawan = $this->prosesKaryawan($row, $jabatan->id, $shouldUpdateKaryawan);
                $this->info("Memproses: '{$karyawan->nama}' (NIP: {$karyawan->nip})");

                $this->prosesAbsensi($karyawan, (int)($row['Jumlah Kehadiran'] ?? 0), $bulanAngka, $tahun);

                $gajiData = $this->prepareGajiData($row, $defaultTunjanganId);

                Gaji::updateOrCreate(
                    ['karyawan_id' => $karyawan->id, 'bulan' => $bulanString],
                    $gajiData
                );
            }
            DB::commit();
            $this->info('Proses import berhasil diselesaikan dengan sukses!');
        } catch (Exception $e) {
            DB::rollBack();
            $this->error('Terjadi kesalahan fatal: ' . $e->getMessage() . ' di file ' . $e->getFile() . ' baris ' . $e->getLine());
            return 1;
        }

        return 0;
    }

    private function prosesKaryawan(array $data, int $jabatanId, bool $shouldUpdate): Karyawan
    {
        $karyawanData = [
            'nama' => trim($data['Nama']),
            'email' => trim($data['Email'] ?? null),
            'telepon' => trim($data['Telepon'] ?? null),
            'alamat' => trim($data['Alamat'] ?? null),
            'jabatan_id' => trim($jabatanId ?? null),
        ];
        return Karyawan::updateOrCreate(['nip' => trim($data['NIP'])], $karyawanData);
    }

    /**
     * @param Karyawan $karyawan
     * @param int $jumlahHari
     * @param int $bulan
     * @param int $tahun
     */
    private function prosesAbsensi(Karyawan $karyawan, int $jumlahHari, int $bulan, int $tahun)
    {
        $tanggalAwal = Carbon::create($tahun, $bulan, 1);
        $hariAbsenDibuat = 0;

        for ($i = 0; $i < $tanggalAwal->daysInMonth && $hariAbsenDibuat < $jumlahHari; $i++) {
            $tanggalCek = $tanggalAwal->copy()->addDays($i);

            if ($tanggalCek->isWeekday() || $tanggalCek->isSaturday()) {

                // **KUNCI PERBAIKAN ADA DI SINI**
                // Langkah 1: Buat sesi absensi 'masuk' jika belum ada.
                $sesi = SesiAbsensi::firstOrCreate(
                    [
                        'tanggal' => $tanggalCek->toDateString(),
                        'tipe'    => 'masuk',
                    ],
                    [
                        'jam_buka' => '07:00:00',
                        'jam_tutup' => '17:00:00',
                    ]
                );

                // Langkah 2: Buat array data untuk absensi secara eksplisit.
                $dataAbsensi = [
                    'nip'             => $karyawan->nip,
                    'nama'            => $karyawan->nama,
                    'tanggal'         => $tanggalCek->toDateString(),
                    'jam'             => '07:30:00',
                    'sesi_absensi_id' => $sesi->id,
                ];

                // Langkah 3: Gunakan 'firstOrCreate' dengan data yang sudah lengkap.
                Absensi::firstOrCreate(
                    [
                        'nip'     => $karyawan->nip,
                        'tanggal' => $tanggalCek->toDateString(),
                    ],
                    $dataAbsensi // Gunakan array data yang sudah lengkap di sini
                );

                $hariAbsenDibuat++;
            }
        }
        $this->line("-> Absensi ({$hariAbsenDibuat} hari) berhasil diproses untuk {$karyawan->nama}.");
    }

    private function prepareGajiData(array $data, int $defaultTunjanganId): array
    {
        $columnMap = [
            'Gaji Pokok' => 'gaji_pokok',
            'TJ. Anak' => 'tunj_anak',
            'TJ. Pengabdian' => 'tunj_pengabdian',
            'Lembur' => 'lembur',
            'Potongan' => 'potongan',
            'TJ.Komunikasi' => 'tunj_komunikasi',
            'TJ. Kinerja' => 'tunj_kinerja',
        ];
        $gajiData = [];
        foreach ($columnMap as $csvHeader => $dbColumn) {
            $gajiData[$dbColumn] = $this->cleanNumeric($data[$csvHeader] ?? 0);
        }
        $gajiData['tunjangan_kehadiran_id'] = $defaultTunjanganId;
        return $gajiData;
    }

    private function readCsv(string $filePath): array
    {
        $data = [];
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $header = fgetcsv($handle, 2000, ";");
            while (($row = fgetcsv($handle, 2000, ";")) !== FALSE) {
                if (count($header) == count($row)) {
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }
        return $data;
    }

    private function cleanNumeric($value)
    {
        return preg_replace('/[^0-9]/', '', $value);
    }

    private function getBulanAngka($bulanInput)
    {
        if (is_numeric($bulanInput) && $bulanInput >= 1 && $bulanInput <= 12) return (int)$bulanInput;
        $daftarBulan = ["januari" => 1, "februari" => 2, "maret" => 3, "april" => 4, "mei" => 5, "juni" => 6, "juli" => 7, "agustus" => 8, "september" => 9, "oktober" => 10, "november" => 11, "desember" => 12];
        return $daftarBulan[strtolower($bulanInput)] ?? null;
    }
}
