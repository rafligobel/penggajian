<?php

namespace App\Http\Controllers;

use App\Models\SesiAbsensi;
use App\Services\AbsensiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SesiAbsensiController extends Controller
{
    protected $absensiService;

    public function __construct(AbsensiService $absensiService)
    {
        $this->absensiService = $absensiService;
    }

    /**
     * Menampilkan halaman utama dan memastikan data default selalu ada.
     */
    public function index()
    {
        $defaultDate = '1970-01-01';

        $defaultSetting = SesiAbsensi::firstOrCreate(
            ['tanggal' => $defaultDate],
            [
                'tipe' => 'default',
                'is_default' => true,
                'waktu_mulai' => '07:00',
                'waktu_selesai' => '17:00',
                'hari_kerja' => [1, 2, 3, 4, 5], // Default: Senin-Jumat
                'keterangan' => 'Pengaturan Waktu Default',
            ]
        );

        $defaultTimes = [
            'waktu_mulai' => $defaultSetting->waktu_mulai,
            'waktu_selesai' => $defaultSetting->waktu_selesai,
            'hari_kerja' => $defaultSetting->hari_kerja ?? [],
        ];

        $upcoming_days = [];
        // Pastikan variabel $i menggunakan tanda dolar
        for ($i = 0; $i < 7; $i++) {
            $date = today()->addDays($i);
            $upcoming_days[] = [
                'date' => $date,
                'status_info' => $this->absensiService->getSessionStatus($date),
            ];
        }

        return view('sesi_absensi.index', compact('defaultTimes', 'upcoming_days'));
    }

    /**
     * Memperbarui satu-satunya record pengaturan waktu default.
     */
    public function updateDefaultTime(Request $request)
    {
        // PENYEDERHANAAN DAN PERBAIKAN:
        // Aturan validasi dikembalikan ke format standar 'H:i' (Jam:Menit)
        // karena input type="time" pada HTML secara default mengirimkan format ini.
        // Ini adalah cara yang paling sederhana dan benar untuk mengatasi error format.
        $request->validate([
            'waktu_mulai' => 'required',
            'waktu_selesai' => 'required|after:waktu_mulai',
            'hari_kerja' => 'nullable|array',
            'hari_kerja.*' => 'integer|between:1,7',
        ]);

        $data = SesiAbsensi::where('is_default', true)->first();

        $defaultDate = '1970-01-01';

        $waktu_mulai = $request->waktu_mulai ?? $data->waktu_mulai;
        $waktu_selesai = $request->waktu_selesai ?? $data->waktu_selesai;


        // $waktu_mulai = Carbon::createFromFormat('H.i', $request->waktu_mulai)->format('H:i');
        // $waktu_selesai = Carbon::createFromFormat('H.i', $request->waktu_selesai)->format('H:i');

        SesiAbsensi::updateOrCreate(
            ['tanggal' => $defaultDate],
            [
                'tipe' => 'default',
                'is_default' => true,
                'waktu_mulai' => $waktu_mulai,
                'waktu_selesai' => $waktu_selesai,
                'keterangan' => 'Pengaturan Waktu Default',
                'hari_kerja' => $request->input('hari_kerja', []),
            ]
        );

        Cache::forget('default_session_times');

        return redirect()->route('sesi-absensi.index')->with('success', 'Pengaturan waktu default berhasil diperbarui.');
    }

    /**
     * Menyediakan data event untuk kalender modal.
     */
    public function getCalendarEvents(Request $request)
    {
        $start = Carbon::parse($request->input('start'))->startOfDay();
        $end = Carbon::parse($request->input('end'))->endOfDay();
        $events = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $statusInfo = $this->absensiService->getSessionStatus($date);
            $events[] = [
                'title' => $statusInfo['keterangan'] ?? $statusInfo['status'],
                'start' => $date->format('Y-m-d'),
                'allDay' => true,
                'backgroundColor' => $statusInfo['is_active'] ? '#198754' : '#6c757d',
                'borderColor' => $statusInfo['is_active'] ? '#198754' : '#6c757d',
            ];
        }
        return response()->json($events);
    }

    /**
     * Menyimpan atau mereset pengecualian sesi untuk tanggal tertentu.
     */
    public function storeException(Request $request)
    {
        // PENYEDERHANAAN DAN PERBAIKAN:
        // Aturan validasi disesuaikan juga ke format standar 'H:i' untuk konsistensi.
        $request->validate([
            'tanggal' => 'required|date',
            'tipe' => 'required|in:aktif,nonaktif,reset',
            'waktu_mulai' => 'nullable|required_if:tipe,aktif|date_format:H:i',
            'waktu_selesai' => 'nullable|required_if:tipe,aktif|date_format:H:i|after:waktu_mulai',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($request->tipe === 'reset') {
            SesiAbsensi::where('tanggal', $request->tanggal)->where('is_default', false)->delete();
            return back()->with('success', 'Sesi untuk tanggal ' . $request->tanggal . ' berhasil direset.');
        }

        SesiAbsensi::updateOrCreate(
            ['tanggal' => $request->tanggal],
            [
                'tipe' => $request->tipe,
                'waktu_mulai' => $request->waktu_mulai,
                'waktu_selesai' => $request->waktu_selesai,
                'keterangan' => $request->keterangan,
                'is_default' => false,
            ]
        );
        return back()->with('success', 'Pengecualian sesi untuk tanggal ' . $request->tanggal . ' berhasil disimpan.');
    }
}
