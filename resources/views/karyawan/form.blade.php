@csrf
<div class="mb-3">
    <label>Nama</label>
    <input type="text" name="nama" class="form-control" value="{{ old('nama', $karyawan->nama ?? '') }}">
</div>
<div class="mb-3">
    <label>NIP</label>
    <input type="text" name="nip" class="form-control" value="{{ old('nip', $karyawan->nip ?? '') }}">
</div>
<div class="mb-3">
    <label>Alamat</label>
    <textarea name="alamat" class="form-control">{{ old('alamat', $karyawan->alamat ?? '') }}</textarea>
</div>
<div class="mb-3">
    <label>Telepon</label>
    <input type="text" name="telepon" class="form-control" value="{{ old('telepon', $karyawan->telepon ?? '') }}">
</div>
<div class="mb-3">
    <label>Jabatan</label>
    <input type="text" name="jabatan" class="form-control" value="{{ old('jabatan', $karyawan->jabatan ?? '') }}">
</div>
<button class="btn btn-success">Simpan</button>
