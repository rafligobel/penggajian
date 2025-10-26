@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4 fw-bold text-primary">Kelola Gaji</h3>

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
                            {{-- ================== PERBAIKAN BLADE DIMULAI (Kode Asli Anda) ================== --}}
                            @forelse ($dataGaji as $gajiData)
                                {{-- Tidak perlu @php, $gajiData adalah array yang kita gunakan --}}
                                <tr data-gaji-json="{{ json_encode($gajiData) }}" class="karyawan-row"
                                    data-karyawan-id="{{ $gajiData['karyawan_id'] }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong class="nama-karyawan">{{ $gajiData['nama'] }}</strong><br>
                                        {{-- NIP sudah diambil dari SalaryService --}}
                                        <small class="text-muted nip-karyawan">NP: {{ $gajiData['nip'] }}</small>
                                    </td>
                                    <td class="text-end gaji-pokok-col">
                                        {{-- Ambil 'gaji_pokok_string' dari service, sudah termasuk Rp --}}
                                        {{ $gajiData['gaji_pokok_string'] }}
                                    </td>
                                    <td class="text-end tunj-jabatan-col">
                                        {{-- Ambil 'tunj_jabatan_string' dari service, sudah termasuk Rp --}}
                                        {{ $gajiData['tunj_jabatan_string'] }}
                                    </td>
                                    <td class="text-end fw-bold gaji-bersih-col">
                                        {{-- Ambil 'gaji_bersih_string' dan cek 'gaji_id', sudah termasuk Rp --}}
                                        <span
                                            class="badge {{ $gajiData['gaji_id'] ? 'bg-success' : 'bg-light text-dark' }}">{{ $gajiData['gaji_bersih_string'] }}</span>
                                    </td>
                                    <td class="text-center status-col">
                                        {{-- Cek 'gaji_id' (dari $gajiTersimpan->id di service) --}}
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
                            {{-- ================== PERBAIKAN BLADE SELESAI ================== --}}

                            <tr id="no-search-results" style="display: none;">
                                <td colspan="7" class="text-center fst-italic py-4">Karyawan tidak ditemukan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ============== MODALS (Struktur Asli Anda) ============== --}}
    {{-- Modal Detail --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Gaji Pegawai</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detail-content"></div>
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

    {{-- Modal Edit --}}
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
                                            {{ $tunjangan->nama_tunjangan }}

                                            ({{ 'Rp ' . number_format($tunjangan->nilai ?? $tunjangan->jumlah_tunjangan, 0, ',', '.') }}/hari)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <hr>
                        <div id="edit-form-content" class="row"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi Modal
            const detailModalEl = document.getElementById('detailModal');
            const editModalEl = document.getElementById('editModal');
            const detailModal = new bootstrap.Modal(detailModalEl);
            const editModal = new bootstrap.Modal(editModalEl);

            const editGajiForm = document.getElementById('editGajiForm');
            const responseMessageEl = document.getElementById('ajax-response-message');

            // --- FUNGSI-FUNGSI HELPER ---
            const formatRupiah = (angka) => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(angka || 0);

            function showResponseMessage(message, isSuccess = true) {
                responseMessageEl.textContent = message;
                responseMessageEl.className = isSuccess ? 'alert alert-success' : 'alert alert-danger';
                responseMessageEl.style.display = 'block';
                setTimeout(() => responseMessageEl.style.display = 'none', 5000);
            }

            // ================== PERBAIKAN JAVASCRIPT (updateTableRow) ==================
            function updateTableRow(newData) {
                // 'newData' adalah flat array dari SalaryService
                const row = document.querySelector(
                    `.karyawan-row[data-karyawan-id="${newData.karyawan_id}"]`);
                if (!row) return;

                row.setAttribute('data-gaji-json', JSON.stringify(newData));
                // Gunakan key _string untuk data tampilan
                row.querySelector('.gaji-pokok-col').textContent = newData.gaji_pokok_string;

                // 'tunj_jabatan' tidak ada di service, jadi tetap 0
                // row.querySelector('.tunj-jabatan-col').textContent = formatRupiah(newData.tunj_jabatan).replace('Rp', 'Rp ');
                row.querySelector('.tunj-jabatan-col').textContent = newData.tunj_jabatan_string;
                row.querySelector('.gaji-bersih-col').innerHTML =
                    `<span class="badge bg-success">${newData.gaji_bersih_string}</span>`;
                row.querySelector('.status-col').innerHTML =
                    `<span class="badge bg-primary">Sudah Diproses</span>`;
            }

            // --- EVENT LISTENER UNTUK SUBMIT FORM EDIT (AJAX) ---
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
                            // 'data.newData' adalah flat array, sesuai dengan service
                            updateTableRow(data.newData);
                        } else {
                            // [REVISI] Menampilkan error validasi (jika ada)
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

            // --- EVENT LISTENER UNTUK TOMBOL-TOMBOL AKSI DI TABEL ---
            document.getElementById('gaji-table-body').addEventListener('click', function(e) {
                // [REVISI] Target 'btn-edit' atau 'btn-detail'
                const button = e.target.closest('.btn-detail, .btn-edit');
                if (!button) return;

                // Event 'show.bs.modal' akan menangani populasi data
            });

            // [REVISI] Gunakan event 'show.bs.modal' untuk 'editModal'
            editModalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget; // Tombol yang memicu modal
                if (!button) return;

                const row = button.closest('tr.karyawan-row');
                if (!row) return;

                const gajiData = JSON.parse(row.getAttribute('data-gaji-json'));
                populateEditModal(gajiData);
            });

            // [REVISI] Gunakan event 'show.bs.modal' untuk 'detailModal'
            detailModalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget; // Tombol yang memicu modal
                if (!button) return;

                const row = button.closest('tr.karyawan-row');
                if (!row) return;

                const gajiData = JSON.parse(row.getAttribute('data-gaji-json'));
                populateDetailModal(gajiData);
            });


            // --- FUNGSI PENCARIAN ---
            document.getElementById('search-input').addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('#gaji-table-body tr.karyawan-row');
                const noResultsRow = document.getElementById('no-search-results');
                let visibleRows = 0;

                rows.forEach(row => {
                    // Sesuaikan pencarian dengan data yang ada: 'nama' dan 'jabatan'
                    const nama = row.querySelector('.nama-karyawan').textContent.toLowerCase();
                    const nip = row.querySelector('.nip-karyawan').textContent
                        .toLowerCase(); // Ini sekarang isinya 'Jabatan'
                    if (nama.includes(searchTerm) || nip.includes(searchTerm)) {
                        row.style.display = '';
                        visibleRows++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                noResultsRow.style.display = (visibleRows === 0 && searchTerm) ? '' : 'none';
            });

            // ================== [REVISI UTAMA JAVASCRIPT] (populateEditModal) ==================
            function populateEditModal(data) {
                // 'data' adalah flat array
                const modal = editModalEl;
                modal.querySelector('#editModalLabel').textContent = `Kelola Gaji: ${data.nama}`;
                modal.querySelector('#periode-modal').value = new Date(data.bulan + '-02').toLocaleDateString(
                    'id-ID', {
                        month: 'long',
                        year: 'numeric'
                    });
                modal.querySelector('#edit-karyawan-id').value = data.karyawan_id;
                modal.querySelector('#tunjangan_kehadiran_id_modal').value = data.tunjangan_kehadiran_id;
                const formContent = modal.querySelector('#edit-form-content');

                // Siapkan data field dengan properti tambahan untuk kontrol
                const fields = [{
                    name: 'tunj_jabatan',
                    label: 'Tunjangan Jabatan',
                    value: data.tunj_jabatan,
                    readonly: true,
                    isNumeric: false
                }, {
                    name: 'gaji_pokok',
                    label: 'Gaji Pokok',
                    value: data.gaji_pokok_numeric,
                    readonly: false,
                    isNumeric: true
                }, {
                    // --- REVISI 2: Tunjangan Anak (Otomatis) ---
                    name: 'tunj_anak',
                    label: 'Tunjangan Anak (Otomatis)', // Label diubah
                    value: data.tunj_anak, // Nilai diambil dari data kalkulasi
                    readonly: true, // Dibuat Readonly
                    isNumeric: true
                }, {
                    // --- REVISI 3: Tunjangan Pengabdian (Otomatis) ---
                    name: 'tunj_pengabdian',
                    label: 'Tunj. Pengabdian (Otomatis)', // Label diubah
                    value: data.tunj_pengabdian, // Nilai diambil dari data kalkulasi
                    readonly: true, // Dibuat Readonly
                    isNumeric: true
                }, {
                    // --- REVISI 1: Tunjangan Komunikasi (Dihapus) ---
                    // Objek 'tunj_komunikasi' dihapus dari array ini
                }, {
                    name: 'tunj_kinerja',
                    label: 'Tunj. Kinerja',
                    value: data.tunj_kinerja,
                    readonly: false,
                    isNumeric: true
                }, {
                    name: 'lembur',
                    label: 'Lembur',
                    value: data.lembur,
                    readonly: false,
                    isNumeric: true
                }, {
                    name: 'potongan',
                    label: 'Potongan',
                    value: data.potongan,
                    readonly: false,
                    isNumeric: true
                }];

                // Map field menjadi HTML, menggunakan input-group untuk prefix "Rp"
                let fieldsHtml = fields.map(f => {
                    // Filter objek kosong (dari tunj_komunikasi yang dihapus)
                    if (!f.name) return '';

                    const inputType = f.isNumeric ? 'number' : 'text';
                    const readonlyAttr = f.readonly ? 'readonly' : '';

                    // [REVISI] Jika readonly, JANGAN kirim 'name'
                    const inputName = f.readonly ? '' : `name="${f.name}"`;
                    const requiredAttr = f.readonly ? '' : 'required';

                    const displayValue = f.readonly ? formatRupiah(f.value).replace('Rp', '').trim() :
                        parseFloat(f.value || 0);

                    return `
                    <div class="col-md-6 mb-3">
                        <label class="form-label">${f.label} ${f.readonly ? '' : ''}</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="${inputType}" ${inputName} class="form-control" value="${displayValue}" ${readonlyAttr} ${requiredAttr}>
                        </div>
                    </div>`;
                }).join('');

                formContent.innerHTML = fieldsHtml;
            }

            // ================== PERBAIKAN JAVASCRIPT (populateDetailModal) ==================
            // (Tidak ada perubahan, kode asli Anda sudah benar)
            function populateDetailModal(data) {
                // 'data' adalah flat array
                const modal = detailModalEl;
                modal.querySelector('#detailModalLabel').textContent = `Detail Gaji: ${data.nama}`;
                const detailContent = modal.querySelector('#detail-content');
                const rincianHtml = (items) => items.map(item =>
                    `<div class="row mb-2"><div class="col-7">${item.label}</div><div class="col-5 text-end">${item.value}</div></div>`
                ).join('');

                const pendapatanItems = [{
                    // Sudah menggunakan formatRupiah
                    label: 'Gaji Pokok',
                    value: formatRupiah(data.gaji_pokok_numeric)
                }, {
                    // Sudah menggunakan formatRupiah
                    label: 'Tunjangan Jabatan',
                    value: formatRupiah(data.tunj_jabatan)
                }, {
                    // Sudah menggunakan formatRupiah
                    label: 'Tunjangan Anak',
                    value: formatRupiah(data.tunj_anak)
                }, {
                    // Sudah menggunakan formatRupiah
                    label: 'Tunjangan Komunikasi',
                    value: formatRupiah(data.tunj_komunikasi)
                }, {
                    // Sudah menggunakan formatRupiah
                    label: 'Tunjangan Pengabdian',
                    value: formatRupiah(data.tunj_pengabdian)
                }, {
                    // Sudah menggunakan formatRupiah
                    label: 'Tunjangan Kinerja',
                    value: formatRupiah(data.tunj_kinerja)
                }, {
                    // Gunakan 'total_kehadiran' dan 'total_tunjangan_kehadiran_string' (Diasumsikan sudah ber-Rp)
                    label: `Tunj. Kehadiran (${data.total_kehadiran} hari)`,
                    value: data.total_tunjangan_kehadiran_string
                }, {
                    // Sudah menggunakan formatRupiah
                    label: 'Lembur',
                    value: formatRupiah(data.lembur)
                }, ];

                detailContent.innerHTML = `
                {{-- 'nip' tidak ada di service --}}
                <p><strong>Jabatan:</strong> ${data.jabatan}</p><hr>
                <div class="row">
                    <div class="col-lg-6 mb-4 mb-lg-0 border-end">
                        <h5 class="mb-3 text-primary">A. Pendapatan</h5>
                        ${rincianHtml(pendapatanItems)}
                    </div>
                    <div class="col-lg-6">
                        <h5 class="mb-3 text-danger">B. Potongan</h5>
                        ${rincianHtml([{ label: 'Potongan Lain-lain', value: `<span class="text-danger">(${formatRupiah(data.potongan)})</span>` }])}
                    </div>
                </div>
                <hr class="my-4">
                <div class="bg-light p-3 rounded">
                    <div class="row align-items-center">
                        <div class="col-7"><h5 class="mb-0">GAJI BERSIH (A - B)</h5></div>
                        {{-- Gunakan 'gaji_bersih_string' (Diasumsikan sudah ber-Rp) --}}
                        <div class="col-5 text-end"><h5 class="mb-0 fw-bold text-success">${data.gaji_bersih_string}</h5></div>
                    </div>
                </div>
            `;

                const downloadBtn = modal.querySelector('.btn-download-slip');
                const emailBtn = modal.querySelector('.btn-send-email');

                // --- PERBAIKAN Logika Tombol Slip/Email ---
                // Hapus event listener lama
                const newDownloadBtn = downloadBtn.cloneNode(true);
                downloadBtn.parentNode.replaceChild(newDownloadBtn, downloadBtn);
                const newEmailBtn = emailBtn.cloneNode(true);
                emailBtn.parentNode.replaceChild(newEmailBtn, emailBtn);

                // Cek 'gaji_id'
                if (data.gaji_id) {
                    newDownloadBtn.disabled = false;

                    // Tombol kirim email akan aktif jika gaji sudah diproses DAN karyawan memiliki email.
                    // Asumsi: field 'email' ada di flat array 'data'
                    const hasEmail = data.email && data.email.trim() !== '';
                    newEmailBtn.disabled = !hasEmail;

                    // Bangun URL dari 'gaji_id'
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
        });
    </script>
@endpush
