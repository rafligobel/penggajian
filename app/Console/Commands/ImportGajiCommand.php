<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Karyawan;
use App\Models\Gaji;
use App\Models\Absensi;
use App\Models\SesiAbsensi;
use App\Models\Jabatan;
use App\Models\TunjanganKehadiran;
use App\Models\User;
use Carbon\Carbon;
use Exception;

class ImportGajiCommand extends Command
{
    // [MASTER DATA] Definisi Tunjangan Kehadiran dan Jabatan
    const TK_MASTERS = [
        ['name' => 'Level 1: Kehadiran', 'amount' => 37500],
        ['name' => 'Level 2: Kehadiran', 'amount' => 47500],
        ['name' => 'Level 3: Kehadiran', 'amount' => 50000],
    ];

    const JABATAN_MASTERS = [
        ['name' => 'Kurikulum', 'tunjangan' => 100000],
        ['name' => 'Ekstrakulikuler', 'tunjangan' => 100000],
        ['name' => 'Sumber Daya Manusia', 'tunjangan' => 500000],
    ];

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

        // ====================================================================
        // [FOKUS 1: SETUP MASTER DATA]
        // ====================================================================

        // 1. Setup Tunjangan Kehadiran (Level 1, 2, 3)
        $this->info("-> Setup Tunjangan Kehadiran Master (3 Level)...");
        $tkLookup = [];
        $defaultTunjanganId = 1;
        foreach (self::TK_MASTERS as $tk) {
            // Mengganti nama kolom 'nama_tunjangan' karena tidak ada di tabel yang diberikan.
            // Gunakan hanya 'jenis_tunjangan' yang ada di tabel.
            $tunjangan = TunjanganKehadiran::firstOrCreate(
                ['jumlah_tunjangan' => $tk['amount']],
                ['jenis_tunjangan' => $tk['name']]
            );
            $tkLookup[$tk['amount']] = $tunjangan->id;

            // Set ID level 1 sebagai default jika belum ada ID 1
            if ($tk['amount'] == 37500) {
                $defaultTunjanganId = $tunjangan->id;
            }
        }
        $this->info("-> Tunjangan Kehadiran Master dibuat/ditemukan.");

        // 2. Setup Jabatan Master (Kurikulum, SDM, dst.)
        $this->info("-> Setup Jabatan Master...");
        $jabatanLookup = [];
        foreach (self::JABATAN_MASTERS as $j) {
            // Kita menggunakan tunjangan dan nama jabatan untuk memastikan keunikan master
            $jabatan = Jabatan::firstOrCreate(
                ['tunj_jabatan' => $j['tunjangan'], 'nama_jabatan' => $j['name']],
                ['tunj_jabatan' => $j['tunjangan'], 'nama_jabatan' => $j['name']]
            );
            // Simpan ID Jabatan berdasarkan nilai tunjangan untuk lookup cepat
            $jabatanLookup[$j['tunjangan']][$jabatan->id] = $j['name'];
        }
        $this->line("-> Jabatan Master berhasil dibuat/ditemukan.");

        // 3. Sesi Absensi Default (untuk constraint DB)
        $sesiAbsensiDefault = SesiAbsensi::firstOrCreate(
            ['is_default' => true],
            [
                'tanggal' => '1970-01-01', // Wajib ada untuk default
                'tipe' => 'aktif',
                // 'jam_buka' diubah menjadi 'waktu_mulai'
                'waktu_mulai' => '07:00:00',
                // 'jam_tutup' diubah menjadi 'waktu_selesai'
                'waktu_selesai' => '17:00:00'
            ]
        );
        $sesiAbsensiId = $sesiAbsensiDefault->id;

        // ====================================================================
        // [FOKUS 2: LOOPING DATA CSV]
        // ====================================================================
        DB::beginTransaction();
        try {
            foreach ($dataCsv as $row) {
                // 1. Proses User & Karyawan (termasuk Jabatan ID)
                $karyawan = $this->prosesKaryawan($row, $jabatanLookup, $shouldUpdateKaryawan);
                $this->info("Memproses: '{$karyawan->nama}' (NIP: {$karyawan->nip})");

                // 2. Proses Absensi (menggunakan karyawan_id)
                $this->prosesAbsensi($karyawan, (int)($row['Jumlah Kehadiran'] ?? 0), $bulanAngka, $tahun, $sesiAbsensiId);

                // 3. Proses Gaji (menentukan tunjangan kehadiran ID berdasarkan perhitungan)
                $gajiData = $this->prepareGajiData($row, $tkLookup, $defaultTunjanganId);

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

    // ====================================================================
    // [HELPER BARU DAN MODIFIKASI]
    // ====================================================================

    private function prosesKaryawan(array $data, array $jabatanLookup, bool $shouldUpdate): Karyawan
    {
        $nip = trim($data['NIP']);
        $nama = trim($data['Nama']);
        $email = trim($data['Email'] ?? null);
        $gajiPokokValue = (int) $this->cleanNumeric($data['Gaji Pokok'] ?? 0);
        $tunjanganJabatanValue = (int) $this->cleanNumeric($data['TJ. Jabatan'] ?? 0);

        // 1. Membuat User Otomatis
        $user = $this->prosesUser($nama, $email);
        $this->line("-> Akun user dibuat/ditemukan untuk {$user->email}");

        // 2. Menentukan Jabatan ID (Bisa NULL)
        $jabatanId = $this->getJabatanId($tunjanganJabatanValue, $jabatanLookup);

        $karyawanData = [
            'nama' => $nama,
            'email' => $email,
            'telepon' => trim($data['Telepon'] ?? null),
            'alamat' => trim($data['Alamat'] ?? null),
            'jabatan_id' => $jabatanId, // <-- Bisa NULL
            'user_id' => $user->id,
            // 'gaji_pokok_default' tidak ada di skema yang diunggah, tapi biarkan jika itu kolom lokal.
            // Jika ada di skema Anda, biarkan. Jika tidak, hapus atau ganti dengan gaji_pokok di Gaji
            'gaji_pokok_default' => $gajiPokokValue,
        ];

        $findData = ['nip' => $nip];

        if (!$shouldUpdate) {
            return Karyawan::firstOrCreate($findData, $karyawanData);
        }

        return Karyawan::updateOrCreate($findData, $karyawanData);
    }

    // [FUNGSI YANG DIPERBAIKI FOKUS] Mengembalikan NULL jika tunjangan 0
    private function getJabatanId(int $tunjanganJabatanValue, array $jabatanLookup): ?int
    {
        // [PERBAIKAN FOKUS] Jika tunjangan 0, kembalikan NULL (tanpa membuat Jabatan Otomatis Rp 0)
        if ($tunjanganJabatanValue === 0) {
            return null;
        }

        // Cek apakah nilai tunjangan ada di master map (Kurikulum, SDM, dll.)
        if (isset($jabatanLookup[$tunjanganJabatanValue])) {
            return array_key_first($jabatanLookup[$tunjanganJabatanValue]);
        }

        // Jika tidak ada di lookup master, buat Jabatan Otomatis
        $calculatedName = 'Jabatan Otomatis Rp ' . number_format($tunjanganJabatanValue, 0, ',', '.');

        // Gunakan NAMA dan TUNJANGAN yang unik sebagai Kriteria Pencarian
        $jabatan = Jabatan::firstOrCreate(
            [
                'tunj_jabatan' => $tunjanganJabatanValue,
                'nama_jabatan' => $calculatedName
            ],
            ['tunj_jabatan' => $tunjanganJabatanValue]
        );

        return $jabatan->id;
    }

    // [FUNGSI BARU] Membuat atau menemukan akun User
    private function prosesUser(string $name, ?string $email): User
    {
        if (empty($email)) {
            throw new Exception("Gagal membuat akun user: Kolom Email kosong untuk karyawan bernama {$name}.");
        }

        // Password default: "password"
        // Default role untuk karyawan di aplikasi penggajian biasanya adalah 'tenaga_kerja'
        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'role' => 'tenaga_kerja', // Asumsi role default
                'password' => Hash::make('password'),
            ]
        );
    }

