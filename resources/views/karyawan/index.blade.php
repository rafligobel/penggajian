@extends('layouts.app')

@section('content')
    <style>
        .table-minimalis {
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .table-minimalis th {
            border: none;
            font-weight: 600;
            color: #6c757d;
            padding: 0.75rem 1.25rem;
        }

        .summary-row {
            background-color: #fff;
            transition: all 0.2s ease-in-out;
            border-radius: 8px !important;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .summary-row:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .summary-row td {
            vertical-align: middle;
            border: none;
            padding: 1rem 1.25rem;
        }
    </style>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-primary">Daftar Karyawan</h3>
            @if (Auth::check() && Auth::user()->role === 'admin')
                <a href="{{ route('karyawan.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Karyawan
                    Baru</a>
            @endif
        </div>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <div class="input-group">
                    <input type="text" id="search-input" class="form-control"
                        placeholder="Ketik untuk mencari berdasarkan Nama atau NIP...">
                    <span class="input-group-text bg-white border-start-0"><i class="fas fa-search"></i></span>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-borderless table-minimalis">
                <thead class="text-center">
                    <tr>
                        <th>No.</th>
                        <th class="text-start">Nama</th>
                        <th>NIP</th>
                        <th class="text-start">Jabatan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="karyawan-table-body"></tbody>
            </table>
            <p id="no-data-message" class="text-center text-muted mt-4" style="display:none;">Karyawan tidak ditemukan.</p>
        </div>
    </div>

    <div class="modal fade" id="detailKaryawanModal" tabindex="-1" aria-labelledby="detailKaryawanModalLabel"
        aria-hidden="true">
        {{-- PERBAIKAN: Mengubah modal-lg menjadi modal-xl --}}
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailKaryawanModalLabel">Detail Karyawan</h5><button type="button"
                        class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailKaryawanModalBody">
                    <p class="text-center">Memuat data...</p>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Tutup</button></div>
            </div>
        </div>
    </div>

    <x-delete-confirmation-modal title="Konfirmasi Hapus Karyawan"
        body="Apakah Anda yakin ingin menghapus data karyawan ini?" />

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const allKaryawanData = @json($karyawans);
                const searchInput = document.getElementById('search-input');
                const tableBody = document.getElementById('karyawan-table-body');
                const noDataMessage = document.getElementById('no-data-message');
                const detailModalEl = document.getElementById('detailKaryawanModal');
                const userIsAdmin = {{ Auth::check() && Auth::user()->role === 'admin' ? 'true' : 'false' }};

                function renderTable(dataToRender) {
                    tableBody.innerHTML = '';
                    noDataMessage.style.display = 'none';
                    if (!dataToRender || dataToRender.length === 0) {
                        noDataMessage.style.display = 'block';
                        return;
                    }
                    dataToRender.forEach((karyawan, index) => {
                        const row = document.createElement('tr');
                        row.className = 'summary-row';
                        row.dataset.karyawanJson = JSON.stringify(karyawan);
                        const statusBadge = karyawan.status_aktif ?
                            `<span class="badge bg-success">Aktif</span>` :
                            `<span class="badge bg-danger">Tidak Aktif</span>`;
                        let adminButtons = '';
                        if (userIsAdmin) {
                            adminButtons =
                                `
                        <a href="/karyawan/${karyawan.id}/edit" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                        <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                           data-url="/karyawan/${karyawan.id}" onclick="event.preventDefault();" title="Hapus"><i class="fas fa-trash"></i></a>`;
                        }
                        row.innerHTML = `
                    <td class="text-center">${index + 1}</td>
                    <td class="text-start">${karyawan.nama}</td><td class="text-center">${karyawan.nip}</td>
                    <td class="text-start">${karyawan.jabatan}</td><td class="text-center">${statusBadge}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-info btn-detail" title="Lihat Detail"
                                data-bs-toggle="modal" data-bs-target="#detailKaryawanModal"><i class="fas fa-eye"></i></button>
                        ${adminButtons}
                    </td>`;
                        tableBody.appendChild(row);
                    });
                }

                function filterAndRender() {
                    const searchTerm = searchInput.value.toLowerCase().trim();
                    const filteredData = allKaryawanData.filter(k =>
                        k.nama.toLowerCase().includes(searchTerm) || k.nip.toLowerCase().includes(searchTerm)
                    );
                    renderTable(filteredData);
                }

                if (detailModalEl) {
                    detailModalEl.addEventListener('show.bs.modal', function(event) {
                        const button = event.relatedTarget;
                        const row = button.closest('tr');
                        const karyawan = JSON.parse(row.dataset.karyawanJson);
                        const modalTitle = detailModalEl.querySelector('.modal-title');
                        const modalBody = detailModalEl.querySelector('#detailKaryawanModalBody');
                        modalTitle.textContent = 'Detail Karyawan: ' + karyawan.nama;
                        const statusBadge = karyawan.status_aktif ?
                            '<span class="badge bg-success">Aktif</span>' :
                            '<span class="badge bg-danger">Tidak Aktif</span>';
                        const joinedDate = new Date(karyawan.created_at).toLocaleDateString('id-ID', {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                        });
                        const updatedDate = new Date(karyawan.updated_at).toLocaleDateString('id-ID', {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                        });
                        modalBody.innerHTML = `
                    <div class="row">
                        <div class="col-lg-6">
                            <dl class="row">
                                <dt class="col-md-5">Nama Lengkap</dt><dd class="col-md-7">${karyawan.nama}</dd>
                                <dt class="col-md-5">NIP</dt><dd class="col-md-7">${karyawan.nip}</dd>
                                <dt class="col-md-5">Jabatan</dt><dd class="col-md-7">${karyawan.jabatan}</dd>
                                <dt class="col-md-5">Status Aktif</dt><dd class="col-md-7">${statusBadge}</dd>
                            </dl>
                        </div>
                        <div class="col-lg-6">
                            <dl class="row">
                                <dt class="col-md-5">Alamat</dt><dd class="col-md-7">${karyawan.alamat}</dd>
                                <dt class="col-md-5">Telepon</dt><dd class="col-md-7">${karyawan.telepon}</dd>
                                <dt class="col-md-5">Tgl. Bergabung</dt><dd class="col-md-7">${joinedDate}</dd>
                                <dt class="col-md-5">Terakhir Diperbarui</dt><dd class="col-md-7">${updatedDate}</dd>
                            </dl>
                        </div>
                    </div>`;
                    });
                }

                searchInput.addEventListener('input', filterAndRender);
                renderTable(allKaryawanData);
            });
        </script>
    @endpush
@endsection
