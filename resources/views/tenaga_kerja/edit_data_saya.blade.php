@extends('layouts.tenaga_kerja_layout')

@section('title', 'Edit Data Saya')

@section('content')
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-lg-8 col-md-10 mx-auto">
                <div class="card shadow-sm mb-4">
                    <div class="card-header p-3">
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="fas fa-user-edit me-2"></i>Edit Data Kepegawaian Saya
                        </h5>
                    </div>
                    <div class="card-body px-4 py-3">

                        @if (session('success'))
                            <div class="alert alert-success" role="alert">
                                {{ session('success') }}
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif

                        <form action="{{ route('tenaga_kerja.data_saya.update') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            {{-- Bagian Data Read-only (Tidak Bisa Diubah Pegawai) --}}
                            <h6 class="text-muted mb-3">Data Utama</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" value="{{ $karyawan->nama }}" readonly
                                        disabled>
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
                                <input class="form-control @error('foto') is-invalid @enderror" type="file"
                                    id="foto" name="foto">
                                @error('foto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                @if ($karyawan->foto)
                                    <div class="mt-3">
                                        <small class="text-muted d-block mb-2">Foto Saat Ini:</small>
                                        <img src="{{ asset('uploads/foto_pegawai/' . $karyawan->foto) }}"
                                            alt="Foto saat ini"
                                            style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                                    </div>
                                @endif
                            </div>

                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