    private function prepareGajiData(array $data, array $tkLookup, int $defaultTkId): array
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
            $gajiData[$dbColumn] = (int) $this->cleanNumeric($data[$csvHeader] ?? 0);
        }

        // 1. Ambil nilai TK dari CSV
        $tunjanganKehadiranCsv = (int) $this->cleanNumeric($data['Kehadiran'] ?? 0);
        $jumlahKehadiran = (int) $this->cleanNumeric($data['Jumlah Kehadiran'] ?? 0);

        // 2. Hitung Allowance Per Hari dari CSV
        $perDayAllowance = ($jumlahKehadiran > 0)
            ? round($tunjanganKehadiranCsv / $jumlahKehadiran)
            : 0;

        // 3. Temukan TK ID berdasarkan Level yang paling dekat
        $matchedTkId = $this->matchTunjanganKehadiranLevel($perDayAllowance, $tkLookup);

        $gajiData['tunjangan_kehadiran_id'] = $matchedTkId;
        return $gajiData;
    }

    // Mencocokkan allowance per hari ke level master yang paling dekat
    private function matchTunjanganKehadiranLevel(float $amount, array $tkLookup): int
    {
        $levels = array_keys($tkLookup);

        // Cari perbedaan terkecil
        $closestAmount = null;
        $minDifference = PHP_INT_MAX;

        foreach ($levels as $levelAmount) {
            $difference = abs($amount - $levelAmount);
            if ($difference < $minDifference) {
                $minDifference = $difference;
                $closestAmount = $levelAmount;
            }
        }

        // Jika ditemukan kecocokan, kembalikan ID level tersebut, jika tidak, kembalikan ID 1 (default)
        return $tkLookup[$closestAmount] ?? 1;
    }

    /**
     * @param Karyawan $karyawan
     * @param int $jumlahHari
     * @param int $bulan
     * @param int $tahun
     * @param int $sesiAbsensiId // <-- Tambahkan Sesi ID
     */
    private function prosesAbsensi(Karyawan $karyawan, int $jumlahHari, int $bulan, int $tahun, int $sesiAbsensiId)
    {
        $tanggalAwal = Carbon::create($tahun, $bulan, 1);
        $hariAbsenDibuat = 0;

        // Hapus Absensi lama di bulan ini untuk memastikan data bersih
        // PERBAIKAN: Mengganti 'nip' dengan 'karyawan_id'
        Absensi::where('karyawan_id', $karyawan->id)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->delete();

        for ($i = 0; $i < $tanggalAwal->daysInMonth && $hariAbsenDibuat < $jumlahHari; $i++) {
            $tanggalCek = $tanggalAwal->copy()->addDays($i);

            // Cek hari kerja (Senin s/d Sabtu)
            if ($tanggalCek->isWeekday() || $tanggalCek->isSaturday()) {

                // Data absensi sudah disederhanakan
                // PERBAIKAN: Mengganti 'nip' dan 'nama' dengan 'karyawan_id'
                $dataAbsensi = [
                    'sesi_absensi_id' => $sesiAbsensiId, // <-- Gunakan ID sesi yang sudah diambil
                    'karyawan_id'     => $karyawan->id, // BARU & KONSISTEN
                    'tanggal'         => $tanggalCek->toDateString(),
                    'jam'             => '07:30:00',
                    'koordinat'       => '0,0', // <-- Tambahkan default koordinat
                    'jarak'           => 0,     // <-- Tambahkan default jarak
                ];

                Absensi::firstOrCreate(
                    [
                        // PERBAIKAN: Mengganti 'nip' dengan 'karyawan_id'
                        'karyawan_id' => $karyawan->id,
                        'tanggal'     => $tanggalCek->toDateString(),
                    ],
                    $dataAbsensi
                );

                $hariAbsenDibuat++;
            }
        }
        $this->line("-> Absensi ({$hariAbsenDibuat} hari) berhasil diproses untuk {$karyawan->nama}.");
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
        // Menghapus semua karakter kecuali digit (0-9)
        return preg_replace('/[^0-9]/', '', $value);
    }

    private function getBulanAngka($bulanInput)
    {
        if (is_numeric($bulanInput) && $bulanInput >= 1 && $bulanInput <= 12) return (int)$bulanInput;
        $daftarBulan = ["januari" => 1, "februari" => 2, "maret" => 3, "april" => 4, "mei" => 5, "juni" => 6, "juli" => 7, "agustus" => 8, "september" => 9, "oktober" => 10, "november" => 11, "desember" => 12];
        return $daftarBulan[strtolower($bulanInput)] ?? null;
    }
}
