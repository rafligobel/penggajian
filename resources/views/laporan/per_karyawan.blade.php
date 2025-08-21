@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4 fw-bold text-primary">Laporan Rincian per Karyawan</h3>
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <form method="GET" action="{{ route('laporan.per.karyawan') }}">
                    <div class="row align-items-end">
                        <div class="col-md-4"><label for="karyawan_id" class="form-label fw-bold">Pilih Karyawan</label><select
                                name="karyawan_id" id="karyawan_id" class="form-select" required>
                                <option value="">-- Silakan Pilih --</option>
                                @foreach ($karyawans as $karyawan)
                                    <option value="{{ $karyawan->id }}" @selected($karyawan->id == $selectedKaryawanId)>{{ $karyawan->nama }}
                                        (NIP: {{ $karyawan->nip }})</option>
                                @endforeach
                            </select></div>
                        <div class="col-md-3"><label for="tanggal_mulai" class="form-label fw-bold">Dari Bulan</label><input
                                type="month" class="form-control" id="tanggal_mulai" name="tanggal_mulai"
                                value="{{ $tanggalMulai }}"></div>
                        <div class="col-md-3"><label for="tanggal_selesai" class="form-label fw-bold">Sampai
                                Bulan</label><input type="month" class="form-control" id="tanggal_selesai"
                                name="tanggal_selesai" value="{{ $tanggalSelesai }}"></div>
                        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i
                                    class="fas fa-search me-1"></i> Tampilkan</button></div>
                    </div>
                </form>
            </div>
        </div>

        @if ($selectedKaryawan)
            <div class="d-flex justify-content-end gap-2 mb-4">
                <form action="{{ route('laporan.cetak') }}" method="POST" target="_blank">
                    @csrf
                    <input type="hidden" name="tipe" value="per_karyawan">
                    <input type="hidden" name="karyawan_id" value="{{ $selectedKaryawanId }}">
                    <input type="hidden" name="tanggal_mulai" value="{{ $tanggalMulai }}">
                    <input type="hidden" name="tanggal_selesai" value="{{ $tanggalSelesai }}">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-file-pdf me-1"></i> Cetak Laporan
                        PDF</button>
                </form>
                <form action="{{ route('laporan.kirim-email') }}" method="POST">
                    @csrf
                    <input type="hidden" name="tipe" value="per_karyawan">
                    <input type="hidden" name="karyawan_id" value="{{ $selectedKaryawanId }}">
                    <input type="hidden" name="tanggal_mulai" value="{{ $tanggalMulai }}">
                    <input type="hidden" name="tanggal_selesai" value="{{ $tanggalSelesai }}">
                    <button type="submit" class="btn btn-info"><i class="fas fa-envelope me-1"></i> Kirim via
                        Email</button>
                </form>
            </div>
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h4 class="mb-0">Laporan untuk: <strong>{{ $selectedKaryawan->nama }}</strong></h4>
                    <p class="mb-0 text-muted">Periode: {{ \Carbon\Carbon::parse($tanggalMulai)->translatedFormat('F Y') }}
                        s.d. {{ \Carbon\Carbon::parse($tanggalSelesai)->translatedFormat('F Y') }}</p>
                </div>
                <div class="card-body">
                    <h5 class="mb-3">Ringkasan Absensi Periode Ini</h5>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card text-center text-bg-success">
                                <div class="card-body">
                                    <h6 class="card-title">Total Kehadiran</h6>
                                    <p class="card-text fs-4 fw-bold">{{ $laporanData['absensi_summary']['hadir'] }} Hari
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center text-bg-danger">
                                <div class="card-body">
                                    <h6 class="card-title">Total Alpha</h6>
                                    <p class="card-text fs-4 fw-bold">{{ $laporanData['absensi_summary']['alpha'] }} Hari
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="my-4">
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
                                    @php $totalTunjangan = $gaji->tunj_kehadiran + $gaji->tunj_anak + $gaji->tunj_komunikasi + $gaji->tunj_pengabdian + $gaji->tunj_jabatan + $gaji->tunj_kinerja + $gaji->lembur + $gaji->kelebihan_jam; @endphp
                                    <tr>
                                        <td class="text-center">
                                            {{ \Carbon\Carbon::parse($gaji->bulan)->translatedFormat('F Y') }}</td>
                                        <td class="text-end">Rp {{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($totalTunjangan, 0, ',', '.') }}</td>
                                        <td class="text-end text-danger">(Rp
                                            {{ number_format($gaji->potongan, 0, ',', '.') }})</td>
                                        <td class="text-end fw-bold">Rp
                                            {{ number_format($gaji->gaji_bersih, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center fst-italic py-4">Tidak ditemukan data gaji
                                            untuk karyawan pada periode yang dipilih.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info text-center"><i class="fas fa-info-circle me-2"></i> Untuk memulai, silakan pilih
                seorang karyawan dan tentukan rentang waktu, lalu klik "Tampilkan".</div>
        @endif
    </div>
@endsection
