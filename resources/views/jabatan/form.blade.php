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
    <label for="gaji_pokok" class="block text-sm font-medium text-gray-700">Jumlah Tunjangan Jabatan (Rp)</label>
    <input type="number" name="tunjangan_jabatan" id="gaji_pokok"
        value="{{ old('tunjangan_jabatan', $jabatan->tunjangan_jabatan ?? '') }}"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        required>
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
