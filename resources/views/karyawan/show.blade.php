@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Detail Pegawai: {{ $karyawan->nama }}</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-3 mb-md-0">
                        @php
                            $fotoUrl = $karyawan->foto
                                ? asset('uploads/foto_pegawai/' . $karyawan->foto)
                                : asset('logo/user.png');
                        @endphp
                        <img src="{{ $fotoUrl }}" class="img-fluid img-thumbnail rounded-circle"
                            style="width: 150px; height: 150px; object-fit: cover;" alt="Foto {{ $karyawan->nama }}">
                        <h5 class="mt-3 mb-0 fw-bold">{{ $karyawan->nama }}</h5>
                        <p class="text-muted">{{ $karyawan->nip }}</p>
                    </div>
                    <div class="col-md-8">
                        <h5 class="text-primary fw-bold border-bottom pb-2 mb-3">Data Kepegawaian</h5>
                        <dl class="row">
                            <dt class="col-sm-4">Jabatan</dt>
                            <dd class="col-sm-8">{{ $karyawan->jabatan->nama_jabatan ?? 'Belum diatur' }}</dd>

                            <dt class="col-sm-4">Gaji Pokok (Master)</dt>
                            <dd class="col-sm-8 fw-bold">
                                {{ 'Rp ' . number_format($karyawan->gaji_pokok_default, 0, ',', '.') }}</dd>

                            <dt class="col-sm-4">Tanggal Masuk</dt>
                            <dd class="col-sm-8">
                                {{ $karyawan->tanggal_masuk ? $karyawan->tanggal_masuk->translatedFormat('d F Y') : '-' }}
                            </dd>

                            <dt class="col-sm-4">Jumlah Anak</dt>
                            <dd class="col-sm-8">{{ $karyawan->jumlah_anak ?? 0 }} Anak</dd>
                        </dl>

                        <h5 class="text-primary fw-bold border-bottom pb-2 mt-4 mb-3">Data Personal</h5>
                        <dl class="row">
                            <dt class="col-sm-4">Email</dt>
                            <dd class="col-sm-8">{{ $karyawan->email ?: 'Tidak ada' }}</dd>

                            <dt class="col-sm-4">Alamat</dt>
                            <dd class="col-sm-8">{{ $karyawan->alamat }}</dd>

                            <dt class="col-sm-4">Telepon</dt>
                            <dd class="col-sm-8">{{ $karyawan->telepon }}</dd>

                            <dt class="col-sm-4">Tanggal Bergabung</dt>
                            <dd class="col-sm-8">
                                {{ $karyawan->created_at ? $karyawan->created_at->translatedFormat('d F Y, H:i') : '-' }}
                            </dd>

                            <dt class="col-sm-4">Terakhir Diperbarui</dt>
                            <dd class="col-sm-8">
                                {{ $karyawan->updated_at ? $karyawan->updated_at->translatedFormat('d F Y, H:i') : '-' }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('karyawan.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                </a>
                {{-- Tombol Edit hanya untuk Admin --}}
                @if (Auth::check() && in_array(Auth::user()->role, ['superadmin', 'admin']))
                    <a href="{{ route('karyawan.edit', $karyawan->id) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit Pegawai
                    </a>
                @endif
            </div>
        </div>
    </div>
    {{-- Font Awesome for icons --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
@endsection
