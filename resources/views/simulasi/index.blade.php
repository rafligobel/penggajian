@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Simulasi Gaji (Tanpa Login)</h3>
    <form action="{{ route('simulasi.hitung') }}" method="POST">
        @csrf

        @php
        $fields = [
            'gaji_pokok' => 'Gaji Pokok',
            'tunj_kehadiran' => 'Tunjangan Kehadiran',
            'tunj_anak' => 'Tunjangan Anak',
            'tunj_komunikasi' => 'Tunjangan Komunikasi',
            'tunj_pengabdian' => 'Tunjangan Pengabdian',
            'tunj_jabatan' => 'Tunjangan Jabatan',
            'tunj_kinerja' => 'Tunjangan Kinerja',
            'lembur' => 'Lembur',
            'kelebihan_jam' => 'Kelebihan Jam',
            'potongan' => 'Potongan Gaji'
        ];
        @endphp

        @foreach ($fields as $name => $label)
        <div class="mb-3">
            <label for="{{ $name }}" class="form-label">{{ $label }}</label>
            <input type="number" class="form-control @error($name) is-invalid @enderror" id="{{ $name }}" name="{{ $name }}" value="{{ old($name, 0) }}">
            @error($name)
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @endforeach

        <button type="submit" class="btn btn-primary">Hitung Gaji</button>
    </form>
</div>
@endsection
