@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4 fw-bold text-primary">Laporan Rekapitulasi Absensi Bulanan</h3>

        {{-- Form Filter --}}
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <form method="GET" action="{{ route('laporan.absensi') }}">
                    <div class="row align-items-end">
                        <div class="col-md-8">
                            <label for="periode" class="form-label fw-bold">Pilih Periode</label>
                            <input type="month" class="form-control" id="periode" name="periode"
                                value="{{ $tahun }}-{{ $bulan }}">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>
                                Tampilkan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form id="laporan-absensi-form" method="POST">
            @csrf
            {{-- Input periode diubah agar sesuai dengan filter baru --}}
            <input type="hidden" name="periode" value="{{ $tahun }}-{{ $bulan }}">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Data Rekap Absensi untuk
                        {{ \Carbon\Carbon::create($tahun, $bulan)->translatedFormat('F Y') }}</h5>
                    <div>
                        <button type="button" id="cetak-terpilih-btn" class="btn btn-danger btn-sm">
                            <i class="fas fa-file-pdf me-1"></i> Cetak PDF Terpilih
                        </button>
                        <button type="button" id="kirim-email-terpilih-btn" class="btn btn-info btn-sm text-white">
                            <i class="fas fa-envelope me-1"></i> Kirim Email Terpilih
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light text-center">
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>NIP</th>
                                    <th>Nama Karyawan</th>
                                    @foreach ($sesiAbsensi as $sesi)
                                        <th>{{ $sesi->nama }}</th>
                                    @endforeach
                                    <th>Total Hadir</th>
                                    <th>Total Alpha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rekapData as $data)
                                    <tr>
                                        <td class="text-center"><input type="checkbox" name="karyawan_ids[]"
                                                value="{{ $data->id }}" class="karyawan-checkbox"></td>
                                        <td>{{ $data->nip }}</td>
                                        <td>{{ $data->nama }}</td>
                                        @foreach ($sesiAbsensi as $sesi)
                                            <td class="text-center">{{ $data->summary['sesi'][$sesi->id]['hadir'] }}</td>
                                        @endforeach
                                        <td class="text-center fw-bold text-success">{{ $data->summary['total_hadir'] }}
                                        </td>
                                        <td class="text-center fw-bold text-danger">{{ $data->summary['total_alpha'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 5 + count($sesiAbsensi) }}" class="text-center fst-italic py-4">
                                            Tidak ada data absensi untuk periode ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('laporan-absensi-form');
                const selectAllCheckbox = document.getElementById('select-all');
                const karyawanCheckboxes = document.querySelectorAll('.karyawan-checkbox');

                selectAllCheckbox.addEventListener('change', function() {
                    karyawanCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });

                document.getElementById('cetak-terpilih-btn').addEventListener('click', function() {
                    form.action = "{{ route('laporan.absensi.cetak') }}";
                    form.submit();
                });

                document.getElementById('kirim-email-terpilih-btn').addEventListener('click', function() {
                    form.action = "{{ route('laporan.absensi.kirim-email') }}";
                    form.submit();
                });
            });
        </script>
    @endpush
@endsection
