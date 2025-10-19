<?php

namespace App\Http\Controllers;

use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\TunjanganKehadiran;
use Illuminate\Http\Request;
use App\Services\SalaryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Jobs\GenerateIndividualSlip;
use App\Jobs\SendSlipToEmail;

class GajiController extends Controller
{
    protected SalaryService $salaryService;

    public function __construct(SalaryService $salaryService)
    {
        $this->salaryService = $salaryService;
    }

    public function index(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));

        // [PERBAIKAN] Menghapus filter 'status_aktif'
        $karyawans = Karyawan::with('jabatan')->orderBy('nama')->get();

        $dataGaji = [];
        foreach ($karyawans as $karyawan) {
            $dataGaji[] = $this->salaryService->calculateDetailsForForm($karyawan, $selectedMonth);
        }

        $tunjanganKehadirans = TunjanganKehadiran::all();
        return view('gaji.index', compact('dataGaji', 'selectedMonth', 'tunjanganKehadirans'));
    }

    public function saveOrUpdate(Request $request)
    {
        $validatedData = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'bulan' => 'required|date_format:Y-m', // Validasi tetap Y-m
            'gaji_pokok' => 'required|numeric|min:0',
            'tunj_anak' => 'required|numeric|min:0',
            'tunj_komunikasi' => 'required|numeric|min:0',
            'tunj_pengabdian' => 'required|numeric|min:0',
            'tunj_kinerja' => 'required|numeric|min:0',
            'lembur' => 'required|numeric|min:0',
            'potongan' => 'required|numeric|min:0',
            'tunjangan_kehadiran_id' => 'required|exists:tunjangan_kehadirans,id',
        ]);

        // [PERBAIKAN] Tidak perlu konversi tanggal di controller.
        // Serahkan data 'Y-m' langsung ke service, biarkan service
        // (yang sudah diperbaiki) menanganinya.
        $this->salaryService->saveGaji($validatedData);

        $karyawan = Karyawan::find($validatedData['karyawan_id']);

        // Ambil data terbaru dari service untuk dikirim balik ke view
        $newData = $this->salaryService->calculateDetailsForForm($karyawan, $validatedData['bulan']);

        return response()->json([
            'success' => true,
            'message' => 'Data gaji berhasil disimpan.',
            'newData' => $newData
        ]);
    }

    // Fungsi lain tidak berubah
    public function downloadSlip(Gaji $gaji)
    {
        GenerateIndividualSlip::dispatch($gaji->id, Auth::id());
        return response()->json(['message' => 'Permintaan diterima! Slip sedang dibuat & akan muncul di notifikasi jika siap.']);
    }

    public function sendEmail(Gaji $gaji)
    {
        if (empty($gaji->karyawan->email)) {
            return response()->json(['message' => 'Gagal. Karyawan ini tidak memiliki alamat email.'], 422);
        }

        SendSlipToEmail::dispatch([$gaji->id], Auth::id());

        return response()->json(['message' => 'Permintaan diterima! Email sedang dikirim & notifikasi akan muncul jika berhasil.']);
    }
}