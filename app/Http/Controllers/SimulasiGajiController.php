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
        $validated = $request->validate([
            'jumlah_hari_masuk' => 'required|integer|min:0|max:31',
            'lembur' => 'nullable|numeric|min:0',
            'potongan' => 'nullable|numeric|min:0',
        ]);

        $karyawan = Auth::user()->karyawan;

        // Jika karyawan tidak ditemukan, kirim respons error
        if (!$karyawan) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Data karyawan Anda tidak ditemukan.'], 404);
            }
            return back()->with('error', "Data karyawan Anda tidak ditemukan.");
        }

        // Gunakan service untuk menghitung simulasi
        $hasilSimulasi = $this->salaryService->calculateSimulation($karyawan, $validated);

        // Siapkan data untuk dikirim ke view
        $hasil = [
            'karyawan' => $karyawan,
            'jumlah_hari_masuk' => $validated['jumlah_hari_masuk'],
            'rincian' => $hasilSimulasi, // Langsung gunakan hasil dari service
            'gaji_bersih' => $hasilSimulasi['gaji_bersih'],
        ];

        // **BAGIAN TERPENTING:** Cek apakah ini request AJAX
        if ($request->ajax()) {
            // Jika ya, kembalikan HANYA view untuk konten modal
            return view('tenaga_kerja.modals.hasil', compact('hasil'))->render();
        }

        // Fallback jika diakses secara langsung (seharusnya tidak terjadi dalam alur normal)
        // Anda perlu membuat view 'simulasi.hasil_penuh' jika ingin fallback ini berfungsi
        return view('simulasi.hasil_penuh', compact('hasil'));
    }
}
