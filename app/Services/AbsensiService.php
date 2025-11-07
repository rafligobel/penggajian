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
     * (Fungsi ini tidak diubah)
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
     * Mengambil rekap absensi untuk satu bulan.
     * (Fungsi ini tidak diubah, sudah benar)
     */
    public function getAttendanceRecap(Carbon $month, ?array $karyawanIds = null): array
    {
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();
        $daysInMonth = $endOfMonth->day;

        $query = Karyawan::with('jabatan')->orderBy('nama');
        if ($karyawanIds) {
            $query->whereIn('id', $karyawanIds);
        }
        $karyawans = $query->get();

        $absensiBulanIni = Absensi::whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(function ($item) {
                // Buat kunci unik per karyawan per tanggal
                return $item->karyawan_id . '_' . $item->tanggal;
            });

        $rekapData = collect();
        $workingDaysCount = 0;

        // Iterasi setiap hari dalam bulan untuk menghitung hari kerja
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);
        foreach ($period as $date) {
            $sessionStatus = $this->getSessionStatus($date);
            if ($sessionStatus['is_active']) {
                $workingDaysCount++;
            }
        }

        // Proses data untuk setiap karyawan
        foreach ($karyawans as $karyawan) {
            $totalHadir = 0;
            $detailHarian = [];
            $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

            foreach ($period as $date) {
                $tanggalString = $date->toDateString();
                $dayNumber = $date->day;
                $keyAbsensi = $karyawan->id . '_' . $tanggalString;
                $sessionStatusHariIni = $this->getSessionStatus($date); // Cek sesi untuk hari ini

                $status = '-'; // Default status
                $jam = '-';

                if (isset($absensiBulanIni[$keyAbsensi])) {
                    // Jika ada data absensi pada hari ini
                    $status = 'H'; // Hadir
                    $jam = Carbon::parse($absensiBulanIni[$keyAbsensi]->jam)->format('H:i');
                    $totalHadir++;
                } elseif ($sessionStatusHariIni['is_active']) {
                    // Jika hari kerja (sesi aktif) tapi tidak ada data absensi
                    $status = 'A'; // Alfa/Absen
                } elseif (!$sessionStatusHariIni['is_active'] && $sessionStatusHariIni['status'] != 'Tidak Ada Sesi') {
                    // Jika sesi tidak aktif dan statusnya bukan 'Tidak Ada Sesi' (berarti libur spesifik/default)
                    $status = 'L'; // Libur
                }

                $detailHarian[$dayNumber] = [
                    'status' => $status,
                    'jam' => $jam
                ];
            }

            $rekapData->push([
                'id' => $karyawan->id,
                'nip' => $karyawan->nip,
                'nama' => $karyawan->nama,
                'jabatan' => $karyawan->jabatan->nama_jabatan ?? 'N/A',
                'summary' => [
                    'total_hadir' => $totalHadir,
                    'total_alpha' => $workingDaysCount - $totalHadir, // total_alpha
                ],
                'detail' => $detailHarian,
            ]);
        }

        return [
            'rekapData' => $rekapData,
            'workingDaysCount' => $workingDaysCount,
            'daysInMonth' => $daysInMonth,
        ];
    }

    /**
     * Memeriksa status sesi untuk tanggal tertentu dengan logika yang benar dan berurutan.
     * [PERBAIKAN] Mengganti `whereJsonContains` (penyebab crash) dengan logika PHP manual
     * untuk kompatibilitas database yang lebih baik.
     */
    public function getSessionStatus(Carbon $date): array
    {
        $todayDateString = $date->toDateString();
        $appDayOfWeek = $date->dayOfWeekIso; // 1 for Monday, 7 for Sunday

        // 1. Cari Sesi Spesifik (Pengecualian) 'aktif'
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

        // 2. Cek apakah ada Sesi Spesifik (Pengecualian) 'libur'
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

        // 3. & 4. Cek Sesi Default (Aktif dan Libur)
        // [PERBAIKAN] Ambil semua sesi default (biasanya hanya 2) dan cek manual di PHP
        $sesiDefaults = SesiAbsensi::where('is_default', true)->get();

        // Cek Sesi Default Aktif
        $sesiDefaultAktif = $sesiDefaults->first(function ($sesi) use ($appDayOfWeek) {
            // Cek manual menggunakan PHP in_array
            return $sesi->tipe === 'aktif' &&
                is_array($sesi->hari_kerja) && // Pastikan 'hari_kerja' adalah array
                in_array($appDayOfWeek, $sesi->hari_kerja, true); // 'true' untuk strict checking (integer vs integer)
        });

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

        // Cek Sesi Default Libur
        $sesiDefaultLibur = $sesiDefaults->first(function ($sesi) use ($appDayOfWeek) {
            // Cek manual menggunakan PHP in_array
            return $sesi->tipe === 'libur' &&
                is_array($sesi->hari_kerja) && // Pastikan 'hari_kerja' adalah array
                in_array($appDayOfWeek, $sesi->hari_kerja, true); // 'true' untuk strict checking
        });

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

        // 5. Jika tidak cocok sama sekali (tidak ada aturan untuk hari ini)
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
     * (Fungsi ini tidak diubah)
     */
    public function getNationalHolidays(int $year): array
    {
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
