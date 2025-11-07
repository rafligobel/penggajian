<?php

namespace App\Http\Controllers;

use App\Models\SesiAbsensi;
use App\Services\AbsensiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator; // Pastikan ini ada

class SesiAbsensiController extends Controller
{
    protected $absensiService;

    public function __construct(AbsensiService $absensiService)
    {
        $this->absensiService = $absensiService;
    }

    /**
     * == PERBAIKAN BUG LOGIKA ==
     * Diubah ke firstOrCreate agar TIDAK menimpa data yang sudah
     * disimpan oleh storeOrUpdate.
     */
    public function index()
    {
        $defaultDateAktif = '1970-01-01';
        $defaultDateLibur = '1970-01-02';

        // 1. Default Aktif (Hari Kerja)
        // [FIX] Menggunakan firstOrCreate
        $defaultAktif = SesiAbsensi::firstOrCreate(
            ['tanggal' => $defaultDateAktif], // Kunci unik
            [
                // Data ini hanya akan dipakai saat migrate:fresh
                'is_default' => true,
                'tipe' => 'aktif',
                'waktu_mulai' => '07:00',
                'waktu_selesai' => '12:00',
                'hari_kerja' => [1, 2, 3, 4, 5], // Tipe data INTEGER (Sudah Benar)
                'keterangan' => 'Sesi Default Aktif (Hari Kerja)',
            ]
        );

        // 2. Default Libur
        // [FIX] Menggunakan firstOrCreate
        SesiAbsensi::firstOrCreate(
            ['tanggal' => $defaultDateLibur], // Kunci unik
            [
                'is_default' => true,
                'tipe' => 'libur',
                'waktu_mulai' => null,
                'waktu_selesai' => null,
                'hari_kerja' => [6, 7], // Tipe data INTEGER (Sudah Benar)
                'keterangan' => 'Sesi Default Libur (Akhir Pekan)',
            ]
        );

        // Ambil data terbaru (yang mungkin baru saja dibuat atau sudah ada)
        $defaultAktif = SesiAbsensi::where('tanggal', $defaultDateAktif)->first();

        // Variabel $defaultTimes ini dikirim ke view 'index.blade.php'
        $defaultTimes = [
            'waktu_mulai' => $defaultAktif->waktu_mulai,
            'waktu_selesai' => $defaultAktif->waktu_selesai,
            'hari_kerja' => $defaultAktif->hari_kerja ?? [],
        ];

        // Variabel $upcoming_days ini dikirim ke view 'index.blade.php'
        $upcoming_days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = today()->addDays($i);
            $status_info = $this->absensiService->getSessionStatus($date);
            $upcoming_days[] = [
                'date' => $date,
                'status_info' => $status_info,
            ];
        }

