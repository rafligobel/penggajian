@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Data Karyawan</h3>
    <a href="{{ route('karyawan.create') }}" class="btn btn-primary mb-2">+ Tambah</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nama</th>
                <th>NIP</th>
                <th>Jabatan</th>
                <th>Telepon</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($karyawans as $k)
            <tr>
                <td>{{ $k->nama }}</td>
                <td>{{ $k->nip }}</td>
                <td>{{ $k->jabatan }}</td>
                <td>{{ $k->telepon }}</td>
                <td>{{ $k->status_aktif ? 'Aktif' : 'Tidak Aktif' }}</td>
                <td>
                    <a href="{{ route('karyawan.show', $k->id) }}" class="btn btn-info btn-sm">Detail</a>
                    <a href="{{ route('karyawan.edit', $k->id) }}" class="btn btn-warning btn-sm">Edit</a>
                    <form action="{{ route('karyawan.destroy', $k->id) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus?')">Hapus</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
