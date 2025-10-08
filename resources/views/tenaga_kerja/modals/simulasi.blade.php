<div class="modal-header">
    <h5 class="modal-title"><i class="fas fa-calculator me-2"></i>Simulasi Gaji</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<form method="POST" action="{{ route('tenaga_kerja.simulasi.hitung') }}">
    @csrf
    <div class="modal-body">
        <p class="text-muted text-center mb-4">
            Ubah nilai di bawah untuk mendapatkan estimasi gaji Anda.
            @if (isset($gajiTerakhir))
                <br><span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                    <i class="fas fa-check-circle me-1"></i>Form ini diisi otomatis dari gaji terakhir Anda.
                </span>
            @endif
        </p>

        {{-- Input Utama (Tidak Tetap) --}}
        <div class="mb-3">
            <label for="jumlah_hari_masuk" class="form-label fw-bold">Jumlah Hari Masuk</label>
            <input type="number" class="form-control" name="jumlah_hari_masuk" id="jumlah_hari_masuk" value="26" required>
            <div class="form-text">Input ini akan menghitung Tunjangan Kehadiran.</div>
        </div>
        <hr>

        {{-- Grup Input Tambahan (Tetap tapi bisa diubah) --}}
        <p class="fw-bold text-primary">Pendapatan Tambahan</p>

        {{-- Semua input di bawah ini mengambil nilai dari $gajiTerakhir jika ada, jika tidak, default-nya 0 --}}
        <div class="mb-3">
            <label for="tunj_anak" class="form-label">Tunjangan Anak</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" name="tunj_anak" id="tunj_anak" value="{{ $gajiTerakhir->tunj_anak ?? 0 }}" min="0">
            </div>
        </div>

        <div class="mb-3">
            <label for="tunj_komunikasi" class="form-label">Tunjangan Komunikasi</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" name="tunj_komunikasi" id="tunj_komunikasi" value="{{ $gajiTerakhir->tunj_komunikasi ?? 0 }}" min="0">
            </div>
        </div>

        <div class="mb-3">
            <label for="tunj_pengabdian" class="form-label">Tunjangan Pengabdian</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" name="tunj_pengabdian" id="tunj_pengabdian" value="{{ $gajiTerakhir->tunj_pengabdian ?? 0 }}" min="0">
            </div>
        </div>

        <div class="mb-3">
            <label for="tunj_kinerja" class="form-label">Tunjangan Kinerja</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" name="tunj_kinerja" id="tunj_kinerja" value="{{ $gajiTerakhir->tunj_kinerja ?? 0 }}" min="0">
            </div>
        </div>

        <div class="mb-3">
            <label for="lembur" class="form-label">Estimasi Lembur</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" name="lembur" id="lembur" value="{{ $gajiTerakhir->lembur ?? 0 }}" min="0">
            </div>
        </div>
        <hr>

        {{-- Grup Input Potongan --}}
        <p class="fw-bold text-danger">Potongan</p>
        <div class="mb-3">
            <label for="potongan" class="form-label">Potongan Lain-lain</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" name="potongan" id="potongan" value="{{ $gajiTerakhir->potongan ?? 0 }}" min="0">
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-calculator me-1"></i> Hitung Ulang Simulasi
        </button>
    </div>
</form>