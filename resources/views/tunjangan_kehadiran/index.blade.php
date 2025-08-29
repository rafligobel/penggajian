@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-primary mb-0">Kelola Tunjangan Kehadiran</h3>
            {{-- Tombol untuk memicu modal tambah --}}
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fas fa-plus me-1"></i> Tambah Tunjangan
            </button>
        </div>

        {{-- Notifikasi Sukses --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Menampilkan error validasi --}}
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
                                <th>Jenis Tunjangan</th>
                                <th class="text-end">Jumlah (Rp)</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tunjanganKehadirans as $tunjangan)
                                {{-- Tambahkan atribut data-* untuk menyimpan data dalam format JSON --}}
                                <tr data-tunjangan-json="{{ json_encode($tunjangan) }}">
                                    <td>{{ $loop->iteration + $tunjanganKehadirans->firstItem() - 1 }}</td>
                                    <td>{{ $tunjangan->jenis_tunjangan }}</td>
                                    <td class="text-end">{{ number_format($tunjangan->jumlah_tunjangan, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        {{-- Tombol Edit dan Hapus dengan atribut data-bs-* untuk modal --}}
                                        <button class="btn btn-sm btn-warning btn-edit" title="Edit Tunjangan"
                                            data-bs-toggle="modal" data-bs-target="#editModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-delete" title="Hapus Tunjangan"
                                            data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center fst-italic py-4">
                                        Tidak ada data tunjangan kehadiran untuk ditampilkan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- Paginasi --}}
                @if ($tunjanganKehadirans->hasPages())
                    <div class="mt-3">
                        {{ $tunjanganKehadirans->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ============== MODALS ============== --}}

    <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('tunjangan-kehadiran.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createModalLabel">Tambah Tunjangan Kehadiran Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="jenis_tunjangan" class="form-label">Jenis Tunjangan</label>
                            <input type="text" class="form-control" id="jenis_tunjangan" name="jenis_tunjangan" required>
                        </div>
                        <div class="mb-3">
                            <label for="jumlah_tunjangan" class="form-label">Jumlah Tunjangan (Rp)</label>
                            <input type="number" class="form-control" id="jumlah_tunjangan" name="jumlah_tunjangan" required>
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

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                {{-- Form action akan diisi oleh JavaScript --}}
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Tunjangan Kehadiran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_jenis_tunjangan" class="form-label">Jenis Tunjangan</label>
                            <input type="text" class="form-control" id="edit_jenis_tunjangan" name="jenis_tunjangan" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_jumlah_tunjangan" class="form-label">Jumlah Tunjangan (Rp)</label>
                            <input type="number" class="form-control" id="edit_jumlah_tunjangan" name="jumlah_tunjangan" required>
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

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                {{-- Form action akan diisi oleh JavaScript --}}
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Apakah Anda yakin ingin menghapus tunjangan <strong id="delete-tunjangan-name"></strong>?
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
                    const data = JSON.parse(row.getAttribute('data-tunjangan-json'));
                    
                    const form = editModal.querySelector('#editForm');
                    const modalTitle = editModal.querySelector('#editModalLabel');

                    // Set URL action form
                    form.action = `/tunjangan-kehadiran/${data.id}`;

                    // Isi nilai form
                    modalTitle.textContent = `Edit Tunjangan: ${data.jenis_tunjangan}`;
                    form.querySelector('#edit_jenis_tunjangan').value = data.jenis_tunjangan;
                    form.querySelector('#edit_jumlah_tunjangan').value = data.jumlah_tunjangan;
                });
            });

            // Script untuk Modal Hapus
            const deleteModal = document.getElementById('deleteModal');
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const data = JSON.parse(row.getAttribute('data-tunjangan-json'));

                    const form = deleteModal.querySelector('#deleteForm');
                    const tunjanganName = deleteModal.querySelector('#delete-tunjangan-name');
                    
                    // Set URL action form
                    form.action = `/tunjangan-kehadiran/${data.id}`;

                    // Isi nama tunjangan yang akan dihapus
                    tunjanganName.textContent = `"${data.jenis_tunjangan}"`;
                });
            });
        });
    </script>
@endpush