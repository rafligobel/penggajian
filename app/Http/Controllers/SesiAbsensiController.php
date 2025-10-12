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

    public function index()
    {
        $defaultDate = '1970-01-01';
        $defaultSetting = SesiAbsensi::firstOrCreate(
            ['tanggal' => $defaultDate, 'is_default' => true],
            [
                'tipe' => 'default',
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

    public function storeOrUpdate(Request $request)
    {
        if ($request->has('update_default')) {
            $validated = $request->validate([
                'waktu_mulai' => 'required|date_format:H:i',
                'waktu_selesai' => 'required|date_format:H:i|after:waktu_mulai',
                'hari_kerja' => 'nullable|array',
                'hari_kerja.*' => 'integer|between:1,7',
            ], [
                'waktu_selesai.after' => 'Waktu selesai harus setelah waktu mulai.',
                'hari_kerja.array' => 'Hari kerja harus berupa pilihan yang valid.'
            ]);

            SesiAbsensi::updateOrCreate(
                ['tanggal' => '1970-01-01', 'is_default' => true],
                [
                    'tipe' => 'default',
                    'waktu_mulai' => $validated['waktu_mulai'],
                    'waktu_selesai' => $validated['waktu_selesai'],
                    'keterangan' => 'Pengaturan Waktu Default',
                    'hari_kerja' => $validated['hari_kerja'] ?? [],
                ]
            );

            Cache::forget('sesi_absensi_default_setting');
            return redirect()->route('sesi-absensi.index')->with('success', 'Pengaturan waktu default berhasil diperbarui.');
        }

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
            SesiAbsensi::updateOrCreate(
                ['tanggal' => $validated['tanggal'], 'is_default' => false],
                [
                    'tipe' => $validated['tipe'],
                    'waktu_mulai' => $validated['tipe'] === 'aktif' ? $validated['waktu_mulai'] : null,
                    'waktu_selesai' => $validated['tipe'] === 'aktif' ? $validated['waktu_selesai'] : null,
                    'keterangan' => $validated['keterangan'],
                    'hari_kerja' => null,
                ]
            );
            $message = 'Pengecualian sesi untuk tanggal ' . Carbon::parse($validated['tanggal'])->isoFormat('D MMMM YYYY') . ' berhasil disimpan.';
        }

        return redirect()->route('sesi-absensi.index')->with('success', $message);
    }

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
}
