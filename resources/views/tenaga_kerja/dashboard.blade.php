@extends('layouts.tenaga_kerja_layout')

@section('content')
    <div class="container">
        {{-- Kartu Sambutan --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                {{-- Tambahkan notifikasi sukses di sini --}}
                @if (session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif
                {{-- Tambahkan notifikasi error jika ada --}}
                @if (session('error'))
                    <div class="alert alert-danger" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                <h4 class="fw-bold text-primary">Selamat Datang, {{ $karyawan->nama }}!</h4>
                <p class="text-muted mb-0">Ini adalah pusat kendali Anda. Semua yang Anda butuhkan ada di sini.</p>
            </div>
        </div>

        {{-- Kartu Statistik --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-success text-white p-3 rounded-3 me-3"><i class="fas fa-money-bill-wave fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-1">Gaji Bulan Ini ({{ now()->translatedFormat('F Y') }})
                            </h6>
                            @if ($gajiBulanIni)
                                <p class="card-text fs-5 fw-bold mb-0">Rp
                                    {{ number_format($gajiBulanIni->gaji_bersih, 0, ',', '.') }}</p>
                                <small class="text-muted">Status: {{ $gajiBulanIni->status ?? 'Diproses' }}</small>
                            @else
                                <p class="card-text fs-6 mb-0">Data gaji bulan ini belum tersedia.</p>
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
            <a href="#" class="list-group-item list-group-item-action fs-5" data-bs-toggle="modal"
                data-bs-target="#laporanGajiModal">
                <i class="fas fa-file-invoice-dollar fa-fw me-3 text-primary"></i>Laporan Gaji
            </a>

            <a href="#" class="list-group-item list-group-item-action fs-5" data-bs-toggle="modal"
                data-bs-target="#dataSayaModal">
                <i class="fas fa-id-card fa-fw me-3 text-info"></i>Data Saya
            </a>
            {{-- Link slip gaji asli bisa Anda tambahkan lagi di sini jika mau --}}
            {{-- <a href="#" class="list-group-item list-group-item-action fs-5" data-bs-toggle="modal"
                data-bs-target="#slipGajiModal">
                <i class="fas fa-receipt fa-fw me-3 text-success"></i>Unduh Slip Gaji
            </a> --}}
        </div>
    </div>

    {{-- =================================================================== --}}
    {{-- SEMUA MODAL DIDEFINISIKAN DI SINI --}}
    {{-- =================================================================== --}}

    {{-- 1. Modal Absensi (Statis) --}}
    @include('tenaga_kerja.modals.absensi', [
        'isSesiDibuka' => $isSesiDibuka ?? false,
        'sudahAbsen' => $sudahAbsen ?? false,
        'pesanSesi' => $pesanSesi ?? null,
    ])

    {{-- 2. Modal Simulasi Gaji (Form) --}}
    <div class="modal fade" id="simulasiModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                @include('tenaga_kerja.modals.simulasi', ['gajiTerakhir' => $gajiTerakhir ?? null])
            </div>
        </div>
    </div>

    {{-- 3. Modal Laporan Gaji (Statis) --}}
    <div class="modal fade" id="laporanGajiModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                @include('tenaga_kerja.modals.laporan_gaji', [
                    'laporanData' => $laporanData,
                    'tahun' => $tahun,
                    'availableYears' => $availableYears,
                ])
            </div>
        </div>
    </div>

    {{-- 4. Modal Unduh Slip Gaji (Statis) --}}
    <div class="modal fade" id="slipGajiModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                @include('tenaga_kerja.modals.slip_gaji', [
                    'availableMonths' => $slipTersedia ?? collect(),
                ])
            </div>
        </div>
    </div>

    {{-- 5. Modal untuk menampung HASIL simulasi (AJAX) --}}
    <div class="modal fade" id="hasilSimulasiModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content" id="hasilSimulasiModalContent">
                {{-- Dibiarkan kosong, akan diisi oleh AJAX --}}
            </div>
        </div>
    </div>

    {{-- 6. Modal Data Saya --}}
    <div class="modal fade" id="dataSayaModal" tabindex="-1" aria-labelledby="dataSayaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                {{-- Form mengarah ke rute baru yang kita buat --}}
                <form action="{{ route('tenaga_kerja.data_saya.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-primary" id="dataSayaModalLabel">
                            <i class="fas fa-id-card me-2"></i>Data Kepegawaian Saya
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        {{-- Bagian Data Read-only (Tidak Bisa Diubah Pegawai) --}}
                        <h6 class="text-muted mb-3">Data Utama</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" value="{{ $karyawan->nama }}" readonly disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nomor Pegawai (NP)</label>
                                <input type="text" class="form-control" value="{{ $karyawan->nip }}" readonly
                                    disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jabatan</label>
                                <input type="text" class="form-control"
                                    value="{{ $karyawan->jabatan->nama_jabatan ?? 'Tidak ada jabatan' }}" readonly
                                    disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Masuk</label>
                                <input type="text" class="form-control"
                                    value="{{ $karyawan->tanggal_masuk ? $karyawan->tanggal_masuk->format('d M Y') : '-' }}"
                                    readonly disabled>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Bagian Data yang Bisa Diubah --}}
                        <h6 class="text-muted mb-3">Data yang Dapat Diperbarui</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telepon" class="form-label">No. Telepon</label>
                                <input type="text" name="telepon" id="telepon"
                                    class="form-control @error('telepon') is-invalid @enderror"
                                    value="{{ old('telepon', $karyawan->telepon ?? '') }}">
                                @error('telepon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="jumlah_anak" class="form-label">Jumlah Anak (Tanggungan)</label>
                                <input type="number" class="form-control @error('jumlah_anak') is-invalid @enderror"
                                    id="jumlah_anak" name="jumlah_anak"
                                    value="{{ old('jumlah_anak', $karyawan->jumlah_anak ?? 0) }}" min="0">
                                @error('jumlah_anak')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="alamat" class="form-label">Alamat</label>
                            <textarea name="alamat" id="alamat" class="form-control @error('alamat') is-invalid @enderror" rows="3">{{ old('alamat', $karyawan->alamat ?? '') }}</textarea>
                            @error('alamat')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="foto" class="form-label">Perbarui Foto Profil</label>
                            <input class="form-control @error('foto') is-invalid @enderror" type="file" id="foto"
                                name="foto">
                            @error('foto')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            @if ($karyawan->foto)
                                <div class="mt-3">
                                    <small class="text-muted d-block mb-2">Foto Saat Ini:</small>
                                    <img src="{{ asset('uploads/foto_pegawai/' . $karyawan->foto) }}" alt="Foto saat ini"
                                        style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                                </div>
                            @endif
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // 1. Logika auto-submit form tahun laporan
            const tahunSelect = document.getElementById('laporan-tahun-select');
            if (tahunSelect) {
                tahunSelect.addEventListener('change', function() {
                    this.form.submit();
                });
            }

            // 2. Logika untuk otomatis menampilkan modal LAPORAN GAJI (jika ada param 'tahun')
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('tahun')) {
                const laporanModalEl = document.getElementById('laporanGajiModal');
                if (laporanModalEl) {
                    const laporanModal = bootstrap.Modal.getOrCreateInstance(laporanModalEl);
                    laporanModal.show();
                }
            }

            // 3. Logika AJAX SIMULASI GAJI (DINONAKTIFKAN AGAR REDIRECT KE HALAMAN BARU)
            /*
            const formSimulasi = document.getElementById('form-simulasi');
            if (formSimulasi) {
                formSimulasi.addEventListener('submit', function(e) {
                   // Dinonaktifkan agar form melakukan POST standar ke halaman hasil
                });
            }
            */

            // 4. Menangani tombol "Hitung Ulang" dari modal hasil
            const hasilModalEl = document.getElementById('hasilSimulasiModal');
            if (hasilModalEl) {
                hasilModalEl.addEventListener('click', function(e) {
                    // Delegasi event untuk tombol yang mungkin dirender via AJAX
                    if (e.target && e.target.closest('[data-bs-target="#simulasiModal"]')) {
                        const hasilModal = bootstrap.Modal.getOrCreateInstance(hasilModalEl);
                        const simulasiModalEl = document.getElementById('simulasiModal');
                        const simulasiModal = bootstrap.Modal.getOrCreateInstance(simulasiModalEl);

                        hasilModal.hide();
                        simulasiModal.show();
                    }
                });
            }

            // 5. Otomatis Buka Modal 'Data Saya' Jika Ada Error Validasi (Laravel Validation)
            @if ($errors->has('telepon') || $errors->has('alamat') || $errors->has('jumlah_anak') || $errors->has('foto'))
                const dataSayaModalEl = document.getElementById('dataSayaModal');
                if (dataSayaModalEl) {
                    const dataSayaModal = bootstrap.Modal.getOrCreateInstance(dataSayaModalEl);
                    dataSayaModal.show();
                }
            @endif
        });
    </script>
@endpush
