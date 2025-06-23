@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-primary">Riwayat Notifikasi</h3>
            @if ($notifications->isNotEmpty())
                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAllModal">
                    <i class="fas fa-trash-alt me-1"></i> Hapus Semua
                </button>
            @endif
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('notifications.deleteSelected') }}" method="POST" id="notifications-form">
                    @csrf
                    @if ($notifications->isNotEmpty())
                        <div class="mb-3">
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash me-1"></i> Hapus yang Dipilih
                            </button>
                        </div>
                    @endif

                    <ul class="list-group list-group-flush">
                        @forelse ($notifications as $notification)
                            <li
                                class="list-group-item d-flex align-items-center p-3 {{ $notification->read_at ? 'bg-light text-muted' : '' }}">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="checkbox" name="notification_ids[]"
                                        value="{{ $notification->id }}" id="notif-{{ $notification->id }}">
                                </div>

                                <div
                                    class="icon-circle {{ $notification->data['is_error'] ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} me-3">
                                    <i
                                        class="fas {{ $notification->data['is_error'] ? 'fa-exclamation-triangle' : 'fa-file-pdf' }}"></i>
                                </div>

                                <div class="flex-grow-1">
                                    <p class="mb-0 fw-semibold">{{ $notification->data['message'] }}</p>
                                    <small>{{ $notification->created_at->translatedFormat('d F Y, H:i') }}</small>
                                </div>

                                @if (!$notification->data['is_error'])
                                    <a href="{{ route('notifications.markAsRead', $notification->id) }}" target="_blank"
                                        class="btn btn-sm btn-outline-primary ms-3">Lihat PDF</a>
                                @endif
                            </li>
                        @empty
                            <div class="text-center py-5">
                                <p class="mb-0 text-muted">Anda tidak memiliki riwayat notifikasi.</p>
                            </div>
                        @endforelse
                    </ul>
                </form>
            </div>
            @if ($notifications->hasPages())
                <div class="card-footer bg-white">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="deleteAllModal" tabindex="-1" aria-labelledby="deleteAllModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAllModalLabel">Konfirmasi Hapus Semua Notifikasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus **semua** riwayat notifikasi Anda? Tindakan ini tidak dapat
                    dibatalkan.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form action="{{ route('notifications.deleteAll') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger">Ya, Hapus Semua</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
    </style>
@endsection
