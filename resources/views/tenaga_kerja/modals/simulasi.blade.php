<div class="modal-header">
    <h5 class="modal-title"><i class="fas fa-calculator me-2"></i>Simulasi Gaji</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

{{-- [PERBAIKAN] Tambahkan ID pada form untuk hook JavaScript --}}
<form method="POST" action="{{ route('tenaga_kerja.simulasi.hitung') }}" id="form-simulasi">
    @csrf
    <div class="modal-body">
        <p class="text-muted text-center mb-4">
            Ubah nilai di bawah untuk mendapatkan estimasi gaji Anda.
            {{-- [PERBAIKAN] Pastikan variabel $gajiTerakhir dikirim dari controller --}}
            @if (isset($gajiTerakhir))
                <br><span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                    <i class="fas fa-check-circle me-1"></i>Form ini diisi otomatis dari gaji terakhir Anda.
                </span>
            @endif
        </p>

        {{-- [PERBAIKAN] Container untuk menampilkan error validasi AJAX --}}
        <div id="simulasi-error-container" class="alert alert-danger py-2 d-none">
            <ul class="mb-0 ps-3"></ul>
        </div>

        {{-- [PERBAIKAN 1: TAMBAHKAN INPUT HIDDEN] 
             Ini WAJIB ada untuk dikirim ke service kalkulasi --}}
        <input type="hidden" name="tunjangan_kehadiran_id"
            value="{{ $gajiTerakhir->tunjangan_kehadiran_id ?? ($tunjanganKehadiranDefault->id ?? 1) }}">


        {{-- Input Utama (Tidak Tetap) --}}
        <div class="mb-3">
            <label for="jumlah_hari_masuk" class="form-label fw-bold">Jumlah Hari Masuk</label>
            <input type="number" class="form-control" name="jumlah_hari_masuk" id="jumlah_hari_masuk" value="26"
                required>
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
                <input type="number" class="form-control" name="tunj_anak" id="tunj_anak"
                    value="{{ $gajiTerakhir->tunj_anak ?? 0 }}" min="0">
            </div>
        </div>

        <div class="mb-3">
            <label for="tunj_komunikasi" class="form-label">Tunjangan Komunikasi</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" name="tunj_komunikasi" id="tunj_komunikasi"
                    value="{{ $gajiTerakhir->tunj_komunikasi ?? 0 }}" min="0">
            </div>
        </div>

        <div class="mb-3">
            <label for="tunj_pengabdian" class="form-label">Tunjangan Pengabdian</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" name="tunj_pengabdian" id="tunj_pengabdian"
                    value="{{ $gajiTerakhir->tunj_pengabdian ?? 0 }}" min="0">
            </div>
        </div>

        <div class="mb-3">
            <label for="tunj_kinerja" class="form-label">Tunjangan Kinerja</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" name="tunj_kinerja" id="tunj_kinerja"
                    value="{{ $gajiTerakhir->tunj_kinerja ?? 0 }}" min="0">
            </div>
        </div>

        <div class="mb-3">
            <label for="lembur" class="form-label">Estimasi Lembur</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" name="lembur" id="lembur"
                    value="{{ $gajiTerakhir->lembur ?? 0 }}" min="0">
            </div>
        </div>
        <hr>

        {{-- Grup Input Potongan --}}
        <p class="fw-bold text-danger">Potongan</p>
        <div class="mb-3">
            <label for="potongan" class="form-label">Potongan Lain-lain</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" name="potongan" id="potongan"
                    value="{{ $gajiTerakhir->potongan ?? 0 }}" min="0">
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" id="btn-hitung-simulasi">
            <i class="fas fa-calculator me-1"></i> Hitung Simulasi
        </button>
    </div>
</form>

{{-- [PERBAIKAN 2: TAMBAHKAN SCRIPT AJAX] 
     Ini adalah kunci agar modalnya berfungsi.
     Letakkan ini di file dashboard utama Anda dalam @push('scripts') --}}
<script>
    // Pastikan script ini dijalankan setelah DOM siap
    document.addEventListener('DOMContentLoaded', function() {
        const formSimulasi = document.getElementById('form-simulasi');
        const btnHitung = document.getElementById('btn-hitung-simulasi');

        // Modal Bootstrap
        // Asumsi ID modal Anda adalah #simulasiModal dan #hasilSimulasiModal
        const modalSimulasiEl = document.getElementById('simulasiModal');
        const modalHasilEl = document.getElementById('hasilSimulasiModal');

        if (!formSimulasi || !btnHitung || !modalSimulasiEl || !modalHasilEl) {
            console.error('Elemen modal simulasi tidak ditemukan. Pastikan ID modal sudah benar.');
            return;
        }

        const modalSimulasi = new bootstrap.Modal(modalSimulasiEl);
        const modalHasil = new bootstrap.Modal(modalHasilEl);

        // Target untuk inject HTML hasil
        const hasilContent = document.getElementById('hasil-simulasi-content');
        const errorContainer = document.getElementById('simulasi-error-container');
        const errorList = errorContainer.querySelector('ul');

        formSimulasi.addEventListener('submit', function(e) {
            e.preventDefault(); // Hentikan submit form biasa

            // Tampilkan loading
            btnHitung.disabled = true;
            btnHitung.innerHTML =
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menghitung...';
            errorContainer.classList.add('d-none');
            errorList.innerHTML = '';

            const formData = new FormData(formSimulasi);

            fetch(formSimulasi.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': formData.get('_token'),
                        'Accept': 'text/html' // Minta HTML sebagai balasan
                    },
                    body: formData
                })
                .then(response => {
                    if (response.ok) { // Status 200-299
                        return response.text();
                    }

                    // Handle error validasi (status 422)
                    if (response.status === 422) {
                        return response.json().then(data => {
                            throw {
                                type: 'validation',
                                errors: data.errors
                            };
                        });
                    }
                    // Handle error server (status 500)
                    throw {
                        type: 'server',
                        message: 'Terjadi kesalahan pada server.'
                    };
                })
                .then(html => {
                    // SUKSES: Inject HTML, tutup modal form, buka modal hasil
                    if (hasilContent) {
                        hasilContent.innerHTML = html;
                        modalSimulasi.hide();
                        modalHasil.show();
                    } else {
                        console.error('Target #hasil-simulasi-content tidak ditemukan.');
                    }
                })
                .catch(error => {
                    // GAGAL: Tampilkan error di modal form
                    errorContainer.classList.remove('d-none');
                    if (error.type === 'validation') {
                        for (const key in error.errors) {
                            const li = document.createElement('li');
                            li.textContent = error.errors[key][0];
                            errorList.appendChild(li);
                        }
                    } else {
                        const li = document.createElement('li');
                        li.textContent = error.message || 'Gagal memproses permintaan.';
                        errorList.appendChild(li);
                    }
                })
                .finally(() => {
                    // Kembalikan tombol ke state normal
                    btnHitung.disabled = false;
                    btnHitung.innerHTML = '<i class="fas fa-calculator me-1"></i> Hitung Simulasi';
                });
        });
    });
</script>
