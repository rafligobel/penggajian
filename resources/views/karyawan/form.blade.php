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
    <label for="foto" class="form-label">Foto Pegawai</label>
    <input class="form-control @error('foto') is-invalid @enderror" type="file" id="foto" name="foto">
    @error('foto')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror

    {{-- Tampilkan foto saat ini di halaman edit --}}
    @if (isset($karyawan) && $karyawan->foto)
        <div class="mt-2">
            {{-- Jalur foto ini sudah benar sesuai KaryawanController Anda --}}
            <img src="{{ asset('uploads/foto_pegawai/' . $karyawan->foto) }}" alt="Foto saat ini"
                style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
            <small class="d-block text-muted">Foto saat ini. Upload file baru untuk mengganti.</small>
        </div>
    @endif
</div>
<div class="mb-3">
    <label for="nip">NIP</label>
    <input type="text" name="nip" id="nip" class="form-control @error('nip') is-invalid @enderror"
        value="{{ old('nip', $karyawan->nip ?? '') }}" required>
    @error('nip')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="alamat">Alamat</label>
    <textarea name="alamat" id="alamat" class="form-control @error('alamat') is-invalid @enderror" rows="3">{{ old('alamat', $karyawan->alamat ?? '') }}</textarea>
    @error('alamat')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="telepon">Telepon</label>
    <input type="tel" name="telepon" id="telepon" class="form-control @error('telepon') is-invalid @enderror"
        value="{{ old('telepon', $karyawan->telepon ?? '') }}">
    @error('telepon')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="jabatan_id">Jabatan</label>
    <select name="jabatan_id" id="jabatan_id" class="form-select @error('jabatan_id') is-invalid @enderror">
        <option value="" {{ old('jabatan_id', $karyawan->jabatan_id ?? '') ? '' : 'selected' }}>Pilih Jabatan
            (Opsional)</option>
        @foreach ($jabatans as $jabatan)
            <option value="{{ $jabatan->id }}"
                {{ old('jabatan_id', $karyawan->jabatan_id ?? '') == $jabatan->id ? 'selected' : '' }}>
                {{ $jabatan->nama_jabatan }}</option>
        @endforeach
    </select>
    @error('jabatan_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="gaji_pokok_default" class="form-label">Gaji Pokok (Master)</label>
    <div class="input-group">
        <span class="input-group-text">Rp</span>
        <input type="number" class="form-control @error('gaji_pokok_default') is-invalid @enderror"
            id="gaji_pokok_default" name="gaji_pokok_default"
            value="{{ old('gaji_pokok_default', $karyawan->gaji_pokok_default ?? 0) }}" min="0" required>
    </div>
    @error('gaji_pokok_default')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
{{-- --- AWAL PERUBAHAN --- --}}
<div class="mb-3">
    <label for="tanggal_masuk" class="form-label">Tanggal Masuk</label>
    <input type="date" class="form-control @error('tanggal_masuk') is-invalid @enderror" id="tanggal_masuk"
        name="tanggal_masuk"
        value="{{ old('tanggal_masuk', $karyawan->tanggal_masuk ? $karyawan->tanggal_masuk->format('Y-m-d') : '') }}">
    @error('tanggal_masuk')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="jumlah_anak" class="form-label">Jumlah Anak</label>
    <input type="number" class="form-control @error('jumlah_anak') is-invalid @enderror" id="jumlah_anak"
        name="jumlah_anak" value="{{ old('jumlah_anak', $karyawan->jumlah_anak ?? 0) }}" min="0">
    @error('jumlah_anak')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
{{-- --- AKHIR PERUBAHAN --- --}}
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
<div class="mt-4 d-flex justify-content-end">
    <a href="{{ route('karyawan.index') }}" class="btn btn-secondary me-2">Batal</a>
    <button type="submit" class="btn btn-primary">{{ $tombol }}</button>
</div>
