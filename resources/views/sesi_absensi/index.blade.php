@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <h3 class="mb-4 fw-bold text-primary">Keterangan Data Sesi Absensi</h3>
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Menampilkan error validasi jika ada --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Gagal menyimpan!</strong> Harap periksa kembali data yang Anda masukkan.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
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
                            @forelse ($defaultTimes['hari_kerja'] as $hari)
                                {{ ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'][$hari - 1] }}{{ !$loop->last ? ',' : '' }}
                            @empty
                                Tidak ada
                            @endforelse
                        </p>
                        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#defaultTimeModal">
                            <i class="fas fa-edit"></i> Ubah Waktu Keseluruhan
                        </button>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light fw-bold">Aksi Cepat</div>
                    <div class="card-body d-grid gap-2">
                        <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#exceptionModal"><i
                                class="fas fa-exchange-alt"></i> Ubah Sesi Harian</button>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#calendarModal"><i
                                class="fas fa-calendar-alt"></i> Lihat Kalender Sesi Lengkap</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light fw-bold">Status Sesi Absensi (7 Hari ke Depan)</div>
                    <div class="list-group list-group-flush">
                        @foreach ($upcoming_days as $day)
                            <div
                                class="list-group-item d-flex justify-content-between align-items-center @if ($day['date']->isToday()) list-group-item-primary @endif">
                                <div>
                                    <h6 class="mb-0">{{ $day['date']->isoFormat('dddd, D MMMM YYYY') }}</h6>
                                    <small class="text-muted">{{ $day['status_info']['keterangan'] ?? '' }}</small>
                                </div>
                                @if ($day['status_info']['is_active'])
                                    <span class="badge bg-success rounded-pill">Aktif</span>
                                @else
                                    <span class="badge bg-secondary rounded-pill">Tidak Aktif</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- MODAL UBAH WAKTU KESELURUHAN (DEFAULT) --}}
    <div class="modal fade" id="defaultTimeModal" tabindex="-1" aria-labelledby="defaultTimeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('sesi-absensi.update-default-time') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="defaultTimeModalLabel">Ubah Waktu Default Sesi</h5>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">Perubahan ini akan berlaku untuk semua hari kerja yang tidak memiliki
                            pengaturan khusus.</p>
                        <div class="row">
                            <div class="col-6">
                                <label for="default_waktu_mulai" class="form-label">Waktu Mulai</label>
                                <input type="time" name="waktu_mulai" id="default_waktu_mulai"
                                    class="form-control @error('waktu_mulai') is-invalid @enderror"
                                    value="{{ old('waktu_mulai', $defaultTimes['waktu_mulai']) }}">
                                @error('waktu_mulai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6">
                                <label for="default_waktu_selesai" class="form-label">Waktu Selesai</label>
                                <input type="time" name="waktu_selesai" id="default_waktu_selesai"
                                    class="form-control @error('waktu_selesai') is-invalid @enderror"
                                    value="{{ old('waktu_selesai', $defaultTimes['waktu_selesai']) }}">
                                @error('waktu_selesai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label class="form-label">Hari Kerja Aktif</label>
                            <div class="border rounded p-2">
                                <div class="row">
                                    @php $hariKerja = old('hari_kerja', $defaultTimes['hari_kerja']); @endphp
                                    @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $index => $hari)
                                        <div class="col-lg-4 col-6">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="hari_kerja[]"
                                                    value="{{ $index + 1 }}" id="hari_{{ $index + 1 }}"
                                                    @if (is_array($hariKerja) && in_array($index + 1, $hariKerja)) checked @endif>
                                                <label class="form-check-label"
                                                    for="hari_{{ $index + 1 }}">{{ $hari }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
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

    {{-- MODAL UBAH SESI HARIAN (PENGECUALIAN) --}}
    <div class="modal fade" id="exceptionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('sesi-absensi.store-exception') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Ubah Sesi Harian</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Pilih Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="tipe" class="form-label">Pilih Tindakan</label>
                            <select name="tipe" class="form-select" id="tipePengecualian">
                                <option value="aktif">Aktifkan Sesi (di Hari Libur)</option>
                                <option value="nonaktif">Nonaktifkan Sesi (di Hari Kerja)</option>
                                <option value="reset">Reset ke Default</option>
                            </select>
                        </div>
                        <div id="waktu-container-exception">
                            <p class="text-muted small">Atur waktu khusus untuk sesi yang diaktifkan ini.</p>
                            <div class="row">
                                <div class="col-6"><label class="form-label">Waktu Mulai</label><input type="time"
                                        name="waktu_mulai" class="form-control" value="07:00"></div>
                                <div class="col-6"><label class="form-label">Waktu Selesai</label><input type="time"
                                        name="waktu_selesai" class="form-control" value="17:00"></div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
                            <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Lembur proyek A"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL KALENDER --}}
    <div class="modal fade" id="calendarModal" tabindex="-1" aria-labelledby="calendarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="calendarModalLabel">Kalender Sesi Absensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
            const tanggalPengecualian = document.querySelector('#exceptionModal input[name="tanggal"]');

            // Set tanggal default ke hari ini
            if (tanggalPengecualian) {
                tanggalPengecualian.valueAsDate = new Date();
            }

            if (tipePengecualian) {
                // Fungsi untuk menampilkan/menyembunyikan input waktu
                const toggleWaktuContainer = () => {
                    waktuContainer.style.display = tipePengecualian.value === 'aktif' ? 'block' : 'none';
                };
                tipePengecualian.addEventListener('change', toggleWaktuContainer);
                // Panggil sekali saat halaman dimuat
                toggleWaktuContainer();
            }

            // Script untuk FullCalendar
            const calendarEl = document.getElementById('calendar');
            const calendarModalEl = document.getElementById('calendarModal');
            let calendar;

            if (calendarModalEl) {
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
                            eventDidMount: function(info) {
                                if (typeof bootstrap !== 'undefined') {
                                    new bootstrap.Tooltip(info.el, {
                                        title: info.event.title,
                                        placement: 'top',
                                        trigger: 'hover',
                                        container: 'body'
                                    });
                                }
                            }
                        });
                        calendar.render();
                    }
                    calendar.updateSize();
                });
            }

            // Jika ada error validasi, secara otomatis buka kembali modal yang relevan
            @if ($errors->any())
                var defaultTimeModal = new bootstrap.Modal(document.getElementById('defaultTimeModal'));
                defaultTimeModal.show();
            @endif
        });
    </script>
@endpush
