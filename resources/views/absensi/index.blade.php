@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h4 class="mb-3">Form Absensi Karyawan</h4>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @elseif(session('info'))
                <div class="alert alert-info">{{ session('info') }}</div>
            @endif

            <form method="POST" action="{{ route('absensi.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="nip" class="form-label">NIP Karyawan</label>
                    <input type="text" name="nip" class="form-control @error('nip') is-invalid @enderror" required>
                    @error('nip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Absen Sekarang</button>
            </form>
        </div>
    </div>
@endsection
