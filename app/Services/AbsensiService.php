<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\SesiAbsensi;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class AbsensiService
{
    /**
     * Menghitung jarak antara dua titik koordinat (Haversine).
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; 

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius; 
    }

    /**
     * [OPTIMASI] Mengambil rekap absensi dengan teknik Eager Loading & Memory Checking.
     */
    public function getAttendanceRecap(Carbon $month, ?array $karyawanIds = null): array
    {
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();
        $daysInMonth = $endOfMonth->day;

        // 1. Ambil Data Karyawan
        $query = Karyawan::with('jabatan')->orderBy('nama');
        if ($karyawanIds) {
            $query->whereIn('id', $karyawanIds);
        }
        $karyawans = $query->get();

        // 2. Ambil Data Absensi Sekaligus (Bulk Fetch)
        $absensiBulanIni = Absensi::whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(function ($item) {
                return $item->karyawan_id . '_' . $item->tanggal;
            });

        // 3. Ambil Sesi Spesifik & Default Sekaligus
        $sesiSpesifikBulanIni = SesiAbsensi::whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->where('is_default', false)
            ->get()
            ->keyBy('tanggal');
            
        $sesiDefaults = SesiAbsensi::where('is_default', true)->get();

        $rekapData = collect();
        $workingDaysCount = 0;
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        // 4. Hitung Hari Kerja (Tanpa Query Loop)
        foreach ($period as $date) {
            // [FIX] Tambahkan type hint ini agar Intelephense tidak error
            /** @var Carbon $date */
            
            $sessionStatus = $this->checkSessionInMemory($date, $sesiSpesifikBulanIni, $sesiDefaults);
            if ($sessionStatus['is_active']) {
                $workingDaysCount++;
            }
        }

        // 5. Proses Data Per Karyawan
        foreach ($karyawans as $karyawan) {
            $totalHadir = 0;
            $detailHarian = [];
            
            // Reset periode untuk loop karyawan
            $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

            foreach ($period as $date) {
                // [FIX] Tambahkan type hint ini juga di sini
                /** @var Carbon $date */

                $tanggalString = $date->toDateString();
                $dayNumber = $date->day;
                $keyAbsensi = $karyawan->id . '_' . $tanggalString;
                
                // Cek status sesi dari MEMORI
                $sessionStatusHariIni = $this->checkSessionInMemory($date, $sesiSpesifikBulanIni, $sesiDefaults);

                $status = '-';
                $jam = '-';

                if (isset($absensiBulanIni[$keyAbsensi])) {
                    $status = 'H'; 
                    $jam = Carbon::parse($absensiBulanIni[$keyAbsensi]->jam)->format('H:i');
                    $totalHadir++;
                } elseif ($sessionStatusHariIni['is_active']) {
                    $status = 'A'; 
                } elseif (!$sessionStatusHariIni['is_active'] && $sessionStatusHariIni['status'] != 'Tidak Ada Sesi') {
                    $status = 'L'; 
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
                    'total_alpha' => max(0, $workingDaysCount - $totalHadir),
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
     * Helper function untuk mengecek status sesi dari Collection (Memori).
     */
    private function checkSessionInMemory(Carbon $date, Collection $sesiSpesifikCollection, Collection $sesiDefaults): array
    {
        $todayDateString = $date->toDateString();
        $appDayOfWeek = $date->dayOfWeekIso;

        // 1. Cek Sesi Spesifik di Memori
        if ($sesiSpesifikCollection->has($todayDateString)) {
            $sesi = $sesiSpesifikCollection->get($todayDateString);
            if ($sesi->tipe === 'aktif') {
                return [
                    'is_active' => true,
                    'waktu_mulai' => $sesi->waktu_mulai,
                    'waktu_selesai' => $sesi->waktu_selesai,
                    'status' => 'Sesi Spesifik Aktif',
                    'keterangan' => $sesi->keterangan,
                    'sesi_id' => $sesi->id,
                ];
            } else {
                return [
                    'is_active' => false,
                    'waktu_mulai' => null,
                    'waktu_selesai' => null,
                    'status' => 'Libur Spesifik',
                    'keterangan' => $sesi->keterangan,
                    'sesi_id' => $sesi->id,
                ];
            }
        }

        // 2. Cek Sesi Default di Memori
        $sesiDefaultAktif = $sesiDefaults->first(function ($sesi) use ($appDayOfWeek) {
            return $sesi->tipe === 'aktif' &&
                is_array($sesi->hari_kerja) &&
                in_array($appDayOfWeek, $sesi->hari_kerja, true);
        });

        if ($sesiDefaultAktif) {
            return [
                'is_active' => true,
                'waktu_mulai' => $sesiDefaultAktif->waktu_mulai,
                'waktu_selesai' => $sesiDefaultAktif->waktu_selesai,
                'status' => 'Sesi Default Aktif',
                'keterangan' => $sesiDefaultAktif->keterangan,
                'sesi_id' => $sesiDefaultAktif->id,
            ];
        }

        $sesiDefaultLibur = $sesiDefaults->first(function ($sesi) use ($appDayOfWeek) {
            return $sesi->tipe === 'libur' &&
                is_array($sesi->hari_kerja) &&
                in_array($appDayOfWeek, $sesi->hari_kerja, true);
        });

        if ($sesiDefaultLibur) {
            return [
                'is_active' => false,
                'waktu_mulai' => null,
                'waktu_selesai' => null,
                'status' => 'Libur Default',
                'keterangan' => $sesiDefaultLibur->keterangan,
                'sesi_id' => $sesiDefaultLibur->id,
            ];
        }

        return [
            'is_active' => false,
            'waktu_mulai' => null,
            'waktu_selesai' => null,
            'status' => 'Tidak Ada Sesi',
            'keterangan' => 'Tidak ada jadwal sesi absensi aktif maupun libur.',
            'sesi_id' => null,
        ];
    }

    /**
     * Memeriksa status sesi untuk SATU tanggal (tetap pakai DB query).
     */
    public function getSessionStatus(Carbon $date): array
    {
        $todayDateString = $date->toDateString();
        
        $sesiSpesifik = SesiAbsensi::where('tanggal', $todayDateString)
            ->where('is_default', false)
            ->get()
            ->keyBy('tanggal');

        $sesiDefaults = SesiAbsensi::where('is_default', true)->get();

        return $this->checkSessionInMemory($date, $sesiSpesifik, $sesiDefaults);
    }
    
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