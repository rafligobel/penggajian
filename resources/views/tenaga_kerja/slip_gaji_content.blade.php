<div class="modal-header">
    <h5 class="modal-title">Unduh Slip Gaji</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($availableMonths->isEmpty())
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle me-2"></i>
            Saat ini belum ada data slip gaji yang dapat diunduh.
        </div>
    @else
        <p class="text-muted">Silakan pilih periode slip gaji yang ingin Anda unduh. Proses pembuatan slip akan berjalan
            di belakang layar.</p>
        {{-- Form ini akan submit normal karena bertujuan untuk download file, bukan AJAX --}}
        <form action="{{ route('tenaga_kerja.slip_gaji.download') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="bulan" class="form-label">Pilih Periode</label>
                <select name="bulan" id="bulan" class="form-select" required>
                    @foreach ($availableMonths as $periode)
                        <option value="{{ $periode }}">
                            {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-download me-2"></i>Buat & Unduh Slip
                </button>
            </div>
        </form>
    @endif
</div>
