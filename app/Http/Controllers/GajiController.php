<?php

namespace App\Http\Controllers;

use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\TunjanganKehadiran;
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
    protected SalaryService $salaryService;

    public function __construct(SalaryService $salaryService)
    {
        $this->salaryService = $salaryService;
    }

    public function index(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));

        // Eager loading untuk mencegah N+1 Query Problem
        $karyawans = Karyawan::with('jabatan')
            ->where('status_aktif', true)
            ->orderBy('nama')
            ->get();

        $dataGaji = [];
        foreach ($karyawans as $karyawan) {
            // Service akan melakukan semua pekerjaan berat
            $dataGaji[] = $this->salaryService->calculateDetailsForForm($karyawan, $selectedMonth);
        }

        $tunjanganKehadirans = TunjanganKehadiran::all();
        return view('gaji.index', compact('dataGaji', 'selectedMonth', 'tunjanganKehadirans'));
    }

    public function saveOrUpdate(Request $request)
    {
        // --- VALIDASI DIKEMBALIKAN KE VERSI LENGKAP & BENAR ---
        $validated = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'bulan' => 'required|date_format:Y-m',
            'tunjangan_kehadiran_id' => 'required|exists:tunjangan_kehadirans,id',
            'gaji_pokok' => 'required|numeric|min:0',
            'tunj_anak' => 'required|numeric|min:0',
            'tunj_komunikasi' => 'required|numeric|min:0',
            'tunj_pengabdian' => 'required|numeric|min:0',
            'tunj_kinerja' => 'required|numeric|min:0',
            'lembur' => 'required|numeric|min:0',
            'kelebihan_jam' => 'required|numeric|min:0',
            'potongan' => 'required|numeric|min:0',
        ]);

        // Panggil service untuk menyimpan data
        $gaji = $this->salaryService->saveGaji($validated);

        // Ambil kembali data yang sudah terhitung ulang untuk dikirim ke frontend
        $newData = $this->salaryService->calculateDetailsForForm($gaji->karyawan, $validated['bulan']);

        return response()->json([
            'success' => true,
            'message' => 'Data gaji untuk ' . $gaji->karyawan->nama . ' berhasil diperbarui.',
            'newData' => $newData
        ]);
    }

    public function downloadSlip(Gaji $gaji)
    {
        GenerateIndividualSlip::dispatch($gaji->id, Auth::id());
        return response()->json(['message' => 'Proses pembuatan slip gaji dimulai.']);
    }

    public function sendEmail(Gaji $gaji)
    {
        if (empty($gaji->karyawan->email)) {
            return response()->json(['message' => 'Gagal. Karyawan ini tidak memiliki alamat email.'], 422);
        }
        SendSlipToEmail::dispatch($gaji->id, Auth::id());
        return response()->json(['message' => 'Proses pengiriman email dimulai.']);
    }

    public function cetakPDF($id)
    {
        $gaji = Gaji::findOrFail($id);
        // Selalu panggil service untuk mendapatkan data final yang terhitung
        $data = $this->salaryService->calculateDetailsForForm($gaji->karyawan, $gaji->bulan);

        if (!$data) {
            abort(404, 'Data gaji tidak ditemukan');
        }

        $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
        $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));
        $bendaharaUser = User::where('role', 'bendahara')->first();
        $bendaharaNama = $bendaharaUser ? $bendaharaUser->name : 'Bendahara Umum';

        $pdf = Pdf::loadView('gaji.slip_pdf', [
            'data' => $data,
            'gaji' => $gaji, // Gaji di sini hanya untuk ID, tanggal, dll.
            'logoAlAzhar' => $logoAlAzhar,
            'logoYayasan' => $logoYayasan,
            'bendaharaNama' => $bendaharaNama,
        ]);

        return $pdf->stream('slip-gaji-' . $gaji->karyawan->nama . '-' . $gaji->bulan . '.pdf');
    }
}
