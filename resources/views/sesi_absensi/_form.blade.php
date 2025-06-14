{{-- File: resources/views/sesi_absensi/_form.blade.php --}}
<div class="card shadow-sm">
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-3">
            <label for="tanggal" class="form-label">Tanggal</label>
            <input type="date" name="tanggal" id="tanggal" class="form-control"
                value="{{ old('tanggal', isset($sesiAbsensi) ? $sesiAbsensi->tanggal->format('Y-m-d') : date('Y-m-d')) }}"
                required>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="waktu_mulai" class="form-label">Waktu Mulai</label>
                <input type="time" name="waktu_mulai" id="waktu_mulai" class="form-control"
                    value="{{ old('waktu_mulai', $sesiAbsensi->waktu_mulai ?? '07:00') }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="waktu_selesai" class="form-label">Waktu Selesai</label>
                <input type="time" name="waktu_selesai" id="waktu_selesai" class="form-control"
                    value="{{ old('waktu_selesai', $sesiAbsensi->waktu_selesai ?? '17:00') }}" required>
            </div>
        </div>

        @if (isset($sesiAbsensi))
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                    @checked(old('is_active', $sesiAbsensi->is_active))>
                <label class="form-check-label" for="is_active">Aktifkan Sesi Ini</label>
            </div>
        @endif
    </div>
    <div class="card-footer bg-light">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('sesi-absensi.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</div>
