@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4 fw-bold text-primary">Kelola Aturan Tunjangan Anak</h3>

        @if (session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <p class="text-muted">Hanya ada satu pengaturan untuk nilai tunjangan per anak. Silakan ubah nilai di bawah ini dan simpan.</p>
                
                <form action="{{ route('aturan-anak.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="nama_aturan" class="form-label">Nama Aturan</label>
                                <input type="text" class="form-control" id="nama_aturan" 
                                       value="{{ $aturan->nama_aturan }}" readonly disabled>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="nilai_per_anak" class="form-label fw-bold">Nilai Tunjangan (per Anak)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('nilai_per_anak') is-invalid @enderror" 
                                           id="nilai_per_anak" name="nilai_per_anak" 
                                           value="{{ old('nilai_per_anak', $aturan->nilai_per_anak) }}" min="0">
                                </div>
                                @error('nilai_per_anak')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection