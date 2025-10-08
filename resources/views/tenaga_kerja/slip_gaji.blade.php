@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-receipt me-2"></i>Unduh Slip Gaji
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        @if ($availableMonths->isEmpty())
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i>
                                Saat ini belum ada data slip gaji yang dapat diunduh.
                            </div>
                        @else
                            <p class="text-muted">Silakan pilih periode slip gaji yang ingin Anda unduh. Proses pembuatan
                                slip akan berjalan di belakang layar dan hasilnya akan muncul di halaman notifikasi jika
                                sudah siap.</p>

                            <form action="{{ route('tenaga_kerja.slip_gaji.download') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="bulan" class="form-label">Pilih Periode</label>
                                    <select name="bulan" id="bulan" class="form-select" required>
                                        @foreach ($availableMonths as $periode)
                                            <option value="{{ $periode }}">
                                                {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-download me-2"></i>Buat & Unduh Slip
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
