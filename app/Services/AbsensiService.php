<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\SesiAbsensi;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AbsensiService
{
    /**
     * Menghitung jarak antara dua titik koordinat geografis menggunakan formula Haversine.
     *
     * @param float $lat1 Latitude titik pertama.
     * @param float $lon1 Longitude titik pertama.
     * @param float $lat2 Latitude titik kedua.
     * @param float $lon2 Longitude titik kedua.
     * @return float Jarak dalam meter.
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Radius bumi dalam meter

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        // Rumus Haversine
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius; // Mengembalikan jarak dalam meter
    }

    /**
     * Mengambil rekap absensi untuk satu bulan, termasuk menghitung hari kerja efektif.
     * [PERBAIKAN] Struktur data diubah total agar sesuai dengan kebutuhan view (nested summary & detail).
     * [PERBAIKAN] Key detail diubah dari 'Y-m-d' menjadi 'day' (int).
     * [PERBAIKAN] Menambahkan 'jam' pada detail harian.
     * [PERBAIKAN] Mengganti nama 'total_absen' menjadi 'total_alpha' sesuai view.
     * [PERBAIKAN] Menambahkan 'daysInMonth' pada return.
     * [PERBAIKAN KRITIS] Menambahkan parameter opsional $karyawanIds dan menggunakannya untuk filter.
     */
    public function getAttendanceRecap(Carbon $month, ?array $karyawanIds = null): array
    {
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();
        $daysInMonth = $endOfMonth->day; // [FIX] Dapatkan jumlah hari dalam bulan

        // [PERBAIKAN] Ambil karyawan berdasarkan $karyawanIds jika disediakan
        $query = Karyawan::with('jabatan')->orderBy('nama');
        if ($karyawanIds) {
            $query->whereIn('id', $karyawanIds);
        }
        $karyawans = $query->get(); // Sertakan jabatan

        // Ambil data absensi untuk bulan terkait
        $absensiBulanIni = Absensi::whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(function ($item) {
                // Buat kunci unik per karyawan per tanggal
                return $item->karyawan_id . '_' . $item->tanggal;
            });

        $rekapData = collect();
        $workingDaysCount = 0; // Hitung hari kerja

        // Iterasi setiap hari dalam bulan
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);
        foreach ($period as $date) {
            // Cek status sesi untuk hari ini untuk menentukan apakah hari kerja atau tidak
            $sessionStatus = $this->getSessionStatus($date);
            // Anggap hari kerja jika sesi aktif (sesuaikan logika jika perlu)
            if ($sessionStatus['is_active']) {
                $workingDaysCount++;
            }
        }


        // Proses data untuk setiap karyawan
        foreach ($karyawans as $karyawan) {
            $totalHadir = 0;
            $detailHarian = []; // [FIX] Ganti nama jadi 'detailHarian'

            // Iterasi lagi untuk mengisi detail harian per karyawan
            $period = CarbonPeriod::create($startOfMonth, $endOfMonth);
            foreach ($period as $date) {
                $tanggalString = $date->toDateString();
                $dayNumber = $date->day; // [FIX] Gunakan nomor hari (1-31) sebagai key
                $keyAbsensi = $karyawan->id . '_' . $tanggalString;
                $sessionStatusHariIni = $this->getSessionStatus($date); // Cek sesi untuk hari ini

                $status = '-'; // Default status
                $jam = '-';

                if (isset($absensiBulanIni[$keyAbsensi])) {
                    // Jika ada data absensi pada hari ini
                    $status = 'H'; // Hadir
                    $jam = Carbon::parse($absensiBulanIni[$keyAbsensi]->jam)->format('H:i'); // [FIX] Ambil jam
                    $totalHadir++;
                } elseif ($sessionStatusHariIni['is_active']) {
                    // Jika hari kerja (sesi aktif) tapi tidak ada data absensi
                    $status = 'A'; // Alfa/Absen
                } elseif (!$sessionStatusHariIni['is_active'] && $sessionStatusHariIni['status'] != 'Tidak Ada Sesi') {
                    // Jika sesi tidak aktif dan statusnya bukan 'Tidak Ada Sesi' (berarti libur spesifik/default)
                    $status = 'L'; // Libur
                }
                // Jika status masih '-', berarti bukan hari kerja dan bukan hari libur terdefinisi (misal weekend tanpa sesi libur)

                // [FIX] Simpan data dengan key nomor hari dan sertakan jam
                $detailHarian[$dayNumber] = [
                    'status' => $status,
                    'jam' => $jam
                ];
            }


            // [FIX] Ubah struktur data agar sesuai dengan view (nested)
            $rekapData->push([
                'id' => $karyawan->id,
                'nip' => $karyawan->nip,
                'nama' => $karyawan->nama,
                'jabatan' => $karyawan->jabatan->nama_jabatan ?? 'N/A', // Tampilkan nama jabatan
                'summary' => [
                    'total_hadir' => $totalHadir,
                    'total_alpha' => $workingDaysCount - $totalHadir, // [FIX] Ganti nama jadi total_alpha
                ],
                'detail' => $detailHarian, // [FIX] Ganti nama jadi detail
            ]);
        }

        // [FIX] Kembalikan semua data yang dibutuhkan controller
        return [
            'rekapData' => $rekapData,
            'workingDaysCount' => $workingDaysCount,
            'daysInMonth' => $daysInMonth,
        ];
    }

    /**
     * Memeriksa status sesi untuk tanggal tertentu dengan logika yang benar dan berurutan.
     * [PERBAIKAN] Menggunakan dayOfWeekIso (1=Senin - 7=Minggu) agar sesuai DB.
     */
    public function getSessionStatus(Carbon $date): array
    {
        $todayDateString = $date->toDateString();
        $appDayOfWeek = $date->dayOfWeekIso; // 1 for Monday, 7 for Sunday

        // 1. Cari Sesi Spesifik untuk tanggal ini (tipe aktif)
        $sesiSpesifik = SesiAbsensi::where('tanggal', $todayDateString)
            ->where('tipe', 'aktif')
            ->where('is_default', false)
            ->first();

        if ($sesiSpesifik) {
            return [
                'is_active' => true,
                'waktu_mulai' => $sesiSpesifik->waktu_mulai,
                'waktu_selesai' => $sesiSpesifik->waktu_selesai,
                'status' => 'Sesi Spesifik Aktif',
                'keterangan' => $sesiSpesifik->keterangan ?? 'Sesi absensi khusus untuk hari ini.',
                'sesi_id' => $sesiSpesifik->id,
            ];
        }

        // 2. Cek apakah ada Sesi Spesifik Libur untuk tanggal ini
        $sesiLibur = SesiAbsensi::where('tanggal', $todayDateString)
            ->where('tipe', 'libur')
            ->where('is_default', false)
            ->first();

        if ($sesiLibur) {
            return [
                'is_active' => false,
                'waktu_mulai' => null,
                'waktu_selesai' => null,
                'status' => 'Libur Spesifik',
                'keterangan' => $sesiLibur->keterangan ?? 'Hari ini ditetapkan sebagai hari libur.',
                'sesi_id' => $sesiLibur->id,
            ];
        }

        // 3. Cari Sesi Default yang Aktif untuk hari ini
        $sesiDefaultAktif = SesiAbsensi::where('is_default', true)
            ->where('tipe', 'aktif')
            // [FIX] Menggunakan whereJsonContains untuk mengecek kolom 'hari_kerja'
            ->whereJsonContains('hari_kerja', $appDayOfWeek)
            ->first();

        if ($sesiDefaultAktif) {
            return [
                'is_active' => true,
                'waktu_mulai' => $sesiDefaultAktif->waktu_mulai,
                'waktu_selesai' => $sesiDefaultAktif->waktu_selesai,
                'status' => 'Sesi Default Aktif',
                'keterangan' => $sesiDefaultAktif->keterangan ?? 'Sesi absensi reguler.',
                'sesi_id' => $sesiDefaultAktif->id,
            ];
        }

        // 4. Cek Sesi Default Libur (misal, untuk Sabtu/Minggu)
        $sesiDefaultLibur = SesiAbsensi::where('is_default', true)
            ->where('tipe', 'libur')
            // [FIX] Menggunakan whereJsonContains untuk mengecek kolom 'hari_kerja'
            ->whereJsonContains('hari_kerja', $appDayOfWeek)
            ->first();

        if ($sesiDefaultLibur) {
            return [
                'is_active' => false,
                'waktu_mulai' => null,
                'waktu_selesai' => null,
                'status' => 'Libur Default',
                'keterangan' => $sesiDefaultLibur->keterangan ?? 'Hari libur reguler.',
                'sesi_id' => $sesiDefaultLibur->id,
            ];
        }

        // 5. Jika tidak cocok sama sekali
        return [
            'is_active' => false,
            'waktu_mulai' => null,
            'waktu_selesai' => null,
            'status' => 'Tidak Ada Sesi',
            'keterangan' => 'Tidak ada jadwal sesi absensi aktif maupun libur yang ditemukan untuk hari ini.',
            'sesi_id' => null,
        ];
    }

    /**
     * Mengambil daftar hari libur nasional dari API.
     */
    public function getNationalHolidays(int $year): array
    {
        // ... (Fungsi ini tidak berubah)
        $apiUrl = "https://api-harilibur.vercel.app/api?year={$year}";
        $cacheKey = "national_holidays_indonesia_{$year}";

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($apiUrl) {
            try {
                $response = Http::get($apiUrl);
                if ($response->successful()) {
                    $holidays = collect($response->json());
                    return $holidays->filter(fn($holiday) => $holiday['is_national_holiday'])
                        ->mapWithKeys(function ($holiday) {
                            return [
                                $holiday['holiday_date'] => [
                                    'localName' => $holiday['holiday_name']
                                ]
                            ];
                        })->all();
                }
            } catch (\Exception $e) {
                report($e);
            }
            return [];
        });
    }
}
