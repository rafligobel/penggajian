{{-- KARTU RINGKASAN --}}
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-start border-primary border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="text-muted">Jumlah Karyawan</div>
                        <div class="fs-4 fw-bold">{{ $jumlahKaryawan }} Orang</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-start border-success border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-wallet fa-2x text-success"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="text-muted">Total Gaji Bulan Ini</div>
                        <div class="fs-4 fw-bold">Rp {{ number_format($totalGajiBersih, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
