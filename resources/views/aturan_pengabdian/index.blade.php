@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-primary mb-0">Kelola Aturan Tunjangan Pengabdian</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAturanPengabdianModal">
                <i class="fas fa-plus me-1"></i> Tambah Aturan Baru
            </button>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <p class="fw-bold">Gagal menyimpan data:</p>
                <ul class="mb-0">
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
                                <th>Nama Aturan</th>
                                <th>Masa Kerja</th>
                                <th class="text-center">Nilai (Persen)</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($aturans as $aturan)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $aturan->nama_aturan }}</td>
                                    <td>{{ $aturan->minimal_tahun_kerja }} - {{ $aturan->maksimal_tahun_kerja }} Tahun</td>
                                    <td class="text-center fw-bold">{{ $aturan->nilai_tunjangan }} %</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning btn-edit" data-bs-toggle="modal"
                                            data-bs-target="#editAturanPengabdianModal" data-id="{{ $aturan->id }}"
                                            data-action="{{ route('aturan-pengabdian.update', $aturan->id) }}"
                                            data-item="{{ $aturan->toJson() }}" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                            data-bs-target="#deleteModal-{{ $aturan->id }}" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>

                                        <x-delete-confirmation-modal :id="$aturan->id" :action="route('aturan-pengabdian.destroy', $aturan)"
                                            :itemName="$aturan->nama_aturan" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center fst-italic py-4">
                                        Belum ada aturan tunjangan pengabdian.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Aturan --}}
    <div class="modal fade" id="createAturanPengabdianModal" tabindex="-1" aria-labelledby="createModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('aturan-pengabdian.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createModalLabel">Tambah Aturan Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nama_aturan" class="form-label">Nama Aturan</label>
                            <input type="text" class="form-control @error('nama_aturan') is-invalid @enderror"
                                id="nama_aturan" name="nama_aturan" value="{{ old('nama_aturan', 'Tunj. Pengabdian') }}"
                                required>
                            @error('nama_aturan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="minimal_tahun_kerja" class="form-label">Minimal Masa Kerja (Tahun)</label>
                                <input type="number"
                                    class="form-control @error('minimal_tahun_kerja') is-invalid @enderror"
                                    id="minimal_tahun_kerja" name="minimal_tahun_kerja"
                                    value="{{ old('minimal_tahun_kerja', 0) }}" min="0" required>
                                @error('minimal_tahun_kerja')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="maksimal_tahun_kerja" class="form-label">Maksimal Masa Kerja (Tahun)</label>
                                <input type="number"
                                    class="form-control @error('maksimal_tahun_kerja') is-invalid @enderror"
                                    id="maksimal_tahun_kerja" name="maksimal_tahun_kerja"
                                    value="{{ old('maksimal_tahun_kerja', 0) }}" min="0" required>
                                @error('maksimal_tahun_kerja')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="nilai_tunjangan" class="form-label">Nilai Tunjangan (Persen)</label>
                            <div class="input-group">
                                <input type="number" class="form-control @error('nilai_tunjangan') is-invalid @enderror"
                                    id="nilai_tunjangan" name="nilai_tunjangan" value="{{ old('nilai_tunjangan', 0) }}"
                                    min="0" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="form-text text-muted">Masukkan angka saja, misal: 5 atau 10.</small>
                            @error('nilai_tunjangan')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
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

    {{-- Modal Edit Aturan --}}
    <div class="modal fade" id="editAturanPengabdianModal" tabindex="-1" aria-labelledby="editModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editAturanPengabdianForm" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Aturan</h5>
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
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Logika untuk mengisi modal edit
            var editModal = document.getElementById('editAturanPengabdianModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var item = JSON.parse(button.getAttribute('data-item'));
                var action = button.getAttribute('data-action');

                var form = editModal.querySelector('#editAturanPengabdianForm');
                form.setAttribute('action', action);

                var content = editModal.querySelector('#edit-form-content');
                // Mengisi konten form dengan data
                // PERBAIKAN: Ubah input-group dari "Rp" ke "%" di modal edit
                content.innerHTML = `
                <div class="mb-3">
                    <label for="edit_nama_aturan" class="form-label">Nama Aturan</label>
                    <input type="text" class="form-control" id="edit_nama_aturan" name="nama_aturan" value="${item.nama_aturan}" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="edit_minimal_tahun_kerja" class="form-label">Minimal (Tahun)</label>
                        <input type="number" class="form-control" id="edit_minimal_tahun_kerja" name="minimal_tahun_kerja" value="${item.minimal_tahun_kerja}" min="0" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="edit_maksimal_tahun_kerja" class="form-label">Maksimal (Tahun)</label>
                        <input type="number" class="form-control" id="edit_maksimal_tahun_kerja" name="maksimal_tahun_kerja" value="${item.maksimal_tahun_kerja}" min="0" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="edit_nilai_tunjangan" class="form-label">Nilai Tunjangan (Persen)</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="edit_nilai_tunjangan" name="nilai_tunjangan" value="${item.nilai_tunjangan}" min="0" required>
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="form-text text-muted">Masukkan angka saja, misal: 5 atau 10.</small>
                </div>
            `;
            });

            // Script untuk membuka kembali modal jika ada error validasi
            @if ($errors->any())
                // Logika sederhana: jika ada error, asumsikan itu dari modal "Tambah"
                // dan buka kembali.
                var createModal = new bootstrap.Modal(document.getElementById('createAturanPengabdianModal'), {
                    keyboard: false
                });
                createModal.show();
            @endif
        });
    </script>
@endpush
