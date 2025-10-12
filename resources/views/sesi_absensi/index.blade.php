@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <h3 class="mb-4 fw-bold text-primary">Kelola Sesi Absensi</h3>
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Gagal menyimpan!</strong> Harap periksa kembali data yang Anda masukkan.
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            {{-- KOLOM KIRI (INFORMASI & AKSI) --}}
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light fw-bold">Pengaturan Waktu Sesi (Default)</div>
                    <div class="card-body text-center">
                        <p class="mb-2">Sesi aktif dari pukul:</p>
                        <h3 class="fw-bold text-primary">
                            {{ \Carbon\Carbon::parse($defaultTimes['waktu_mulai'])->format('H:i') }} -
                            {{ \Carbon\Carbon::parse($defaultTimes['waktu_selesai'])->format('H:i') }}
                        </h3>
                        <p class="text-muted small mt-2">
                            Hari Kerja:
                            @php
                                $hariMapping = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
                                $hariKerjaAktif = [];
                                if (is_array($defaultTimes['hari_kerja'])) {
                                    foreach ($defaultTimes['hari_kerja'] as $hari) {
                                        // Pastikan index ada sebelum diakses
                                        if (isset($hariMapping[$hari - 1])) {
                                            $hariKerjaAktif[] = $hariMapping[$hari - 1];
                                        }
                                    }
                                }
                            @endphp
                            {{ !empty($hariKerjaAktif) ? implode(', ', $hariKerjaAktif) : 'Tidak ada' }}
                        </p>
                        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#defaultTimeModal">
                            <i class="fas fa-edit"></i> Ubah Waktu Default
                        </button>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light fw-bold">Aksi Cepat</div>
                    <div class="card-body d-grid gap-2">
                        <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#exceptionModal"><i
                                class="fas fa-calendar-day"></i> Buat Pengecualian Harian</button>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#calendarModal"><i
                                class="fas fa-calendar-alt"></i> Lihat Kalender Penuh</button>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN (STATUS 7 HARI) --}}
            <div class="col-lg-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light fw-bold">Status Sesi Absensi (7 Hari ke Depan)</div>
                    <div class="list-group list-group-flush">
                        @foreach ($upcoming_days as $day)
                            <div
                                class="list-group-item d-flex justify-content-between align-items-center @if ($day['date']->isToday()) list-group-item-primary @endif">
                                <div>
                                    <h6 class="mb-0">{{ $day['date']->isoFormat('dddd, D MMMM YYYY') }}</h6>
                                    {{-- [PERBAIKAN TAMPILAN] Tampilkan keterangan yang lebih informatif --}}
                                    <small class="text-muted fst-italic">{{ $day['status_info']['keterangan'] }}</small>
                                </div>
                                @if ($day['status_info']['is_active'])
                                    <span class="badge bg-success rounded-pill">Aktif</span>
                                @else
                                    {{-- [PERBAIKAN TAMPILAN] Beri warna berbeda untuk libur vs dinonaktifkan --}}
                                    @if (str_contains($day['status_info']['status'], 'Dinonaktifkan'))
                                        <span class="badge bg-danger rounded-pill">Non-Aktif</span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill">Libur</span>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= MODALS ================= --}}

    {{-- MODAL UBAH WAKTU DEFAULT --}}
    <div class="modal fade" id="defaultTimeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('sesi-absensi.storeOrUpdate') }}" method="POST">
                    @csrf
                    <input type="hidden" name="update_default" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title">Ubah Waktu Default Sesi</h5>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Perubahan ini akan berlaku untuk semua hari kerja yang tidak memiliki
                            pengaturan khusus.</p>
                        <div class="row">
                            <div class="col-6">
                                <label for="default_waktu_mulai" class="form-label">Waktu Mulai</label>
                                <input type="time" name="waktu_mulai" id="default_waktu_mulai" class="form-control"
                                    value="{{ old('waktu_mulai', \Carbon\Carbon::parse($defaultTimes['waktu_mulai'])->format('H:i')) }}"
                                    required>
                            </div>
                            <div class="col-6">
                                <label for="default_waktu_selesai" class="form-label">Waktu Selesai</label>
                                <input type="time" name="waktu_selesai" id="default_waktu_selesai" class="form-control"
                                    value="{{ old('waktu_selesai', \Carbon\Carbon::parse($defaultTimes['waktu_selesai'])->format('H:i')) }}"
                                    required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Hari Kerja Aktif</label>
                            <div class="border rounded p-2">
                                @php $hariKerja = old('hari_kerja', $defaultTimes['hari_kerja']); @endphp
                                @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $index => $hari)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="hari_kerja[]"
                                            value="{{ $index + 1 }}" id="hari_{{ $index + 1 }}"
                                            @if (is_array($hariKerja) && in_array($index + 1, $hariKerja)) checked @endif>
                                        <label class="form-check-label"
                                            for="hari_{{ $index + 1 }}">{{ $hari }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL PENGECUALIAN HARIAN --}}
    <div class="modal fade" id="exceptionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('sesi-absensi.storeOrUpdate') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Buat Pengecualian Sesi Harian</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Pilih Tanggal</label>
                            <input type="date" name="tanggal" class="form-control"
                                value="{{ old('tanggal', date('Y-m-d')) }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="tipe" class="form-label">Pilih Tindakan</label>
                            <select name="tipe" class="form-select" id="tipePengecualian">
                                <option value="aktif" @if (old('tipe') == 'aktif') selected @endif>Aktifkan Sesi (di
                                    Hari Libur)</option>
                                <option value="nonaktif" @if (old('tipe') == 'nonaktif') selected @endif>Nonaktifkan Sesi
                                    (di Hari Kerja)</option>
                                <option value="reset" @if (old('tipe') == 'reset') selected @endif>Reset ke Default
                                </option>
                            </select>
                        </div>
                        <div id="waktu-container-exception">
                            <p class="text-muted small">Atur waktu khusus untuk sesi yang diaktifkan ini.</p>
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label">Waktu Mulai</label>
                                    <input type="time" name="waktu_mulai" class="form-control"
                                        value="{{ old('waktu_mulai', '07:00') }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Waktu Selesai</label>
                                    <input type="time" name="waktu_selesai" class="form-control"
                                        value="{{ old('waktu_selesai', '17:00') }}">
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
                            <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Libur Cuti Bersama">{{ old('keterangan') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Pengecualian</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL KALENDER --}}
    <div class="modal fade" id="calendarModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kalender Sesi Absensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- FullCalendar JS --}}
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/bootstrap5@6.1.15/index.global.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Script untuk modal pengecualian harian
            const tipePengecualian = document.getElementById('tipePengecualian');
            const waktuContainer = document.getElementById('waktu-container-exception');

            function toggleWaktuInputs() {
                // Tampilkan input waktu hanya jika tipe adalah 'aktif'
                waktuContainer.style.display = tipePengecualian.value === 'aktif' ? 'block' : 'none';
            }
            tipePengecualian.addEventListener('change', toggleWaktuInputs);
            toggleWaktuInputs(); // Jalankan saat pertama kali load

            // Script untuk FullCalendar
            const calendarEl = document.getElementById('calendar');
            const calendarModalEl = document.getElementById('calendarModal');
            let calendar;

            calendarModalEl.addEventListener('shown.bs.modal', function() {
                if (!calendar) {
                    calendar = new FullCalendar.Calendar(calendarEl, {
                        themeSystem: 'bootstrap5',
                        initialView: 'dayGridMonth',
                        locale: 'id',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek'
                        },
                        events: '{{ route('sesi-absensi.calendar-events') }}',
                        eventDidMount: (info) => {
                            if (bootstrap.Tooltip) {
                                new bootstrap.Tooltip(info.el, {
                                    title: info.event.title,
                                    placement: 'top',
                                    trigger: 'hover',
                                    container: 'body'
                                });
                            }
                        },
                        // [PERBAIKAN TAMPILAN] Refresh events saat navigasi bulan
                        datesSet: function() {
                            calendar.refetchEvents();
                        }
                    });
                    calendar.render();
                }
                calendar.updateSize();
            });

            // [BUG FIX] Jika ada error validasi, buka kembali modal yang sesuai
            @if ($errors->any())
                // Cek apakah error berasal dari form default time
                @if ($errors->has('hari_kerja') || ($errors->has('waktu_mulai') && old('update_default')))
                    new bootstrap.Modal(document.getElementById('defaultTimeModal')).show();
                    // Jika tidak, asumsikan dari form pengecualian
                @else
                    new bootstrap.Modal(document.getElementById('exceptionModal')).show();
                @endif
            @endif
        });
    </script>
@endpush
