@extends('layouts.app')
@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-primary">Manajemen Pengguna</h3>
            <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Tambah Pengguna</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th class="text-center">Peran</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td class="text-center"><span class="badge bg-info">{{ ucfirst($user->role) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-warning"><i
                                                class="fas fa-edit"></i></a>
                                        @if (Auth::id() !== $user->id)
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                                data-bs-target="#deleteConfirmationModal"
                                                data-url="{{ route('users.destroy', $user) }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($users->hasPages())
                <div class="card-footer bg-white">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
    <x-delete-confirmation-modal title="Konfirmasi Hapus Pengguna"
        body="Apakah Anda yakin ingin menghapus pengguna ini? Tindakan ini tidak dapat dibatalkan." />
@endsection
