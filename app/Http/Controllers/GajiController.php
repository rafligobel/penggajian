<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Gaji;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class GajiController extends Controller
{
    public function index(Request $request)
    {
        // Ambil input dari user, atau gunakan nilai default
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        $tarif_tunjangan_kehadiran = $request->input('tarif_kehadiran', 10000); // Default Rp 10.000
        $date = Carbon::createFromFormat('Y-m', $selectedMonth);

        $karyawans = Karyawan::where('status_aktif', true)->orderBy('nama')->get();
        $absensiBulanan = Absensi::whereYear('tanggal', $date->year)
            ->whereMonth('tanggal', $date->month)
            ->get()->groupBy('nip');

        $dataGaji = $karyawans->map(function ($karyawan) use ($absensiBulanan, $selectedMonth, $tarif_tunjangan_kehadiran) {

            $gajiBulanIni = Gaji::where('karyawan_id', $karyawan->id)
                ->where('bulan', $selectedMonth)
                ->first();

            $templateGaji = $gajiBulanIni ?? Gaji::where('karyawan_id', $karyawan->id)
                ->orderBy('bulan', 'desc')
                ->first();

            $gaji = new Gaji();
            if ($templateGaji) {
                $gaji->fill($templateGaji->getAttributes());
            }

            // Atur properti penting, pastikan data 'updated_at' berasal dari data bulan ini
            $gaji->id = $gajiBulanIni->id ?? null;
            $gaji->created_at = $gajiBulanIni->created_at ?? null;
            $gaji->updated_at = $gajiBulanIni->updated_at ?? null;
            $gaji->karyawan_id = $karyawan->id;
            $gaji->bulan = $selectedMonth;
            $gaji->karyawan = $karyawan;

            // Hitung komponen dinamis
            $jumlahKehadiran = $absensiBulanan->get($karyawan->nip, collect())->count();
            $gaji->jumlah_kehadiran = $jumlahKehadiran;
            $gaji->tunj_kehadiran = $jumlahKehadiran * $tarif_tunjangan_kehadiran;

            // Hitung ulang total gaji bersih
            $gaji->gaji_bersih = ($gaji->gaji_pokok ?? 0) +
                ($gaji->tunj_kehadiran) +
                ($gaji->tunj_jabatan ?? 0) +
                ($gaji->tunj_anak ?? 0) +
                ($gaji->tunj_komunikasi ?? 0) +
                ($gaji->tunj_pengabdian ?? 0) +
                ($gaji->tunj_kinerja ?? 0) +
                ($gaji->lembur ?? 0) +
                ($gaji->kelebihan_jam ?? 0) -
                ($gaji->potongan ?? 0);

            return $gaji;
        });

        return view('gaji.index', [
            'dataGaji' => $dataGaji,
            'selectedMonth' => $selectedMonth,
            'tarifKehadiran' => $tarif_tunjangan_kehadiran // Kirim tarif ke view
        ]);
    }

    // Method saveOrUpdate dan lainnya tidak perlu diubah dari versi sebelumnya
    public function saveOrUpdate(Request $request)
    {
        $validated = $request->validate([
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
        ]);

        $karyawan = Karyawan::find($validated['karyawan_id']);
        $date = Carbon::createFromFormat('Y-m', $validated['bulan']);

        // Dapatkan tarif dari request saat menyimpan, jika tidak ada, gunakan default
        $tarif_tunjangan_kehadiran = $request->input('tarif_kehadiran_hidden', 10000);

        $jumlahKehadiran = Absensi::where('nip', $karyawan->nip)
            ->whereYear('tanggal', $date->year)
            ->whereMonth('tanggal', $date->month)
            ->count();

        $tunjangan_kehadiran = $jumlahKehadiran * $tarif_tunjangan_kehadiran;

        $gaji_bersih = $validated['gaji_pokok'] + $tunjangan_kehadiran + $validated['tunj_jabatan'] + $validated['tunj_anak'] +
            $validated['tunj_komunikasi'] + $validated['tunj_pengabdian'] + $validated['tunj_kinerja'] +
            $validated['lembur'] + $validated['kelebihan_jam'] - $validated['potongan'];

        Gaji::updateOrCreate(
            ['karyawan_id' => $validated['karyawan_id'], 'bulan' => $validated['bulan']],
            array_merge($validated, [
                'tunj_kehadiran' => $tunjangan_kehadiran,
                'gaji_bersih' => $gaji_bersih,
            ])
        );

        return redirect()->route('gaji.index', [
            'bulan' => $validated['bulan'],
            'tarif_kehadiran' => $tarif_tunjangan_kehadiran
        ])->with('success', 'Data gaji untuk ' . $karyawan->nama . ' berhasil disimpan.');
    }

    public function cetakPDF($id)
    {
        $gaji = Gaji::findOrFail($id);
        $pdf = Pdf::loadView('gaji.slip_pdf', compact('gaji'))->setPaper('A4', 'portrait');
        return $pdf->stream('slip_gaji_' . $gaji->karyawan->nama . '.pdf');
    }
}
