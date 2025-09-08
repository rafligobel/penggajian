@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">Form Absensi Karyawan</h4>
                </div>
                <div class="card-body p-4">

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @elseif (session('info'))
                        <div class="alert alert-info">{{ session('info') }}</div>
                    @endif

                    @if ($isSesiDibuka)
                        {{-- Jika sesi dibuka, tampilkan form --}}
                        <div class="alert alert-info text-center">
                            <i class="fas fa-clock"></i> Sesi absensi dibuka hingga pukul
                            <strong>{{ \Carbon\Carbon::parse($sesiHariIni->waktu_selesai)->format('H:i') }}</strong>.
                        </div>
                        <form method="POST" action="{{ route('absensi.store') }}">
                            @csrf
                            <div class="mb-3">
                                {{-- PERUBAHAN LABEL --}}
                                <label for="identifier" class="form-label">Masukkan NIP atau Nama Lengkap Anda</label>

                                {{-- PERUBAHAN INPUT FIELD --}}
                                <input type="text" name="identifier" id="identifier"
                                    class="form-control form-control-lg @error('identifier') is-invalid @enderror"
                                    placeholder="Ketik NIP atau Nama Anda di sini..." value="{{ old('identifier') }}"
                                    required autofocus>

                                {{-- PERUBAHAN ERROR MESSAGE --}}
                                @error('identifier')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Absen Sekarang</button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-warning text-center">
                            <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Informasi Sesi</h5>
                            {{-- Gunakan pesanSesi yang lebih dinamis dari controller --}}
                            <p class="mb-0">{{ $pesanSesi ?? 'Sesi absensi untuk hari ini ditutup.' }}</p>
                            @if ($sesiHariIni)
                                <hr>
                                <p class="mb-0">Jadwal Sesi: {{ $sesiHariIni->waktu_mulai }} -
                                    {{ $sesiHariIni->waktu_selesai }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
