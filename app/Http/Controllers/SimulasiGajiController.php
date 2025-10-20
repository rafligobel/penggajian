<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SalaryService;
use Illuminate\Support\Facades\Auth;

class SimulasiGajiController extends Controller
{
    protected $salaryService;

    public function __construct(SalaryService $salaryService)
    {
        $this->salaryService = $salaryService;
    }

    /**
     * Menangani perhitungan simulasi gaji.
     * Didesain untuk merespons permintaan AJAX dari modal.
     */
    public function hitung(Request $request)
    {
        // [PERBAIKAN] Menambahkan validasi untuk semua field di form
        $validated = $request->validate([
            'jumlah_hari_masuk' => 'required|integer|min:0|max:31',
            'tunj_anak'         => 'nullable|numeric|min:0',
            'tunj_komunikasi'   => 'nullable|numeric|min:0',
            'tunj_pengabdian'   => 'nullable|numeric|min:0',
            'tunj_kinerja'      => 'nullable|numeric|min:0',
            'lembur'            => 'nullable|numeric|min:0',
            'potongan'          => 'nullable|numeric|min:0',
        ]);

        $karyawan = Auth::user()->karyawan;

        if (!$karyawan) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Data karyawan Anda tidak ditemukan.'], 404);
            }
            return back()->with('error', "Data karyawan Anda tidak ditemukan.");
        }

        // Gunakan service untuk menghitung simulasi
        // Service akan mengambil $validated sebagai $input
        $hasilSimulasi = $this->salaryService->calculateSimulasi($karyawan, $validated);

        $hasil = [
            'karyawan'          => $karyawan,
            'jumlah_hari_masuk' => $validated['jumlah_hari_masuk'],
            'rincian'           => $hasilSimulasi,
            'gaji_bersih'       => $hasilSimulasi['gaji_bersih'],
        ];

        // Jika ini request AJAX, kembalikan HANYA view untuk konten modal
        if ($request->ajax()) {
            return view('tenaga_kerja.modals.hasil', compact('hasil'))->render();
        }

        // Fallback jika diakses langsung (seharusnya tidak terjadi)
        return redirect()->route('tenaga_kerja.dashboard')->with('error', 'Akses tidak sah.');
    }
}
