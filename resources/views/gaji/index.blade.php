@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Data Gaji</h1>
        <td>
            <a href="{{ route('gaji.create') }}" class="btn btn-primary mb-2">Tambah Gaji</a>
        </td>
        <td>
            <a href="{{ route('gaji.cetak.semua') }}" class="btn btn-primary mb-2" target="_blank">
                Cetak Daftar Gaji PDF
            </a>
        </td>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Karyawan</th>
                    <th>Bulan</th>
                    <th>Gaji Pokok</th>
                    <th>Gaji Bersih</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($gajis as $gaji)
                    <tr>
                        <td>{{ $gaji->karyawan->nama }}</td>
                        <td>{{ $gaji->bulan }}</td>
                        <td>Rp{{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                        <td>Rp{{ number_format($gaji->gaji_bersih, 0, ',', '.') }}</td>
                        <td>
                            <a href="{{ route('gaji.cetak.semua') }}" class="btn btn-primary" target="_blank">
                                Cetak Daftar Gaji PDF
                            </a>

                            <a href="{{ route('gaji.edit', $gaji->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('gaji.destroy', $gaji->id) }}" method="POST"
                                style="display:inline-block;">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger"
                                    onclick="return confirm('Yakin ingin hapus?')">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
