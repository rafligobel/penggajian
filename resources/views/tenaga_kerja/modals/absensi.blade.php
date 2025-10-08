<div class="modal-header">
    <h5 class="modal-title">Form Absensi Kehadiran</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @elseif (session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    @if ($isSesiDibuka)
        @if ($sudahAbsen)
            <div class="alert alert-success text-center">
                <h5 class="alert-heading"><i class="fas fa-check-circle"></i> Terima Kasih!</h5>
                <p class="mb-0">Anda sudah berhasil melakukan absensi hari ini.</p>
            </div>
        @else
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> Sesi absensi dibuka. Silakan klik tombol di bawah.
            </div>
            <form method="POST" action="{{ route('tenaga_kerja.absensi.store') }}">
                @csrf
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Absen Sekarang</button>
                </div>
            </form>
        @endif
    @else
        <div class="alert alert-warning text-center">
            <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Informasi Sesi</h5>
            <p class="mb-0">{{ $pesanSesi }}</p>
        </div>
    @endif
</div>
