{{-- KARTU FILTER --}}
<div class="card shadow-sm mb-4 border-0">
    <div class="card-body">
        <form method="GET" action="{{ route('gaji.index') }}">
            <div class="row align-items-end g-3">
                <div class="col-md-3">
                    <label for="bulan" class="form-label fw-bold">Periode</label>
                    <input type="month" class="form-control" id="bulan" name="bulan" value="{{ $selectedMonth }}">
                </div>
                <div class="col-md-3">
                    <label for="jabatan_id" class="form-label fw-bold">Jabatan</label>
                    <select name="jabatan_id" id="jabatan_id" class="form-select">
                        <option value="">Semua Jabatan</option>
                        @foreach ($jabatans as $jabatan)
                            <option value="{{ $jabatan->id }}" @selected($jabatan->id == $selectedJabatanId)>
                                {{ $jabatan->nama_jabatan }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="tarif_kehadiran" class="form-label fw-bold">Tunjangan Kehadiran / Hari</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="tarif_kehadiran" name="tarif_kehadiran"
                            value="{{ $tarifKehadiran }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Terapkan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
