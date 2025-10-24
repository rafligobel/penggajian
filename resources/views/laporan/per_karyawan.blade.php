@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4 fw-bold text-primary">Laporan Rincian per Pegawai</h3>

        {{-- Form Filter --}}
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <form method="GET" action="{{ route('laporan.per.karyawan') }}">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="karyawan_id" class="form-label fw-bold">Pilih Pegawai</label>
                            <select name="karyawan_id" id="karyawan_id" class="form-select" required>
                                <option value="">-- Silakan Pilih --</option>
                                @foreach ($karyawans as $karyawan_item)
                                    <option value="{{ $karyawan_item->id }}" @selected($karyawan_item->id == $selectedKaryawanId)>
                                        {{ $karyawan_item->nama }} (NIP: {{ $karyawan_item->nip }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            {{-- PERBAIKAN: Mengganti name ke bulan_mulai --}}
                            <label for="bulan_mulai" class="form-label fw-bold">Dari Bulan</label>
                            <input type="month" class="form-control" id="bulan_mulai" name="bulan_mulai"
                                value="{{ $bulanMulai }}" required>
                        </div>
                        <div class="col-md-3">
                            {{-- PERBAIKAN: Mengganti name ke bulan_selesai --}}
                            <label for="bulan_selesai" class="form-label fw-bold">Sampai Bulan</label>
                            <input type="month" class="form-control" id="bulan_selesai" name="bulan_selesai"
                                value="{{ $bulanSelesai }}" required>
                        </div>
                        <div class="col-md-2">
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

        @if ($karyawan)
            <div class="card shadow-sm border-0">
                {{-- CARD HEADER DENGAN TOMBOL AKSI --}}
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Laporan untuk: <strong>{{ $karyawan->nama }}</strong></h4>
                        <p class="mb-0 text-muted">Periode:
                            {{ \Carbon\Carbon::createFromFormat('Y-m', $bulanMulai)->translatedFormat('F Y') }}
                            s.d. {{ \Carbon\Carbon::createFromFormat('Y-m', $bulanSelesai)->translatedFormat('F Y') }}</p>
                    </div>
                    <div>
                        {{-- Form dipisahkan untuk aksi Cetak dan Kirim --}}
                        <form id="laporan-karyawan-form" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="karyawan_id" value="{{ $karyawan->id }}">
                            <input type="hidden" name="bulan_mulai" value="{{ $bulanMulai }}">
                            <input type="hidden" name="bulan_selesai" value="{{ $bulanSelesai }}">

                            <button type="button" id="cetak-pdf-btn" class="btn btn-danger">
                                <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                            </button>
                            <button type="button" id="kirim-email-btn" class="btn btn-info text-white">
                                <i class="fas fa-envelope me-1"></i> Kirim Email
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Ringkasan Absensi --}}
                    <h5 class="mb-3">Ringkasan Absensi Periode Ini</h5>
                    <div class="row mb-4">
                        {{-- LOGIKA PERBAIKAN SINTAKS ALPHA CALCULATION --}}
                        @php
                            // Perhitungan hari hadir dan total hari kalender
                            try {
                                $start = \Carbon\Carbon::createFromFormat('Y-m', $bulanMulai)->startOfMonth();
                                $end = \Carbon\Carbon::createFromFormat('Y-m', $bulanSelesai)->endOfMonth();
                                $totalDaysInPeriod = $start->diffInDays($end) + 1;
                                $totalHadir = $laporanData['absensi_summary']['total_hadir_periode'] ?? 0;

                                // PERBAIKAN KRITIS: Menggunakan round() untuk menghilangkan presisi float
                                $totalAlpha = round($totalDaysInPeriod - $totalHadir);
                            } catch (\Throwable $e) {
                                $totalAlpha = 0;
                            }
                        @endphp

                        <div class="col-md-4">
                            <div class="card text-center text-bg-success">
                                <div class="card-body">
                                    <h6 class="card-title">Total Kehadiran</h6>
                                    <p class="card-text fs-4 fw-bold">{{ $totalHadir }} Hari
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center text-bg-danger">
                                <div class="card-body">
                                    <h6 class="card-title">Total Alpha</h6>
                                    <p class="card-text fs-4 fw-bold">
                                        {{-- Menggunakan variabel yang sudah dihitung dan dibulatkan --}}
                                        {{ $totalAlpha }} Hari
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- Riwayat Gaji --}}
                    <h5 class="mb-3">Riwayat Gaji Diterima</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>Periode Gaji</th>
                                    <th>Gaji Pokok</th>
                                    <th>Total Tunjangan</th>
                                    <th>Potongan</th>
                                    <th>Gaji Bersih</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($laporanData['gajis'] as $gaji)
                                    <tr>
                                        <td class="text-center">
                                            {{ \Carbon\Carbon::parse($gaji->bulan)->translatedFormat('F Y') }}</td>
                                        <td class="text-end">Rp {{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp
                                            {{ number_format($gaji->total_tunjangan_custom, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end text-danger">(Rp
                                            {{ number_format($gaji->total_potongan_custom, 0, ',', '.') }})</td>
                                        <td class="text-end fw-bold">Rp
                                            {{ number_format($gaji->gaji_bersih, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center fst-italic py-4">
                                            Tidak ditemukan data gaji untuk karyawan pada periode yang dipilih.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>
                Untuk memulai, silakan pilih seorang karyawan dan tentukan rentang waktu, lalu klik "Tampilkan".
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('laporan-karyawan-form');

                if (form) {
                    // [PERBAIKAN BUG] Logika JS yang sudah benar, memastikan action diatur sebelum submit
                    document.getElementById('cetak-pdf-btn').addEventListener('click', function() {
                        form.action = "{{ route('laporan.per.karyawan.cetak') }}";
                        form.submit();
                    });

                    document.getElementById('kirim-email-btn').addEventListener('click', function() {
                        form.action = "{{ route('laporan.per.karyawan.kirim-email') }}";
                        form.submit();
                    });
                }
            });
        </script>
    @endpush
@endsection
