@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-primary">Dasbor Utama</h3>
                <p class="text-muted">Selamat datang kembali, {{ Auth::user()->name }}!</p>
            </div>
            <div class="text-end">
                <h5 class="fw-normal">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</h5>
            </div>
        </div>

        {{-- Baris Statistik Utama --}}
        <div class="row g-4">
            {{-- Statistik Gaji --}}
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-success text-white p-3 rounded-3 me-4">
                            <i class="fas fa-money-bill-wave fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="card-title text-muted">Total Gaji Bulan Ini</h6>
                            <p class="card-text fs-4 fw-bold mb-0">Rp {{ number_format($totalGajiBulanIni, 0, ',', '.') }}
                            </p>
                            @if ($perbandinganGaji != 0)
                                <small class="{{ $perbandinganGaji > 0 ? 'text-danger' : 'text-success' }}">
                                    <i class="fas {{ $perbandinganGaji > 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                                    {{ number_format(abs($perbandinganGaji), 1) }}% vs bulan lalu
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Statistik Karyawan --}}
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-primary text-white p-3 rounded-3 me-4">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="card-title text-muted">Karyawan Aktif</h6>
                            <p class="card-text fs-4 fw-bold mb-0">{{ $jumlahKaryawan }} Orang</p>
                            <small class="text-success">+{{ $karyawanBaruBulanIni }} baru bulan ini</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Statistik Proses Gaji & Absensi --}}
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-warning text-dark p-3 rounded-3 me-4">
                            <i class="fas fa-cogs fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="card-title text-muted">Proses Bulan Ini</h6>
                            <p class="card-text mb-1">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                Gaji Yang Dicetak: <strong>{{ $gajiDiproses }} / {{ $jumlahKaryawan }}</strong> Karyawan
                            </p>
                            {{-- <p class="card-text mb-0">
                                <i class="fas fa-calendar-check text-primary me-1"></i>
                                Sesi Absensi: <span class="badge bg-info">{{ $statusSesi }}</span>
                            </p> --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Shortcut/Aksi Cepat --}}
        <div class="mt-5">
            <h4 class="fw-bold text-primary mb-3">Aksi Cepat</h4>
            <div class="row g-3">
                @if (Auth::user()->role === 'bendahara')
                    <div class="col-md-4">
                        <a href="{{ route('gaji.index') }}" class="text-decoration-none">
                            <div class="card card-menu shadow-sm">
                                <div class="card-body text-center">
                                    <i class="fas fa-money-check-alt fa-3x text-primary mb-2"></i>
                                    <h5 class="card-title">Kelola Gaji</h5>
                                    <p class="card-text text-muted">Proses dan lihat gaji karyawan.</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('laporan.gaji.bulanan') }}" class="text-decoration-none">
                            <div class="card card-menu shadow-sm">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-line fa-3x text-warning mb-2"></i>
                                    <h5 class="card-title">Lihat Laporan</h5>
                                    <p class="card-text text-muted">Cetak laporan gaji bulanan.</p>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
                @if (Auth::user()->role === 'admin')
                    <div class="col-md-4">
                        <a href="{{ route('karyawan.index') }}" class="text-decoration-none">
                            <div class="card card-menu shadow-sm">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-plus fa-3x text-success mb-2"></i>
                                    <h5 class="card-title">Kelola Karyawan</h5>
                                    <p class="card-text text-muted">Tambah atau ubah data karyawan.</p>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection
