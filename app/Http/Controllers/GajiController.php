<?php

namespace App\Http\Controllers;

use App\Models\Gaji;
use App\Models\Jabatan;
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
use Illuminate\Support\Facades\DB;


class GajiController extends Controller
{
    use ManagesImageEncoding;
    protected $salaryService;

    public function __construct(SalaryService $salaryService)
    {
        $this->salaryService = $salaryService;
    }

    /**
     * Menampilkan halaman utama kelola gaji.
     * Termasuk logika untuk menyalin data gaji secara otomatis dari bulan terakhir yang tersedia.
     */
    public function index(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));

        // --- LOGIKA PENYALINAN OTOMATIS YANG DISEMPURNAKAN ---
        $gajiExists = Gaji::where('bulan', $selectedMonth)->exists();

        // Jika bulan yang dipilih belum ada data gajinya, coba salin dari bulan terakhir yang ada datanya.
        if (!$gajiExists) {
            // Cari data gaji terakhir yang ada di database, diurutkan berdasarkan bulan.
            $gajiTerakhir = Gaji::orderBy('bulan', 'desc')->first();

            if ($gajiTerakhir) {
                $bulanSumber = $gajiTerakhir->bulan;

                // Panggil metode private untuk menyalin data
                $isSuccess = $this->salinGajiSecaraOtomatis($bulanSumber, $selectedMonth);

                if ($isSuccess) {
                    $bulanSumberFormatted = Carbon::createFromFormat('Y-m', $bulanSumber)->locale('id')->translatedFormat('F Y');
                    // Beri pesan sukses dan muat ulang halaman untuk menampilkan data baru
                    return redirect()->route('gaji.index', ['bulan' => $selectedMonth])
                        ->with('success', 'Data gaji telah disiapkan secara otomatis berdasarkan data dari bulan ' . $bulanSumberFormatted . '.');
                }
            }
        }
        // --- AKHIR LOGIKA OTOMATIS ---

        $jabatans = Jabatan::orderBy('nama_jabatan')->get();
        $tunjanganKehadirans = TunjanganKehadiran::orderBy('jenis_tunjangan')->get();
        $selectedJabatanId = $request->input('jabatan_id');
        $defaultTunjangan = $tunjanganKehadirans->first();

        // Eksekusi query untuk mendapatkan data karyawan yang relevan
        $karyawanQuery = Karyawan::with('jabatan')->where('status_aktif', true);
        if ($selectedJabatanId) {
            $karyawanQuery->where('jabatan_id', $selectedJabatanId);
        }
        $karyawans = $karyawanQuery->orderBy('nama')->get();

        // Memanggil service untuk kalkulasi dan penampilan data
        $dataGaji = $karyawans->map(function ($karyawan) use ($selectedMonth) {
            return $this->salaryService->calculateDetailsForForm($karyawan, $selectedMonth);
        });

        return view('gaji.index', [
            'dataGaji' => $dataGaji,
            'selectedMonth' => $selectedMonth,
            'jabatans' => $jabatans,
            'selectedJabatanId' => $selectedJabatanId,
            'tunjanganKehadirans' => $tunjanganKehadirans,
            'selectedTunjanganId' => $defaultTunjangan ? $defaultTunjangan->id : null,
        ]);
    }

    /**
     * Menyimpan atau memperbarui data gaji dari modal edit.
     */
    public function saveOrUpdate(Request $request)
    {
        $validatedData = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'bulan' => 'required|date_format:Y-m',
            'jabatan_id' => 'nullable|exists:jabatans,id',
            'tunjangan_kehadiran_id' => 'required|exists:tunjangan_kehadirans,id',
            'gaji_pokok' => 'required|numeric|min:0',
            'tunj_jabatan' => 'required|numeric|min:0',
            'tunj_anak' => 'required|numeric|min:0',
            'tunj_komunikasi' => 'required|numeric|min:0',
            'tunj_pengabdian' => 'required|numeric|min:0',
            'tunj_kinerja' => 'required|numeric|min:0',
            'lembur' => 'required|numeric|min:0',
            'kelebihan_jam' => 'required|numeric|min:0',
            'potongan' => 'required|numeric|min:0',
        ]);

        $this->salaryService->saveOrUpdateSalary($validatedData);
        $karyawan = Karyawan::find($validatedData['karyawan_id']);

        return redirect()->route('gaji.index', $request->only(['bulan', 'jabatan_id']))
            ->with('success', 'Data gaji untuk ' . $karyawan->nama . ' berhasil diperbarui.');
    }

    /**
     * Metode private untuk menyalin data gaji secara otomatis.
     */
    private function salinGajiSecaraOtomatis(string $bulanSumber, string $bulanTarget): bool
    {
        $gajiBulanLalu = Gaji::where('bulan', $bulanSumber)->get();
        $defaultTunjanganKehadiran = TunjanganKehadiran::first();

        if ($gajiBulanLalu->isEmpty() || !$defaultTunjanganKehadiran) {
            return false;
        }

        DB::beginTransaction();
        try {
            foreach ($gajiBulanLalu as $gajiLama) {
                $dataBaru = [
                    'karyawan_id' => $gajiLama->karyawan_id,
                    'bulan' => $bulanTarget,
                    'jabatan_id' => $gajiLama->karyawan->jabatan_id,
                    'tunjangan_kehadiran_id' => $defaultTunjanganKehadiran->id,
                    'gaji_pokok' => $gajiLama->gaji_pokok,
                    'tunj_jabatan' => $gajiLama->tunj_jabatan,
                    'tunj_anak' => $gajiLama->tunj_anak,
                    'tunj_komunikasi' => $gajiLama->tunj_komunikasi,
                    'tunj_pengabdian' => $gajiLama->tunj_pengabdian,
                    'tunj_kinerja' => $gajiLama->tunj_kinerja,
                    'lembur' => $gajiLama->lembur,
                    'kelebihan_jam' => $gajiLama->kelebihan_jam,
                    'potongan' => $gajiLama->potongan,
                ];

                $this->salaryService->saveOrUpdateSalary($dataBaru);
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return false;
        }
    }

    /**
     * Memulai proses download slip gaji sebagai PDF.
     */
    public function downloadSlip(Gaji $gaji)
    {
        GenerateIndividualSlip::dispatch($gaji, Auth::user());
        return response()->json(['message' => 'Permintaan cetak PDF diterima. Anda akan dinotifikasi jika sudah siap.']);
    }

    /**
     * Memulai proses pengiriman slip gaji ke email karyawan.
     */
    public function sendEmail(Gaji $gaji)
    {
        if (empty($gaji->karyawan->email)) {
            return response()->json(['message' => 'Gagal. Karyawan ini tidak memiliki alamat email.'], 422);
        }
        SendSlipToEmail::dispatch($gaji->id, Auth::id());
        return response()->json(['message' => 'Proses pengiriman email dimulai. Anda akan dinotifikasi jika berhasil.']);
    }

    /**
     * Menghasilkan dan men-stream file PDF slip gaji secara langsung.
     */
    public function cetakPDF($id)
    {
        $gaji = Gaji::with('karyawan')->findOrFail($id);

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
            'gaji' => $gaji, // Tetap kirimkan objek gaji untuk referensi jika perlu
            'logoAlAzhar' => $logoAlAzhar,
            'logoYayasan' => $logoYayasan,
            'bendaharaNama' => $bendaharaNama
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('slip_gaji_' . $gaji->karyawan->nama . '.pdf');
    }
}
