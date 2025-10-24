<div class="modal-header">
    <h5 class="modal-title">Unduh Slip Gaji</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<form method="GET" action="{{ route('tenaga_kerja.slip_gaji.download') }}">

    <div class="modal-body">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <p class="text-muted">Silakan pilih periode slip gaji yang ingin Anda unduh.</p>
        <div class="mb-3">
            <label for="bulan_slip" class="form-label">Pilih Bulan</label>
            <select name="bulan" id="bulan_slip" class="form-select" required>
                @forelse ($availableMonths as $bulan)
                    {{--
                        [PERBAIKAN] Nilai (value) dari option diformat menjadi 'Y-m'
                        agar lolos validasi 'date_format:Y-m' di controller.
                    --}}
                    <option value="{{ \Carbon\Carbon::parse($bulan)->format('Y-m') }}">
                        {{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}
                    </option>
                @empty
                    <option disabled>Belum ada data slip gaji yang tersedia</option>
                @endforelse
            </select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" {{ $availableMonths->isEmpty() ? 'disabled' : '' }}>
            <i class="fas fa-download me-1"></i> Unduh
        </button>
    </div>
</form>
