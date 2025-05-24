@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Tambah Data Gaji</h1>
        <form action="{{ route('gaji.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="karyawan_id" class="form-label">Nama Karyawan</label>
                <select name="karyawan_id" class="form-control" required>
                    <option value="">-- Pilih Karyawan --</option>
                    @foreach ($karyawans as $karyawan)
                        <option value="{{ $karyawan->id }}">{{ $karyawan->nama }} ({{ $karyawan->nip }})</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="bulan" class="form-label">Bulan</label>
                <input type="month" name="bulan" class="form-control" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Gaji Pokok</label>
                    <input type="number" name="gaji_pokok" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Tunjangan Kehadiran</label>
                    <input type="number" name="tunj_kehadiran" class="form-control" value="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Tunjangan Anak</label>
                    <input type="number" name="tunj_anak" class="form-control" value="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Tunjangan Komunikasi</label>
                    <input type="number" name="tunj_komunikasi" class="form-control" value="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Tunjangan Pengabdian</label>
                    <input type="number" name="tunj_pengabdian" class="form-control" value="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Tunjangan Jabatan</label>
                    <input type="number" name="tunj_jabatan" class="form-control" value="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Tunjangan Kinerja</label>
                    <input type="number" name="tunj_kinerja" class="form-control" value="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Lembur</label>
                    <input type="number" name="lembur" class="form-control" value="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Kelebihan Jam</label>
                    <input type="number" name="kelebihan_jam" class="form-control" value="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Potongan</label>
                    <input type="number" name="potongan" class="form-control" value="0">
                </div>
            </div>

            <button class="btn btn-primary">Simpan</button>
            <a href="{{ route('gaji.index') }}" class="btn btn-secondary">Batal</a>
        </form>
    </div>
@endsection
