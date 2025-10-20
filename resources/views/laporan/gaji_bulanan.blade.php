@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4 fw-bold text-primary">Laporan Rekapitulasi Gaji Bulanan</h3>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <form id="filter-form" method="GET" action="{{ route('laporan.gaji.bulanan') }}">
                    <div class="row align-items-end g-3 mb-3">
                        <div class="col-md-3">
                            <label for="bulan" class="form-label fw-bold">Pilih Periode</label>
                            <input type="month" class="form-control" id="bulan" name="bulan"
                                value="{{ $selectedMonth }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Tampilkan
                            </button>
                        </div>
                    </div>
                </form>

                <hr>

                <form id="laporan-gaji-form" method="POST">
                    @csrf
                    <input type="hidden" name="bulan" value="{{ $selectedMonth }}">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button" id="cetak-terpilih-btn" class="btn btn-danger">
                            <i class="fas fa-file-pdf me-1"></i> Cetak PDF Terpilih
                        </button>
                        <button type="button" id="kirim-email-terpilih-btn" class="btn btn-info text-white">
                            <i class="fas fa-envelope me-1"></i> Kirim Email Terpilih
                        </button>
                    </div>

                    {{-- [PERBAIKAN] Menampilkan pesan error jika tidak ada checkbox yang dipilih --}}
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
                                @forelse ($laporanGaji as $item)
                                    <tr>
                                        {{-- Kunci fungsionalitas: name="gaji_ids[]" mengirim ID sebagai array --}}
                                        <td><input type="checkbox" name="gaji_ids[]" value="{{ $item['gaji']->id }}"
                                                class="gaji-checkbox"></td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item['karyawan']->nama }}</td>
                                        <td>{{ $item['karyawan']->jabatan?->nama_jabatan ?? '-' }}</td>
                                        <td class="text-end fw-bold">Rp
                                            {{ number_format($item['gaji_bersih'], 0, ',', '.') }}
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
                const cetakBtn = document.getElementById('cetak-terpilih-btn');
                const kirimEmailBtn = document.getElementById('kirim-email-terpilih-btn');

                if (cetakBtn) {
                    cetakBtn.addEventListener('click', function() {
                        form.method = 'POST';
                        form.action = "{{ route('laporan.gaji.cetak') }}";
                        form.submit();
                    });
                }

                if (kirimEmailBtn) {
                    kirimEmailBtn.addEventListener('click', function() {
                        form.method = 'POST';
                        form.action = "{{ route('laporan.gaji.kirim-email-terpilih') }}";
                        form.submit();
                    });
                }

                const selectAllCheckbox = document.getElementById('select-all');
                const gajiCheckboxes = document.querySelectorAll('.gaji-checkbox');

                if (selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('change', e => {
                        gajiCheckboxes.forEach(checkbox => checkbox.checked = e.target.checked);
                    });
                }
            });
        </script>
    @endpush
@endsection
