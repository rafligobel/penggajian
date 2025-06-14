@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Kelola Sesi Absensi</h1>
        <a href="{{ route('sesi-absensi.create') }}" class="btn btn-primary mb-3">
            <i class="fas fa-plus"></i> Buat Sesi Baru
        </a>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light text-center">
                            <tr>
                                <th>Tanggal</th>
                                <th>Waktu Mulai</th>
                                <th>Waktu Selesai</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sesi as $item)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('l, d F Y') }}</td>
                                    <td class="text-center">{{ \Carbon\Carbon::parse($item->waktu_mulai)->format('H:i') }}
                                    </td>
                                    <td class="text-center">{{ \Carbon\Carbon::parse($item->waktu_selesai)->format('H:i') }}
                                    </td>
                                    <td class="text-center">
                                        @if ($item->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Tidak Aktif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('sesi-absensi.edit', $item->id) }}"
                                            class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                            data-bs-target="#deleteConfirmationModal"
                                            data-url="{{ route('sesi-absensi.destroy', $item->id) }}">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Belum ada sesi absensi yang dibuat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $sesi->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Panggil komponen modal di sini --}}
    <x-delete-confirmation-modal />
@endsection
