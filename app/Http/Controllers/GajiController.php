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


class GajiController extends Controller
{
    use ManagesImageEncoding;
    protected $salaryService;

    public function __construct(SalaryService $salaryService)
    {
        $this->salaryService = $salaryService;
    }

    /**
     * Menampilkan halaman utama kelola gaji dengan data yang sudah difilter.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // 2. Mengambil SEMUA data jabatan dari database untuk ditampilkan di filter
        $jabatans = Jabatan::orderBy('nama_jabatan')->get();
        $tunjanganKehadirans = TunjanganKehadiran::orderBy('jenis_tunjangan')->get();

        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        // 3. Menangkap ID jabatan yang dipilih bendahara dari filter
        $selectedJabatanId = $request->input('jabatan_id');

        $defaultTunjangan = $tunjanganKehadirans->first();
        $tarifKehadiran = $defaultTunjangan ? (int)$defaultTunjangan->jumlah_tunjangan : 0;

        // 4. Membangun query untuk karyawan
        $karyawanQuery = Karyawan::with('jabatan')->where('status_aktif', true);

        // 5. JIKA bendahara memilih jabatan, filter karyawan berdasarkan jabatan tersebut
        if ($selectedJabatanId) {
            $karyawanQuery->where('jabatan_id', $selectedJabatanId);
        }

        $karyawans = $karyawanQuery->orderBy('nama')->get();

        $dataGaji = $karyawans->map(function ($karyawan) use ($selectedMonth, $tarifKehadiran) {
            return $this->salaryService->calculateSalary($karyawan, $selectedMonth, $tarifKehadiran);
        });

        // 6. Mengirim daftar jabatan dan jabatan yang terpilih ke tampilan
        return view('gaji.index', [
            'dataGaji' => $dataGaji,
            'selectedMonth' => $selectedMonth,
            'jabatans' => $jabatans, // Dikirim untuk mengisi dropdown
            'selectedJabatanId' => $selectedJabatanId, // Dikirim untuk menandai pilihan
            'tunjanganKehadirans' => $tunjanganKehadirans,
            'selectedTunjanganId' => $defaultTunjangan ? $defaultTunjangan->id : null,
        ]);
    }

    /**
     * Menyimpan atau memperbarui data gaji dari modal edit.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveOrUpdate(Request $request)
    {
        // Validasi data dari form modal edit
        $validatedData = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'bulan' => 'required|date_format:Y-m',
            'jabatan_id' => 'nullable|exists:jabatans,id', // Ambil juga jabatan_id
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

        // Dapatkan tarif tunjangan berdasarkan ID yang dipilih di modal
        $tunjangan = TunjanganKehadiran::find($validatedData['tunjangan_kehadiran_id']);
        $validatedData['tarif_kehadiran_hidden'] = $tunjangan->jumlah_tunjangan;

        // Panggil service untuk menyimpan data
        $karyawan = $this->salaryService->saveSalaryData($validatedData);

        // Redirect kembali ke halaman index dengan membawa parameter filter 'bulan' dan 'jabatan_id'
        // agar tampilan tidak kembali ke default setelah menyimpan.
        return redirect()->route('gaji.index', $request->only(['bulan', 'jabatan_id']))
            ->with('success', 'Data gaji untuk ' . $karyawan->nama . ' berhasil diperbarui.');
    }

    /**
     * Memulai proses download slip gaji sebagai PDF.
     *
     * @param Gaji $gaji
     * @return \Illuminate\Http\JsonResponse
     */
    public function downloadSlip(Gaji $gaji)
    {
        GenerateIndividualSlip::dispatch($gaji, Auth::user());
        return response()->json(['message' => 'Permintaan cetak PDF diterima. Anda akan dinotifikasi jika sudah siap.']);
    }

    /**
     * Memulai proses pengiriman slip gaji ke email karyawan.
     *
     * @param Gaji $gaji
     * @return \Illuminate\Http\JsonResponse
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
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
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
