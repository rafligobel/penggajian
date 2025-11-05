@extends('layouts.app')

@section('title', 'Aturan & Indikator Tunjangan Kinerja')

@section('content')
    <div class="container-fluid">

        <h3 class="fw-bold text-primary mb-4">Aturan & Indikator Kinerja</h3>

        {{-- Tampilkan pesan sukses/error --}}
        @if (session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="row">
            <div class="col-lg-5 col-md-12 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header py-3 bg-light border-0">
                        <h6 class="m-0 font-weight-bold text-primary">1. Atur Tunjangan Kinerja Maksimal</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('pengaturan-kinerja.aturan.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="maksimal_tunjangan" class="form-label fw-bold">Nominal Tukin Maksimal (per
                                    bulan)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number"
                                        class="form-control @error('maksimal_tunjangan') is-invalid @enderror"
                                        id="maksimal_tunjangan" name="maksimal_tunjangan"
                                        value="{{ old('maksimal_tunjangan', $aturan->maksimal_tunjangan) }}" min="0"
                                        step="1000">
                                </div>
                                @error('maksimal_tunjangan')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <small class="form-text text-muted mt-1">
                                    Ini adalah jumlah Tukin yang akan diterima pegawai jika mendapat skor rata-rata 100%.
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary">Simpan Aturan</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 col-md-12 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header py-3 bg-light border-0">
                        <h6 class="m-0 font-weight-bold text-primary">2. Kelola Master Indikator Kinerja</h6>
                    </div>
                    <div class="card-body">
                        <form id="indicator-form" action="{{ route('pengaturan-kinerja.indikator.store') }}" method="POST"
                            class="mb-4">
                            @csrf
                            <div id="form-method"></div>
                            <div class="mb-3">
                                <label for="nama_indikator" class="form-label fw-bold" id="form-title">Tambah Indikator
                                    Baru</label>
                                <div class="input-group">
                                    <input type="text" class="form-control @error('nama_indikator') is-invalid @enderror"
                                        id="nama_indikator" name="nama_indikator" required>
                                    <button type="submit" class="btn btn-success" id="submit-btn">Simpan</button>
                                    <button type="button" class="btn btn-secondary" id="cancel-btn"
                                        style="display: none;">Batal</button>
                                </div>
                                @error('nama_indikator')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" width="100%" cellspacing="0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Indikator</th>
                                        <th width="30%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($indikators as $indikator)
                                        <tr>
                                            <td>{{ $indikator->nama_indikator }}</td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-warning edit-btn"
                                                    data-id="{{ $indikator->id }}"
                                                    data-name="{{ $indikator->nama_indikator }}"
                                                    data-url="{{ route('pengaturan-kinerja.indikator.update', $indikator->id) }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form
                                                    action="{{ route('pengaturan-kinerja.indikator.destroy', $indikator->id) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Yakin ingin menghapus indikator ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center fst-italic py-3">Belum ada indikator.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('indicator-form');
                const formTitle = document.getElementById('form-title');
                const formMethod = document.getElementById('form-method');
                const inputName = document.getElementById('nama_indikator');
                const submitBtn = document.getElementById('submit-btn');
                const cancelBtn = document.getElementById('cancel-btn');
                const storeUrl = "{{ route('pengaturan-kinerja.indikator.store') }}";

                document.querySelectorAll('.edit-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const name = this.dataset.name;
                        const url = this.dataset.url;

                        form.action = url;
                        formTitle.textContent = 'Edit Indikator: ' + name;
                        formMethod.innerHTML = '@method('PUT')';
                        inputName.value = name;
                        submitBtn.textContent = 'Update';
                        submitBtn.classList.remove('btn-success');
                        submitBtn.classList.add('btn-warning');
                        cancelBtn.style.display = 'inline-block';
                        inputName.focus();
                    });
                });

                cancelBtn.addEventListener('click', function() {
                    form.action = storeUrl;
                    formTitle.textContent = 'Tambah Indikator Baru';
                    formMethod.innerHTML = '';
                    inputName.value = '';
                    submitBtn.textContent = 'Simpan';
                    submitBtn.classList.remove('btn-warning');
                    submitBtn.classList.add('btn-success');
                    cancelBtn.style.display = 'none';
                });
            });
        </script>
    @endpush
@endsection
