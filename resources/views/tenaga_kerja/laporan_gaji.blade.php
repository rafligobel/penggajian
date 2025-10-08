@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-file-invoice-dollar me-2"></i>Laporan Gaji Anda
                </h5>
                <div class="d-flex">
                    <span class="me-2 align-self-center">Tampilkan Tahun:</span>
                    <form method="GET" action="{{ route('tenaga_kerja.laporan_gaji') }}" class="mb-0">
                        <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                            @forelse ($availableYears as $year)
                                <option value="{{ $year }}" @selected($year == $tahun)>{{ $year }}
                                </option>
                            @empty
                                <option>{{ $tahun }}</option>
                            @endforelse
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @if ($gajis->isEmpty())
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        Belum ada data gaji yang tercatat untuk tahun {{ $tahun }}.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Periode Bulan</th>
                                    <th class="text-end">Gaji Pokok</th>
                                    <th class="text-end">Total Tunjangan</th>
                                    <th class="text-end">Potongan</th>
                                    <th class="text-end fw-bold">Gaji Bersih</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($gajis as $gaji)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($gaji->bulan)->translatedFormat('F Y') }}</td>
                                        <td class="text-end">Rp {{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($gaji->total_tunjangan, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end text-danger">Rp
                                            {{ number_format($gaji->total_potongan, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold text-success">Rp
                                            {{ number_format($gaji->gaji_bersih, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
