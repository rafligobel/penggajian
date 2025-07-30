@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>Manajemen Pengguna</h1>
        </div>

        {{-- Menampilkan pesan sukses --}}
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="section-body">
            <div class="card">
                <div class="card-header">
                    <h4>Daftar Pengguna</h4>
                    <div class="card-header-action">
                        <a href="{{ route('users.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tambah Pengguna
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Gunakan @forelse untuk menangani jika data kosong --}}
                                @forelse ($users as $index => $user)
                                    <tr>
                                        <td>{{ $index + $users->firstItem() }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->role }}</td>
                                        <td>
                                            {{-- Tombol Edit --}}
                                            <a href="{{ route('users.edit', $user->id) }}"
                                                class="btn btn-primary btn-sm">Edit</a>

                                            {{-- Tombol Hapus dengan konfirmasi --}}
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Tidak ada data pengguna.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{-- Menampilkan Paginasi --}}
                    <div class="float-right">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
