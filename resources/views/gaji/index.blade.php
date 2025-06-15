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
                                        <button class="btn btn-sm btn-info btn-detail" title="Detail Gaji"
                                            data-bs-toggle="modal" data-bs-target="#detailModal"><i
                                                class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-warning btn-edit" title="Edit Gaji"
                                            data-bs-toggle="modal" data-bs-target="#editModal"><i
                                                class="fas fa-edit"></i></button>
                                        @if ($gaji->id)
                                            <a href="{{ route('gaji.cetak', $gaji->id) }}" target="_blank"
                                                class="btn btn-sm btn-danger" title="Cetak PDF"><i
                                                    class="fas fa-file-pdf"></i></a>
                                        @else
                                            <button class="btn btn-sm btn-secondary" disabled
                                                title="Simpan data terlebih dahulu untuk mencetak"><i
                                                    class="fas fa-file-pdf"></i></button>
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
        {{-- PERBAIKAN: Mengubah modal-lg menjadi modal-xl --}}
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Gaji Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detail-content"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        {{-- PERBAIKAN: Mengubah modal-lg menjadi modal-xl --}}
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

    {{-- JavaScript tidak berubah, karena hanya class modal yang diubah --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.getElementById('gaji-table-body');

            function formatRupiah(angka) {
                if (angka === null || isNaN(angka)) {
                    angka = 0;
                }
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(angka);
            }

            tableBody.addEventListener('click', function(event) {
                const targetButton = event.target.closest('button, a');
                if (!targetButton) return;
                const row = targetButton.closest('tr');
                if (!row) return;
                const gajiData = JSON.parse(row.getAttribute('data-gaji-json'));

                if (targetButton.classList.contains('btn-detail')) {
                    populateDetailModal(gajiData);
                }
                if (targetButton.classList.contains('btn-edit')) {
                    populateEditModal(gajiData);
                }
            });

            function populateDetailModal(data) {
                const detailContent = document.getElementById('detail-content');
                document.getElementById('detailModalLabel').textContent = `Detail Gaji: ${data.karyawan.nama}`;

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

                const rincian = [{
                        label: 'Gaji Pokok',
                        value: data.gaji_pokok
                    }, {
                        label: 'Tunjangan Jabatan',
                        value: data.tunj_jabatan
                    },
                    {
                        label: 'Tunjangan Kehadiran',
                        value: data.tunj_kehadiran,
                        highlight: true
                    }, {
                        label: 'Tunjangan Anak',
                        value: data.tunj_anak
                    },
                    {
                        label: 'Tunjangan Komunikasi',
                        value: data.tunj_komunikasi
                    }, {
                        label: 'Tunjangan Pengabdian',
                        value: data.tunj_pengabdian
                    },
                    {
                        label: 'Tunjangan Kinerja',
                        value: data.tunj_kinerja
                    }, {
                        label: 'Lembur',
                        value: data.lembur
                    },
                    {
                        label: 'Kelebihan Jam',
                        value: data.kelebihan_jam
                    },
                ];

                let rincianHtml = rincian.map(item =>
                    `<dt class="col-12 col-md-5 ${item.highlight ? 'text-primary' : ''}">${item.label}</dt><dd class="col-12 col-md-7 text-start text-md-end ${item.highlight ? 'text-primary' : ''}">${formatRupiah(item.value)}</dd>`
                    ).join('');
                let potonganHtml =
                    `<dt class="col-12 col-md-5">Potongan</dt><dd class="col-12 col-md-7 text-start text-md-end">(${formatRupiah(data.potongan || 0)})</dd>`;
                let totalHtml =
                    `<dt class="col-12 col-md-5 fs-5">GAJI BERSIH</dt><dd class="col-12 col-md-7 text-start text-md-end fs-5 fw-bold">${formatRupiah(data.gaji_bersih || 0)}</dd>`;

                detailContent.innerHTML = `
                    <div class="row mb-3"><div class="col-md-6"><p class="mb-0"><strong>Periode:</strong> ${data.bulan}</p></div><div class="col-md-6"><p class="mb-0"><strong>Jabatan:</strong> ${data.karyawan.jabatan}</p></div></div><hr>
                    <h5>Rincian Pendapatan</h5><dl class="row">${rincianHtml}</dl><hr>
                    <h5>Potongan</h5><dl class="row">${potonganHtml}</dl><hr>
                    <dl class="row bg-light p-2 rounded align-items-center">${totalHtml}</dl>
                    <div class="mt-4 border-top pt-2 text-center small">${updateInfo}</div>`;
            }

            function populateEditModal(data) {
                const formContent = document.getElementById('edit-form-content');
                document.getElementById('editModalLabel').textContent = `Edit Gaji: ${data.karyawan.nama}`;

                const fields = [{
                        name: 'gaji_pokok',
                        label: 'Gaji Pokok',
                        value: data.gaji_pokok
                    }, {
                        name: 'tunj_jabatan',
                        label: 'Tunjangan Jabatan',
                        value: data.tunj_jabatan
                    },
                    {
                        name: 'tunj_anak',
                        label: 'Tunjangan Anak',
                        value: data.tunj_anak
                    }, {
                        name: 'tunj_komunikasi',
                        label: 'Tunjangan Komunikasi',
                        value: data.tunj_komunikasi
                    },
                    {
                        name: 'tunj_pengabdian',
                        label: 'Tunjangan Pengabdian',
                        value: data.tunj_pengabdian
                    }, {
                        name: 'tunj_kinerja',
                        label: 'Tunjangan Kinerja',
                        value: data.tunj_kinerja
                    },
                    {
                        name: 'lembur',
                        label: 'Lembur',
                        value: data.lembur
                    }, {
                        name: 'kelebihan_jam',
                        label: 'Kelebihan Jam',
                        value: data.kelebihan_jam
                    },
                ];

                let fieldsHtml = fields.map(f =>
                    `<div class="col-md-6 mb-3"><label class="form-label">${f.label}</label><input type="number" name="${f.name}" class="form-control" value="${f.value || 0}"></div>`
                    ).join('');

                formContent.innerHTML =
                    `
                    <input type="hidden" name="karyawan_id" value="${data.karyawan.id}"><input type="hidden" name="bulan" value="${data.bulan}">
                    <div class="alert alert-info"><p class="mb-1"><strong>Periode: ${data.bulan}</strong></p><p class="mb-1">Jumlah Kehadiran: <strong>${data.jumlah_kehadiran} hari</strong></p><p class="mb-0">Tunjangan Kehadiran (Otomatis): <strong>${formatRupiah(data.tunj_kehadiran || 0)}</strong></p></div>
                    <div class="row">${fieldsHtml}<div class="col-md-6 mb-3"><label class="form-label">Potongan</label><input type="number" name="potongan" class="form-control" value="${data.potongan || 0}"></div></div>`;
            }
        });
    </script>
@endsection
