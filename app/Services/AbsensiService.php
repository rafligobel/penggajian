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
     * Mengambil rekap absensi untuk satu bulan, termasuk menghitung hari kerja efektif.
     */
    public function getAttendanceRecap(Carbon $bulan, array $karyawanIds = null): array
    {
        $startOfMonth = $bulan->copy()->startOfMonth();
        $endOfMonth = $bulan->copy()->endOfMonth();

        $karyawanQuery = Karyawan::orderBy('nama', 'asc');
        if ($karyawanIds) {
            $karyawanQuery->whereIn('id', $karyawanIds);
        }
        $karyawans = $karyawanQuery->get();
        $rekapData = [];
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        $absensiBulanan = Absensi::whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->whereIn('nip', $karyawans->pluck('nip'))
            ->get()
            ->groupBy('nip');

        // Menghitung hari kerja efektif dalam sebulan
        $workingDaysCount = 0;
        foreach ($period as $date) {
            if ($this->getSessionStatus($date)['is_active']) {
                $workingDaysCount++;
            }
        }

        foreach ($karyawans as $karyawan) {
            $absensiKaryawan = $absensiBulanan->get($karyawan->nip, collect())->keyBy(function ($item) {
                return Carbon::parse($item->tanggal)->format('Y-m-d');
            });

            $totalHadir = 0;
            $totalAlpha = 0;
            $detailHarian = [];

            // Buat periode baru untuk setiap karyawan agar tidak ada masalah referensi
            $employeePeriod = CarbonPeriod::create($startOfMonth, $endOfMonth);
            foreach ($employeePeriod as $date) {
                $dateString = $date->toDateString();
                $dayIndex = $date->day;

                if ($absensiKaryawan->has($dateString)) {
                    $totalHadir++;
                    $jamMasuk = $absensiKaryawan[$dateString]->jam;
                    $detailHarian[$dayIndex] = ['status' => 'H', 'jam' => Carbon::parse($jamMasuk)->format('H:i')];
                } else {
                    $statusSesi = $this->getSessionStatus($date);
                    if ($statusSesi['is_active']) {
                        // Hanya hitung Alpha jika sesi seharusnya aktif
                        $totalAlpha++;
                        $detailHarian[$dayIndex] = ['status' => 'A', 'jam' => '-'];
                    } else {
                        // Jika hari libur, statusnya L (Libur)
                        $detailHarian[$dayIndex] = ['status' => 'L', 'jam' => '-'];
                    }
                }
            }

            $rekapData[] = [
                'id' => $karyawan->id,
                'nip' => $karyawan->nip,
                'nama' => $karyawan->nama,
                'email' => $karyawan->email,
                'summary' => [
                    'total_hadir' => $totalHadir,
                    'total_alpha' => $totalAlpha,
                ],
                'detail' => $detailHarian,
            ];
        }

        return [
            'rekapData' => $rekapData,
            'daysInMonth' => $endOfMonth->day,
            'workingDaysCount' => $workingDaysCount,
        ];
    }

    /**
     * Memeriksa status sesi untuk tanggal tertentu dengan logika yang benar dan berurutan.
     */
    public function getSessionStatus(Carbon $date): array
    {
        // 1. Cek Pengecualian Spesifik untuk tanggal tersebut
        $exception = SesiAbsensi::where('tanggal', $date->toDateString())->where('is_default', false)->first();
        if ($exception) {
            if ($exception->tipe === 'nonaktif') {
                return [
                    'is_active' => false,
                    'status' => 'Sesi Dinonaktifkan',
                    'keterangan' => $exception->keterangan ?: 'Sesi dinonaktifkan secara manual',
                ];
            }
            if ($exception->tipe === 'aktif') {
                return [
                    'is_active' => true,
                    'status' => 'Sesi Khusus Aktif',
                    'keterangan' => $exception->keterangan ?: 'Sesi diaktifkan pada hari libur',
                    'waktu_mulai' => $exception->waktu_mulai,
                    'waktu_selesai' => $exception->waktu_selesai,
                ];
            }
        }

        // 2. Cek apakah tanggal ini adalah hari libur nasional
        $holidays = $this->getNationalHolidays($date->year);
        $dateString = $date->format('Y-m-d');
        if (isset($holidays[$dateString])) {
            return [
                'is_active' => false,
                'status' => 'Libur Nasional',
                'keterangan' => $holidays[$dateString]['localName'],
            ];
        }

        // 3. Cek Aturan Default (Hari Kerja)
        $defaultSetting = Cache::remember('sesi_absensi_default_setting', now()->addHours(24), function () {
            return SesiAbsensi::where('is_default', true)->first();
        });

        // Konversi Carbon dayOfWeek (0=Minggu, 1=Senin) ke format ISO 8601 (1=Senin, 7=Minggu)
        $dayOfWeek = $date->dayOfWeekIso;

        if ($defaultSetting && is_array($defaultSetting->hari_kerja) && in_array($dayOfWeek, $defaultSetting->hari_kerja)) {
            return [
                'is_active' => true,
                'status' => 'Hari Kerja',
                'keterangan' => 'Sesi berjalan sesuai jadwal default',
                'waktu_mulai' => $defaultSetting->waktu_mulai,
                'waktu_selesai' => $defaultSetting->waktu_selesai,
            ];
        }

        // 4. Jika tidak cocok semua, maka sesi tidak aktif
        return [
            'is_active' => false,
            'status' => 'Libur Akhir Pekan',
            'keterangan' => 'Tidak ada sesi yang dijadwalkan',
        ];
    }

    /**
     * Mengambil daftar hari libur nasional dari API.
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