        return view('sesi_absensi.index', compact('defaultTimes', 'upcoming_days'));
    }

    /**
     * Menyimpan update.
     */
    public function storeOrUpdate(Request $request)
    {
        $defaultDateAktif = '1970-01-01';
        $defaultDateLibur = '1970-01-02';

        // LOGIKA UNTUK MODAL 1: Ubah Waktu Default
        if ($request->has('update_default')) {
            $validated = $request->validate([
                'waktu_mulai' => 'required|date_format:H:i',
                'waktu_selesai' => 'required|date_format:H:i|after:waktu_mulai',
                'hari_kerja' => 'nullable|array',
                'hari_kerja.*' => 'integer|between:1,7', // Validasi sudah benar
            ], [
                'waktu_selesai.after' => 'Waktu selesai harus setelah waktu mulai.',
                'hari_kerja.array' => 'Hari kerja harus berupa pilihan yang valid.'
            ]);


            $hariKerjaDipilih = array_map(
                'intval',
                $validated['hari_kerja'] ?? []
            );

            $hariLiburDefault = array_diff([1, 2, 3, 4, 5, 6, 7], $hariKerjaDipilih);

            // Update Sesi Default Aktif
            SesiAbsensi::updateOrCreate(
                ['tanggal' => $defaultDateAktif],
                [
                    'is_default' => true,
                    'tipe' => 'aktif',
                    'waktu_mulai' => $validated['waktu_mulai'],
                    'waktu_selesai' => $validated['waktu_selesai'],
                    'keterangan' => 'Sesi Default Aktif (Hari Kerja)',
                    'hari_kerja' => $hariKerjaDipilih, // Sekarang berisi INTEGER
                ]
            );

            // Update Sesi Default Libur
            SesiAbsensi::updateOrCreate(
                ['tanggal' => $defaultDateLibur],
                [
                    'is_default' => true,
                    'tipe' => 'libur',
                    'waktu_mulai' => null,
                    'waktu_selesai' => null,
                    'keterangan' => 'Sesi Default Libur (Akhir Pekan)',
                    'hari_kerja' => $hariLiburDefault, // Ini juga akan berisi INTEGER
                ]
            );

            Cache::forget('sesi_absensi_default_setting');

            // [FIX TYPO ROUTE] Pastikan route() pakai underscore
            return redirect()->route('sesi_absensi.index')->with('success', 'Pengaturan waktu default berhasil diperbarui.');
        }

        // ... (Logika Modal 2 Pengecualian Harian tidak perlu diubah) ...

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'tipe' => 'required|in:aktif,nonaktif,reset',
            'waktu_mulai' => 'nullable|required_if:tipe,aktif|date_format:H:i',
            'waktu_selesai' => 'nullable|required_if:tipe,aktif|date_format:H:i|after:waktu_mulai',
            'keterangan' => 'nullable|string|max:255',
        ], [
            'waktu_selesai.after' => 'Waktu selesai harus setelah waktu mulai untuk sesi pengecualian.'
        ]);

        if ($validated['tipe'] === 'reset') {
            SesiAbsensi::where('tanggal', $validated['tanggal'])->where('is_default', false)->delete();
            $message = 'Sesi untuk tanggal ' . Carbon::parse($validated['tanggal'])->isoFormat('D MMMM YYYY') . ' berhasil di-reset ke pengaturan default.';
        } else {
            $tipeDatabase = $validated['tipe'] === 'nonaktif' ? 'libur' : $validated['tipe'];

            SesiAbsensi::updateOrCreate(
                ['tanggal' => $validated['tanggal'], 'is_default' => false],
                [
                    'tipe' => $tipeDatabase,
                    'waktu_mulai' => $validated['tipe'] === 'aktif' ? $validated['waktu_mulai'] : null,
                    'waktu_selesai' => $validated['tipe'] === 'aktif' ? $validated['waktu_selesai'] : null,
                    'keterangan' => $validated['keterangan'],
                    'hari_kerja' => null,
                ]
            );
            $message = 'Pengecualian sesi untuk tanggal ' . Carbon::parse($validated['tanggal'])->isoFormat('D MMMM YYYY') . ' berhasil disimpan.';
        }

        // [FIX TYPO ROUTE]
        return redirect()->route('sesi_absensi.index')->with('success', $message);
    }

    /**
     * Menyediakan data untuk FullCalendar di 'index.blade.php'.
     */
    public function getCalendarEvents(Request $request)
    {
        $start = Carbon::parse($request->input('start'))->startOfDay();
        $end = Carbon::parse($request->input('end'))->endOfDay();
        $events = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $statusInfo = $this->absensiService->getSessionStatus($date);

            $title = $statusInfo['keterangan'] ?? $statusInfo['status'];
            $color = '#6c757d';

            if ($statusInfo['is_active']) {
                $color = '#198754';
            } elseif ($statusInfo['status'] == 'Libur Spesifik') {
                $color = '#dc3545';
                $title = $statusInfo['keterangan'] ? "Non-Aktif: " . $statusInfo['keterangan'] : "Non-Aktif (Sesi Spesifik)";
            } elseif ($statusInfo['status'] == 'Sesi Spesifik Aktif') {
                $title = $statusInfo['keterangan'] ? "Aktif: " . $statusInfo['keterangan'] : "Aktif (Sesi Spesifik)";
            }

            $events[] = [
                'title' => $title,
                'start' => $date->format('Y-m-d'),
                'allDay' => true,
                'backgroundColor' => $color,
                'borderColor' => $color,
            ];
        }
        return response()->json($events);
    }
}
// [FIX SINTAKSIS] Pastikan tidak ada '}' ekstra di sini