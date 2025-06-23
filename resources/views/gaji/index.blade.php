@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4 fw-bold text-primary">Kelola Gaji Karyawan</h3>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <form method="GET" action="{{ route('gaji.index') }}">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="bulan" class="form-label fw-bold">Pilih Periode Gaji</label>
                            <input type="month" class="form-control" id="bulan" name="bulan"
                                value="{{ $selectedMonth }}">
                        </div>
                        <div class="col-md-4">
                            <label for="tarif_kehadiran" class="form-label fw-bold">Tarif Tunjangan Kehadiran / Hari</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="tarif_kehadiran" name="tarif_kehadiran"
                                    value="{{ $tarifKehadiran }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Tampilkan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div id="ajax-response-message" class="alert" style="display:none;"></div>


        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>Nama Karyawan</th>
                                <th class="text-center">Jml. Hadir</th>
                                <th class="text-end">Tunj. Kehadiran</th>
                                <th class="text-end">Total Gaji</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="gaji-table-body">
                            @forelse ($dataGaji as $gaji)
                                <tr data-gaji-json="{{ json_encode($gaji) }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $gaji->karyawan->nama }}</strong><br>
                                        <small class="text-muted">NIP: {{ $gaji->karyawan->nip }}</small>
                                    </td>
                                    <td class="text-center">{{ $gaji->jumlah_kehadiran }}</td>
                                    <td class="text-end">Rp {{ number_format($gaji->tunj_kehadiran, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($gaji->gaji_bersih, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        @if (Auth::user()->role === 'bendahara')
                                            <button class="btn btn-sm btn-info btn-detail" title="Detail Gaji"
                                                data-bs-toggle="modal" data-bs-target="#detailModal"><i
                                                    class="fas fa-eye"></i></button>
                                            <button class="btn btn-sm btn-warning btn-edit" title="Edit Gaji"
                                                data-bs-toggle="modal" data-bs-target="#editModal"><i
                                                    class="fas fa-edit"></i></button>
                                        @else
                                            <span class="badge bg-secondary">Hanya Dilihat</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center fst-italic py-4">
                                        Tidak ada data karyawan untuk ditampilkan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ============== MODALS ============== --}}

    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Gaji Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detail-content">Memuat data...</div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-success btn-send-email" disabled>
                            <i class="fas fa-envelope"></i> Kirim ke Email
                        </button>
                        <button type="button" class="btn btn-danger btn-download-slip" disabled>
                            <i class="fas fa-file-pdf"></i> Cetak PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="editGajiForm" action="{{ route('gaji.save') }}" method="POST">
                    @csrf
                    <input type="hidden" name="tarif_kehadiran_hidden" id="tarif_kehadiran_hidden"
                        value="{{ $tarifKehadiran }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Gaji Karyawan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="edit-form-content"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const detailModalEl = document.getElementById('detailModal');
                const editModalEl = document.getElementById('editModal');
                const responseMessageEl = document.getElementById('ajax-response-message');

                const formatRupiah = (angka) => new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(angka || 0);

                function showResponseMessage(message, isSuccess = true) {
                    responseMessageEl.textContent = message;
                    responseMessageEl.className = isSuccess ? 'alert alert-info' : 'alert alert-danger';
                    responseMessageEl.style.display = 'block';
                    setTimeout(() => {
                        responseMessageEl.style.display = 'none';
                    }, 5000);
                }

                function handleAjaxRequest(button) {
                    const url = button.dataset.url;
                    const originalHtml = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json().then(data => ({
                            status: response.status,
                            body: data
                        })))
                        .then(({
                            status,
                            body
                        }) => {
                            showResponseMessage(body.message, status === 200);
                            if (status === 200) {
                                const modalInstance = bootstrap.Modal.getInstance(detailModalEl);
                                modalInstance.hide();
                            }
                        }).catch(error => {
                            console.error('Error:', error);
                            showResponseMessage('Terjadi kesalahan. Silakan cek konsol browser.', false);
                        }).finally(() => {
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        });
                }

                detailModalEl.addEventListener('click', function(event) {
                    const downloadBtn = event.target.closest('.btn-download-slip');
                    if (downloadBtn) handleAjaxRequest(downloadBtn);

                    const emailBtn = event.target.closest('.btn-send-email');
                    if (emailBtn) handleAjaxRequest(emailBtn);
                });

                function populateDetailModal(data, modal) {
                    const detailContent = modal.querySelector('#detail-content');
                    modal.querySelector('#detailModalLabel').textContent = `Detail Gaji: ${data.karyawan.nama}`;

                    let updateInfo =
                        '<p class="text-muted fst-italic mb-0">Data gaji bulan ini belum pernah disimpan.</p>';
                    if (data.updated_at) {
                        const updatedAt = new Date(data.updated_at);
                        const formattedDate = updatedAt.toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: 'long',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        updateInfo = `<p class="text-muted mb-0">Terakhir diperbarui: ${formattedDate} WITA</p>`;
                    }

                    const createRincianHtml = (items) => items.map(item =>
                        `<div class="row mb-2"><div class="col-7">${item.label}</div><div class="col-5 text-end">${formatRupiah(item.value)}</div></div>`
                    ).join('');
                    const pendapatanTetap = [{
                        label: 'Gaji Pokok',
                        value: data.gaji_pokok
                    }, {
                        label: 'Tunjangan Jabatan',
                        value: data.tunj_jabatan
                    }, {
                        label: 'Tunjangan Anak',
                        value: data.tunj_anak
                    }, {
                        label: 'Tunjangan Komunikasi',
                        value: data.tunj_komunikasi
                    }, {
                        label: 'Tunjangan Pengabdian',
                        value: data.tunj_pengabdian
                    }, {
                        label: 'Tunjangan Kinerja',
                        value: data.tunj_kinerja
                    }, ];
                    const pendapatanTidakTetap = [{
                        label: `Tunjangan Kehadiran (${data.jumlah_kehadiran} hari)`,
                        value: data.tunj_kehadiran
                    }, {
                        label: 'Lembur',
                        value: data.lembur
                    }, {
                        label: 'Kelebihan Jam',
                        value: data.kelebihan_jam
                    }, ];

                    detailContent.innerHTML = `
                    <div class="row"><div class="col-md-6"><p class="mb-1"><strong>Periode:</strong> ${new Date(data.bulan + '-01').toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })}</p><p><strong>Jabatan:</strong> ${data.karyawan.jabatan}</p></div></div><hr>
                    <div class="row">
                        <div class="col-lg-6 mb-4 mb-lg-0"><h5 class="mb-3">A. Pendapatan Tetap</h5>${createRincianHtml(pendapatanTetap)}</div>
                        <div class="col-lg-6"><h5 class="mb-3">B. Pendapatan Tidak Tetap</h5>${createRincianHtml(pendapatanTidakTetap)}<hr><h5 class="mb-3">C. Potongan</h5><div class="row mb-2"><div class="col-7">Potongan Lain-lain</div><div class="col-5 text-end text-danger">(${formatRupiah(data.potongan)})</div></div></div>
                    </div><hr class="my-4">
                    <div class="bg-light p-3 rounded"><div class="row align-items-center"><div class="col-7"><h5 class="mb-0">GAJI BERSIH</h5></div><div class="col-5 text-end"><h5 class="mb-0 fw-bold">${formatRupiah(data.gaji_bersih)}</h5></div></div></div>
                    <div class="mt-4 border-top pt-2 text-center small">${updateInfo}</div>`;

                    const downloadBtn = modal.querySelector('.btn-download-slip');
                    const emailBtn = modal.querySelector('.btn-send-email');

                    if (data.id) {
                        downloadBtn.disabled = false;
                        downloadBtn.dataset.url = `/gaji/${data.id}/download`;
                        downloadBtn.removeAttribute('title');

                        if (data.karyawan.email) {
                            emailBtn.disabled = false;
                            emailBtn.dataset.url = `/gaji/${data.id}/send-email`;
                            emailBtn.removeAttribute('title');
                        } else {
                            emailBtn.disabled = true;
                            emailBtn.setAttribute('title', 'Karyawan tidak memiliki email.');
                        }
                    } else {
                        downloadBtn.disabled = true;
                        emailBtn.disabled = true;
                        downloadBtn.setAttribute('title', 'Simpan data terlebih dahulu.');
                        emailBtn.setAttribute('title', 'Simpan data terlebih dahulu.');
                    }
                }

                function populateEditModal(data, modal) {
                    const formContent = modal.querySelector('#edit-form-content');
                    modal.querySelector('#editModalLabel').textContent = `Edit Gaji: ${data.karyawan.nama}`;

                    const fields = [{
                        name: 'gaji_pokok',
                        label: 'Gaji Pokok',
                        value: data.gaji_pokok
                    }, {
                        name: 'tunj_jabatan',
                        label: 'Tunjangan Jabatan',
                        value: data.tunj_jabatan
                    }, {
                        name: 'tunj_anak',
                        label: 'Tunjangan Anak',
                        value: data.tunj_anak
                    }, {
                        name: 'tunj_komunikasi',
                        label: 'Tunjangan Komunikasi',
                        value: data.tunj_komunikasi
                    }, {
                        name: 'tunj_pengabdian',
                        label: 'Tunjangan Pengabdian',
                        value: data.tunj_pengabdian
                    }, {
                        name: 'tunj_kinerja',
                        label: 'Tunjangan Kinerja',
                        value: data.tunj_kinerja
                    }, {
                        name: 'lembur',
                        label: 'Lembur',
                        value: data.lembur
                    }, {
                        name: 'kelebihan_jam',
                        label: 'Kelebihan Jam',
                        value: data.kelebihan_jam
                    }, ];
                    let fieldsHtml = fields.map(f =>
                        `<div class="col-md-6 mb-3"><label class="form-label">${f.label}</label><input type="number" name="${f.name}" class="form-control" value="${f.value || 0}"></div>`
                    ).join('');

                    formContent.innerHTML =
                        `<input type="hidden" name="karyawan_id" value="${data.karyawan.id}"><input type="hidden" name="bulan" value="${data.bulan}"><div class="alert alert-info"><p class="mb-1"><strong>Periode: ${data.bulan}</strong></p><p class="mb-1">Jumlah Kehadiran: <strong>${data.jumlah_kehadiran} hari</strong></p><p class="mb-0">Tunjangan Kehadiran (Otomatis): <strong>${formatRupiah(data.tunj_kehadiran || 0)}</strong></p></div><div class="row">${fieldsHtml}<div class="col-md-6 mb-3"><label class="form-label">Potongan</label><input type="number" name="potongan" class="form-control" value="${data.potongan || 0}"></div></div>`;
                }

                document.querySelectorAll('.btn-detail').forEach(button => {
                    button.addEventListener('click', function() {
                        const row = this.closest('tr');
                        const gajiData = JSON.parse(row.getAttribute('data-gaji-json'));
                        populateDetailModal(gajiData, detailModalEl);
                    });
                });

                document.querySelectorAll('.btn-edit').forEach(button => {
                    button.addEventListener('click', function() {
                        const row = this.closest('tr');
                        const gajiData = JSON.parse(row.getAttribute('data-gaji-json'));
                        populateEditModal(gajiData, editModalEl);
                    });
                });
            });
        </script>
    @endpush
@endsection
