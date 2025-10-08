@extends('layouts.tenaga_kerja_layout')

@section('content')
    <div class="container">
        {{-- Kartu Sambutan --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h4 class="fw-bold text-primary">Selamat Datang, {{ $karyawan->nama }}!</h4>
                <p class="text-muted mb-0">Ini adalah pusat kendali Anda. Semua yang Anda butuhkan ada di sini.</p>
            </div>
        </div>

        {{-- Kartu Statistik --}}
        {{-- PERUBAHAN 1: Menambahkan class col-12 agar eksplisit full-width di mobile --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-success text-white p-3 rounded-3 me-3"><i class="fas fa-money-bill-wave fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-1">Gaji Terakhir Diterima</h6>
                            @if ($gajiTerbaru)
                                <p class="card-text fs-5 fw-bold mb-0">Rp
                                    {{ number_format($gajiTerbaru->gaji_bersih, 0, ',', '.') }}</p>
                                <small class="text-muted">Periode
                                    {{ \Carbon\Carbon::parse($gajiTerbaru->bulan)->translatedFormat('F Y') }}</small>
                            @else
                                <p class="card-text fs-6 mb-0">Belum ada data gaji.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-info text-white p-3 rounded-3 me-3"><i class="fas fa-calendar-check fa-lg"></i></div>
                        <div>
                            <h6 class="card-title text-muted mb-1">Kehadiran Bulan Ini</h6>
                            <p class="card-text fs-5 fw-bold mb-0">{{ $absensiBulanIni }} Hari</p>
                            <small class="text-muted">Periode {{ now()->translatedFormat('F Y') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tombol Aksi Cepat --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-light fw-bold">Aksi Cepat</div>
            <div class="card-body">
                {{-- PERUBAHAN 2: Tombol dibuat full-width di layar extra small (xs) dan 50% di layar small (sm) ke atas --}}
                <div class="row g-2">
                    <div class="col-12 col-sm-6 d-grid">
                        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#absensiModal">
                            <i class="fas fa-user-check me-2"></i>Absensi
                        </button>
                    </div>
                    <div class="col-12 col-sm-6 d-grid">
                        <button class="btn btn-secondary btn-lg" data-bs-toggle="modal" data-bs-target="#simulasiModal">
                            <i class="fas fa-calculator me-2"></i>Simulasi Gaji
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Menu Lainnya --}}
        <div class="list-group">
            {{-- PERUBAHAN: Trigger diubah menjadi data-bs-target biasa, tanpa AJAX --}}
            <a href="#" class="list-group-item list-group-item-action fs-5" data-bs-toggle="modal"
                data-bs-target="#laporanGajiModal">
                <i class="fas fa-file-invoice-dollar fa-fw me-3 text-primary"></i>Laporan Gaji
            </a>
            <a href="#" class="list-group-item list-group-item-action fs-5" data-bs-toggle="modal"
                data-bs-target="#slipGajiModal">
                <i class="fas fa-receipt fa-fw me-3 text-success"></i>Unduh Slip Gaji
            </a>
        </div>
    </div>

    {{-- =================================================================== --}}
    {{-- SEMUA MODAL DIDEFINISIKAN DI SINI --}}
    {{-- =================================================================== --}}

    {{-- 1. Modal Absensi (Statis) --}}
    <div class="modal fade" id="absensiModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                {{-- Menggunakan variabel yang sudah disiapkan di controller dashboard --}}
                @include('tenaga_kerja.modals.absensi', [
                    'isSesiDibuka' => $isSesiDibuka,
                    'sudahAbsen' => $sudahAbsen,
                    'pesanSesi' => $pesanSesi,
                ])
            </div>
        </div>
    </div>

    {{-- 2. Modal Simulasi Gaji (Statis) --}}
    <div class="modal fade" id="simulasiModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                @include('tenaga_kerja.modals.simulasi', ['gajiTerakhir' => $gajiTerbaru])
            </div>
        </div>
    </div>

    {{-- 3. Modal Laporan Gaji (Statis) --}}
    <div class="modal fade" id="laporanGajiModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                {{-- 
                [PERIKSA BAGIAN INI DENGAN SEKSAMA]
                Pastikan Anda meneruskan variabel dengan nama yang benar:
                - 'gajis'         => $laporanGaji
                - 'tahun'         => $tahunLaporan
                - 'availableYears' => $laporanTersedia
            --}}
                @include('tenaga_kerja.modals.laporan_gaji', [
                    'gajis' => $laporanGaji,
                    'tahun' => $tahunLaporan,
                    'availableYears' => $laporanTersedia,
                ])
            </div>
        </div>
    </div>

    {{-- 4. Modal Unduh Slip Gaji (Statis) --}}
    <div class="modal fade" id="slipGajiModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                {{-- Menggunakan variabel yang sudah disiapkan di controller dashboard --}}
                @include('tenaga_kerja.modals.slip_gaji', ['availableMonths' => $slipTersedia])
            </div>
        </div>
    </div>

    {{-- 5. Modal untuk menampung HASIL simulasi dari redirect --}}
    @if (session('hasil_simulasi'))
        <div class="modal fade" id="hasilSimulasiModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    {{-- Menggunakan data dari session 'hasil_simulasi' --}}
                    @include('tenaga_kerja.modals.hasil', ['hasil' => session('hasil_simulasi')])
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);

            // [PERBAIKAN] Logika untuk menampilkan modal LAPORAN GAJI
            // jika ada parameter 'tahun' di URL setelah refresh.
            if (urlParams.has('tahun')) {
                const laporanModalEl = document.getElementById('laporanGajiModal');
                if (laporanModalEl) {
                    const laporanModal = new bootstrap.Modal(laporanModalEl);
                    laporanModal.show();
                }
            }

            // Logika untuk menampilkan modal HASIL SIMULASI setelah redirect
            const hasilModalEl = document.getElementById('hasilSimulasiModal');
            if (hasilModalEl) {
                const hasilModal = new bootstrap.Modal(hasilModalEl);
                hasilModal.show();
            }

            // Logika untuk auto-submit form tahun pada modal laporan
            const tahunSelect = document.getElementById('laporan-tahun-select');
            if (tahunSelect) {
                tahunSelect.addEventListener('change', function() {
                    this.form.submit();
                });
            }
        });
    </script>
@endpush
