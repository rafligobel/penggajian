@extends('layouts.app')

@section('content')
    <div class="container ">
        <h3 class="fw-bold text-primary">Kelola Gaji</h3>

        {{-- ... Bagian Filter dan Tombol (Tidak Berubah) ... --}}
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <form method="GET" action="{{ route('gaji.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="bulan" class="form-label fw-bold">Pilih Periode Gaji</label>
                            <div class="input-group">
                                <input type="month" class="form-control" id="bulan" name="bulan"
                                    value="{{ $selectedMonth }}">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>
                                    Tampilkan</button>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <label for="search-input" class="form-label fw-bold">Cari Pegawai</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
                                <input type="text" id="search-input" class="form-control border-start-0"
                                    placeholder="Ketik nama atau jabatan karyawan...">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div id="ajax-response-message" class="alert" style="display:none;"></div>

        {{-- ... Bagian Tabel (Tidak Berubah) ... --}}
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>Nama Pegawai</th>
                                <th class="text-end">Gaji Pokok</th>
                                <th class="text-end">Tunj. Jabatan</th>
                                <th class="text-end">Gaji Bersih</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="gaji-table-body">
                            {{-- Kode Blade Anda sudah benar, menggunakan data dari SalaryService --}}
                            @forelse ($dataGaji as $gajiData)
                                <tr data-gaji-json="{{ json_encode($gajiData) }}" class="karyawan-row"
                                    data-karyawan-id="{{ $gajiData['karyawan_id'] }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong class="nama-karyawan">{{ $gajiData['nama'] }}</strong><br>
                                        <small class="text-muted nip-karyawan">NP: {{ $gajiData['nip'] }}</small>
                                    </td>
                                    <td class="text-end gaji-pokok-col">
                                        {{ $gajiData['gaji_pokok_string'] }}
                                    </td>
                                    <td class="text-end tunj-jabatan-col">
                                        {{ $gajiData['tunj_jabatan_string'] }}
                                    </td>
                                    <td class="text-end fw-bold gaji-bersih-col">
                                        <span
                                            class="badge {{ $gajiData['gaji_id'] ? 'bg-success' : 'bg-light text-dark' }}">{{ $gajiData['gaji_bersih_string'] }}</span>
                                    </td>
                                    <td class="text-center status-col">
                                        @if ($gajiData['gaji_id'])
                                            <span class="badge bg-primary">Sudah Diproses</span>
                                        @else
                                            <span class="badge bg-secondary">Belum Diproses</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if (Auth::user()->role === 'bendahara')
                                            <button class="btn btn-sm btn-info btn-detail" title="Detail Gaji"
                                                data-bs-toggle="modal" data-bs-target="#detailModal"><i
                                                    class="fas fa-eye"></i></button>

                                            <button class="btn btn-sm btn-warning btn-edit" title="Kelola Gaji"
                                                data-bs-toggle="modal" data-bs-target="#editModal"><i
                                                    class="fas fa-edit"></i></button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center fst-italic py-4">Tidak ada data pegawai yang
                                        aktif.</td>
                                </tr>
                            @endforelse
                            <tr id="no-search-results" style="display: none;">
                                <td colspan="7" class="text-center fst-italic py-4">Karyawan tidak ditemukan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================================================================== --}}
    {{-- ================== AWAL MODAL DETAIL (DIRAPIKAN) =================== --}}
    {{-- ==================================================================== --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Gaji: <span id="detail-nama-title"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detail-content">
                    {{-- Skeleton HTML dipindah ke sini dari JS --}}
                    <p>
                        <strong>Jabatan:</strong> <span id="detail-jabatan">-</span><br>
                        <strong>Periode:</strong> <span id="detail-periode">-</span>
                    </p>
                    <hr>
                    <div class="row">
                        <div class="col-lg-6 mb-4 mb-lg-0 border-end">
                            <h5 class="mb-3 text-primary">A. Pendapatan</h5>
                            <div class="row mb-2">
                                <div class="col-7">Gaji Pokok</div>
                                <div class="col-5 text-end"><strong id="detail-gaji-pokok">Rp 0</strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-7">Tunjangan Jabatan</div>
                                <div class="col-5 text-end"><strong id="detail-tunj-jabatan">Rp 0</strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-7">Tunjangan Anak</div>
                                <div class="col-5 text-end"><strong id="detail-tunj-anak">Rp 0</strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-7">Tunjangan Komunikasi</div>
                                <div class="col-5 text-end"><strong id="detail-tunj-komunikasi">Rp 0</strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-7">Tunjangan Pengabdian</div>
                                <div class="col-5 text-end"><strong id="detail-tunj-pengabdian">Rp 0</strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-7">Tunjangan Kinerja</div>
                                <div class="col-5 text-end"><strong id="detail-tunj-kinerja">Rp 0</strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-7">Tunj. Kehadiran (<span id="detail-total-kehadiran">0</span> hari)
                                </div>
                                <div class="col-5 text-end"><strong id="detail-tunj-kehadiran">Rp 0</strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-7">Lembur</div>
                                <div class="col-5 text-end"><strong id="detail-lembur">Rp 0</strong></div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <h5 class="mb-3 text-danger">B. Potongan</h5>
                            <div class="row mb-2">
                                <div class="col-7">Potongan Lain-lain</div>
                                <div class="col-5 text-end"><strong class="text-danger" id="detail-potongan">(Rp
                                        0)</strong></div>
                            </div>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="bg-light p-3 rounded">
                        <div class="row align-items-center">
                            <div class="col-7">
                                <h5 class="mb-0">GAJI BERSIH (A - B)</h5>
                            </div>
                            <div class="col-5 text-end">
                                <h5 class="mb-0 fw-bold text-success" id="detail-gaji-bersih">Rp 0</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <div>
                        <button type="button" class="btn btn-success btn-download-slip" disabled><i
                                class="fas fa-download me-2"></i>Unduh Slip</button>
                        <button type="button" class="btn btn-primary btn-send-email" disabled><i
                                class="fas fa-paper-plane me-2"></i>Kirim ke Email</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- ================================================================== --}}
    {{-- ================== AKHIR MODAL DETAIL (DIRAPIKAN) ================== --}}
    {{-- ================================================================== --}}


    {{-- ================================================================== --}}
    {{-- ================== AWAL MODAL EDIT (DIRAPIKAN) =================== --}}
    {{-- ================================================================== --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="editGajiForm" action="{{ route('gaji.save') }}" method="POST">
                    @csrf
                    <input type="hidden" name="bulan" value="{{ $selectedMonth }}">
                    <input type="hidden" id="edit-karyawan-id" name="karyawan_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Kelola Gaji Pegawai</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Bagian 1: Info Umum --}}
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Periode</label>
                                <input type="text" id="periode-modal" class="form-control" readonly>
                            </div>
                            <div class="col-md-8">
                                <label for="tunjangan_kehadiran_id_modal" class="form-label fw-bold">Pilih Tunjangan
                                    Kehadiran (Revisi 1)</label>
                                <select name="tunjangan_kehadiran_id" id="tunjangan_kehadiran_id_modal"
                                    class="form-select" required>
                                    @foreach ($tunjanganKehadirans as $tunjangan)
                                        <option value="{{ $tunjangan->id }}">
                                            {{ $tunjangan->jenis_tunjangan }}
                                            ({{ 'Rp ' . number_format($tunjangan->jumlah_tunjangan, 0, ',', '.') }}/hari)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <hr>

                        {{-- Bagian 2: Tunjangan Kinerja --}}
                        <h6 class="form-label fw-bold">Penilaian Kinerja (untuk Tunjangan Kinerja)</h6>
                        @if ($aturanKinerja && $aturanKinerja->maksimal_tunjangan > 0)
                            <div class="alert alert-info py-2" role="alert">
                                Tunjangan Kinerja Maksimal: <strong>Rp
                                    {{ number_format($aturanKinerja->maksimal_tunjangan, 0, ',', '.') }}</strong>.
                                <br>
                                <small>Nominal Tukin = (Rata-rata Skor / 100) * Tunjangan Maksimal.</small>
                            </div>
                        @else
                            <div class="alert alert-warning py-2" role="alert">
                                Tunjangan Kinerja Maksimal belum diatur oleh Admin.
                                Silakan atur di menu <a href="{{ route('pengaturan-kinerja.index') }}"
                                    target="_blank"></a>.
                            </div>
                        @endif

                        <div class="row">
                            @forelse ($indikatorKinerjas as $indikator)
                                <div class="col-md-6 mb-3">
                                    <label for="score-{{ $indikator->id }}"
                                        class="form-label">{{ $indikator->nama_indikator }}</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control score-input"
                                            name="scores[{{ $indikator->id }}]" id="score-{{ $indikator->id }}"
                                            data-indikator-id="{{ $indikator->id }}" value="0" min="0"
                                            max="100" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="alert alert-warning py-2" role="alert">
                                        Belum ada Master Indikator Kinerja yang diatur oleh Admin.

                                    </div>
                                </div>
                            @endforelse
                        </div>
                        <hr>

                        {{-- Bagian 3: Tunjangan dan Potongan Lain --}}
                        <h6 class="form-label fw-bold">Input Tunjangan Lain & Potongan</h6>
                        <div id="edit-form-content" class="row">
                            {{-- Skeleton HTML dipindah ke sini dari JS --}}

                            {{-- Tunjangan Komunikasi (Sesuai HTML Asli Anda) --}}
                            <div class="col-md-6 mb-3">
                                <label for="modalTunjanganKomunikasi" class="form-label">
                                    Tunjangan Komunikasi
                                </label>
                                <select class="form-select" id="modalTunjanganKomunikasi" name="tunjangan_komunikasi_id">
                                    <option value="">-- Tidak Dapat Tunjangan --</option>
                                    @foreach ($tunjanganKomunikasis as $item)
                                        <option value="{{ $item->id }}">
                                            {{ $item->nama_level }}
                                            ({{ 'Rp ' . number_format($item->besaran, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Tunjangan Jabatan (Readonly) --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tunjangan Jabatan (Otomatis)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control" id="edit-tunj-jabatan" value="0"
                                        readonly>
                                </div>
                            </div>

                            {{-- Gaji Pokok (Editable) --}}
                            <div class="col-md-6 mb-3">
                                <label for="edit-gaji-pokok" class="form-label">Gaji Pokok</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="edit-gaji-pokok" name="gaji_pokok"
                                        value="0" min="0" readonly>
                                </div>
                            </div>

                            {{-- Tunjangan Anak (Readonly) --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tunjangan Anak (Otomatis)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control" id="edit-tunj-anak" value="0"
                                        readonly>
                                </div>
                            </div>

                            {{-- Tunjangan Pengabdian (Readonly) --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tunj. Pengabdian (Otomatis)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control" id="edit-tunj-pengabdian" value="0"
                                        readonly>
                                </div>
                            </div>

                            {{-- Lembur (Editable) --}}
                            <div class="col-md-6 mb-3">
                                <label for="edit-lembur" class="form-label">Lembur</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="edit-lembur" name="lembur"
                                        value="0" min="0" required>
                                </div>
                            </div>

                            {{-- Potongan (Editable) --}}
                            <div class="col-md-6 mb-3">
                                <label for="edit-potongan" class="form-label">Potongan</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="edit-potongan" name="potongan"
                                        value="0" min="0" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- ================================================================== --}}
    {{-- ================== AKHIR MODAL EDIT (DIRAPIKAN) ================== --}}
    {{-- ================================================================== --}}
@endsection

@push('scripts')
    <script>
        // ================== AWAL PERBAIKAN (Menyimpan Data Master ke JS) ==================
        // Tidak ada perubahan di sini, ini sudah benar.
        const masterTunjanganKomunikasi = @json($tunjanganKomunikasis ?? []);
        // ================== AKHIR PERBAIKAN ==================

        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi Modal
            const detailModalEl = document.getElementById('detailModal');
            const editModalEl = document.getElementById('editModal');
            const detailModal = new bootstrap.Modal(detailModalEl);
            const editModal = new bootstrap.Modal(editModalEl);

            const editGajiForm = document.getElementById('editGajiForm');
            const responseMessageEl = document.getElementById('ajax-response-message');

            // --- FUNGSI-FUNGSI HELPER (Tidak Berubah) ---
            const formatRupiah = (angka) => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(angka || 0);

            // Fungsi ini diformat ulang sedikit untuk input readonly
            const formatRupiahInput = (angka) => {
                return formatRupiah(angka).replace('Rp', '').trim();
            };

            function showResponseMessage(message, isSuccess = true) {
                responseMessageEl.textContent = message;
                responseMessageEl.className = isSuccess ? 'alert alert-success' : 'alert alert-danger';
                responseMessageEl.style.display = 'block';
                setTimeout(() => responseMessageEl.style.display = 'none', 5000);
            }

            // --- FUNGSI UPDATE TABLE ROW (Tidak Berubah) ---
            function updateTableRow(newData) {
                // 'newData' adalah flat array dari SalaryService
                const row = document.querySelector(
                    `.karyawan-row[data-karyawan-id="${newData.karyawan_id}"]`);
                if (!row) return;

                row.setAttribute('data-gaji-json', JSON.stringify(newData));
                // Gunakan key _string untuk data tampilan
                row.querySelector('.gaji-pokok-col').textContent = newData.gaji_pokok_string;

                row.querySelector('.tunj-jabatan-col').textContent = newData.tunj_jabatan_string;
                row.querySelector('.gaji-bersih-col').innerHTML =
                    `<span class="badge bg-success">${newData.gaji_bersih_string}</span>`;
                row.querySelector('.status-col').innerHTML =
                    `<span class="badge bg-primary">Sudah Diproses</span>`;
            }

            // --- EVENT LISTENER UNTUK SUBMIT FORM EDIT (AJAX) (Tidak Berubah) ---
            editGajiForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const form = e.target;
                const formData = new FormData(form);
                const submitButton = form.querySelector('button[type="submit"]');
                const originalButtonHtml = submitButton.innerHTML;

                submitButton.disabled = true;
                submitButton.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Menyimpan...`;

                fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showResponseMessage(data.message, true);
                            editModal.hide();
                            updateTableRow(data.newData);
                        } else {
                            if (data.errors) {
                                let errorMsg = data.message || 'Gagal menyimpan data.';
                                for (const key in data.errors) {
                                    errorMsg += `\n- ${data.errors[key][0]}`;
                                }
                                showResponseMessage(errorMsg, false);
                            } else {
                                showResponseMessage(data.message || 'Gagal menyimpan data.', false);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showResponseMessage('Terjadi kesalahan koneksi.', false);
                    })
                    .finally(() => {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonHtml;
                    });
            });

            // --- EVENT LISTENER UNTUK TOMBOL-TOMBOL AKSI DI TABEL (Tidak Berubah) ---
            document.getElementById('gaji-table-body').addEventListener('click', function(e) {
                const button = e.target.closest('.btn-detail, .btn-edit');
                if (!button) return;
            });

            // --- EVENT LISTENER 'show.bs.modal' UNTUK MODAL EDIT ---
            editModalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (!button) return;

                const row = button.closest('tr.karyawan-row');
                if (!row) return;

                const gajiData = JSON.parse(row.getAttribute('data-gaji-json'));

                // Panggil fungsi populate yang baru (lebih bersih)
                populateEditModal(gajiData);

                // --- Logika untuk mengisi skor (Sudah Benar, Tidak Berubah) ---
                // 1. Reset semua input skor ke 0 (atau nilai default)
                editModalEl.querySelectorAll('.score-input').forEach(input => {
                    input.value = 0;
                });

                // 2. Ambil data skor tersimpan (dari SalaryService 'penilaian_kinerja')
                const savedScores = gajiData.penilaian_kinerja;

                // 3. Isi input skor jika datanya ada
                if (savedScores) {
                    for (const [indikator_id, skor] of Object.entries(savedScores)) {
                        const scoreInput = editModalEl.querySelector(
                            `.score-input[data-indikator-id="${indikator_id}"]`);
                        if (scoreInput) {
                            scoreInput.value = skor;
                        }
                    }
                }
                // --- Akhir Logika Skor ---

                // ================== AWAL PERBAIKAN (JS MENGISI TUNJ. KOMUNIKASI) ==================
                // Logika yang membingungkan dihapus, karena sudah ditangani
                // di dalam 'populateEditModal' yang baru.
                // ================== AKHIR PERBAIKAN ==================
            });

            // --- EVENT LISTENER 'show.bs.modal' UNTUK MODAL DETAIL ---
            detailModalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (!button) return;

                const row = button.closest('tr.karyawan-row');
                if (!row) return;

                const gajiData = JSON.parse(row.getAttribute('data-gaji-json'));

                // Panggil fungsi populate yang baru (lebih bersih)
                populateDetailModal(gajiData);
            });


            // --- FUNGSI PENCARIAN (Tidak Berubah) ---
            document.getElementById('search-input').addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('#gaji-table-body tr.karyawan-row');
                const noResultsRow = document.getElementById('no-search-results');
                let visibleRows = 0;

                rows.forEach(row => {
                    const nama = row.querySelector('.nama-karyawan').textContent.toLowerCase();
                    const nip = row.querySelector('.nip-karyawan').textContent
                        .toLowerCase();
                    if (nama.includes(searchTerm) || nip.includes(searchTerm)) {
                        row.style.display = '';
                        visibleRows++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                noResultsRow.style.display = (visibleRows === 0 && searchTerm) ? '' : 'none';
            });

            // ================== AWAL PERBAIKAN (JS LISTENER DROPDOWN KOMUNIKASI) ==================
            // Seluruh blok event listener 'change' untuk 'edit_tunj_komunikasi_select'
            // telah dihapus karena elemen tersebut tidak ada lagi di HTML yang dirapikan.
            // Form sekarang langsung mengirimkan ID dari <select id="modalTunjanganKomunikasi">.
            // ================== AKHIR PERBAIKAN ==================


            // =========================================================================
            // ================== [REVISI UTAMA] (populateEditModal) ===================
            // =========================================================================
            function populateEditModal(data) {
                const modal = editModalEl;
                modal.querySelector('#editModalLabel').textContent = `Kelola Gaji: ${data.nama}`;
                modal.querySelector('#periode-modal').value = new Date(data.bulan + '-02').toLocaleDateString(
                    'id-ID', {
                        month: 'long',
                        year: 'numeric'
                    });
                modal.querySelector('#edit-karyawan-id').value = data.karyawan_id;

                // --- Mengisi Tunjangan Kehadiran ---
                const tunjKehadiranSelect = modal.querySelector('#tunjangan_kehadiran_id_modal');
                if (data.tunjangan_kehadiran_id) {
                    tunjKehadiranSelect.value = data.tunjangan_kehadiran_id;
                } else if (tunjKehadiranSelect.options.length > 0) {
                    tunjKehadiranSelect.value = tunjKehadiranSelect.options[0].value;
                }

                // --- Mengisi Tunjangan Komunikasi ---
                modal.querySelector('#modalTunjanganKomunikasi').value = data.tunjangan_komunikasi_id || '';

                // --- Mengisi Input Readonly (Format Rupiah) ---
                modal.querySelector('#edit-tunj-jabatan').value = formatRupiahInput(data.tunj_jabatan);
                modal.querySelector('#edit-tunj-anak').value = formatRupiahInput(data.tunj_anak);
                modal.querySelector('#edit-tunj-pengabdian').value = formatRupiahInput(data.tunj_pengabdian);

                // --- Mengisi Input Editable (Angka) ---
                modal.querySelector('#edit-gaji-pokok').value = parseFloat(data.gaji_pokok || 0);
                modal.querySelector('#edit-lembur').value = parseFloat(data.lembur || 0);
                modal.querySelector('#edit-potongan').value = parseFloat(data.potongan || 0);

                // Konten dinamis (fields, fieldsHtml) tidak diperlukan lagi
            }

            // =========================================================================
            // ================== [REVISI UTAMA] (populateDetailModal) =================
            // =========================================================================
            function populateDetailModal(data) {
                const modal = detailModalEl;

                // --- Mengisi Info Header ---
                modal.querySelector('#detail-nama-title').textContent = data.nama;
                modal.querySelector('#detail-jabatan').textContent = data.jabatan || '-';
                modal.querySelector('#detail-periode').textContent = new Date(data.bulan + '-02')
                    .toLocaleDateString('id-ID', {
                        month: 'long',
                        year: 'numeric'
                    });

                // --- Mengisi Rincian Pendapatan ---
                modal.querySelector('#detail-gaji-pokok').textContent = data.gaji_pokok_string;
                modal.querySelector('#detail-tunj-jabatan').textContent = data.tunj_jabatan_string;
                modal.querySelector('#detail-tunj-anak').textContent = data.tunj_anak_string;
                modal.querySelector('#detail-tunj-komunikasi').textContent = data.tunj_komunikasi_string;
                modal.querySelector('#detail-tunj-pengabdian').textContent = data.tunj_pengabdian_string;
                modal.querySelector('#detail-tunj-kinerja').textContent = data.tunj_kinerja_string;
                modal.querySelector('#detail-total-kehadiran').textContent = data.total_kehadiran || 0;
                modal.querySelector('#detail-tunj-kehadiran').textContent = data.total_tunjangan_kehadiran_string;
                modal.querySelector('#detail-lembur').textContent = data.lembur_string;

                // --- Mengisi Rincian Potongan ---
                modal.querySelector('#detail-potongan').textContent = `(${data.potongan_string})`;

                // --- Mengisi Total Gaji Bersih ---
                modal.querySelector('#detail-gaji-bersih').textContent = data.gaji_bersih_string;

                // --- Logika Tombol (Tidak Berubah, hanya dipindah) ---
                const downloadBtn = modal.querySelector('.btn-download-slip');
                const emailBtn = modal.querySelector('.btn-send-email');

                // Clone & replace untuk hapus event listener lama
                const newDownloadBtn = downloadBtn.cloneNode(true);
                downloadBtn.parentNode.replaceChild(newDownloadBtn, downloadBtn);
                const newEmailBtn = emailBtn.cloneNode(true);
                emailBtn.parentNode.replaceChild(newEmailBtn, emailBtn);

                if (data.gaji_id) {
                    newDownloadBtn.disabled = false;
                    const hasEmail = data.email && data.email.trim() !== '';
                    newEmailBtn.disabled = !hasEmail;

                    const downloadUrl = `/gaji/${data.gaji_id}/download-slip`;
                    const emailUrl = `/gaji/${data.gaji_id}/send-email`;

                    function handleJobDispatch(button, url, processName, event) {
                        event.preventDefault();
                        const originalButtonHtml = button.innerHTML;
                        button.disabled = true;
                        button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Memproses...`;

                        fetch(url, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(apiData => {
                                if (apiData.message) {
                                    showResponseMessage(apiData.message, true);
                                    detailModal.hide();
                                } else {
                                    showResponseMessage('Terjadi kesalahan.', false);
                                }
                            })
                            .catch(error => {
                                console.error(`Error ${processName}:`, error);
                                showResponseMessage(`Gagal memulai proses ${processName}.`, false);
                            })
                            .finally(() => {
                                button.disabled = false;
                                button.innerHTML = originalButtonHtml;
                            });
                    }

                    newDownloadBtn.addEventListener('click', function(e) {
                        handleJobDispatch(this, downloadUrl, 'unduh slip', e);
                    });

                    newEmailBtn.addEventListener('click', function(e) {
                        handleJobDispatch(this, emailUrl, 'kirim email', e);
                    });
                } else {
                    newDownloadBtn.disabled = true;
                    newEmailBtn.disabled = true;
                }
            }
            // --- Akhir populateDetailModal ---
        });
    </script>
@endpush
