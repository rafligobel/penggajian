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
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-success text-white p-3 rounded-3 me-3"><i class="fas fa-money-bill-wave fa-lg"></i>
                        </div>
                        <div>
                            {{-- [PERBAIKAN SINTAKS BLADE FOKUS] Memastikan blok @if tertutup dengan benar --}}
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
                data-bs-target="#slipGajiModal">
                <i class="fas fa-receipt fa-fw me-3 text-success"></i>Unduh Slip Gaji
            </a>
        </div>
    </div>

    {{-- =================================================================== --}}
    {{-- SEMUA MODAL DIDEFINISIKAN DI SINI --}}
    {{-- =================================================================== --}}

    {{-- 1. Modal Absensi (Statis) --}}
    @include('tenaga_kerja.modals.absensi', [
        'isSesiDibuka' => $isSesiDibuka,
        'sudahAbsen' => $sudahAbsen,
        'pesanSesi' => $pesanSesi,
    ])

    {{-- 2. Modal Simulasi Gaji (Form) --}}
    <div class="modal fade" id="simulasiModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                @include('tenaga_kerja.modals.simulasi', ['gajiTerakhir' => $gajiTerakhir])
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
                @include('tenaga_kerja.modals.slip_gaji', ['availableMonths' => $slipTersedia])
            </div>
        </div>
    </div>


    {{-- 5. Modal untuk menampung HASIL simulasi (AJAX) --}}
    {{-- [PERBAIKAN] Blok @if (session(...)) dihapus. Modal ini sekarang 
         kosong dan siap diisi oleh JavaScript. --}}
    <div class="modal fade" id="hasilSimulasiModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            {{-- Konten modal (header, body, footer) akan di-render di sini --}}
            <div class="modal-content" id="hasilSimulasiModalContent">
                {{-- Dibiarkan kosong --}}
            </div>
        </div>
    </div>

    <div class="modal fade" id="dataSayaModal" tabindex="-1" aria-labelledby="dataSayaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
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
                                <input type="text" class="form-control" value="{{ $karyawan->nip }}" readonly disabled>
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
            // Inisialisasi modal Bootstrap
            const simulasiModalEl = document.getElementById('simulasiModal');
            const simulasiModal = new bootstrap.Modal(simulasiModalEl);

            const hasilModalEl = document.getElementById('hasilSimulasiModal');
            const hasilModal = new bootstrap.Modal(hasilModalEl);

            const hasilModalContent = document.getElementById('hasilSimulasiModalContent');

            // [PERBAIKAN] Logika untuk menampilkan modal LAPORAN GAJI
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('tahun')) {
                const laporanModalEl = document.getElementById('laporanGajiModal');
                if (laporanModalEl) {
                    new bootstrap.Modal(laporanModalEl).show();
                }
            }

            // [PERBAIKAN] Logika auto-submit form tahun
            const tahunSelect = document.getElementById('laporan-tahun-select');
            if (tahunSelect) {
                tahunSelect.addEventListener('change', function() {
                    this.form.submit();
                });
            }

            // ===================================================================
            // [PERBAIKAN BARU: AJAX UNTUK SIMULASI GAJI]
            // ===================================================================
            const formSimulasi = document.getElementById('form-simulasi');
            if (formSimulasi) {
                formSimulasi.addEventListener('submit', function(e) {
                    e.preventDefault(); // Hentikan submit form standar

                    const formData = new FormData(this);
                    const actionUrl = this.getAttribute('action');
                    const submitButton = this.querySelector('button[type="submit"]');
                    const originalButtonText = submitButton.innerHTML;

                    // Tampilkan loading
                    submitButton.disabled = true;
                    submitButton.innerHTML =
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menghitung...';

                    fetch(actionUrl, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': formData.get('_token'),
                                'Accept': 'text/html', // Minta HTML sebagai respons
                                'X-Requested-With': 'XMLHttpRequest' // Tandai sebagai AJAX
                            }
                        })
                        .then(response => {
                            if (response.status === 422) { // Error validasi
                                // TODO: Tambahkan penanganan error validasi (misal: alert)
                                alert('Input tidak valid. Periksa kembali data Anda.');
                                return response.json().then(err => {
                                    throw err;
                                });
                            }
                            if (!response.ok) {
                                alert('Terjadi kesalahan. Silakan coba lagi.');
                                throw new Error('Network response was not ok');
                            }
                            return response.text(); // Ambil HTML sebagai teks
                        })
                        .then(html => {
                            // Suntikkan HTML hasil ke modal hasil
                            hasilModalContent.innerHTML = html;

                            // Tukar modal
                            simulasiModal.hide();
                            hasilModal.show();
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        })
                        .finally(() => {
                            // Kembalikan tombol ke keadaan semula
                            submitButton.disabled = false;
                            submitButton.innerHTML = originalButtonText;
                        });
                });
            }

            // Menangani tombol "Hitung Ulang" dari modal hasil
            // Tombol ini sudah dikonfigurasi di hasil.blade.php
            // untuk menutup modal hasil dan membuka modal form
            hasilModalEl.addEventListener('click', function(e) {
                if (e.target && e.target.matches('[data-bs-target="#simulasiModal"]')) {
                    hasilModal.hide();
                    simulasiModal.show();
                }
            });
            @if ($errors->has('telepon') || $errors->has('alamat') || $errors->has('jumlah_anak') || $errors->has('foto'))
                const dataSayaModal = new bootstrap.Modal(document.getElementById('dataSayaModal'));
                dataSayaModal.show();
            @endif

        });
    </script>
@endpush
