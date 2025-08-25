<div class="form-group">
    <label for="nama_jabatan">Nama Jabatan</label>
    <input type="text" name="nama_jabatan" id="nama_jabatan"
        class="form-control @error('nama_jabatan') is-invalid @enderror"
        value="{{ old('nama_jabatan', $jabatan->nama_jabatan ?? '') }}" required>
    @error('nama_jabatan')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="gaji_pokok">Gaji Pokok (Rp)</label>
    <input type="number" name="gaji_pokok" id="gaji_pokok"
        class="form-control @error('gaji_pokok') is-invalid @enderror"
        value="{{ old('gaji_pokok', $jabatan->gaji_pokok ?? '') }}" required min="0">
    @error('gaji_pokok')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> {{ isset($jabatan) ? 'Update' : 'Simpan' }}
    </button>
    <a href="{{ route('jabatan.index') }}" class="btn btn-secondary">
        Batal
    </a>
</div>
