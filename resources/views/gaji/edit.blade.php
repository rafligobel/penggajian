@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Edit Data Gaji</h1>
        <form action="{{ route('gaji.update', $gaji->id) }}" method="POST">
            @csrf @method('PUT')

            <div class="mb-3">
                <label for="karyawan_id" class="form-label">Nama Karyawan</label>
                <select name="karyawan_id" class="form-control" required>
                    @foreach ($karyawans as $karyawan)
                        <option value="{{ $karyawan->id }}" {{ $gaji->karyawan_id == $karyawan->id ? 'selected' : '' }}>
                            {{ $karyawan->nama }} ({{ $karyawan->nip }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="bulan" class="form-label">Bulan</label>
                <input type="month" name="bulan" class="form-control" value="{{ $gaji->bulan }}" required>
            </div>

            @php
                $fields = [
                    'gaji_pokok',
                    'tunj_kehadiran',
                    'tunj_anak',
                    'tunj_komunikasi',
                    'tunj_pengabdian',
                    'tunj_jabatan',
                    'tunj_kinerja',
                    'lembur',
                    'kelebihan_jam',
                    'potongan',
                ];
            @endphp

            <div class="row">
                @foreach ($fields as $field)
                    <div class="col-md-6 mb-3">
                        <label>{{ ucwords(str_replace('_', ' ', $field)) }}</label>
                        <input type="number" name="{{ $field }}" class="form-control" value="{{ $gaji->$field }}">
                    </div>
                @endforeach
            </div>

            <button class="btn btn-primary">Update</button>
            <a href="{{ route('gaji.index') }}" class="btn btn-secondary">Batal</a>
        </form>
    </div>
@endsection
