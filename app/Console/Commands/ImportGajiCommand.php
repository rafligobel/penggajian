<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Karyawan;
use App\Models\Gaji;
use App\Models\Absensi;
use App\Services\AbsensiService; // Menggunakan service yang sudah ada
use Carbon\Carbon;
use Exception;

class ImportGajiCommand extends Command
{
    /**
     * The name and signature of the console command.
     * {bulan} bisa diisi nama bulan (e.g., "Juni") atau angka (e.g., "6").
     * {--update-karyawan} adalah flag untuk memperbarui data karyawan yang sudah ada.
     */
    protected $signature = 'import:gaji {bulan} {tahun} {--file=} {--update-karyawan}';

    /**
     * The console command description.
     */
    protected $description = 'Import data karyawan, absensi, dan gaji dari file CSV sesuai struktur sistem';

    /**
     * Instance dari AbsensiService untuk mengecek hari kerja.
     */
    protected AbsensiService $absensiService;

    /**
     * Create a new command instance.
     *
     * @param AbsensiService $absensiService
     */
    public function __construct(AbsensiService $absensiService)
    {
        parent::__construct();
        $this->absensiService = $absensiService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bulanInput = $this->argument('bulan');
        $tahun = $this->argument('tahun');
        $filePath = $this->option('file');
        $shouldUpdateKaryawan = $this->option('update-karyawan');

        if (!$filePath || !file_exists($filePath)) {
            $this->error('File tidak ditemukan! Mohon sertakan path yang benar menggunakan --file=<path_ke_file>');
            return 1;
        }

        // --- PERBAIKAN FINAL: Menggunakan pemetaan manual untuk bulan ---
        $bulanAngka = $this->getBulanAngka($bulanInput);

        if (is_null($bulanAngka)) {
            $this->error("Nama atau nomor bulan '{$bulanInput}' tidak valid.");
            return 1;
        }

        $bulanString = Carbon::createFromDate($tahun, $bulanAngka, 1)->format('Y-m');

        $this->info("Memulai proses import untuk data bulan: {$bulanString} dari file: {$filePath}");

        DB::beginTransaction();
        try {
            $file = fopen($filePath, 'r');

            $header = array_map('trim', fgetcsv($file, 0, ';'));

            $rowIndex = 1;

            while (($row = fgetcsv($file, 0, ';')) !== false) {
                $rowIndex++;
                if (count($row) !== count($header)) {
                    continue;
                }
                $data = array_combine($header, $row);

                if (empty($data['NIP'])) {
                    $this->warn("PERINGATAN: NIP kosong di baris {$rowIndex}. Baris ini dilewati.");
                    continue;
                }
                $karyawan = $this->prosesKaryawan($data, $shouldUpdateKaryawan);
                $this->line("Karyawan '{$karyawan->nama}' (NIP: {$karyawan->nip}) diproses.");

                $jumlahKehadiran = isset($data['Jumlah Kehadiran']) ? (int)$data['Jumlah Kehadiran'] : 0;
                if ($jumlahKehadiran > 0) {
                    $this->generateAbsensi($karyawan, $bulanAngka, $tahun, $jumlahKehadiran);
                    $this->info("-> {$jumlahKehadiran} data absensi untuk '{$karyawan->nama}' telah dibuat.");
                }

                $gajiExists = Gaji::where('karyawan_id', $karyawan->id)
                    ->where('bulan', $bulanString)
                    ->exists();

                if ($gajiExists) {
                    $this->warn("-> INFO: Data gaji untuk '{$karyawan->nama}' bulan {$bulanString} sudah ada. Dilewati.");
                    continue;
                }
                $gajiData = $this->prepareGajiData($karyawan, $bulanString, $data);
                Gaji::create($gajiData);
                $this->info("-> Data gaji untuk '{$karyawan->nama}' berhasil disimpan.");
            }

            fclose($file);
            DB::commit();
            $this->info('================================================');
            $this->info('Proses import data telah selesai dengan sukses!');
        } catch (Exception $e) {
            DB::rollBack();
            $this->error('Terjadi kesalahan fatal: ' . $e->getMessage() . ' di file ' . $e->getFile() . ' baris ' . $e->getLine());
            return 1;
        }

        return 0;
    }

