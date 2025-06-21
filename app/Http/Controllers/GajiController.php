<?php

namespace App\Http\Controllers;

use App\Models\Gaji;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use App\Services\SalaryService;
use App\Traits\ManagesImageEncoding;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth; // <-- Tambahkan ini
use App\Jobs\GenerateIndividualSlip; // <-- Tambahkan ini
use App\Jobs\SendSlipToEmail;       // <-- Tambahkan ini


class GajiController extends Controller
{
    use ManagesImageEncoding;
    protected $salaryService;

    public function __construct(SalaryService $salaryService)
    {
        $this->salaryService = $salaryService;
    }

    public function index(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        $tarifKehadiran = $request->input('tarif_kehadiran', 10000);

        $karyawans = Karyawan::where('status_aktif', true)->orderBy('nama')->get();

        $dataGaji = $karyawans->map(function ($karyawan) use ($selectedMonth, $tarifKehadiran) {
            return $this->salaryService->calculateSalary($karyawan, $selectedMonth, $tarifKehadiran);
        });

        return view('gaji.index', [
            'dataGaji' => $dataGaji,
            'selectedMonth' => $selectedMonth,
            'tarifKehadiran' => $tarifKehadiran
        ]);
    }

    // app/Http/Controllers/GajiController.php

    public function saveOrUpdate(Request $request)
    {
        // Validasi semua field yang relevan dari form
        $validatedData = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'bulan' => 'required|date_format:Y-m',
            'gaji_pokok' => 'required|numeric|min:0',
            'tunj_jabatan' => 'required|numeric|min:0',
            'tunj_anak' => 'required|numeric|min:0',
            'tunj_komunikasi' => 'required|numeric|min:0',
            'tunj_pengabdian' => 'required|numeric|min:0',
            'tunj_kinerja' => 'required|numeric|min:0',
            'lembur' => 'required|numeric|min:0',
            'kelebihan_jam' => 'required|numeric|min:0',
            'potongan' => 'required|numeric|min:0',
            'tarif_kehadiran_hidden' => 'required|numeric|min:0',
        ]);

        // Panggil service untuk menangani penyimpanan
        $karyawan = $this->salaryService->saveSalaryData($validatedData);

        return redirect()->route('gaji.index', [
            'bulan' => $validatedData['bulan'],
            'tarif_kehadiran' => $validatedData['tarif_kehadiran_hidden']
        ])->with('success', 'Data gaji untuk ' . $karyawan->nama . ' berhasil diperbarui.');
    }

    /**
     * Menangani permintaan untuk mencetak slip PDF individual di latar belakang.
     */
    public function downloadSlip(Request $request, Gaji $gaji)
    {
        GenerateIndividualSlip::dispatch($gaji, Auth::user());

        return response()->json(['message' => 'Permintaan cetak PDF diterima. Anda akan dinotifikasi jika sudah siap.']);
    }

    /**
     * Menangani permintaan untuk mengirim slip PDF ke email di latar belakang.
     */
    public function sendEmail(Request $request, Gaji $gaji)
    {
        if (empty($gaji->karyawan->email)) {
            return response()->json(['message' => 'Gagal. Karyawan ini tidak memiliki alamat email.'], 422);
        }

        // Kirim ID-nya, bukan seluruh objek
        SendSlipToEmail::dispatch($gaji->id, Auth::id());

        return response()->json(['message' => 'Proses pengiriman email dimulai. Anda akan dinotifikasi jika berhasil.']);
    }

    /**
     * Method ini tidak lagi digunakan oleh UI, tapi bisa dipertahankan untuk referensi atau debugging.
     */
    public function cetakPDF($id)
    {
        $gaji = Gaji::findOrFail($id);
        $logoAlAzhar = $this->encodeImageToBase64(public_path('logo/logoalazhar.png'));
        $logoYayasan = $this->encodeImageToBase64(public_path('logo/logoyayasan.png'));

        $pdf = Pdf::loadView('gaji.slip_pdf', [
            'gaji' => $gaji,
            'logoAlAzhar' => $logoAlAzhar,
            'logoYayasan' => $logoYayasan
        ]);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('slip_gaji_' . $gaji->karyawan->nama . '.pdf');
    }
}
