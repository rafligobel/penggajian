@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4 fw-bold text-primary">Kelola Gaji</h3>

        {{-- FORM FILTER UTAMA --}}
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <form method="GET" action="{{ route('gaji.index') }}" id="filter-form">
                    <div class="row align-items-end">
                        <div class="col-md-5">
                            <label for="bulan" class="form-label fw-bold">Pilih Periode Gaji</label>
                            <input type="month" class="form-control" id="bulan" name="bulan"
                                value="{{ $selectedMonth }}">
                        </div>
                        <div class="col-md-4">
                            <label for="jabatan_id" class="form-label fw-bold">Filter Jabatan</label>
                            <select name="jabatan_id" id="jabatan_id" class="form-select">
                                <option value="">Semua Jabatan</option>
                                @foreach ($jabatans as $jabatan)
                                    <option value="{{ $jabatan->id }}" @selected($jabatan->id == $selectedJabatanId)>
                                        {{ $jabatan->nama_jabatan }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Tampilkan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div id="ajax-response-message" class="alert" style="display:none;"></div>

        {{-- TABEL DATA GAJI --}}
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>Nama Karyawan</th>
                                <th class="text-center">Jml. Hadir</th>
                                <th class="text-end">Tunj. Kehadiran</th>
                                <th class="text-end">Total Gaji</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="gaji-table-body">
                            {{-- PERBAIKAN: Looping menggunakan variabel $dataGaji yang dikirim dari controller --}}
                            @forelse ($dataGaji as $gajiData)
                                @php
                                    // Variabel diambil dari hasil kalkulasi service, bukan query baru
                                    $karyawan = $gajiData['karyawan'];
                                    $gaji = $gajiData['gaji']; // Ini bisa null jika belum ada data di db
                                @endphp
                                <tr data-gaji-json="{{ json_encode($gajiData) }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $karyawan->nama }}</strong><br>
                                        <small class="text-muted">{{ $karyawan->jabatan->nama_jabatan ?? 'N/A' }}</small>
                                    </td>
                                    {{-- Menggunakan data yang sudah dihitung di service --}}
                                    <td class="text-center">{{ $gajiData['jumlah_kehadiran'] }}</td>
                                    <td class="text-end">Rp {{ number_format($gajiData['tunj_kehadiran'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold">Rp
                                            {{ number_format($gajiData['gaji_bersih'], 0, ',', '.') }}</span>
                                        @if ($gaji)
                                            <br>
                                            <small class="text-muted" style="font-size: 0.8em;">
                                                Diperbarui:
                                                {{ \Carbon\Carbon::parse($gaji->updated_at)->timezone('Asia/Makassar')->locale('id')->translatedFormat('d M Y H:i') }}
                                            </small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if (Auth::user()->role === 'bendahara')
                                            <button class="btn btn-sm btn-info btn-detail" title="Detail Gaji"
                                                data-bs-toggle="modal" data-bs-target="#detailModal"><i
                                                    class="fas fa-eye"></i></button>
                                            <button class="btn btn-sm btn-warning btn-edit" title="Kelola Gaji"
                                                data-bs-toggle="modal" data-bs-target="#editModal"><i
                                                    class="fas fa-edit"></i></button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center fst-italic py-4">Tidak ada data karyawan yang
                                        cocok dengan filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ============== MODALS ============== --}}
    {{-- Modal Detail --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Gaji Karyawan</h5><button type="button"
                        class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detail-content"></div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <div>
                        <button type="button" class="btn btn-success btn-download-slip" disabled><i
                                class="fas fa-download me-2"></i>Unduh Slip</button>
                        <button type="button" class="btn btn-primary btn-send-email" disabled><i
                                class="fas fa-paper-plane me-2"></i>Kirim ke Email</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="editGajiForm" action="{{ route('gaji.save') }}" method="POST">
                    @csrf
                    <input type="hidden" name="bulan" value="{{ $selectedMonth }}">
                    <input type="hidden" name="jabatan_id" value="{{ $selectedJabatanId }}">
                    <input type="hidden" id="edit-karyawan-id" name="karyawan_id" value="">

                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Kelola Gaji Karyawan</h5><button type="button"
                            class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-4"><label class="form-label">Periode</label><input type="text"
                                    id="periode-modal" class="form-control" readonly></div>
                            <div class="col-md-8">
                                <label for="tunjangan_kehadiran_id_modal" class="form-label fw-bold">Pilih Tunjangan
                                    Kehadiran</label>
                                <select name="tunjangan_kehadiran_id" id="tunjangan_kehadiran_id_modal"
                                    class="form-select" required>
                                    @foreach ($tunjanganKehadirans as $tunjangan)
                                        <option value="{{ $tunjangan->id }}">{{ $tunjangan->jenis_tunjangan }} (Rp
                                            {{ number_format($tunjangan->jumlah_tunjangan, 0, ',', '.') }}/hari)</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <hr>
                        {{-- Form dinamis akan diisi oleh JavaScript --}}
                        <div id="edit-form-content" class="row"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const detailModalEl = document.getElementById('detailModal');
            const editModalEl = document.getElementById('editModal');
            const responseMessageEl = document.getElementById('ajax-response-message');

            const formatRupiah = (angka) => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(angka || 0);

            function showResponseMessage(message, isSuccess = true) {
                responseMessageEl.textContent = message;
                responseMessageEl.className = isSuccess ? 'alert alert-info' : 'alert alert-danger';
                responseMessageEl.style.display = 'block';
                setTimeout(() => {
                    responseMessageEl.style.display = 'none';
                }, 5000);
            }

            function populateDetailModal(data) {
                const modal = detailModalEl;
                modal.querySelector('#detailModalLabel').textContent = `Detail Gaji: ${data.karyawan.nama}`;
                const detailContent = modal.querySelector('#detail-content');

                // PERBAIKAN: Logika untuk menampilkan info update di modal
                let updateInfo =
                    '<p class="text-muted fst-italic mb-0">Data gaji bulan ini belum pernah disimpan.</p>';
                if (data.gaji && data.gaji.updated_at) { // Cek apakah 'gaji' dan 'updated_at' ada
                    const updatedAt = new Date(data.gaji.updated_at);
                    const formattedDate = updatedAt.toLocaleString('id-ID', {
                        dateStyle: 'long',
                        timeStyle: 'short'
                    });
                    updateInfo = `<p class="text-muted mb-0">Terakhir diperbarui: ${formattedDate}</p>`;
                }

                const rincianHtml = (items) => items.map(item =>
                    `<div class="row mb-2"><div class="col-7">${item.label}</div><div class="col-5 text-end">${formatRupiah(item.value)}</div></div>`
                ).join('');

                detailContent.innerHTML = `
            <p><strong>Jabatan:</strong> ${data.karyawan.jabatan?.nama_jabatan || 'N/A'}</p><hr>
            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0 border-end">
                    <h5 class="mb-3 text-primary">A. Pendapatan</h5>
                    ${rincianHtml([
                        {label: 'Gaji Pokok', value: data.gaji_pokok},
                        {label: 'Tunjangan Jabatan', value: data.tunj_jabatan},
                        {label: 'Tunjangan Anak', value: data.tunj_anak},
                        {label: 'Tunjangan Komunikasi', value: data.tunj_komunikasi},
                        {label: 'Tunjangan Pengabdian', value: data.tunj_pengabdian},
                        {label: 'Tunjangan Kinerja', value: data.tunj_kinerja},
                        {label: `
                Tunj.Kehadiran($ {
                        data.jumlah_kehadiran
                    }
                    hari)`, value: data.tunj_kehadiran},
                        {label: 'Lembur', value: data.lembur},
                        {label: 'Kelebihan Jam', value: data.kelebihan_jam},
                    ])}
                </div>
                <div class="col-lg-6">
                    <h5 class="mb-3 text-danger">B. Potongan</h5>
                    <div class="row mb-2"><div class="col-7">Potongan Lain-lain</div><div class="col-5 text-end text-danger">(${formatRupiah(data.potongan)})</div></div>
                </div>
            </div><hr class="my-4">
            <div class="bg-light p-3 rounded">
                <div class="row align-items-center">
                    <div class="col-7"><h5 class="mb-0">GAJI BERSIH (A - B)</h5></div>
                    <div class="col-5 text-end"><h5 class="mb-0 fw-bold text-success">${formatRupiah(data.gaji_bersih)}</h5></div>
                </div>
            </div>
            <div class="mt-4 border-top pt-2 text-center small">${updateInfo}</div>`;

                const downloadBtn = modal.querySelector('.btn-download-slip');
                const emailBtn = modal.querySelector('.btn-send-email');

                // PERBAIKAN: Tombol aktif jika 'gaji' tidak null (artinya data sudah ada di DB)
                if (data.gaji) {
                    downloadBtn.disabled = false;
                    downloadBtn.dataset.url = `/gaji/${data.gaji.id}/download`;
                    emailBtn.disabled = !data.karyawan.email;
                    emailBtn.dataset.url = `/gaji/${data.gaji.id}/send-email`;
                } else {
                    downloadBtn.disabled = true;
                    emailBtn.disabled = true;
                }
            }

            function populateEditModal(data) {
                const modal = editModalEl;
                modal.querySelector('#editModalLabel').textContent = `Kelola Gaji: ${data.karyawan.nama}`;
                modal.querySelector('#periode-modal').value = new Date(data.bulan + '-02').toLocaleDateString(
                    'id-ID', {
                        month: 'long',
                        year: 'numeric'
                    });
                modal.querySelector('#edit-karyawan-id').value = data.karyawan.id;

                const formContent = modal.querySelector('#edit-form-content');
                const fields = [{
                        name: 'gaji_pokok',
                        label: 'Gaji Pokok'
                    },
                    {
                        name: 'tunj_jabatan',
                        label: 'Tunjangan Jabatan'
                    },
                    {
                        name: 'tunj_anak',
                        label: 'Tunjangan Anak'
                    },
                    {
                        name: 'tunj_komunikasi',
                        label: 'Tunj. Komunikasi'
                    },
                    {
                        name: 'tunj_pengabdian',
                        label: 'Tunj. Pengabdian'
                    },
                    {
                        name: 'tunj_kinerja',
                        label: 'Tunj. Kinerja'
                    },
                    {
                        name: 'lembur',
                        label: 'Lembur'
                    },
                    {
                        name: 'kelebihan_jam',
                        label: 'Kelebihan Jam'
                    },
                    {
                        name: 'potongan',
                        label: 'Potongan'
                    },
                ];

                let fieldsHtml = fields.map(f => `
            <div class="col-md-6 mb-3">
                <label class="form-label">${f.label}</label>
                <input type="number" name="${f.name}" class="form-control" value="${data[f.name] || 0}" required>
            </div>`).join('');

                // Tambahkan input hidden untuk tunjangan jabatan agar ikut terkirim
                fieldsHtml += `<input type="hidden" name="tunj_jabatan" value="${data.tunj_jabatan || 0}">`;

                formContent.innerHTML = fieldsHtml;
            }

            document.querySelectorAll('.btn-detail, .btn-edit').forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const gajiData = JSON.parse(row.getAttribute('data-gaji-json'));

                    if (this.classList.contains('btn-detail')) {
                        populateDetailModal(gajiData);
                    } else {
                        populateEditModal(gajiData);
                    }
                });
            });

            detailModalEl.addEventListener('click', function(event) {
                const actionButton = event.target.closest('.btn-download-slip, .btn-send-email');
                if (actionButton) {
                    const url = actionButton.dataset.url;
                    const originalHtml = actionButton.innerHTML;
                    actionButton.disabled = true;
                    actionButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json().then(data => ({
                            status: response.status,
                            body: data
                        })))
                        .then(({
                            status,
                            body
                        }) => {
                            showResponseMessage(body.message, status === 200);
                        }).catch(error => {
                            showResponseMessage('Terjadi kesalahan.', false);
                        }).finally(() => {
                            actionButton.innerHTML = originalHtml;
                            // Tombol tidak di-enable kembali secara otomatis untuk mencegah double click
                        });
                }
            });
        });
    </script>
@endpush
