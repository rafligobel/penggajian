@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-primary mb-0">Kelola Jabatan</h3>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fas fa-plus me-1"></i> Tambah Jabatan
            </button>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>Nama Jabatan</th>
                                <th class="text-end">Tunjangan Jabatan (Rp)</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jabatans as $jabatan)
                                <tr data-jabatan-json="{{ json_encode($jabatan) }}">
                                    <td>{{ $loop->iteration + $jabatans->firstItem() - 1 }}</td>
                                    <td>{{ $jabatan->nama_jabatan }}</td>
                                    <td class="text-end">{{ number_format($jabatan->tunj_jabatan, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning btn-edit" title="Edit Jabatan"
                                            data-bs-toggle="modal" data-bs-target="#editModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-delete" title="Hapus Jabatan"
                                            data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center fst-italic py-4">
                                        Tidak ada data jabatan untuk ditampilkan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($jabatans->hasPages())
                    <div class="mt-3">
                        {{ $jabatans->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ============== MODALS ============== --}}

    {{-- Modal Tambah Jabatan --}}
    <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('jabatan.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createModalLabel">Tambah Jabatan Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nama_jabatan" class="form-label">Nama Jabatan</label>
                            <input type="text" class="form-control" id="nama_jabatan" name="nama_jabatan"
                                value="{{ old('nama_jabatan') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="tunj_jabatan" class="form-label">Tunjangan Jabatan (Rp)</label>
                            <input type="number" class="form-control" id="tunj_jabatan" name="tunj_jabatan"
                                value="{{ old('tunj_jabatan', 0) }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit Jabatan --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Jabatan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nama_jabatan" class="form-label">Nama Jabatan</label>
                            <input type="text" class="form-control" id="edit_nama_jabatan" name="nama_jabatan"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_tunj_jabatan" class="form-label">Tunjangan Jabatan (Rp)</label>
                            <input type="number" class="form-control" id="edit_tunj_jabatan" name="tunj_jabatan"
                                required>
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

    {{-- Modal Hapus Jabatan --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Apakah Anda yakin ingin menghapus jabatan <strong id="delete-jabatan-name"></strong>?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Script untuk Modal Edit
            const editModal = document.getElementById('editModal');
            document.querySelectorAll('.btn-edit').forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const data = JSON.parse(row.getAttribute('data-jabatan-json'));

                    const form = editModal.querySelector('#editForm');
                    form.action = `/jabatan/${data.id}`;
                    form.querySelector('#edit_nama_jabatan').value = data.nama_jabatan;
                    form.querySelector('#edit_tunj_jabatan').value = data.tunj_jabatan;
                });
            });

            // Script untuk Modal Hapus
            const deleteModal = document.getElementById('deleteModal');
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const data = JSON.parse(row.getAttribute('data-jabatan-json'));

                    const form = deleteModal.querySelector('#deleteForm');
                    form.action = `/jabatan/${data.id}`;
                    deleteModal.querySelector('#delete-jabatan-name').textContent =
                        `"${data.nama_jabatan}"`;
                });
            });
        });
    </script>
@endpush