    /**
     * Fungsi baru untuk konversi nama bulan Indonesia ke angka.
     */
    private function getBulanAngka(string $bulanInput): ?int
    {
        $bulanMap = [
            'januari'   => 1,
            'jan' => 1,
            '1' => 1,
            '01' => 1,
            'februari'  => 2,
            'feb' => 2,
            '2' => 2,
            '02' => 2,
            'maret'     => 3,
            'mar' => 3,
            '3' => 3,
            '03' => 3,
            'april'     => 4,
            'apr' => 4,
            '4' => 4,
            '04' => 4,
            'mei'       => 5,
            'mei' => 5,
            '5' => 5,
            '05' => 5,
            'juni'      => 6,
            'jun' => 6,
            '6' => 6,
            '06' => 6,
            'juli'      => 7,
            'jul' => 7,
            '7' => 7,
            '07' => 7,
            'agustus'   => 8,
            'agu' => 8,
            '8' => 8,
            '08' => 8,
            'september' => 9,
            'sep' => 9,
            '9' => 9,
            '09' => 9,
            'oktober'   => 10,
            'okt' => 10,
            '10' => 10,
            'november'  => 11,
            'nov' => 11,
            '11' => 11,
            'desember'  => 12,
            'des' => 12,
            '12' => 12,
        ];
        $bulanInputLower = strtolower(trim($bulanInput));
        return $bulanMap[$bulanInputLower] ?? null;
    }

    private function prosesKaryawan(array $data, bool $shouldUpdate): Karyawan
    {
        $karyawanData = [
            'nama' => trim($data['Nama']),
            'email' => isset($data['Email']) ? trim($data['Email']) : null,
            'telepon' => isset($data['Telepon']) ? trim($data['Telepon']) : null,
            'alamat' => isset($data['Alamat']) ? trim($data['Alamat']) : null,
        ];

        return Karyawan::updateOrCreate(
            ['nip' => trim($data['NIP'])],
            $shouldUpdate ? $karyawanData : array_merge(['nama' => $karyawanData['nama']], [])
        );
    }

    private function generateAbsensi(Karyawan $karyawan, int $bulan, int $tahun, int $jumlahHari)
    {
        $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $hariAbsen = 0;

        for ($date = $startDate; $date->lte($endDate) && $hariAbsen < $jumlahHari; $date->addDay()) {
            $sessionStatus = $this->absensiService->getSessionStatus($date);

            if ($sessionStatus['is_active']) {
                Absensi::firstOrCreate(
                    [
                        'nip' => $karyawan->nip,
                        'tanggal' => $date->toDateString(),
                    ],
                    [
                        'nama' => $karyawan->nama,
                        'jam' => $sessionStatus['waktu_mulai'] ?? '07:00:00',
                    ]
                );
                $hariAbsen++;
            }
        }
    }

    private function prepareGajiData(Karyawan $karyawan, string $bulanString, array $data): array
    {
        $gajiData = [
            'karyawan_id' => $karyawan->id,
            'bulan' => $bulanString,
        ];

        $columnMap = [
            'Gaji Pokok' => 'gaji_pokok',
            'TJ. Anak' => 'tunj_anak',
            'TJ. Pengabdian' => 'tunj_pengabdian',
            'Lembur' => 'lembur',
            'Potongan' => 'potongan',
            'TJ.Komunikasi' => 'tunj_komunikasi',
            'TJ. Kinerja' => 'tunj_kinerja',
            'TJ. Jabatan' => 'tunj_jabatan',
            'Kehadiran' => 'tunj_kehadiran',
            'Jumlah Kehadiran' => 'jumlah_kehadiran',
            'Total' => 'gaji_bersih',
        ];

        foreach ($columnMap as $csvHeader => $dbColumn) {
            if (isset($data[$csvHeader])) {
                $cleanedValue = preg_replace('/[^0-9]/', '', trim($data[$csvHeader]));
                $gajiData[$dbColumn] = is_numeric($cleanedValue) ? (int)$cleanedValue : 0;
            }
        }

        return $gajiData;
    }
}
