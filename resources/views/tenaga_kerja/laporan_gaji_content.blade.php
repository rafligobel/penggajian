<div class="modal-header">
    <h5 class="modal-title">Laporan Gaji Anda</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="d-flex justify-content-end align-items-center mb-3">
        <span class="me-2 align-self-center">Tampilkan Tahun:</span>
        {{-- PERUBAHAN: Hapus onchange, berikan ID pada select untuk JS --}}
        <form method="GET" action="{{ route('modal.laporan_gaji') }}" class="mb-0">
            <select name="tahun" id="laporan-tahun-select" class="form-select form-select-sm">
                @forelse ($availableYears as $year)
                    <option value="{{ $year }}" @selected($year == $tahun)>{{ $year }}</option>
                @empty
                    <option>{{ $tahun }}</option>
                @endforelse
            </select>
        </form>
    </div>

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
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
</div>
