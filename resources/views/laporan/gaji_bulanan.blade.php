@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4 fw-bold text-primary">Laporan Rekapitulasi Gaji Bulanan</h3>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                {{-- Form Filter yang Menggunakan Metode GET --}}
                <form id="filter-form" method="GET" action="{{ route('laporan.gaji.bulanan') }}">
                    <div class="row align-items-end g-3 mb-3">
                        {{-- Kolom 1: Pilih Periode --}}
                        <div class="col-md-3">
                            <label for="bulan" class="form-label fw-bold">Pilih Periode</label>
                            <input type="month" class="form-control" id="bulan" name="bulan"
                                value="{{ $selectedMonth }}">
                        </div>

                        {{-- Kolom 2: Tombol Tampilkan --}}
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Tampilkan
                            </button>
                        </div>

                        {{-- Kolom 3 & 4: Tombol Aksi (Cetak & Email) --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold opacity-0 d-block">Aksi</label>
                            <button type="button" id="cetak-terpilih-btn" class="btn btn-danger w-100">
                                <i class="fas fa-file-pdf me-1"></i> Cetak PDF Terpilih
                            </button>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold opacity-0 d-block">Aksi</label>
                            <button type="button" id="kirim-email-terpilih-btn" class="btn btn-info text-white w-100">
                                <i class="fas fa-envelope me-1"></i> Kirim Email Terpilih
                            </button>
                        </div>
                    </div>
                </form>

                <hr>

                {{-- Form untuk Submit Aksi (mempertahankan struktur Form asli) --}}
                <form id="laporan-gaji-form" method="POST">
                    @csrf
                    <input type="hidden" name="bulan" value="{{ $selectedMonth }}">

                    {{-- Menampilkan pesan error jika ada --}}
                    @if ($errors->has('gaji_ids'))
                        <div class="alert alert-danger py-2 small">{{ $errors->first('gaji_ids') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><input type="checkbox" id="select-all" title="Pilih Semua"></th>
                                    <th>No.</th>
                                    <th>Nama Pegawai</th>
                                    <th>Jabatan</th>
                                    <th class="text-end fw-bold">Gaji Bersih</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- PERBAIKAN KRITIS: Mengganti variabel loop $laporanGaji dengan $gajis --}}
                                @forelse ($gajis as $gaji)
                                    <tr>
                                        <td><input type="checkbox" name="gaji_ids[]" value="{{ $gaji->id }}"
                                                class="gaji-checkbox"></td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $gaji->karyawan->nama }}</td>
                                        {{-- Mengakses jabatan melalui relasi $gaji->karyawan->jabatan --}}
                                        <td>{{ $gaji->karyawan->jabatan?->nama_jabatan ?? '-' }}</td>
                                        <td class="text-end fw-bold">Rp
                                            {{-- Menggunakan variabel hasil perhitungan dari Controller --}}
                                            {{ number_format($gaji->gaji_bersih_perhitungan, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center fst-italic py-4">Tidak ada data gaji untuk
                                            periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('laporan-gaji-form');
                const cetakTerpilihBtn = document.getElementById('cetak-terpilih-btn');
                const kirimEmailTerpilihBtn = document.getElementById('kirim-email-terpilih-btn');

                const selectAllCheckbox = document.getElementById('select-all');
                const gajiCheckboxes = document.querySelectorAll('.gaji-checkbox');

                function updateActionButtons() {
                    const checkedCount = document.querySelectorAll('.gaji-checkbox:checked').length;
                    cetakTerpilihBtn.disabled = checkedCount === 0;
                    kirimEmailTerpilihBtn.disabled = checkedCount === 0;
                }

                // --- Listener untuk Aksi Terpilih (Memicu Job) ---
                cetakTerpilihBtn.addEventListener('click', function() {
                    if (document.querySelectorAll('.gaji-checkbox:checked').length === 0) return;

                    form.method = 'POST';
                    form.action = "{{ route('laporan.gaji.cetak') }}"; // Route Job Cetak
                    form.submit();
                });

                kirimEmailTerpilihBtn.addEventListener('click', function() {
                    if (document.querySelectorAll('.gaji-checkbox:checked').length === 0) return;

                    form.method = 'POST';
                    // PERBAIKAN KRITIS: Menggunakan route yang sudah ada
                    form.action = "{{ route('laporan.gaji.kirim-email-terpilih') }}";
                    form.submit();
                });

                // Listener untuk checkbox dan select-all
                gajiCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', updateActionButtons);
                });

                if (selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('change', e => {
                        // Update semua checkbox sesuai status selectAll
                        gajiCheckboxes.forEach(checkbox => checkbox.checked = e.target.checked);
                        updateActionButtons();
                    });
                }
            });
        </script>
    @endpush
@endsection
