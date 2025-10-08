<div class="modal-header">
    <h5 class="modal-title">Laporan Gaji Anda</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    {{-- Form untuk memilih tahun (Sudah berfungsi dengan baik, tidak perlu diubah) --}}
    <form method="GET" action="{{ route('tenaga_kerja.dashboard') }}" class="mb-3">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label for="laporan-tahun-select" class="form-label">Pilih Tahun:</label>
                <select name="tahun" id="laporan-tahun-select" class="form-select">
                    @forelse ($availableYears as $y)
                        <option value="{{ $y }}" {{ $y == $tahun ? 'selected' : '' }}>{{ $y }}
                        </option>
                    @empty
                        <option>Belum ada data</option>
                    @endforelse
                </select>
            </div>
        </div>
    </form>

    {{-- Tabel untuk menampilkan data gaji --}}
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Periode</th>
                    <th class="text-end">Gaji Pokok</th>
                    <th class="text-end">Total Tunjangan</th>
                    <th class="text-end">Total Potongan</th>
                    <th class="text-end">Gaji Bersih</th>
                    {{-- [PERUBAHAN] Tambah kolom Aksi --}}
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gajis as $gaji)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($gaji->bulan)->translatedFormat('F Y') }}</td>
                        <td class="text-end">Rp {{ number_format($gaji->gaji_pokok, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($gaji->total_tunjangan, 0, ',', '.') }}</td>
                        <td class="text-end text-danger">(Rp {{ number_format($gaji->total_potongan, 0, ',', '.') }})
                        </td>
                        <td class="text-end fw-bold">Rp {{ number_format($gaji->gaji_bersih, 0, ',', '.') }}</td>
                        {{-- [PERUBAHAN] Tambah tombol Cetak PDF per baris di dalam form --}}
                        <td class="text-center">
                            <form method="POST" action="{{ route('tenaga_kerja.laporan_gaji.cetak', $gaji->id) }}"
                                target="_blank">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" title="Cetak Slip Gaji">
                                    <i class="fas fa-print"></i> Cetak
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        {{-- [PERUBAHAN] Sesuaikan colspan menjadi 6 --}}
                        <td colspan="6" class="text-center">Tidak ada data gaji untuk tahun {{ $tahun }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
</div>
