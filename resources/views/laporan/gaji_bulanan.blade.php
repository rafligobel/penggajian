{{-- resources/views/laporan/gaji_bulanan.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4 fw-bold text-primary">Laporan Rekapitulasi Gaji Bulanan</h3>

        {{-- Filter --}}
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <form method="GET" action="{{ route('laporan.gaji.bulanan') }}">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="bulan" class="form-label fw-bold">Pilih Periode</label>
                            <input type="month" class="form-control" id="bulan" name="bulan"
                                value="{{ $selectedMonth }}">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i>
                                Tampilkan</button>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('laporan.gaji.cetak', ['bulan' => $selectedMonth]) }}"
                                class="btn btn-danger w-100" target="_blank">
                                <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Statistik --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Total Pengeluaran</h6>
                        <p class="card-text fs-5 fw-bold">Rp
                            {{ number_format($statistik['total_pengeluaran'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Gaji Rata-rata</h6>
                        <p class="card-text fs-5 fw-bold">Rp {{ number_format($statistik['gaji_rata_rata'], 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Gaji Tertinggi</h6>
                        <p class="card-text fs-5 fw-bold">Rp {{ number_format($statistik['gaji_tertinggi'], 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Jumlah Penerima</h6>
                        <p class="card-text fs-5 fw-bold">{{ $statistik['jumlah_penerima'] }} Karyawan</p>
                    </div>
                </div>
            </div>
        </div>


        {{-- Tabel Rincian --}}
        <div class="card shadow-sm border-0">
            <div class="card-header">
                <h5>Rincian Gaji Periode {{ \Carbon\Carbon::parse($selectedMonth)->translatedFormat('F Y') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>Nama Karyawan</th>
                                <th>Jabatan</th>
                                <th class="text-end">Gaji Pokok</th>
                                <th class="text-end">Total Tunjangan</th>
                                <th class="text-end">Lembur & Lainnya</th>
                                <th class="text-end">Potongan</th>
                                <th class="text-end fw-bold">Gaji Bersih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($gajis as $gaji)
                                @php
                                    $totalTunjangan =
                                        $gaji->tunj_kehadiran +
                                        $gaji->tunj_anak +
                                        $gaji->tunj_komunikasi +
                                        $gaji->tunj_pengabdian +
                                        $gaji->tunj_jabatan +
                                        $gaji->tunj_kinerja;
                                    $totalLainnya = $gaji->lembur + $gaji->kelebihan_jam;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $gaji->karyawan->nama }}</td>
                                    <td>{{ $gaji->karyawan->jabatan }}</td>
                                    <td class="text-end">Rp {{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($totalTunjangan, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($totalLainnya, 0, ',', '.') }}</td>
                                    <td class="text-end text-danger">(Rp {{ number_format($gaji->potongan, 0, ',', '.') }})
                                    </td>
                                    <td class="text-end fw-bold">Rp {{ number_format($gaji->gaji_bersih, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center fst-italic py-4">
                                        Tidak ada data gaji untuk periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
