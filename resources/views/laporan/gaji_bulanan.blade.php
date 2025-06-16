@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4 fw-bold text-primary">Laporan Rekapitulasi Gaji Bulanan</h3>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <form id="laporan-form" method="POST" action="{{ route('laporan.gaji.cetak') }}">
                    @csrf
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="bulan" class="form-label fw-bold">Pilih Periode</label>
                            <input type="month" class="form-control" id="bulan" name="bulan"
                                value="{{ $selectedMonth }}">
                        </div>
                        <div class="col-md-3">
                            <button type="button" id="filter-btn" class="btn btn-primary w-100"><i
                                    class="fas fa-filter me-1"></i>
                                Tampilkan</button>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-file-pdf me-1"></i> Cetak PDF Terpilih
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
                                        <td>{{ $gaji->karyawan->jabatan }}</td>
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
                    // Ubah method ke GET untuk filter
                    form.method = 'GET';
                    form.action = "{{ route('laporan.gaji.bulanan') }}";
                    // Hapus atribut _token sebelum submit GET
                    const token = form.querySelector('input[name="_token"]');
                    if (token) token.remove();
                    form.submit();
                });

                document.getElementById('select-all').addEventListener('change', function(e) {
                    document.querySelectorAll('.gaji-checkbox').forEach(checkbox => {
                        checkbox.checked = e.target.checked;
                    });
                });
            });
        </script>
    @endpush
@endsection
