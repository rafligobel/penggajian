@extends('layouts.app')

@section('content')
    <div class="container">
        <h3>Detail Gaji {{ $gaji->karyawan->nama }} - {{ $gaji->bulan }}</h3>
        <ul class="list-group mb-3">
            <li class="list-group-item">Gaji Pokok: Rp{{ number_format($gaji->gaji_pokok) }}</li>
            <li class="list-group-item">Tunjangan Kehadiran: Rp{{ number_format($gaji->tunj_kehadiran) }}</li>
            <!-- Tambahkan semua detail tunjangan dan potongan -->
            <li class="list-group-item bg-light fw-bold">Gaji Bersih: Rp{{ number_format($gaji->gaji_bersih) }}</li>
        </ul>
        <a href="{{ route('gaji.cetak', $gaji->id) }}" class="btn btn-danger" target="_blank">Cetak Slip PDF</a>
        
        <a href="{{ route('gaji.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
@endsection
