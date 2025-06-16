<?php

namespace App\Http\Controllers;

use App\Models\Gaji;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use App\Services\SalaryService; // <-- Import Service
use App\Traits\ManagesImageEncoding;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class GajiController extends Controller
{
    use ManagesImageEncoding;
    protected $salaryService;

    // Suntikkan service melalui constructor
    public function __construct(SalaryService $salaryService)
    {
        $this->salaryService = $salaryService;
    }

    public function index(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        $tarifKehadiran = $request->input('tarif_kehadiran', 10000);

        $karyawans = Karyawan::where('status_aktif', true)->orderBy('nama')->get();

        // Gunakan service untuk menghitung gaji setiap karyawan
        $dataGaji = $karyawans->map(function ($karyawan) use ($selectedMonth, $tarifKehadiran) {
            return $this->salaryService->calculateSalary($karyawan, $selectedMonth, $tarifKehadiran);
        });

        return view('gaji.index', [
            'dataGaji' => $dataGaji,
            'selectedMonth' => $selectedMonth,
            'tarifKehadiran' => $tarifKehadiran
        ]);
    }

    public function saveOrUpdate(Request $request)
    {
        // ... (validasi tetap sama) ...
        $validated = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'bulan' => 'required|date_format:Y-m',
            // ... field lainnya
        ]);

        $karyawan = Karyawan::find($validated['karyawan_id']);
        $tarifKehadiran = $request->input('tarif_kehadiran_hidden', 10000);

        // Hitung ulang tunjangan kehadiran sebelum menyimpan
        $gajiInstance = $this->salaryService->calculateSalary($karyawan, $validated['bulan'], $tarifKehadiran);

        $gaji_bersih = $this->salaryService->calculateNetSalary((new Gaji)->fill(array_merge($validated, [
            'tunj_kehadiran' => $gajiInstance->tunj_kehadiran
        ])));

        Gaji::updateOrCreate(
            ['karyawan_id' => $validated['karyawan_id'], 'bulan' => $validated['bulan']],
            array_merge($validated, [
                'tunj_kehadiran' => $gajiInstance->tunj_kehadiran,
                'gaji_bersih' => $gaji_bersih,
            ])
        );

        return redirect()->route('gaji.index', [
            'bulan' => $validated['bulan'],
            'tarif_kehadiran' => $tarifKehadiran
        ])->with('success', 'Data gaji untuk ' . $karyawan->nama . ' berhasil disimpan.');
    }

    public function cetakPDF($id)
    {
        $gaji = Gaji::findOrFail($id);

        // 3. Gunakan trait untuk membuat data gambar Base64
        $logoAlAzhar = $this->encodeImageToBase64(public_path('logo/logoalazhar.png'));
        $logoYayasan = $this->encodeImageToBase64(public_path('logo/logoyayasan.png'));

        // 4. Kirim data Base64 ke view bersama dengan data gaji
        $pdf = Pdf::loadView('gaji.slip_pdf', [
            'gaji' => $gaji,
            'logoAlAzhar' => $logoAlAzhar,
            'logoYayasan' => $logoYayasan
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('slip_gaji_' . $gaji->karyawan->nama . '.pdf');
    }
}
