<?php

namespace App\Http\Controllers;

use App\Models\TunjanganKehadiran;
use Illuminate\Http\Request;

class TunjanganKehadiranController extends Controller
{
    public function index()
    {
        $tunjanganKehadirans = TunjanganKehadiran::latest()->paginate(10);
        return view('tunjangan_kehadiran.index', compact('tunjanganKehadirans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis_tunjangan' => 'required|string|max:255',
            'jumlah_tunjangan' => 'required|numeric|min:0',
        ]);

        TunjanganKehadiran::create($request->all());

        return redirect()->route('tunjangan-kehadiran.index')
            ->with('success', 'Tunjangan kehadiran berhasil ditambahkan.');
    }

    public function update(Request $request, TunjanganKehadiran $tunjanganKehadiran)
    {
        $request->validate([
            'jenis_tunjangan' => 'required|string|max:255',
            'jumlah_tunjangan' => 'required|numeric|min:0',
        ]);

        $tunjanganKehadiran->update($request->all());

        return redirect()->route('tunjangan-kehadiran.index')
            ->with('success', 'Tunjangan kehadiran berhasil diperbarui.');
    }

    public function destroy(TunjanganKehadiran $tunjanganKehadiran)
    {
        $tunjanganKehadiran->delete();

        return redirect()->route('tunjangan-kehadiran.index')
            ->with('success', 'Tunjangan kehadiran berhasil dihapus.');
    }
}
