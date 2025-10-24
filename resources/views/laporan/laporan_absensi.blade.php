@extends('layouts.app')

@section('content')
    {{-- CSS untuk styling kalender dan warna status --}}
    <style>
        .table-minimalis {
            border-collapse: separate;
            border-spacing: 0 5px;
        }

        .table-minimalis th {
            border: none;
            font-weight: 600;
            color: #6c757d;
            padding-top: 0;
            padding-bottom: 10px;
        }

        .summary-row {
            background-color: #fff;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .summary-row:hover {
            background-color: #f8f9fa;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        }

        .summary-row.expanded {
            background-color: #e9f5ff;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .summary-row td {
            vertical-align: middle;
            border: none;
            padding: 12px 20px !important;
        }

        .detail-row.collapse.show {
            display: table-row !important;
        }

        .detail-cell {
            background-color: #fdfdff;
            padding: 1.5rem !important;
            border-left: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .detail-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .attendance-day {
            border-radius: 6px;
            padding: 5px;
            width: 48px;
            height: 48px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            text-align: center;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .attendance-day .day-number {
            font-weight: bold;
            font-size: 0.9rem;
        }

        .status-hadir {
            background-color: #d1e7dd;
            color: #0a3622;
        }

        .status-absen {
            background-color: #f8d7da;
            color: #58151c;
        }

        .status-libur {
            background-color: #f8f9fa;
            color: #6c757d;
        }
    </style>

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
                                value="{{ $tanggal->format('Y-m') }}">
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
        @if ($errors->has('karyawan_ids'))
            <div class="alert alert-danger">{{ $errors->first('karyawan_ids') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form Aksi Utama --}}
        <form id="laporan-absensi-form" method="POST">
            @csrf
            <input type="hidden" name="periode" value="{{ $tanggal->format('Y-m') }}">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Data Rekap untuk {{ $tanggal->translatedFormat('F Y') }}</h5>
                    <div>
                        <button type="button" id="cetak-terpilih-btn" class="btn btn-danger btn-sm"> <i
                                class="fas fa-file-pdf me-1"></i> Cetak PDF Terpilih </button>
                        <button type="button" id="kirim-email-terpilih-btn" class="btn btn-info btn-sm text-white"> <i
                                class="fas fa-envelope me-1"></i> Kirim Email Terpilih </button>
                    </div>
                </div>
            </div>

            <table class="table table-borderless table-minimalis">
                <thead>
                    <tr class="text-center">
                        <th style="width: 5%;"><input type="checkbox" id="select-all"></th>
                        <th class="text-start">Nama Pegawai</th>
                        <th style="width: 15%;">NIP</th>
                        <th style="width: 8%;">Hadir</th>
                        <th style="width: 8%;">Alpha</th>
                    </tr>
                </thead>
                <tbody id="rekap-tbody">
                    @forelse ($rekapData as $data)
                        <tr class="summary-row" data-bs-toggle="collapse" data-bs-target="#detail-row-{{ $data['id'] }}"
                            aria-expanded="false">
                            <td class="text-center">
                                <input type="checkbox" name="karyawan_ids[]" value="{{ $data['id'] }}"
                                    class="karyawan-checkbox" onclick="event.stopPropagation();">
                            </td>
                            <td><b>{{ $data['nama'] }}</b></td>
                            <td class="text-center">{{ $data['nip'] }}</td>
                            <td class="text-center text-success fw-bold">{{ $data['summary']['total_hadir'] }}</td>
                            <td class="text-center text-danger fw-bold">{{ $data['summary']['total_alpha'] }}</td>
                        </tr>
                        <tr id="detail-row-{{ $data['id'] }}" class="collapse detail-row">
                            <td colspan="5" class="detail-cell">
                                <div class="detail-grid">
                                    @for ($day = 1; $day <= $daysInMonth; $day++)
                                        @php
                                            $detailHari = $data['detail'][$day];
                                            $statusClass = 'status-libur'; // Default
                                            if ($detailHari['status'] === 'H') {
                                                $statusClass = 'status-hadir';
                                            }
                                            if ($detailHari['status'] === 'A') {
                                                $statusClass = 'status-absen';
                                            }
                                        @endphp
                                        <div class="attendance-day {{ $statusClass }}"
                                            title="Jam: {{ $detailHari['jam'] }}">
                                            <span class="day-number">{{ $day }}</span>
                                            <span>{{ $detailHari['status'] }}</span>
                                        </div>
                                    @endfor
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center fst-italic py-4"> Tidak ada data absensi untuk periode
                                ini. </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('laporan-absensi-form');
                const selectAllCheckbox = document.getElementById('select-all');
                const karyawanCheckboxes = document.querySelectorAll('.karyawan-checkbox');

                if (selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('change', function() {
                        karyawanCheckboxes.forEach(checkbox => checkbox.checked = this.checked);
                    });
                }

                document.getElementById('cetak-terpilih-btn').addEventListener('click', function() {
                    form.action = "{{ route('laporan.absensi.cetak') }}";
                    form.submit();
                });

                document.getElementById('kirim-email-terpilih-btn').addEventListener('click', function() {
                    form.action = "{{ route('laporan.absensi.kirim-email') }}";
                    form.submit();
                });

                document.querySelectorAll('.summary-row').forEach(row => {
                    row.addEventListener('click', function() {
                        this.classList.toggle('expanded');
                    });
                });
            });
        </script>
    @endpush
@endsection
