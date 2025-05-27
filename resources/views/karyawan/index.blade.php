@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Data Karyawan</h1>
        <a href="{{ route('karyawan.create') }}" class="btn btn-primary mb-3">Tambah Karyawan</a>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>Jabatan</th>
                    <th>Status Aktif</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                {{-- Loop through the $karyawans collection --}}
                @forelse ($karyawans as $karyawan)
                    {{-- Changed from $karyawan to $karyawans and loop added --}}
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $karyawan->nama }}</td>
                        <td>{{ $karyawan->nip }}</td>
                        <td>{{ $karyawan->jabatan }}</td>
                        <td>
                            @if ($karyawan->status_aktif)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-danger">Tidak Aktif</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('karyawan.show', $karyawan->id) }}" class="btn btn-sm btn-info">Detail</a>
                            @if (Auth::check() && Auth::user()->role === 'admin')
                                <a href="{{ route('karyawan.edit', $karyawan->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form action="{{ route('karyawan.destroy', $karyawan->id) }}" method="POST"
                                    style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Yakin ingin menghapus karyawan ini?')">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data karyawan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
