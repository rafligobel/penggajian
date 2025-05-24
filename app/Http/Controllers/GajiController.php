<?php

namespace App\Http\Controllers;

use App\Models\Gaji;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;



class GajiController extends Controller
{
    public function index()
    {
        $gajis = Gaji::with('karyawan')->latest()->get();
        return view('gaji.index', compact('gajis'));
    }

    public function create()
    {
        $karyawans = Karyawan::where('status_aktif', true)->get();
        return view('gaji.create', compact('karyawans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'bulan' => 'required',
            'gaji_pokok' => 'required|integer',
            'tunj_kehadiran' => 'nullable|integer',
            'tunj_anak' => 'nullable|integer',
            'tunj_komunikasi' => 'nullable|integer',
            'tunj_pengabdian' => 'nullable|integer',
            'tunj_jabatan' => 'nullable|integer',
            'tunj_kinerja' => 'nullable|integer',
            'lembur' => 'nullable|integer',
            'kelebihan_jam' => 'nullable|integer',
            'potongan' => 'nullable|integer',
        ]);

        $total_tunjangan = $validated['tunj_kehadiran'] + $validated['tunj_anak'] + $validated['tunj_komunikasi'] +
            $validated['tunj_pengabdian'] + $validated['tunj_jabatan'] + $validated['tunj_kinerja'] +
            $validated['lembur'] + $validated['kelebihan_jam'];

        $gaji_bersih = $validated['gaji_pokok'] + $total_tunjangan - $validated['potongan'];

        $validated['gaji_bersih'] = $gaji_bersih;

        Gaji::create($validated);

        return redirect()->route('gaji.index')->with('success', 'Data gaji berhasil ditambahkan.');
    }

    public function show(Gaji $gaji)
    {
        return view('gaji.show', compact('gaji'));
    }

    public function edit(Gaji $gaji)
    {
        $karyawans = Karyawan::all();
        return view('gaji.edit', compact('gaji', 'karyawans'));
    }

    public function update(Request $request, Gaji $gaji)
    {
        $validated = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'bulan' => 'required',
            'gaji_pokok' => 'required|integer',
            'tunj_kehadiran' => 'nullable|integer',
            'tunj_anak' => 'nullable|integer',
            'tunj_komunikasi' => 'nullable|integer',
            'tunj_pengabdian' => 'nullable|integer',
            'tunj_jabatan' => 'nullable|integer',
            'tunj_kinerja' => 'nullable|integer',
            'lembur' => 'nullable|integer',
            'kelebihan_jam' => 'nullable|integer',
            'potongan' => 'nullable|integer',
        ]);

        $total_tunjangan = $validated['tunj_kehadiran'] + $validated['tunj_anak'] + $validated['tunj_komunikasi'] +
            $validated['tunj_pengabdian'] + $validated['tunj_jabatan'] + $validated['tunj_kinerja'] +
            $validated['lembur'] + $validated['kelebihan_jam'];

        $gaji_bersih = $validated['gaji_pokok'] + $total_tunjangan - $validated['potongan'];

        $validated['gaji_bersih'] = $gaji_bersih;

        $gaji->update($validated);

        return redirect()->route('gaji.index')->with('success', 'Data gaji berhasil diperbarui.');
    }

    public function destroy(Gaji $gaji)
    {
        $gaji->delete();
        return redirect()->route('gaji.index')->with('success', 'Data gaji berhasil dihapus.');
    }

    public function cetakPDF($id)
    {
        $gaji = Gaji::findOrFail($id);

        $pdf = Pdf::loadView('gaji.slip_pdf', compact('gaji'))->setPaper('A4', 'portrait');
        return $pdf->stream('slip_gaji_' . $gaji->karyawan->nama . '.pdf');
    }


    public function cetakSemua()
    {
        $karyawans = Karyawan::all();

        // Hitung total gaji setiap karyawan
        foreach ($karyawans as $karyawan) {
            $karyawan->total_gaji =
                ($karyawan->gaji_pokok ?? 0) +
                ($karyawan->tunj_kehadiran ?? 0) +
                ($karyawan->tunj_anak ?? 0) +
                ($karyawan->tunj_komunikasi ?? 0) +
                ($karyawan->tunj_pengabdian ?? 0) +
                ($karyawan->tunj_jabatan ?? 0) +
                ($karyawan->tunj_kinerja ?? 0) +
                ($karyawan->lembur ?? 0) +
                ($karyawan->kelebihan_jam ?? 0) -
                ($karyawan->potongan ?? 0);
        }

        $pdf = PDF::loadView('gaji.cetak_semua', compact('karyawans'));
        return $pdf->stream('daftar_gaji_pegawai.pdf'); // bisa diganti ->download() jika ingin langsung diunduh
    }
    public function aturan()
    {
        return view('gaji.aturan');
    }
}
