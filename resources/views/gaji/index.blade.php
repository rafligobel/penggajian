@extends('layouts.app')

@section('content')
    <div class="container ">
        <h3 class="fw-bold text-primary">Kelola Gaji</h3>

        {{-- ================================================================== --}}
        {{-- ========================== BAGIAN FILTER ========================= --}}
        {{-- ================================================================== --}}
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
                            <label for="search-input" class="form-label fw-bold">Cari Pegawai</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
                                <input type="text" id="search-input" class="form-control border-start-0"
                                    placeholder="Ketik nama atau jabatan karyawan...">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div id="ajax-response-message" class="alert" style="display:none;"></div>

        {{-- ================================================================== --}}
        {{-- ========================== BAGIAN TABEL ========================== --}}
        {{-- ================================================================== --}}
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>Nama Pegawai</th>
                                <th class="text-end">Gaji Pokok</th>
                                <th class="text-end">Tunj. Jabatan</th>
                                <th class="text-end">Gaji Bersih</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="gaji-table-body">
                            @forelse ($dataGaji as $gajiData)
                                <tr data-gaji-json="{{ json_encode($gajiData) }}" class="karyawan-row"
                                    data-karyawan-id="{{ $gajiData['karyawan_id'] }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong class="nama-karyawan">{{ $gajiData['nama'] }}</strong><br>
                                        <small class="text-muted nip-karyawan">NP: {{ $gajiData['nip'] }}</small>
                                    </td>
                                    <td class="text-end gaji-pokok-col">
                                        {{ $gajiData['gaji_pokok_string'] }}
                                    </td>
                                    <td class="text-end tunj-jabatan-col">
                                        {{ $gajiData['tunj_jabatan_string'] }}
                                    </td>
                                    <td class="text-end fw-bold gaji-bersih-col">
                                        <span
                                            class="badge {{ $gajiData['gaji_id'] ? 'bg-success' : 'bg-light text-dark' }}">{{ $gajiData['gaji_bersih_string'] }}</span>
                                    </td>
                                    <td class="text-center status-col">
                                        @if ($gajiData['gaji_id'])
                                            <span class="badge bg-primary">Sudah Diproses</span>
                                        @else
                                            <span class="badge bg-secondary">Belum Diproses</span>
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
                                    <td colspan="7" class="text-center fst-italic py-4">Tidak ada data pegawai yang
                                        aktif.</td>
                                </tr>
                            @endforelse
                            <tr id="no-search-results" style="display: none;">
                                <td colspan="7" class="text-center fst-italic py-4">Karyawan tidak ditemukan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================================================================== --}}
    {{-- ======================= MODAL DETAIL (LENGKAP) ===================== --}}
    {{-- ==================================================================== --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Gaji: <span id="detail-nama-title"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detail-content">
                    <p>
                        <strong>Jabatan:</strong> <span id="detail-jabatan">-</span><br>
                        <strong>Periode:</strong> <span id="detail-periode">-</span>
                    </p>
                    <hr>
                    <div class="row">
                        <div class="col-lg-6 mb-4 mb-lg-0 border-end">
                            <h5 class="mb-3 text-primary">A. Pendapatan</h5>
                            <div class="row mb-2">
                                <div class="col-7">Gaji Pokok</div>
                                <div class="col-5 text-end"><strong id="detail-gaji-pokok">Rp 0</strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-7">Tunjangan Jabatan</div>
                                <div class="col-5 text-end"><strong id="detail-tunj-jabatan">Rp 0</strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-7">Tunjangan Anak</div>
                                <div class="col-5 text-end"><strong id="detail-tunj-anak">Rp 0</strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-7">Tunjangan Komunikasi</div>
                                <div class="col-5 text-end"><strong id="detail-tunj-komunikasi">Rp 0</strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-7">Tunjangan Pengabdian</div>
                                <div class="col-5 text-end"><strong id="detail-tunj-pengabdian">Rp 0</strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-7">Tunjangan Kinerja</div>
                                <div class="col-5 text-end"><strong id="detail-tunj-kinerja">Rp 0</strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-7">Tunj. Kehadiran (<span id="detail-total-kehadiran">0</span> hari)
                                </div>
                                <div class="col-5 text-end"><strong id="detail-tunj-kehadiran">Rp 0</strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-7">Lembur</div>
                                <div class="col-5 text-end"><strong id="detail-lembur">Rp 0</strong></div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <h5 class="mb-3 text-danger">B. Potongan</h5>
                            <div class="row mb-2">
                                <div class="col-7">Potongan Lain-lain</div>
                                <div class="col-5 text-end"><strong class="text-danger" id="detail-potongan">(Rp
                                        0)</strong></div>
                            </div>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="bg-light p-3 rounded">
                        <div class="row align-items-center">
                            <div class="col-7">
                                <h5 class="mb-0">GAJI BERSIH (A - B)</h5>
                            </div>
                            <div class="col-5 text-end">
                                <h5 class="mb-0 fw-bold text-success" id="detail-gaji-bersih">Rp 0</h5>
                            </div>
                        </div>
                    </div>
                </div>
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

    {{-- ==================================================================== --}}
    {{-- ================= MODAL EDIT (REVISI SEMPURNA) ===================== --}}
    {{-- ==================================================================== --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="editGajiForm" action="{{ route('gaji.save') }}" method="POST">
                    @csrf
                    <input type="hidden" name="bulan" value="{{ $selectedMonth }}">
                    <input type="hidden" id="edit-karyawan-id" name="karyawan_id">

                    {{-- Hidden Fields untuk menyimpan nilai tarif saat submit (Data dari Controller) --}}
                    <input type="hidden" id="hidden-tarif-lembur" value="0">
                    <input type="hidden" id="hidden-tarif-potongan" value="0">

                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold" id="editModalLabel">Kelola Gaji Pegawai</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            {{-- ================= KIRI: DATA POKOK & KINERJA ================= --}}
                            <div class="col-lg-6 border-end">
                                <h6 class="fw-bold text-primary mb-3">1. Data Pokok & Kinerja</h6>

                                <div class="mb-3">
                                    <label class="form-label small text-muted">Gaji Pokok (Master Data)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light">Rp</span>
                                        <input type="text" class="form-control fw-bold" id="edit-gaji-pokok-display"
                                            readonly>
                                        {{-- Hidden input untuk kirim nilai asli (angka) --}}
                                        <input type="hidden" name="gaji_pokok" id="edit-gaji-pokok">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small text-muted">Tunjangan Kehadiran</label>
                                    <select name="tunjangan_kehadiran_id" id="tunjangan_kehadiran_id_modal"
                                        class="form-select form-select-sm" required onchange="calculateLive()">
                                        @foreach ($tunjanganKehadirans as $tunjangan)
                                            <option value="{{ $tunjangan->id }}" data-nominal="{{ $tunjangan->jumlah_tunjangan }}">
                                                {{ $tunjangan->jenis_tunjangan }}
                                                ({{ 'Rp ' . number_format($tunjangan->jumlah_tunjangan, 0, ',', '.') }}/hari)
                                            </option>
                                        @endforeach
                                    </select>
                                    {{-- Info Estimasi Total Tunj. Kehadiran --}}
                                    <div class="mt-1 d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><i class="fas fa-calculator me-1"></i>Estimasi (<span id="info-total-hadir">0</span> hari)</small>
                                        <span class="fw-bold text-success small" id="display-total-tunj-kehadiran">Rp 0</span>
                                        {{-- Hidden input untuk menyimpan kehadiran bersih pegawai (Total Hari - Alpha) utk perhitungan JS --}}
                                        <input type="hidden" id="hidden-total-kehadiran-bersih" value="0">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small text-muted">Penilaian Kinerja (Maksimal: Rp
                                        {{ number_format($aturanKinerja->maksimal_tunjangan ?? 0, 0, ',', '.') }})</label>
                                    <div class="card bg-light border-0 p-2">
                                        @forelse ($indikatorKinerjas as $indikator)
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <small>{{ $indikator->nama_indikator }}</small>
                                                <div class="input-group input-group-sm" style="width: 100px;">
                                                    <input type="number" class="form-control score-input text-center p-1"
                                                        name="scores[{{ $indikator->id }}]"
                                                        data-indikator-id="{{ $indikator->id }}" value="0"
                                                        min="0" max="100">
                                                    <span class="input-group-text px-1">%</span>
                                                </div>
                                            </div>
                                        @empty
                                            <small class="text-danger fst-italic">Belum ada indikator kinerja yang
                                                diatur.</small>
                                        @endforelse
                                    </div>
                                </div>

                                {{-- Info Tunjangan Otomatis (Readonly) --}}
                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="form-label small text-muted">Tunj. Jabatan</label>
                                        <input type="text" class="form-control form-control-sm bg-white"
                                            id="edit-tunj-jabatan" readonly>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small text-muted">Tunj. Anak</label>
                                        <input type="text" class="form-control form-control-sm bg-white"
                                            id="edit-tunj-anak" readonly>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small text-muted">Tunj. Pengabdian</label>
                                        <input type="text" class="form-control form-control-sm bg-white"
                                            id="edit-tunj-pengabdian" readonly>
                                    </div>
                                </div>
                            </div>

                            {{-- ================= KANAN: LEMBUR & POTONGAN (OTOMATIS) ================= --}}
                            <div class="col-lg-6 ps-lg-4">
                                <h6 class="fw-bold text-success mb-3">2. Kalkulasi Lembur & Potongan</h6>

                                {{-- SECTION LEMBUR --}}
                                <div class="card mb-3 border-success">
                                    <div class="card-header bg-success text-white py-1"><small><i
                                                class="fas fa-clock me-1"></i> Perhitungan Lembur</small></div>
                                    <div class="card-body p-2">
                                        <div class="row mb-2 border-bottom pb-2">
                                            <div class="col-6">
                                                <label class="form-label small">Jam Lembur</label>
                                                <div class="input-group input-group-sm">
                                                    {{-- Input Jam: Bendahara mengisi ini --}}
                                                    <input type="number" class="form-control" id="input-jam-lembur"
                                                        name="jam_lembur" min="0" step="0.5" value="0" oninput="calculateLive()">
                                                    <span class="input-group-text">Jam</span>
                                                </div>
                                                <small class="text-muted" style="font-size: 10px;">Tarif: Rp <span
                                                        id="display-tarif-lembur">0</span>/jam</small>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-bold text-success">Total Uang
                                                    Lembur</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-light fw-bold text-success">Rp</span>
                                                    {{-- Input Nominal: Hasil perkalian otomatis (Readonly), dikirim ke DB sebagai 'lembur_nominal_manual' --}}
                                                    <input type="number" class="form-control fw-bold text-success"
                                                        id="input-lembur-nominal" name="lembur_nominal_manual" readonly
                                                        value="0">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- SECTION POTONGAN --}}
                                <div class="card mb-3 border-danger">
                                    <div class="card-header bg-danger text-white py-1"><small><i
                                                class="fas fa-minus-circle me-1"></i> Perhitungan Potongan</small></div>
                                    <div class="card-body p-2">
                                        {{-- Potongan Absen (Otomatis dari Data Absensi) --}}
                                        <div class="row mb-2 border-bottom pb-2">
                                            <div class="col-6">
                                                <label class="form-label small">Tidak Hadir (Alpha)</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control bg-white"
                                                        id="info-jumlah-alpha" readonly value="0">
                                                    <span class="input-group-text">Hari</span>
                                                </div>
                                                <small class="text-muted" style="font-size: 10px;">Tarif: Rp <span
                                                        id="display-tarif-potongan">0</span>/hari</small>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small">Potongan Absen</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="text" class="form-control bg-white"
                                                        id="info-potongan-absen" readonly value="0">
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Potongan Lain & Total --}}
                                        <div class="row align-items-end">
                                            <div class="col-6">
                                                <label class="form-label small">Potongan Lainnya (Manual)</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" class="form-control" id="input-potongan-lain"
                                                        value="0" min="0" oninput="calculateLive()">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-bold text-danger">Total Potongan</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-light fw-bold text-danger">Rp</span>
                                                    {{-- Input Total: Dikirim ke server sebagai 'potongan' --}}
                                                    <input type="number" class="form-control fw-bold text-danger"
                                                        id="input-total-potongan" name="potongan" readonly
                                                        value="0">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Tunjangan Komunikasi --}}
                                <div class="mb-2">
                                    <label class="form-label small text-muted">Tunjangan Komunikasi</label>
                                    <select class="form-select form-select-sm" id="modalTunjanganKomunikasi"
                                        name="tunjangan_komunikasi_id">
                                        <option value="">-- Tidak Dapat --</option>
                                        @foreach ($tunjanganKomunikasis as $item)
                                            <option value="{{ $item->id }}">{{ $item->nama_level }} (Rp
                                                {{ number_format($item->besaran, 0, ',', '.') }})</option>
                                        @endforeach
                                    </select>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary px-4"><i class="fas fa-save me-1"></i>
                            Simpan Data Gaji</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // ================== DATA MASTER DARI CONTROLLER ==================
        const masterTunjanganKomunikasi = @json($tunjanganKomunikasis ?? []);

        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi Modal Bootstrap
            const detailModalEl = document.getElementById('detailModal');
            const editModalEl = document.getElementById('editModal');
            const detailModal = new bootstrap.Modal(detailModalEl);
            const editModal = new bootstrap.Modal(editModalEl);

            const editGajiForm = document.getElementById('editGajiForm');
            const responseMessageEl = document.getElementById('ajax-response-message');

            // ================== HELPER FORMATTER ==================
            const formatRupiah = (angka) => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(angka || 0);

            const formatRupiahNoSymbol = (angka) => new Intl.NumberFormat('id-ID').format(angka || 0);

            // Helper untuk input readonly (menghapus simbol Rp untuk value input biasa jika perlu)
            const formatRupiahInput = (angka) => {
                return formatRupiah(angka).replace('Rp', '').trim();
            };

            function showResponseMessage(message, isSuccess = true) {
                responseMessageEl.textContent = message;
                responseMessageEl.className = isSuccess ? 'alert alert-success' : 'alert alert-danger';
                responseMessageEl.style.display = 'block';
                setTimeout(() => responseMessageEl.style.display = 'none', 5000);
            }

            // ================== 1. LOGIKA LIVE CALCULATION (NEW FEATURE) ==================
            window.calculateLive = function() {
                // --- A. Hitung Lembur ---
                const jamLembur = parseFloat(document.getElementById('input-jam-lembur').value) || 0;
                const tarifLembur = parseFloat(document.getElementById('hidden-tarif-lembur').value) || 0;

                // Total Lembur = Jam * Tarif
                const totalLembur = Math.round(jamLembur * tarifLembur);
                const inputLemburNominal = document.getElementById('input-lembur-nominal');
                if(inputLemburNominal) inputLemburNominal.value = totalLembur;

                // --- B. Hitung Potongan ---
                // 1. Potongan Absen (Fix dari Data Absensi)
                const jumlahAlpha = parseFloat(document.getElementById('info-jumlah-alpha').value) || 0;
                const tarifPotongan = parseFloat(document.getElementById('hidden-tarif-potongan').value) || 0;

                const potonganAbsen = Math.round(jumlahAlpha * tarifPotongan);
                // Tampilkan format rupiah di field info (readonly)
                const infoPotonganAbsen = document.getElementById('info-potongan-absen');
                if(infoPotonganAbsen) infoPotonganAbsen.value = formatRupiahNoSymbol(potonganAbsen);

                // 2. Potongan Lain (Manual Input)
                const potonganLain = parseFloat(document.getElementById('input-potongan-lain').value) || 0;

                // 3. Total Potongan = Absen + Lainnya
                const totalPotongan = potonganAbsen + potonganLain;
                const inputTotalPotongan = document.getElementById('input-total-potongan');
                if(inputTotalPotongan) inputTotalPotongan.value = totalPotongan;

                // --- C. Hitung Tunjangan Kehadiran (New!) ---
                const selectTunjangan = document.getElementById('tunjangan_kehadiran_id_modal');
                const totalHadirBersih = parseFloat(document.getElementById('hidden-total-kehadiran-bersih').value) || 0;
                
                if (selectTunjangan && selectTunjangan.selectedIndex >= 0) {
                    const selectedOption = selectTunjangan.options[selectTunjangan.selectedIndex];
                    const tarifPerHari = parseFloat(selectedOption.getAttribute('data-nominal')) || 0;
                    
                    const totalTunjanganHadir = Math.round(totalHadirBersih * tarifPerHari);
                    
                    // Update UI
                    const displayTotalHadir = document.getElementById('display-total-tunj-kehadiran');
                    if(displayTotalHadir) displayTotalHadir.textContent = formatRupiah(totalTunjanganHadir);
                    
                    // Update Info Hari
                    const infoTotalHadir = document.getElementById('info-total-hadir');
                    if(infoTotalHadir) infoTotalHadir.textContent = totalHadirBersih;
                }
            };
            
            // ... (Event Listeners tetap sama) ...
            // Pasang Event Listener untuk kalkulasi otomatis saat user mengetik (Backup listener)
            const inputJamLembur = document.getElementById('input-jam-lembur');
            const inputPotonganLain = document.getElementById('input-potongan-lain');

            if (inputJamLembur) inputJamLembur.addEventListener('input', window.calculateLive);
            if (inputPotonganLain) inputPotonganLain.addEventListener('input', window.calculateLive);


            // ================== 2. POPULATE MODAL EDIT ==================
            editModalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (!button) return;

                const row = button.closest('tr.karyawan-row');
                if (!row) return;

                const data = JSON.parse(row.getAttribute('data-gaji-json'));

                // A. Isi Header & ID
                document.getElementById('editModalLabel').textContent = `Kelola Gaji: ${data.nama}`;
                document.getElementById('edit-karyawan-id').value = data.karyawan_id;

                // B. Isi Data Pokok
                document.getElementById('edit-gaji-pokok-display').value = formatRupiahNoSymbol(data
                    .gaji_pokok);
                document.getElementById('edit-gaji-pokok').value = data.gaji_pokok; // Hidden value

                document.getElementById('edit-tunj-jabatan').value = data.tunj_jabatan_string;
                document.getElementById('edit-tunj-anak').value = data.tunj_anak_string;
                document.getElementById('edit-tunj-pengabdian').value = data.tunj_pengabdian_string;

                // C. Isi Dropdown
                const tunjKehadiranSelect = document.getElementById('tunjangan_kehadiran_id_modal');
                if (data.tunjangan_kehadiran_id) {
                    tunjKehadiranSelect.value = data.tunjangan_kehadiran_id;
                } else if (tunjKehadiranSelect.options.length > 0) {
                    tunjKehadiranSelect.value = tunjKehadiranSelect.options[0].value;
                }
                document.getElementById('modalTunjanganKomunikasi').value = data.tunjangan_komunikasi_id ||
                    '';

                // D. SETUP DATA PENDUKUNG & TARIF (INTEGRASI CONTROLLER)
                // Pastikan data_pendukung ada (dikirim dari Controller)
                const pendukung = data.data_pendukung || {
                    jumlah_alpha: 0,
                    tarif_potongan_absen: 0,
                    tarif_lembur_per_jam: 0
                };

                // Set Tarif ke Hidden Input & Tampilan
                document.getElementById('hidden-tarif-lembur').value = pendukung.tarif_lembur_per_jam;
                document.getElementById('display-tarif-lembur').textContent = formatRupiahNoSymbol(pendukung
                    .tarif_lembur_per_jam);

                document.getElementById('hidden-tarif-potongan').value = pendukung.tarif_potongan_absen;
                document.getElementById('display-tarif-potongan').textContent = formatRupiahNoSymbol(
                    pendukung.tarif_potongan_absen);

                // Set Jumlah Alpha (Readonly)
                document.getElementById('info-jumlah-alpha').value = pendukung.jumlah_alpha;
                
                // Set Total Kehadiran Bersih (Untuk Kalkulasi Tunjangan Kehadiran)
                // Kita ambil dari data.total_kehadiran yang sudah dihitung di Controller (Hari Kerja - Absen/Izin)
                const totalHadir = data.total_kehadiran || 0; 
                document.getElementById('hidden-total-kehadiran-bersih').value = totalHadir;

                // E. LOGIKA REVERSE ENGINEER (Mengisi Input dari Data DB yang sudah ada)

                // 1. Lembur
                const nominalLemburDB = parseFloat(data.lembur) || 0;
                let estimasiJam = 0;
                // Jika tarif ada, hitung balik jamnya. Jika tidak, biarkan 0.
                if (pendukung.tarif_lembur_per_jam > 0) {
                    estimasiJam = nominalLemburDB / pendukung.tarif_lembur_per_jam;
                }
                // Tampilkan jam (bisa desimal)
                document.getElementById('input-jam-lembur').value = estimasiJam;

                // 2. Potongan
                const totalPotonganDB = parseFloat(data.potongan) || 0;
                const potonganAbsenHarusnya = pendukung.jumlah_alpha * pendukung.tarif_potongan_absen;

                // Potongan Lain = Total di DB - (Alpha * Tarif)
                let sisaPotonganLain = totalPotonganDB - potonganAbsenHarusnya;
                if (sisaPotonganLain < 0) sisaPotonganLain = 0; // Safety check

                document.getElementById('input-potongan-lain').value = sisaPotonganLain;

                // F. Jalankan Kalkulasi Awal (agar field readonly terisi benar)
                calculateLive();

                // G. Isi Skor Kinerja
                document.querySelectorAll('.score-input').forEach(input => {
                    input.value = 0; // Reset
                });
                if (data.penilaian_kinerja) {
                    for (const [indikator_id, skor] of Object.entries(data.penilaian_kinerja)) {
                        const scoreInput = document.querySelector(
                            `.score-input[data-indikator-id="${indikator_id}"]`);
                        if (scoreInput) {
                            scoreInput.value = skor;
                        }
                    }
                }
            });


            // ================== 3. POPULATE MODAL DETAIL ==================
            detailModalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (!button) return;

                const row = button.closest('tr.karyawan-row');
                if (!row) return;

                const data = JSON.parse(row.getAttribute('data-gaji-json'));
                const modal = detailModalEl;

                // --- Mengisi Info Header ---
                modal.querySelector('#detail-nama-title').textContent = data.nama;
                modal.querySelector('#detail-jabatan').textContent = data.jabatan || '-';
                modal.querySelector('#detail-periode').textContent = new Date(data.bulan + '-02')
                    .toLocaleDateString('id-ID', {
                        month: 'long',
                        year: 'numeric'
                    });

                // --- Mengisi Rincian Pendapatan ---
                modal.querySelector('#detail-gaji-pokok').textContent = data.gaji_pokok_string;
                modal.querySelector('#detail-tunj-jabatan').textContent = data.tunj_jabatan_string;
                modal.querySelector('#detail-tunj-anak').textContent = data.tunj_anak_string;
                modal.querySelector('#detail-tunj-komunikasi').textContent = data.tunj_komunikasi_string;
                modal.querySelector('#detail-tunj-pengabdian').textContent = data.tunj_pengabdian_string;
                modal.querySelector('#detail-tunj-kinerja').textContent = data.tunj_kinerja_string;
                modal.querySelector('#detail-total-kehadiran').textContent = data.total_kehadiran || 0;
                modal.querySelector('#detail-tunj-kehadiran').textContent = data
                    .total_tunjangan_kehadiran_string;
                modal.querySelector('#detail-lembur').textContent = data.lembur_string;

                // --- Mengisi Rincian Potongan ---
                modal.querySelector('#detail-potongan').textContent = `(${data.potongan_string})`;

                // --- Mengisi Total Gaji Bersih ---
                modal.querySelector('#detail-gaji-bersih').textContent = data.gaji_bersih_string;

                // --- Logika Tombol (Download & Email) ---
                const downloadBtn = modal.querySelector('.btn-download-slip');
                const emailBtn = modal.querySelector('.btn-send-email');

                // Clone & replace untuk hapus event listener lama agar tidak double trigger
                const newDownloadBtn = downloadBtn.cloneNode(true);
                downloadBtn.parentNode.replaceChild(newDownloadBtn, downloadBtn);
                const newEmailBtn = emailBtn.cloneNode(true);
                emailBtn.parentNode.replaceChild(newEmailBtn, emailBtn);

                if (data.gaji_id) {
                    newDownloadBtn.disabled = false;
                    const hasEmail = data.email && data.email.trim() !== '';
                    newEmailBtn.disabled = !hasEmail;

                    const downloadUrl = `/gaji/${data.gaji_id}/download-slip`;
                    const emailUrl = `/gaji/${data.gaji_id}/send-email`;

                    function handleJobDispatch(button, url, processName, event) {
                        event.preventDefault();
                        const originalButtonHtml = button.innerHTML;
                        button.disabled = true;
                        button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Memproses...`;

                        fetch(url, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(apiData => {
                                if (apiData.message) {
                                    showResponseMessage(apiData.message, true);
                                    detailModal.hide();
                                } else {
                                    showResponseMessage('Terjadi kesalahan.', false);
                                }
                            })
                            .catch(error => {
                                console.error(`Error ${processName}:`, error);
                                showResponseMessage(`Gagal memulai proses ${processName}.`, false);
                            })
                            .finally(() => {
                                button.disabled = false;
                                button.innerHTML = originalButtonHtml;
                            });
                    }

                    newDownloadBtn.addEventListener('click', function(e) {
                        handleJobDispatch(this, downloadUrl, 'unduh slip', e);
                    });

                    newEmailBtn.addEventListener('click', function(e) {
                        handleJobDispatch(this, emailUrl, 'kirim email', e);
                    });
                } else {
                    newDownloadBtn.disabled = true;
                    newEmailBtn.disabled = true;
                }
            });


            // ================== 4. EVENT SUBMIT FORM (AJAX) ==================
            editGajiForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const form = e.target;
                const formData = new FormData(form);
                const submitButton = form.querySelector('button[type="submit"]');
                const originalButtonHtml = submitButton.innerHTML;

                submitButton.disabled = true;
                submitButton.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Menyimpan...`;

                fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showResponseMessage(data.message, true);
                            editModal.hide();
                            // Reload halaman untuk memastikan data tabel & perhitungan terbaru muncul
                            // Ini lebih aman daripada update DOM manual karena data_pendukung perlu di-refresh
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            if (data.errors) {
                                let errorMsg = data.message || 'Gagal menyimpan data.';
                                for (const key in data.errors) {
                                    errorMsg += `\n- ${data.errors[key][0]}`;
                                }
                                showResponseMessage(errorMsg, false);
                            } else {
                                showResponseMessage(data.message || 'Gagal menyimpan data.', false);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showResponseMessage('Terjadi kesalahan koneksi.', false);
                    })
                    .finally(() => {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonHtml;
                    });
            });

            // ================== 5. PENCARIAN ==================
            document.getElementById('search-input').addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('#gaji-table-body tr.karyawan-row');
                const noResultsRow = document.getElementById('no-search-results');
                let visibleRows = 0;

                rows.forEach(row => {
                    const nama = row.querySelector('.nama-karyawan').textContent.toLowerCase();
                    const nip = row.querySelector('.nip-karyawan').textContent.toLowerCase();
                    if (nama.includes(searchTerm) || nip.includes(searchTerm)) {
                        row.style.display = '';
                        visibleRows++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                noResultsRow.style.display = (visibleRows === 0 && searchTerm) ? '' : 'none';
            });
        });
    </script>
@endpush
