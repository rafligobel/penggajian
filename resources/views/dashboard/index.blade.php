@extends('layouts.app')

@section('content')
    <div class="container py-1">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <div>
                <h3 class="fw-bold text-primary">Dasbor Utama</h3>
                <p class="text-muted">Selamat datang kembali, {{ Auth::user()->name }}!</p>
            </div>
            <div class="text-end">
                <h5 class="fw-normal">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</h5>
            </div>
        </div>

        {{-- Baris Statistik Utama --}}
        <div class="row g-2">

            {{-- Statistik Gaji --}}
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0 d-flex flex-column">
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
                <div class="card h-100 shadow-sm border-0 d-flex flex-column">
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
                <div class="card h-100 shadow-sm border-0 d-flex flex-column">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-warning text-dark p-3 rounded-3 me-4">
                            <i class="fas fa-cogs fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="card-title text-muted">Proses Bulan Ini</h6>
                            <p class="card-text mb-1">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                Gaji Yang Diproses: <strong>{{ $gajiDiproses }} / {{ $jumlahKaryawan }}</strong> Karyawan
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- MODIFIKASI: Area Grafik (Tata Letak 2 Kolom) --}}
        <div class="row mt-2 g-4">
            {{-- Grafik Garis (Line Chart) --}}
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title text-primary fw-bold mb-3">Grafik Total Gaji (per Bulan)</h5>
                        {{-- Wrapper untuk memastikan rasio aspek --}}
                        <div class="chart-container" style="position: relative; height:300px; width:100%">
                            <canvas id="gajiChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Grafik Donat (Doughnut Chart) --}}
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title text-primary fw-bold mb-3">Status Proses Gaji Bulan Ini</h5>
                        <div class="chart-container" style="position: relative; height:300px; width:100%">
                            <canvas id="prosesGajiChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- AKHIR MODIFIKASI Area Grafik --}}


        {{-- Shortcut/Aksi Cepat --}}
        <div class="mt-4">
            <h4 class="fw-bold text-primary mb-3">Aksi Cepat</h4>
            {{-- ... (Konten Aksi Cepat tetap sama) ... --}}
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
                @if (in_array(Auth::user()->role, ['admin', 'superadmin']))
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

@push('scripts')
    {{-- Memuat library Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- MODIFIKASI: Script untuk menginisialisasi KEDUA grafik --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // === 1. GRAFIK GARIS (TOTAL GAJI) ===
            const ctxLine = document.getElementById('gajiChart');
            if (ctxLine) {
                const labelsLine = @json($labels);
                const dataLine = @json($data);

                new Chart(ctxLine, {
                    type: 'line',
                    data: {
                        labels: labelsLine,
                        datasets: [{
                            label: 'Total Gaji',
                            data: dataLine,
                            fill: false,
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // Penting agar grafik mengisi div
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: (value) => 'Rp ' + new Intl.NumberFormat('id-ID').format(
                                        value)
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        if (context.parsed.y !== null) {
                                            label += 'Rp ' + new Intl.NumberFormat('id-ID').format(
                                                context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // === 2. GRAFIK DONAT (PROSES GAJI) [BARU] ===
            const ctxDoughnut = document.getElementById('prosesGajiChart');
            if (ctxDoughnut) {
                // Ambil data dari variabel Blade yang sudah ada
                const gajiDiproses = @json($gajiDiproses);
                const gajiBelumDiproses = @json($jumlahKaryawan) - gajiDiproses;

                new Chart(ctxDoughnut, {
                    type: 'doughnut',
                    data: {
                        labels: [
                            'Sudah Diproses',
                            'Belum Diproses'
                        ],
                        datasets: [{
                            label: 'Status Gaji',
                            data: [gajiDiproses, gajiBelumDiproses],
                            backgroundColor: [
                                'rgb(25, 135, 84)', // Warna Sukses (Hijau)
                                'rgb(220, 53, 69)' // Warna Bahaya (Merah)
                            ],
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // Penting agar grafik mengisi div
                        plugins: {
                            legend: {
                                position: 'top', // Pindahkan legenda ke atas
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        let label = context.label || '';
                                        if (label) label += ': ';
                                        if (context.parsed !== null) {
                                            label += context.parsed + ' Karyawan';
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }

        });
    </script>
@endpush
