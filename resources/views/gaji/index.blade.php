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
                            {{-- ================== PERBAIKAN BLADE DIMULAI ================== --}}
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
                                        {{-- Ambil 'gaji_pokok_string' dari service --}}
                                        {{ $gajiData['gaji_pokok_string'] }}
                                    </td>
                                    <td class="text-end tunj-jabatan-col">
                                        {{-- 'tunj_jabatan' tidak ada di SalaryService. 
                                             Ini harus ditambahkan di SalaryService agar muncul di sini.
                                             Untuk sementara, kita tampilkan Rp 0 --}}
                                        Rp 0
                                        {{-- TODO: Tambahkan 'tunj_jabatan' ke array return di SalaryService.php --}}
                                    </td>
                                    <td class="text-end fw-bold gaji-bersih-col">
                                        {{-- Ambil 'gaji_bersih_string' dan cek 'gaji_id' --}}
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
                                            <button class="btn btn-sm btn-info btn-detail" title="Detail Gaji"><i
                                                    class="fas fa-eye"></i></button>
                                            <button class="btn btn-sm btn-warning btn-edit" title="Kelola Gaji"><i
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

    {{-- ============== MODALS (Tidak Berubah) ============== --}}
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
                                    Kehadiran</label>
                                <select name="tunjangan_kehadiran_id" id="tunjangan_kehadiran_id_modal"
                                    class="form-select" required>
                                    @foreach ($tunjanganKehadirans as $tunjangan)
                                        <option value="{{ $tunjangan->id }}">
                                            {{-- Perbaikan kecil: Ambil nama_tunjangan, bukan jenis_tunjangan --}}
                                            {{ $tunjangan->nama_tunjangan }}
                                            ({{ 'Rp ' . number_format($tunjangan->jumlah_tunjangan, 0, ',', '.') }}/hari)
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
                            showResponseMessage(data.message || 'Gagal menyimpan data.', false);
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
                const button = e.target.closest('.btn-detail, .btn-edit');
                if (!button) return;

                const row = button.closest('tr.karyawan-row');
                // 'gajiData' adalah flat array
                const gajiData = JSON.parse(row.getAttribute('data-gaji-json'));

                if (button.classList.contains('btn-detail')) {
                    populateDetailModal(gajiData);
                    detailModal.show();
                } else if (button.classList.contains('btn-edit')) {
                    populateEditModal(gajiData);
                    editModal.show();
                }
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

            // ================== PERBAIKAN JAVASCRIPT (populateEditModal) ==================
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

                // Sesuaikan nama field dengan key di flat array
                const fields = [{
                    // 'gaji_pokok_numeric' dari service
                    name: 'gaji_pokok',
                    label: 'Gaji Pokok',
                    value: data.gaji_pokok_numeric
                }, {
                    name: 'tunj_anak',
                    label: 'Tunjangan Anak',
                    value: data.tunj_anak
                }, {
                    name: 'tunj_komunikasi',
                    label: 'Tunj. Komunikasi',
                    value: data.tunj_komunikasi
                }, {
                    name: 'tunj_pengabdian',
                    label: 'Tunj. Pengabdian',
                    value: data.tunj_pengabdian
                }, {
                    name: 'tunj_kinerja',
                    label: 'Tunj. Kinerja',
                    value: data.tunj_kinerja
                }, {
                    name: 'lembur',
                    label: 'Lembur',
                    value: data.lembur
                }, {
                    name: 'potongan',
                    label: 'Potongan',
                    value: data.potongan
                }];

                // 'tunj_jabatan' tidak ada di service
                let fieldsHtml =
                    `<div class="col-md-6 mb-3"><label class="form-label">Tunjangan Jabatan (Otomatis)</label><input type="text" class="form-control" value="${formatRupiah(0)}" readonly></div>`;

                fieldsHtml += fields.map(f =>
                    // Gunakan f.value untuk mengisi nilai
                    `<div class="col-md-6 mb-3"><label class="form-label">${f.label}</label><input type="number" name="${f.name}" class="form-control" value="${parseFloat(f.value || 0)}" required></div>`
                ).join('');
                formContent.innerHTML = fieldsHtml;
            }

            // ================== PERBAIKAN JAVASCRIPT (populateDetailModal) ==================
            function populateDetailModal(data) {
                // 'data' adalah flat array
                const modal = detailModalEl;
                modal.querySelector('#detailModalLabel').textContent = `Detail Gaji: ${data.nama}`;
                const detailContent = modal.querySelector('#detail-content');
                const rincianHtml = (items) => items.map(item =>
                    `<div class="row mb-2"><div class="col-7">${item.label}</div><div class="col-5 text-end">${item.value}</div></div>`
                ).join('');

                const pendapatanItems = [{
                    label: 'Gaji Pokok',
                    value: formatRupiah(data.gaji_pokok_numeric)
                }, {
                    label: 'Tunjangan Jabatan',
                    value: formatRupiah(0) // Tidak ada di service
                }, {
                    label: 'Tunjangan Anak',
                    value: formatRupiah(data.tunj_anak)
                }, {
                    label: 'Tunjangan Komunikasi',
                    value: formatRupiah(data.tunj_komunikasi)
                }, {
                    label: 'Tunjangan Pengabdian',
                    value: formatRupiah(data.tunj_pengabdian)
                }, {
                    label: 'Tunjangan Kinerja',
                    value: formatRupiah(data.tunj_kinerja)
                }, {
                    // Gunakan 'total_kehadiran' dan 'total_tunjangan_kehadiran_string'
                    label: `Tunj. Kehadiran (${data.total_kehadiran} hari)`,
                    value: data.total_tunjangan_kehadiran_string
                }, {
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
                        {{-- Gunakan 'gaji_bersih_string' --}}
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
                    // 'karyawan.email' tidak ada di service. 
                    // Tombol email akan selalu nonaktif kecuali Anda menambahkannya ke service.
                    newEmailBtn.disabled = true; // Set 'true' karena data.email tidak ada

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
