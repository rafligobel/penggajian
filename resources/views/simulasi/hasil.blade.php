@extends('layouts.tenaga_kerja_layout')

@section('content')
    <div class="container d-flex justify-content-center py-5">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-success text-white">
                    {{-- UBAH JUDUL HASIL --}}
                    <h4 class="mb-0">Hasil Simulasi Gaji untuk: {{ $hasil['karyawan']->nama }}</h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-center">Berikut adalah rincian estimasi gaji Anda berdasarkan
                        **{{ $hasil['jumlah_hari_masuk'] }} hari kerja**.</p>

                    <div class="table-responsive">
                        <table class="table table-bordered mt-3">
                            <thead class="table-light">
                                <tr>
                                    <th colspan="2">Komponen Gaji</th>
                                    <th class="text-end">Nilai (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Pendapatan --}}
                                <tr>
                                    <td colspan="3" class="fw-bold bg-light"><strong>A. Pendapatan Tetap (berdasarkan
                                            template)</strong></td>
                                </tr>
                                <tr>
                                    <td colspan="2">Gaji Pokok</td>
                                    <td class="text-end">{{ number_format($hasil['rincian']['gaji_pokok'], 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">Tunjangan Jabatan</td>
                                    <td class="text-end">{{ number_format($hasil['rincian']['tunj_jabatan'], 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">Tunjangan Anak</td>
                                    <td class="text-end">{{ number_format($hasil['rincian']['tunj_anak'], 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">Tunjangan Komunikasi</td>
                                    <td class="text-end">
                                        {{ number_format($hasil['rincian']['tunj_komunikasi'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2">Tunjangan Pengabdian</td>
                                    <td class="text-end">
                                        {{ number_format($hasil['rincian']['tunj_pengabdian'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2">Tunjangan Kinerja</td>
                                    <td class="text-end">{{ number_format($hasil['rincian']['tunj_kinerja'], 0, ',', '.') }}
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="3" class="fw-bold bg-light"><strong>B. Pendapatan Tidak Tetap
                                            (berdasarkan input)</strong></td>
                                </tr>
                                <tr class="table-info">
                                    <td colspan="2">Tunjangan Kehadiran ({{ $hasil['jumlah_hari_masuk'] }} hari)</td>
                                    <td class="text-end">
                                        {{ number_format($hasil['rincian']['tunj_kehadiran'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2">Lembur</td>
                                    <td class="text-end">{{ number_format($hasil['rincian']['lembur'], 0, ',', '.') }}</td>
                                </tr>

                                <tr>
                                    <td colspan="3" class="fw-bold bg-light"><strong>C. Potongan</strong></td>
                                </tr>
                                <tr>
                                    <td colspan="2">Potongan Lain-lain</td>
                                    <td class="text-end text-danger">
                                        ({{ number_format($hasil['rincian']['potongan'], 0, ',', '.') }})</td>
                                </tr>

                            </tbody>
                            <tfoot>
                                <tr class="table-success">
                                    <th colspan="2" class="fs-5">TOTAL ESTIMASI GAJI BERSIH</th>
                                    <th class="text-end fs-5">Rp {{ number_format($hasil['gaji_bersih'], 0, ',', '.') }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="alert alert-warning small mt-4">
                        <strong>Disclaimer:</strong> Ini adalah simulasi dan bukan merupakan nominal gaji final. Nominal
                        akhir pada slip gaji mungkin berbeda tergantung kebijakan dan perhitungan aktual pada bulan terkait.
                    </div>

                    <div class="text-center mt-4">
                        <a href="{{ route('tenaga_kerja.dashboard') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
