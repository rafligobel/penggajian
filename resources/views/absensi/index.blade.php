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
                                <label for="nip" class="form-label">Masukkan NIP Anda</label>
                                <input type="text" name="nip"
                                    class="form-control form-control-lg @error('nip') is-invalid @enderror" required
                                    autofocus>
                                @error('nip')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Absen Sekarang</button>
                            </div>
                        </form>
                    @else
                        {{-- Jika sesi ditutup, tampilkan pesan --}}
                        <div class="alert alert-warning text-center">
                            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Sesi Absensi Ditutup</h5>
                            <p class="mb-0">Saat ini sesi absensi sedang ditutup. Silakan hubungi bendahara untuk
                                informasi jadwal.</p>
                            @if ($sesiHariIni)
                                <hr>
                                <p class="mb-0">Jadwal hari ini:
                                    {{ \Carbon\Carbon::parse($sesiHariIni->waktu_mulai)->format('H:i') }} -
                                    {{ \Carbon\Carbon::parse($sesiHariIni->waktu_selesai)->format('H:i') }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
