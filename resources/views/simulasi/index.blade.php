@extends('layouts.app')

@section('content')
    <div class="container d-flex justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">Form Simulasi Gaji</h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted text-center mb-4">Masukkan Nama atau NIP karyawan dan jumlah kehadiran untuk
                        mendapatkan estimasi gaji.</p>

                    <form action="{{ route('simulasi.hitung') }}" method="POST">
                        @csrf

                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        {{-- DROPDOWN DIGANTI MENJADI INPUT PENCARIAN --}}
                        <div class="mb-3">
                            <label for="karyawan_query" class="form-label fw-bold">Cari Karyawan</label>
                            <input type="text" class="form-control @error('karyawan_query') is-invalid @enderror"
                                id="karyawan_query" name="karyawan_query" value="{{ old('karyawan_query') }}"
                                placeholder="Ketik nama atau NIP" required>
                            @error('karyawan_query')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- AKHIR PERUBAHAN --}}

                        <div class="mb-3">
                            <label for="jumlah_hari_masuk" class="form-label fw-bold">Jumlah Hari Masuk dalam
                                Sebulan</label>
                            <input type="number" class="form-control @error('jumlah_hari_masuk') is-invalid @enderror"
                                id="jumlah_hari_masuk" name="jumlah_hari_masuk" value="{{ old('jumlah_hari_masuk', 26) }}"
                                required>
                            @error('jumlah_hari_masuk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="lembur" class="form-label">Estimasi Lembur (Opsional)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('lembur') is-invalid @enderror"
                                    id="lembur" name="lembur" value="{{ old('lembur', 0) }}">
                            </div>
                            @error('lembur')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="potongan" class="form-label">Potongan Lain-lain (Opsional)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('potongan') is-invalid @enderror"
                                    id="potongan" name="potongan" value="{{ old('potongan', 0) }}">
                            </div>
                            @error('potongan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Hitung Estimasi Gaji</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
