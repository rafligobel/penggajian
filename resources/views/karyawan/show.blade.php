@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Detail Pegawai: {{ $karyawan->nama }}</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Nama Lengkap</dt>
                            <dd class="col-sm-8">{{ $karyawan->nama }}</dd>

                            <dt class="col-sm-4">NIP</dt>
                            <dd class="col-sm-8">{{ $karyawan->nip }}</dd>

                            {{-- PERUBAHAN 4: Tambah baris Email --}}
                            <dt class="col-sm-4">Email</dt>
                            <dd class="col-sm-8">{{ $karyawan->email ?: 'Tidak ada' }}</dd>

                            <dt class="col-sm-4">Jabatan</dt>
                            <dd class="col-sm-8">{{ $karyawan->jabatan }}</dd>


                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
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
                @if (Auth::check() && Auth::user()->role == 'admin')
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
