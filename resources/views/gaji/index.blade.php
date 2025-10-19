@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4 fw-bold text-primary">Kelola Gaji</h3>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <form method="GET" action="{{ route('gaji.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="bulan" class="form-label fw-bold">Pilih Periode Gaji</label>
                            <div class="input-group">
                                <input type="month" class="form-control" id="bulan" name="bulan"
                                    value="{{ $selectedMonth }}">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>
                                    Tampilkan</button>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <label for="search-input" class="form-label fw-bold">Cari Karyawan</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
                                <input type="text" id="search-input" class="form-control border-start-0"
                                    placeholder="Ketik nama atau NIP karyawan...">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="gajiTable" class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class.text-center>No</th>
                                <th>Nama Karyawan</th>
                                <th>Jabatan</th>
                                <th>Gaji Pokok</th>
                                <th>Input Tunjangan & Potongan</th>
                                <th>Total Kehadiran</th>
                                <th>Tunj. Kehadiran</th>
                                <th>Total Tunjangan</th>
                                <th>Total Potongan</th>
                                <th>Gaji Kotor</th>
                                <th>Gaji Bersih</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dataGaji as $data)
                                <tr id="row-{{ $data['karyawan_id'] }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $data['nama'] }}</div>
                                        <small class="text-muted">{{ $data['jabatan'] }}</small>
                                    </td>
                                    <td>{{ $data['jabatan'] }}</td>
                                    <td>
                                        <span class="gaji-pokok-text">{{ $data['gaji_pokok_string'] }}</span>
                                    </td>
                                    <td>
                                        <form class="gaji-form" action="{{ route('gaji.saveOrUpdate') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="karyawan_id" value="{{ $data['karyawan_id'] }}">
                                            <input type="hidden" name="bulan" value="{{ $selectedMonth }}">
                                            <input type="hidden" name="gaji_pokok"
                                                value="{{ $data['gaji_pokok_numeric'] }}">

                                            <button type="button" class="btn btn-outline-primary btn-sm w-100"
                                                data-bs-toggle="modal"
                                                data-bs-target="#detailModal-{{ $data['karyawan_id'] }}">
                                                <i class="fas fa-edit me-1"></i> Input/Edit
                                            </button>

                                            <div class="modal fade" id="detailModal-{{ $data['karyawan_id'] }}"
                                                tabindex="-1" aria-labelledby="modalLabel-{{ $data['karyawan_id'] }}"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"
                                                                id="modalLabel-{{ $data['karyawan_id'] }}">
                                                                Edit Gaji: {{ $data['nama'] }}
                                                                ({{ \Carbon\Carbon::parse($selectedMonth)->isoFormat('MMMM YYYY') }})
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Tunjangan Anak</label>
                                                                    <input type="number" class="form-control"
                                                                        name="tunj_anak" value="{{ $data['tunj_anak'] }}">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Tunjangan Komunikasi</label>
                                                                    <input type="number" class="form-control"
                                                                        name="tunj_komunikasi"
                                                                        value="{{ $data['tunj_komunikasi'] }}">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Tunjangan Pengabdian</label>
                                                                    <input type="number" class="form-control"
                                                                        name="tunj_pengabdian"
                                                                        value="{{ $data['tunj_pengabdian'] }}">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Tunjangan Kinerja</label>
                                                                    <input type="number" class="form-control"
                                                                        name="tunj_kinerja"
                                                                        value="{{ $data['tunj_kinerja'] }}">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Lembur</label>
                                                                    <input type="number" class="form-control"
                                                                        name="lembur" value="{{ $data['lembur'] }}">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Potongan</label>
                                                                    <input type="number" class="form-control"
                                                                        name="potongan" value="{{ $data['potongan'] }}">
                                                                </div>
                                                                <div class="col-12">
                                                                    <label class="form-label">Jenis Tunjangan
                                                                        Kehadiran</label>
                                                                    <select name="tunjangan_kehadiran_id"
                                                                        class="form-select">
                                                                        @foreach ($tunjanganKehadirans as $tunjangan)
                                                                            <option value="{{ $tunjangan->id }}"
                                                                                {{ $data['tunjangan_kehadiran_id'] == $tunjangan->id ? 'selected' : '' }}>
                                                                                {{ $tunjangan->nama_tunjangan }} (Rp
                                                                                {{ number_format($tunjangan->jumlah_tunjangan, 0, ',', '.') }}/hari)
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Batal</button>
                                                            <button type="button"
                                                                class="btn btn-primary save-gaji-btn">Simpan
                                                                Perubahan</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="total-kehadiran-text">{{ $data['total_kehadiran'] }}</span> Hari
                                    </td>
                                    <td>
                                        <span
                                            class="tunjangan-per-kehadiran-text">{{ $data['tunjangan_per_kehadiran_string'] }}</span>
                                        / hari
                                        <br>
                                        <small class="text-success total-tunjangan-kehadiran-text">
                                            Total: {{ $data['total_tunjangan_kehadiran_string'] }}
                                        </small>
                                    </td>
                                    <td>
                                        <span class="total-tunjangan-text">{{ $data['total_tunjangan_string'] }}</span>
                                    </td>
                                    <td>
                                        <span
                                            class="text-danger total-potongan-text">{{ $data['total_potongan_string'] }}</span>
                                    </td>
                                    <td>
                                        <span class="gaji-kotor-text">{{ $data['gaji_kotor_string'] }}</span>
                                    </td>
                                    <td>
                                        <span
                                            class="fw-bold text-success gaji-bersih-text">{{ $data['gaji_bersih_string'] }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-info btn-slip"
                                                data-bs-toggle="modal" data-bs-target="#slipModal"
                                                data-gaji-id="{{ $data['gaji_id'] }}" data-nama="{{ $data['nama'] }}"
                                                data-download-url="{{ $data['gaji_id'] ? route('gaji.downloadSlip', $data['gaji_id']) : '' }}"
                                                data-email-url="{{ $data['gaji_id'] ? route('gaji.sendEmail', $data['gaji_id']) : '' }}"
                                                {{ !$data['gaji_id'] ? 'disabled' : '' }}>
                                                <i class="fas fa-receipt"></i> Slip
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="slipModal" tabindex="-1" aria-labelledby="slipModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="slipModalLabel">Opsi Slip Gaji</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Silakan pilih aksi untuk slip gaji <strong id="namaKaryawanSlip"></strong>.</p>
                    <p class="text-muted small">
                        Proses ini berjalan di latar belakang (menggunakan antrian). Anda akan mendapat notifikasi
                        setelah selesai.
                    </p>
                    <div class="d-grid gap-2">
                        <button id="downloadSlipBtn" class="btn btn-info text-white"><i class="fas fa-download me-2"></i>
                            Buat & Unduh PDF</button>
                        <button id="emailSlipBtn" class="btn btn-success"><i class="fas fa-envelope me-2"></i> Kirim
                            ke Email</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Inisialisasi DataTable
            var table = $('#gajiTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
                },
                "pageLength": 10,
                "lengthMenu": [10, 25, 50, 100],
                "ordering": false,
                "columnDefs": [{
                    "targets": [0, 4, 11],
                    "searchable": false
                }]
            });

            // Fungsi pencarian kustom
            $('#search-input').on('keyup', function() {
                table.search(this.value).draw();
            });

            // AJAX UNTUK SIMPAN Gaji
            $(document).on('click', '.save-gaji-btn', function() {
                var $button = $(this);
                var $modal = $button.closest('.modal');
                var $row = $('#row-' + $modal.find('input[name="karyawan_id"]').val());
                var form = $modal.find('.gaji-form');

                if (!form.length) {
                    console.error('Form tidak ditemukan');
                    return;
                }

                var originalButtonHtml = $button.html();
                $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            var data = response.newData;

                            // 1. Update Teks Tampilan (readonly) di tabel
                            $row.find('.gaji-pokok-text').text(data.gaji_pokok_string);
                            $row.find('.total-kehadiran-text').text(data.total_kehadiran);
                            $row.find('.tunjangan-per-kehadiran-text').text(data
                                .tunjangan_per_kehadiran_string);
                            $row.find('.total-tunjangan-kehadiran-text').text('Total: ' + data
                                .total_tunjangan_kehadiran_string);
                            $row.find('.total-tunjangan-text').text(data
                            .total_tunjangan_string);
                            $row.find('.total-potongan-text').text(data.total_potongan_string);
                            $row.find('.gaji-kotor-text').text(data.gaji_kotor_string);
                            $row.find('.gaji-bersih-text').text(data.gaji_bersih_string);

                            // 2. Update Nilai Input (di dalam modal)
                            $modal.find('input[name="gaji_pokok"]').val(data
                            .gaji_pokok_numeric);
                            $modal.find('input[name="tunj_anak"]').val(data.tunj_anak);
                            $modal.find('input[name="tunj_komunikasi"]').val(data
                                .tunj_komunikasi);
                            $modal.find('input[name="tunj_pengabdian"]').val(data
                                .tunj_pengabdian);
                            $modal.find('input[name="tunj_kinerja"]').val(data.tunj_kinerja);
                            $modal.find('input[name="lembur"]').val(data.lembur);
                            $modal.find('input[name="potongan"]').val(data.potongan);
                            $modal.find('select[name="tunjangan_kehadiran_id"]').val(data
                                .tunjangan_kehadiran_id);

                            // 3. Update tombol slip agar aktif
                            var $slipButton = $row.find('.btn-slip');
                            if (data.gaji_id) {
                                $slipButton.prop('disabled', false);
                                $slipButton.data('gaji-id', data.gaji_id);
                                $slipButton.data('download-url', '{{ url('gaji') }}/' + data
                                    .gaji_id + '/download-slip');
                                $slipButton.data('email-url', '{{ url('gaji') }}/' + data
                                    .gaji_id + '/send-email');
                            }

                            var bootstrapModal = bootstrap.Modal.getInstance($modal[0]);
                            if (bootstrapModal) {
                                bootstrapModal.hide();
                            }

                            toastr.success(response.message || 'Data berhasil disimpan.');
                        } else {
                            toastr.error(response.message || 'Gagal menyimpan data.');
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = 'Terjadi kesalahan server.';
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            errorMsg = 'Gagal menyimpan. Periksa input Anda:';

                            var errorList = '<ul class="text-start">';
                            $.each(errors, function(key, value) {
                                var fieldName = key.replace(/_/g, ' ');
                                fieldName = fieldName.charAt(0).toUpperCase() +
                                    fieldName.slice(1);
                                errorList += '<li>' + fieldName + ': ' + value[0] +
                                    '</li>';
                            });
                            errorList += '</ul>';

                            toastr.error(errorMsg + errorList, "Error Validasi", {
                                timeOut: 8000
                            });
                        } else {
                            toastr.error(errorMsg);
                        }
                    },
                    complete: function() {
                        $button.prop('disabled', false).html(originalButtonHtml);
                    }
                });
            });


            // Handler untuk Modal Slip Gaji
            var slipModalElem = document.getElementById('slipModal');
            if (slipModalElem) {
                var slipModal = new bootstrap.Modal(slipModalElem);
                var downloadBtn = document.getElementById('downloadSlipBtn');
                var emailBtn = document.getElementById('emailSlipBtn');
                var currentDownloadUrl, currentEmailUrl;

                slipModalElem.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var nama = button.getAttribute('data-nama');
                    var gajiId = button.getAttribute('data-gaji-id');

                    document.getElementById('namaKaryawanSlip').innerText = nama;

                    currentDownloadUrl = button.getAttribute('data-download-url');
                    currentEmailUrl = button.getAttribute('data-email-url');

                    if (gajiId && currentDownloadUrl && currentEmailUrl) {
                        downloadBtn.disabled = false;
                        emailBtn.disabled = false;
                    } else {
                        downloadBtn.disabled = true;
                        emailBtn.disabled = true;
                    }
                });

                function showResponseMessage(message, isSuccess) {
                    if (isSuccess) {
                        toastr.success(message);
                    } else {
                        toastr.error(message);
                    }
                }

                function handleJobDispatch(button, url, processName, e) {
                    e.preventDefault();
                    if (!url) return;

                    var originalButtonHtml = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Memproses...`;

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                // Coba baca JSON error jika ada
                                return response.json().then(err => {
                                    throw new Error(err.message || 'Server error')
                                });
                            }
                            return response.json();
                        })
                        .then(apiData => {
                            if (apiData.message) {
                                showResponseMessage(apiData.message, true);
                                slipModal.hide();
                            } else {
                                showResponseMessage('Terjadi kesalahan.', false);
                            }
                        })
                        .catch(error => {
                            console.error(`Error ${processName}:`, error);
                            showResponseMessage(error.message || `Gagal memulai proses ${processName}.`, false);
                        })
                        .finally(() => {
                            button.disabled = false;
                            button.innerHTML = originalButtonHtml;
                        });
                }

                downloadBtn.addEventListener('click', function(e) {
                    handleJobDispatch(this, currentDownloadUrl, 'unduh slip', e);
                });

                emailBtn.addEventListener('click', function(e) {
                    handleJobDispatch(this, currentEmailUrl, 'kirim email', e);
                });
            }
        });
    </script>
@endpush
