@csrf
<div class="mb-3">
    <label for="nama">Nama</label>
    <input type="text" name="nama" id="nama" class="form-control @error('nama') is-invalid @enderror"
        value="{{ old('nama', $karyawan->nama ?? '') }}" required>
    @error('nama')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="email">Email (Untuk Pengiriman Slip Gaji)</label>
    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
        value="{{ old('email', $karyawan->email ?? '') }}">
    @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="nip">NIP</label>
    <input type="text" name="nip" id="nip" class="form-control @error('nip') is-invalid @enderror"
        value="{{ old('nip', $karyawan->nip ?? '') }}" required>
    @error('nip')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- =================== BAGIAN YANG DIUBAH =================== --}}
<div class="mb-3">
    <label for="jabatan_id">Jabatan</label>
    <select name="jabatan_id" id="jabatan_id" class="form-control @error('jabatan_id') is-invalid @enderror" required>
        <option value="" disabled selected>-- Pilih Jabatan --</option>
        @foreach ($jabatans as $jabatan)
            <option value="{{ $jabatan->id }}"
                {{ old('jabatan_id', $karyawan->jabatan_id ?? '') == $jabatan->id ? 'selected' : '' }}>
                {{ $jabatan->nama_jabatan }}
            </option>
        @endforeach
    </select>
    @error('jabatan_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
{{-- ========================================================== --}}

<div class="mb-3">
    <label for="telepon">Telepon</label>
    <input type="text" name="telepon" id="telepon" class="form-control @error('telepon') is-invalid @enderror"
        value="{{ old('telepon', $karyawan->telepon ?? '') }}" required>
    @error('telepon')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="alamat">Alamat</label>
    <textarea name="alamat" id="alamat" class="form-control @error('alamat') is-invalid @enderror" required>{{ old('alamat', $karyawan->alamat ?? '') }}</textarea>
    @error('alamat')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button class="btn btn-success">
    <i class="fas fa-save"></i> {{ isset($karyawan) ? 'Update' : 'Simpan' }}
</button>
<a href="{{ route('karyawan.index') }}" class="btn btn-secondary">Batal</a>
