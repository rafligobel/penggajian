@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4 fw-bold text-primary">Laporan Rekapitulasi Gaji Bulanan</h3>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <form id="laporan-form" method="POST" action="{{ route('laporan.gaji.cetak') }}">
                    @csrf
                    <div class="row align-items-end g-3">
                        <div class="col-md-3">
                            <label for="bulan" class="form-label fw-bold">Pilih Periode</label>
                            <input type="month" class="form-control" id="bulan" name="bulan"
                                value="{{ $selectedMonth }}">
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="filter-btn" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Tampilkan
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" id="cetak-terpilih-btn" class="btn btn-danger w-100">
                                <i class="fas fa-file-pdf me-1"></i> Cetak PDF Terpilih
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button type="button" id="kirim-email-terpilih-btn" class="btn btn-info w-100">
                                <i class="fas fa-envelope me-1"></i> Kirim Email Terpilih
                            </button>
                        </div>
                    </div>

                    @if ($errors->has('gaji_ids'))
                        <div class="text-danger small mt-2">{{ $errors->first('gaji_ids') }}</div>
                    @endif

                    <hr>

                    {{-- Tabel Rincian --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>No.</th>
                                    <th>Nama Karyawan</th>
                                    <th>Jabatan</th>
                                    <th class="text-end fw-bold">Gaji Bersih</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($gajis as $gaji)
                                    <tr>
                                        <td><input type="checkbox" name="gaji_ids[]" value="{{ $gaji->id }}"
                                                class="gaji-checkbox"></td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $gaji->karyawan->nama }}</td>
                                        <td>{{ $gaji->karyawan?->jabatan?->nama_jabatan ?? '-' }}</td>
                                        <td class="text-end fw-bold">Rp {{ number_format($gaji->gaji_bersih, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center fst-italic py-4">
                                            Tidak ada data gaji untuk periode ini.
                                        </td>
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
                const form = document.getElementById('laporan-form');

                document.getElementById('filter-btn').addEventListener('click', function() {
                    form.method = 'GET';
                    form.action = "{{ route('laporan.gaji.bulanan') }}";
                    // [PERBAIKAN BUG 2] Baris yang menghapus token CSRF dihilangkan untuk mencegah error 419
                    form.submit();
                });

                document.getElementById('cetak-terpilih-btn').addEventListener('click', function() {
                    // Pastikan method dan action kembali ke POST untuk cetak
                    form.method = 'POST';
                    form.action = "{{ route('laporan.gaji.cetak') }}";
                    form.removeAttribute('target');
                    form.submit();
                });

                document.getElementById('kirim-email-terpilih-btn').addEventListener('click', function() {
                    // Pastikan method dan action kembali ke POST untuk kirim email
                    form.method = 'POST';
                    form.action = "{{ route('laporan.gaji.kirim-email-terpilih') }}";
                    form.removeAttribute('target');
                    form.submit();
                });

                // [PERBAIKAN BUG 4] Logika checkbox 'select all' dibuat lebih responsif
                const selectAllCheckbox = document.getElementById('select-all');
                const gajiCheckboxes = document.querySelectorAll('.gaji-checkbox');

                if (selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('change', function(e) {
                        gajiCheckboxes.forEach(checkbox => {
                            checkbox.checked = e.target.checked;
                        });
                    });
                }

                gajiCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        const allAreChecked = [...gajiCheckboxes].every(cb => cb.checked);
                        const noneAreChecked = [...gajiCheckboxes].every(cb => !cb.checked);

                        if (allAreChecked) {
                            selectAllCheckbox.checked = true;
                            selectAllCheckbox.indeterminate = false;
                        } else if (noneAreChecked) {
                            selectAllCheckbox.checked = false;
                            selectAllCheckbox.indeterminate = false;
                        } else {
                            // Jika beberapa terpilih (tapi tidak semua), set ke indeterminate
                            selectAllCheckbox.indeterminate = true;
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
