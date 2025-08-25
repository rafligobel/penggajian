<?php

namespace App\Http\Controllers;

use App\Models\Gaji;
use App\Models\Jabatan;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use App\Services\SalaryService;
use App\Traits\ManagesImageEncoding;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Jobs\GenerateIndividualSlip;
use App\Jobs\SendSlipToEmail;
use App\Models\User;


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
        $selectedJabatanId = $request->input('jabatan_id');

        $karyawanQuery = Karyawan::with('jabatan')
            ->where('status_aktif', true);

        if ($selectedJabatanId) {
            $karyawanQuery->where('jabatan_id', $selectedJabatanId);
        }

        $karyawans = $karyawanQuery->orderBy('nama')->get();

        $dataGaji = $karyawans->map(function ($karyawan) use ($selectedMonth, $tarifKehadiran) {
            return $this->salaryService->calculateSalary($karyawan, $selectedMonth, $tarifKehadiran);
        });

        $totalGajiBersih = $dataGaji->sum('gaji_bersih');
        $jumlahKaryawan = $dataGaji->count();
        $jabatans = Jabatan::orderBy('nama_jabatan')->get();

        return view('gaji.index', [
            'dataGaji' => $dataGaji,
            'selectedMonth' => $selectedMonth,
            'tarifKehadiran' => $tarifKehadiran,
            'jabatans' => $jabatans,
            'selectedJabatanId' => $selectedJabatanId,
            'totalGajiBersih' => $totalGajiBersih,
            'jumlahKaryawan' => $jumlahKaryawan,
        ]);
    }

    public function saveOrUpdate(Request $request)
    {
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
        $karyawan = $this->salaryService->saveSalaryData($validatedData);
        return redirect()->route('gaji.index', [
            'bulan' => $validatedData['bulan'],
            'tarif_kehadiran' => $validatedData['tarif_kehadiran_hidden']
        ])->with('success', 'Data gaji untuk ' . $karyawan->nama . ' berhasil diperbarui.');
    }

    public function downloadSlip(Request $request, Gaji $gaji)
    {
        GenerateIndividualSlip::dispatch($gaji, Auth::user());
        return response()->json(['message' => 'Permintaan cetak PDF diterima. Anda akan dinotifikasi jika sudah siap.']);
    }

    public function sendEmail(Request $request, Gaji $gaji)
    {
        if (empty($gaji->karyawan->email)) {
            return response()->json(['message' => 'Gagal. Karyawan ini tidak memiliki alamat email.'], 422);
        }
        SendSlipToEmail::dispatch($gaji->id, Auth::id());
        return response()->json(['message' => 'Proses pengiriman email dimulai. Anda akan dinotifikasi jika berhasil.']);
    }

    public function cetakPDF($id)
    {
        $gaji = Gaji::findOrFail($id);
        $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
        $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));
        $bendaharaUser = User::where('role', 'bendahara')->first();
        $bendaharaNama = $bendaharaUser ? $bendaharaUser->name : 'Bendahara Umum';
        $pdf = Pdf::loadView('gaji.slip_pdf', [
            'gaji' => $gaji,
            'logoAlAzhar' => $logoAlAzhar,
            'logoYayasan' => $logoYayasan,
            'bendaharaNama' => $bendaharaNama
        ]);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('slip_gaji_' . $gaji->karyawan->nama . '.pdf');
    }
}
