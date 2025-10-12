@csrf
<div class="mb-3">
    <label for="nama">Nama</label>
    <input type="text" name="nama" id="nama" class="form-control @error('nama') is-invalid @enderror"
        value="{{ old('nama', $karyawan->nama ?? '') }}" required>
    @error('nama')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- EMAIL DI TABEL KARYAWAN TIDAK DIPERLUKAN LAGI, KARENA AKAN DIAMBIL DARI AKUN USER --}}
{{-- <div class="mb-3">
    <label for="email">Email (Untuk Pengiriman Slip Gaji)</label>
    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
        value="{{ old('email', $karyawan->email ?? '') }}">
    @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div> --}}

<div class="mb-3">
    <label for="nip">NIP</label>
    <input type="text" name="nip" id="nip" class="form-control @error('nip') is-invalid @enderror"
        value="{{ old('nip', $karyawan->nip ?? '') }}" required>
    @error('nip')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="jabatan_id">Jabatan</label>
    <select name="jabatan_id" id="jabatan_id" class="form-control @error('jabatan_id') is-invalid @enderror">
        <option value="">-- Tidak Ada Jabatan --</option>
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

<div class="mb-3">
    <label for="telepon">Telepon</label>
    <input type="text" name="telepon" id="telepon" class="form-control @error('telepon') is-invalid @enderror"
        value="{{ old('telepon', $karyawan->telepon ?? '') }}">
    @error('telepon')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="alamat">Alamat</label>
    <textarea name="alamat" id="alamat" class="form-control @error('alamat') is-invalid @enderror">{{ old('alamat', $karyawan->alamat ?? '') }}</textarea>
    @error('alamat')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- --- AWAL PERUBAHAN --- --}}
<hr>
<h5 class="mt-4 mb-3 fw-bold text-primary">Akun Login Tenaga Kerja</h5>
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="user_email" class="form-label">Email Login</label>
            {{-- Variabel 'user_email' untuk membuat User --}}
            <input type="email" class="form-control @error('user_email') is-invalid @enderror" id="user_email"
                name="user_email" value="{{ old('user_email', $karyawan->email ?? '') }}" required>
            @error('user_email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            {{-- Variabel 'password' untuk membuat User --}}
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                name="password"
                @if ($karyawan->exists) placeholder="Isi hanya jika ingin mengubah" @else required @endif>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>
{{-- --- AKHIR PERUBAHAN --- --}}


<button class="btn btn-success">
    <i class="fas fa-save"></i> {{ $tombol }}
</button>
<a href="{{ route('karyawan.index') }}" class="btn btn-secondary">Batal</a>
