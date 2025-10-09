<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\SesiAbsensi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AbsensiService
{
    /**
     * Mengambil daftar hari libur nasional dari API.
     */
    public function getAttendanceRecap(Carbon $selectedMonth): array
    {
        $daysInMonth = $selectedMonth->daysInMonth;

        // Hitung hari kerja efektif dalam sebulan
        $workingDaysCount = 0;
        $workingDaysMap = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $selectedMonth->copy()->setDay($day);
            if ($this->getSessionStatus($currentDate)['is_active']) {
                $workingDaysCount++;
                $workingDaysMap[$day] = true;
            } else {
                $workingDaysMap[$day] = false;
            }
        }

        $karyawans = Karyawan::where('status_aktif', true)->orderBy('nama')->get();
        $absensiBulanIniGrouped = Absensi::whereYear('tanggal', $selectedMonth->year)
            ->whereMonth('tanggal', $selectedMonth->month)
            ->get()
            ->groupBy('nip');

        $rekapData = [];
        foreach ($karyawans as $karyawan) {
            $karyawanAbsensi = $absensiBulanIniGrouped->get($karyawan->nip, collect());
            $totalHadir = $karyawanAbsensi->count();
            $totalAlpha = max(0, $workingDaysCount - $totalHadir); // max(0, ...) untuk mencegah nilai negatif

            $harian = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $absenPadaHariIni = $karyawanAbsensi->firstWhere(fn($item) => Carbon::parse($item->tanggal)->day == $day);
                $status = '-'; // Default untuk hari non-kerja

                if ($workingDaysMap[$day]) { // Jika ini hari kerja
                    $status = $absenPadaHariIni ? 'H' : 'A';
                }

                $harian[$day] = [
                    'status' => $status,
                    'jam' => $absenPadaHariIni ? Carbon::parse($absenPadaHariIni->jam)->format('H:i') : '-',
                ];
            }

            $rekapData[] = (object)[
                'id' => $karyawan->id,
                'nip' => $karyawan->nip,
                'nama' => $karyawan->nama,
                'email' => $karyawan->email,
                'summary' => [
                    'total_hadir' => $totalHadir,
                    'total_alpha' => $totalAlpha,
                ],
                'detail' => $harian,
            ];
        }

        return [
            'rekapData' => $rekapData,
            'workingDaysCount' => $workingDaysCount,
            'daysInMonth' => $daysInMonth,
        ];
    }

    public function getNationalHolidays(int $year): array
    {
        // Kita ganti URL API ke sumber yang lebih lengkap untuk Indonesia
        $apiUrl = "https://api-harilibur.vercel.app/api?year={$year}";
        $cacheKey = "national_holidays_indonesia_{$year}";

        // Cache selama 30 hari untuk efisiensi
        return Cache::remember($cacheKey, now()->addDays(30), function () use ($apiUrl) {
            try {
                // API ini tidak memerlukan parameter negara (ID)
                $response = Http::get($apiUrl);
                if ($response->successful()) {
                    $holidays = collect($response->json());
                    // Kita format ulang agar strukturnya sama seperti sebelumnya
                    return $holidays->filter(fn($holiday) => $holiday['is_national_holiday'])
                        ->mapWithKeys(function ($holiday) {
                            return [
                                // Key-nya adalah tanggal libur
                                $holiday['holiday_date'] => [
                                    'localName' => $holiday['holiday_name']
                                ]
                            ];
                        })->all();
                }
            } catch (\Exception $e) {
                report($e);
            }
            return []; // Kembalikan array kosong jika gagal
        });
    }

    private function getDefaultTimes(): array
    {
        return Cache::remember('default_session_times', 60, function () {
            $defaultSetting = SesiAbsensi::where('tanggal', '1970-01-01')->first();
            return [
                'waktu_mulai' => $defaultSetting?->waktu_mulai ?? '07:00:00',
                'waktu_selesai' => $defaultSetting?->waktu_selesai ?? '12:00:00',
            ];
        });
    }

    /**
     * Memeriksa status sesi untuk tanggal tertentu.
     */
    public function getSessionStatus(Carbon $date): array
    {
        // 1. Cek pengecualian
        $exception = SesiAbsensi::where('tanggal', $date->format('Y-m-d'))->first();
        if ($exception) {
            if ($exception->tipe === 'aktif') {
                return ['status' => 'Aktif', 'is_active' => true, 'waktu_mulai' => $exception->waktu_mulai, 'waktu_selesai' => $exception->waktu_selesai, 'keterangan' => $exception->keterangan, 'is_exception' => true];
            } else {
                return ['status' => 'Nonaktif', 'is_active' => false, 'keterangan' => $exception->keterangan, 'is_exception' => true];
            }
        }

        // 2. Cek hari libur nasional
        $holidays = $this->getNationalHolidays($date->year);
        if (isset($holidays[$date->format('Y-m-d')])) {
            return ['status' => 'Libur Nasional', 'is_active' => false, 'keterangan' => $holidays[$date->format('Y-m-d')]['localName'], 'is_exception' => false];
        }

        // 3. Cek akhir pekan
        if ($date->isWeekend()) {
            return ['status' => 'Akhir Pekan', 'is_active' => false, 'is_exception' => false];
        }

        // 4. Hari kerja (Default)
        $defaultTimes = $this->getDefaultTimes();
        return ['status' => 'Aktif', 'is_active' => true, 'waktu_mulai' => $defaultTimes['waktu_mulai'], 'waktu_selesai' => $defaultTimes['waktu_selesai'], 'is_exception' => false];
    }
}
