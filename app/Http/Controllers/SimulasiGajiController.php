<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SimulasiGajiController extends Controller
{
    public function index()
    {
        return view('simulasi.index');
    }

    public function hitung(Request $request)
    {
        $validated = $request->validate([
            'gaji_pokok' => 'required|numeric',
            'tunj_kehadiran' => 'nullable|numeric',
            'tunj_anak' => 'nullable|numeric',
            'tunj_komunikasi' => 'nullable|numeric',
            'tunj_pengabdian' => 'nullable|numeric',
            'tunj_jabatan' => 'nullable|numeric',
            'tunj_kinerja' => 'nullable|numeric',
            'lembur' => 'nullable|numeric',
            'kelebihan_jam' => 'nullable|numeric',
            'potongan' => 'nullable|numeric',
        ]);

        $gaji_bersih = $validated['gaji_pokok']
            + ($validated['tunj_kehadiran'] ?? 0)
            + ($validated['tunj_anak'] ?? 0)
            + ($validated['tunj_komunikasi'] ?? 0)
            + ($validated['tunj_pengabdian'] ?? 0)
            + ($validated['tunj_jabatan'] ?? 0)
            + ($validated['tunj_kinerja'] ?? 0)
            + ($validated['lembur'] ?? 0)
            + ($validated['kelebihan_jam'] ?? 0)
            - ($validated['potongan'] ?? 0);

        return view('simulasi.hasil', [
            'input' => $validated,
            'gaji_bersih' => $gaji_bersih
        ]);
    }
}
