<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Gaji;
use App\Services\SalaryService; // <-- Import Service

class SimulasiGajiController extends Controller
{
    protected $salaryService;

    // Suntikkan service melalui constructor
    public function __construct(SalaryService $salaryService)
    {
        $this->salaryService = $salaryService;
    }

    public function index()
    {
        return view('simulasi.index');
    }

    public function hitung(Request $request)
    {
        $validated = $request->validate([
            'karyawan_query' => 'required|string|min:3',
            'jumlah_hari_masuk' => 'required|integer|min:0|max:31',
            'lembur' => 'nullable|numeric|min:0',
            'potongan' => 'nullable|numeric|min:0',
        ]);

        // ... (Logika pencarian karyawan tidak berubah) ...
        $karyawan = Karyawan::where('nip', $validated['karyawan_query'])
            ->orWhere('nama', 'LIKE', "%{$validated['karyawan_query']}%")
            ->first();

        if (!$karyawan) {
            return redirect()->back()->withInput()->with('error', "Karyawan tidak ditemukan.");
        }

        $templateGaji = Gaji::where('karyawan_id', $karyawan->id)->orderBy('bulan', 'desc')->first();
        if (!$templateGaji) {
            return redirect()->back()->withInput()->with('error', 'Data gaji sebelumnya untuk karyawan ini tidak ditemukan.');
        }

        // Gunakan service untuk menghitung gaji
        $gajiHasilSimulasi = $this->salaryService->calculateSalary(
            $karyawan,
            now()->format('Y-m'), // Periode tidak relevan untuk simulasi, hanya untuk struktur
            10000, // Tarif default tunjangan kehadiran
            $validated // Kirim input dari form simulasi
        );

        $hasil = [
            'karyawan' => $karyawan,
            'jumlah_hari_masuk' => $validated['jumlah_hari_masuk'],
            'rincian' => $gajiHasilSimulasi->toArray(),
            'gaji_bersih' => $gajiHasilSimulasi->gaji_bersih,
        ];

        return view('simulasi.hasil', compact('hasil'));
    }
}
