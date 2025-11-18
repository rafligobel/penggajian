@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                {{-- Judul Halaman --}}
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h3 class="fw-bold text-primary m-0">
                        <i class="fas fa-calculator me-2"></i>Pengaturan Tarif Gaji
                    </h3>
                </div>

                {{-- Alert Sukses --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Card Form Pengaturan --}}
                <div class="card shadow border-0 rounded-3">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="m-0 fw-bold text-muted">Atur Nilai Default Potongan & Lembur</h6>
                    </div>
                    <div class="card-body p-4">

                        {{-- Form Update --}}
                        <form action="{{ route('potongan.update') }}" method="POST">
                            @csrf

                            <div class="row g-4">
                                {{-- KIRI: PENGATURAN LEMBUR --}}
                                <div class="col-md-6">
                                    <div class="p-3 border rounded bg-light h-100 position-relative">
                                        <div class="position-absolute top-0 start-50 translate-middle bg-white px-2 border rounded text-primary fw-bold"
                                            style="font-size: 0.8rem;">
                                            <i class="fas fa-clock me-1"></i> TARIF LEMBUR
                                        </div>
                                        <div class="mt-2">
                                            <label for="tarif_lembur" class="form-label fw-bold text-dark small">Nominal Per
                                                Jam</label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text bg-primary text-white border-0">Rp</span>
                                                <input type="number" name="tarif_lembur_per_jam" id="tarif_lembur"
                                                    class="form-control fw-bold text-primary" placeholder="0"
                                                    value="{{ old('tarif_lembur_per_jam', $potongan->tarif_lembur_per_jam) }}"
                                                    min="0" required>
                                            </div>
                                            <div class="form-text text-muted mt-2" style="font-size: 0.85rem;">
                                                Nilai ini akan dikalikan otomatis dengan <strong>jumlah jam lembur</strong>
                                                yang diinput Bendahara.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- KANAN: PENGATURAN POTONGAN --}}
                                <div class="col-md-6">
                                    <div class="p-3 border rounded bg-light h-100 position-relative">
                                        <div class="position-absolute top-0 start-50 translate-middle bg-white px-2 border rounded text-danger fw-bold"
                                            style="font-size: 0.8rem;">
                                            <i class="fas fa-user-times me-1"></i> TARIF POTONGAN
                                        </div>
                                        <div class="mt-2">
                                            <label for="tarif_potongan" class="form-label fw-bold text-dark small">Nominal
                                                Per Hari (Alpha)</label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text bg-danger text-white border-0">Rp</span>
                                                <input type="number" name="tarif_potongan_absen" id="tarif_potongan"
                                                    class="form-control fw-bold text-danger" placeholder="0"
                                                    value="{{ old('tarif_potongan_absen', $potongan->tarif_potongan_absen) }}"
                                                    min="0" required>
                                            </div>
                                            <div class="form-text text-muted mt-2" style="font-size: 0.85rem;">
                                                Nilai ini akan dikalikan otomatis dengan <strong>jumlah hari tidak hadir
                                                    (Alpha)</strong> karyawan.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tombol Simpan --}}
                            <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                                <button type="submit" class="btn btn-dark px-4 py-2 fw-bold">
                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

                {{-- Info Tambahan --}}
                <div class="mt-3 text-center text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    Perubahan tarif ini akan langsung berlaku pada perhitungan gaji periode berjalan.
                </div>

            </div>
        </div>
    </div>
@endsection
