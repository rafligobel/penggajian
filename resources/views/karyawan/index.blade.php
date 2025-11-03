@extends('layouts.app')

@section('content')
    {{-- Gaya kustom untuk tampilan tabel yang lebih modern dan minimalis --}}
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

    <div class="container py-1">
        {{-- Header Halaman --}}
        <div class="d-flex justify-content-between align-items-center mb-1">
            <h3 class="fw-bold text-primary">Daftar Pegawai</h3>
            {{-- Tombol Tambah Pegawai --}}
            @if (in_array(auth()->user()->role, ['superadmin', 'admin']))
                <a href="{{ route('karyawan.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Tambah Pegawai Baru
                </a>
            @endif
        </div>

        {{-- Fitur Pencarian --}}
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
                    <input type="text" id="search-input" class="form-control border-start-0"
                        placeholder="Cari berdasarkan Nama atau NP Pegawai...">
                </div>
            </div>
        </div>

        {{-- Notifikasi Sukses --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Tabel Data Pegawai --}}
        <div class="table-responsive">
            <table class="table table-borderless table-minimalis">
                <thead class="text-center">
                    <tr>
                        <th>No.</th>
                        <th class="text-start">Nama</th>
                        <th>NP</th>
                        <th class="text-start">Email</th>
                        <th class="text-start">Jabatan</th>
                        {{-- <th>Status</th> --}}
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="karyawan-table-body">
                    {{-- Data akan di-render oleh JavaScript --}}
                </tbody>
            </table>
            <p id="no-data-message" class="text-center text-muted mt-4" style="display:none;">Pegawai tidak ditemukan.</p>
        </div>
    </div>

    {{-- Modal untuk Detail Pegawai --}}
    <div class="modal fade" id="detailKaryawanModal" tabindex="-1" aria-labelledby="detailKaryawanModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl"> {{-- Menggunakan modal besar untuk menampung banyak info --}}
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailKaryawanModalLabel">Detail Pegawai</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="detailKaryawanModalBody">
                    <p class="text-center text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Memuat data...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus (Reusable Component) --}}
    <x-delete-confirmation-modal title="Konfirmasi Hapus Pegawai"
        body="Apakah Anda yakin ingin menghapus data karyawan ini secara permanen? Tindakan ini tidak dapat dibatalkan." />


    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const allKaryawanData = @json($karyawans);
                const searchInput = document.getElementById('search-input');
                const tableBody = document.getElementById('karyawan-table-body');
                const noDataMessage = document.getElementById('no-data-message');
                const detailModalEl = document.getElementById('detailKaryawanModal');
                const userIsAdmin = {{ in_array(Auth::user()->role, ['superadmin', 'admin']) ? 'true' : 'false' }};
                const userIsBendahara = {{ Auth::user()->role === 'bendahara' ? 'true' : 'false' }};


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

                        // === PERBAIKAN 1: Ambil nama jabatan dari objek relasi ===
                        const namaJabatan = karyawan.jabatan ? karyawan.jabatan.nama_jabatan :
                            '<span class="text-muted">Tidak ada jabatan</span>';

                        let actionButtons = `
                            <button type="button" class="btn btn-sm btn-info btn-detail" title="Lihat Detail"
                                    data-bs-toggle="modal" data-bs-target="#detailKaryawanModal">
                                <i class="fas fa-eye"></i>
                            </button>`;

                        if (userIsAdmin) {
                            actionButtons +=
                                `
                                <a href="/karyawan/${karyawan.id}/edit" class="btn btn-sm btn-warning" title="Ubah"><i class="fas fa-edit"></i></a>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                    data-url="/karyawan/${karyawan.id}" title="Hapus"><i class="fas fa-trash"></i></button>`;
                        }

                        row.innerHTML = `
                            <td class="text-center">${index + 1}</td>
                            <td class="text-start fw-bold">${karyawan.nama}</td>
                            <td class="text-center">${karyawan.nip}</td>
                            <td class="text-start">${karyawan.email || '-'}</td>
                            <td class="text-start">${namaJabatan}</td>
                            <td class="text-center">${actionButtons}</td>`;
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

                        // === PERBAIKAN 2: Ambil juga nama jabatan untuk modal detail ===
                        const namaJabatanModal = karyawan.jabatan ? karyawan.jabatan.nama_jabatan :
                            '<span class="text-muted">Tidak ada jabatan</span>';

                        const formatDate = (dateString) => new Date(dateString).toLocaleDateString('id-ID', {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        modalBody.innerHTML = `
                            <div class="row">
                                <div class="col-lg-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">Nama Lengkap</dt><dd class="col-sm-8">: ${karyawan.nama}</dd>
                                        <dt class="col-sm-4">NIP</dt><dd class="col-sm-8">: ${karyawan.nip}</dd>
                                        <dt class="col-sm-4">Email</dt><dd class="col-sm-8">: ${karyawan.email || 'Tidak ada'}</dd>
                                        <dt class="col-sm-4">Jabatan</dt><dd class="col-sm-8">: ${namaJabatanModal}</dd>
                                    </dl>
                                </div>
                                <div class="col-lg-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">Alamat</dt><dd class="col-sm-8">: ${karyawan.alamat}</dd>
                                        <dt class="col-sm-4">Telepon</dt><dd class="col-sm-8">: ${karyawan.telepon}</dd>
                                        <dt class="col-sm-4">Tgl. Bergabung</dt><dd class="col-sm-8">: ${formatDate(karyawan.created_at)}</dd>
                                        <dt class="col-sm-4">Diperbarui</dt><dd class="col-sm-8">: ${formatDate(karyawan.updated_at)}</dd>
                                    </dl>
                                </div>
                            </div>`;
                    });
                }

                // ... (sisa event listener Anda) ...

                searchInput.addEventListener('input', filterAndRender);
                renderTable(allKaryawanData); // Initial render
            });
        </script>
    @endpush
@endsection
