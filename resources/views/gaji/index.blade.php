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
                            <label for="search-input" class="form-label fw-bold">Cari Karyawan</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
                                <input type="text" id="search-input" class="form-control border-start-0"
                                    placeholder="Ketik nama atau NIP karyawan...">
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
                                <th>Nama Karyawan</th>
                                <th class="text-end">Gaji Pokok</th>
                                <th class="text-end">Tunj. Jabatan</th>
                                <th class="text-end">Gaji Bersih</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="gaji-table-body">
                            @forelse ($dataGaji as $gajiData)
                                @php
                                    $karyawan = $gajiData['karyawan'];
                                    $gaji = $gajiData['gaji'];
                                @endphp
                                <tr data-gaji-json="{{ json_encode($gajiData) }}" class="karyawan-row"
                                    data-karyawan-id="{{ $karyawan->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong class="nama-karyawan">{{ $karyawan->nama }}</strong><br>
                                        <small class="text-muted nip-karyawan">NIP: {{ $karyawan->nip }}</small>
                                    </td>
                                    <td class="text-end gaji-pokok-col">
                                        {{ 'Rp ' . number_format($gajiData['gaji_pokok'], 0, ',', '.') }}</td>
                                    <td class="text-end tunj-jabatan-col">
                                        {{ 'Rp ' . number_format($gajiData['tunj_jabatan'], 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold gaji-bersih-col">
                                        <span
                                            class="badge {{ $gaji ? 'bg-success' : 'bg-light text-dark' }}">{{ 'Rp ' . number_format($gajiData['gaji_bersih'], 0, ',', '.') }}</span>
                                    </td>
                                    <td class="text-center status-col">
                                        @if ($gaji)
                                            <span class="badge bg-primary">Sudah Diproses</span>
                                        @else
                                            <span class="badge bg-secondary">Template</span>
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
                                    <td colspan="7" class="text-center fst-italic py-4">Tidak ada data karyawan yang
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

    {{-- ============== MODALS ============== --}}
    {{-- Modal Detail --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Gaji Karyawan</h5>
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
                        <h5 class="modal-title" id="editModalLabel">Kelola Gaji Karyawan</h5>
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
                                            {{ $tunjangan->jenis_tunjangan }}
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
            const detailModalEl = document.getElementById('detailModal');
            const editModalEl = document.getElementById('editModal');
            const editGajiForm = document.getElementById('editGajiForm');
            const responseMessageEl = document.getElementById('ajax-response-message');

            const detailModal = new bootstrap.Modal(detailModalEl);
            const editModal = new bootstrap.Modal(editModalEl);

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

            function updateTableRow(newData) {
                const row = document.querySelector(`.karyawan-row[data-karyawan-id="${newData.karyawan.id}"]`);
                if (!row) return;

                row.setAttribute('data-gaji-json', JSON.stringify(newData));
                row.querySelector('.gaji-pokok-col').textContent = formatRupiah(newData.gaji_pokok).replace('Rp',
                    'Rp ');
                row.querySelector('.tunj-jabatan-col').textContent = formatRupiah(newData.tunj_jabatan).replace(
                    'Rp', 'Rp ');
                row.querySelector('.gaji-bersih-col').innerHTML =
                    `<span class="badge bg-success">${formatRupiah(newData.gaji_bersih).replace('Rp', 'Rp ')}</span>`;
                row.querySelector('.status-col').innerHTML = `<span class="badge bg-primary">Sudah Diproses</span>`;
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
                    const nama = row.querySelector('.nama-karyawan').textContent.toLowerCase();
                    const nip = row.querySelector('.nip-karyawan').textContent.toLowerCase();
                    if (nama.includes(searchTerm) || nip.includes(searchTerm)) {
                        row.style.display = '';
                        visibleRows++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                noResultsRow.style.display = (visibleRows === 0 && searchTerm) ? '' : 'none';
            });

            // --- FUNGSI UNTUK MENGISI MODAL ---
            function populateEditModal(data) {
                const modal = editModalEl;
                modal.querySelector('#editModalLabel').textContent = `Kelola Gaji: ${data.karyawan.nama}`;
                modal.querySelector('#periode-modal').value = new Date(data.bulan + '-02').toLocaleDateString(
                    'id-ID', {
                        month: 'long',
                        year: 'numeric'
                    });
                modal.querySelector('#edit-karyawan-id').value = data.karyawan.id;

                // KUNCI PERBAIKAN: Set value dropdown sesuai data
                modal.querySelector('#tunjangan_kehadiran_id_modal').value = data.tunjangan_kehadiran_id;

                const formContent = modal.querySelector('#edit-form-content');
                const fields = [{
                        name: 'gaji_pokok',
                        label: 'Gaji Pokok'
                    }, {
                        name: 'tunj_anak',
                        label: 'Tunjangan Anak'
                    },
                    {
                        name: 'tunj_komunikasi',
                        label: 'Tunj. Komunikasi'
                    }, {
                        name: 'tunj_pengabdian',
                        label: 'Tunj. Pengabdian'
                    },
                    {
                        name: 'tunj_kinerja',
                        label: 'Tunj. Kinerja'
                    }, {
                        name: 'lembur',
                        label: 'Lembur'
                    },
                    {
                        name: 'kelebihan_jam',
                        label: 'Kelebihan Jam'
                    }, {
                        name: 'potongan',
                        label: 'Potongan'
                    }
                ];

                let fieldsHtml =
                    `<div class="col-md-6 mb-3"><label class="form-label">Tunjangan Jabatan (Otomatis)</label><input type="text" class="form-control" value="${formatRupiah(data.tunj_jabatan || 0)}" readonly></div>`;
                fieldsHtml += fields.map(f =>
                    `<div class="col-md-6 mb-3"><label class="form-label">${f.label}</label><input type="number" name="${f.name}" class="form-control" value="${parseFloat(data[f.name] || 0)}" required></div>`
                ).join('');
                formContent.innerHTML = fieldsHtml;
            }

            function populateDetailModal(data) {
                const modal = detailModalEl;
                modal.querySelector('#detailModalLabel').textContent = `Detail Gaji: ${data.karyawan.nama}`;
                const detailContent = modal.querySelector('#detail-content');

                const rincianHtml = (items) => items.map(item =>
                    `<div class="row mb-2"><div class="col-7">${item.label}</div><div class="col-5 text-end">${item.value}</div></div>`
                ).join('');

                const pendapatanItems = [{
                        label: 'Gaji Pokok',
                        value: formatRupiah(data.gaji_pokok)
                    },
                    {
                        label: 'Tunjangan Jabatan',
                        value: formatRupiah(data.tunj_jabatan)
                    },
                    {
                        label: 'Tunjangan Anak',
                        value: formatRupiah(data.tunj_anak)
                    },
                    {
                        label: 'Tunjangan Komunikasi',
                        value: formatRupiah(data.tunj_komunikasi)
                    },
                    {
                        label: 'Tunjangan Pengabdian',
                        value: formatRupiah(data.tunj_pengabdian)
                    },
                    {
                        label: 'Tunjangan Kinerja',
                        value: formatRupiah(data.tunj_kinerja)
                    },
                    {
                        label: `Tunj. Kehadiran (${data.jumlah_kehadiran} hari)`,
                        value: formatRupiah(data.tunj_kehadiran)
                    },
                    {
                        label: 'Lembur',
                        value: formatRupiah(data.lembur)
                    },
                    {
                        label: 'Kelebihan Jam',
                        value: formatRupiah(data.kelebihan_jam)
                    }
                ];

                detailContent.innerHTML = `
            <p><strong>NIP:</strong> ${data.karyawan.nip}</p><hr>
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
                    <div class="col-5 text-end"><h5 class="mb-0 fw-bold text-success">${formatRupiah(data.gaji_bersih)}</h5></div>
                </div>
            </div>
        `;

                const downloadBtn = modal.querySelector('.btn-download-slip');
                const emailBtn = modal.querySelector('.btn-send-email');
                if (data.gaji) {
                    downloadBtn.disabled = false;
                    downloadBtn.dataset.url = `/gaji/${data.gaji.id}/download`;
                    emailBtn.disabled = !data.karyawan.email;
                    emailBtn.dataset.url = `/gaji/${data.gaji.id}/send-email`;
                } else {
                    downloadBtn.disabled = true;
                    emailBtn.disabled = true;
                }
            }
        });
    </script>
@endpush
