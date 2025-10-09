<div class="modal-header">
    <h5 class="modal-title">Form Absensi Kehadiran</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    {{-- Area untuk menampilkan pesan status dari JavaScript --}}
    <div id="absensi-status-message" style="display:none;" class="alert text-center"></div>

    @if ($isSesiDibuka)
        @if ($sudahAbsen)
            <div class="alert alert-success text-center">
                <h5 class="alert-heading"><i class="fas fa-check-circle"></i> Terima Kasih!</h5>
                <p class="mb-0">Anda sudah berhasil melakukan absensi hari ini.</p>
            </div>
        @else
            {{-- Tampilan Awal Sebelum Absen --}}
            <div id="initial-view">
                <div class="alert alert-info text-center">
                    <i class="fas fa-map-marker-alt"></i> Pastikan GPS Anda aktif untuk absensi.
                </div>
                <div class="d-grid mt-4">
                    {{-- Tombol ini BUKAN type="submit" lagi --}}
                    <button type="button" id="btn-absen-sekarang" class="btn btn-primary btn-lg">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"
                            style="display: none;"></span>
                        Absen Sekarang
                    </button>
                </div>
            </div>
            {{-- Tampilan Setelah Berhasil Absen (diisi oleh JS) --}}
            <div id="success-view" class="alert alert-success text-center" style="display: none;">
                <h5 class="alert-heading"><i class="fas fa-check-circle"></i> Absensi Berhasil!</h5>
                <p class="mb-0">Terima kasih telah melakukan absensi hari ini.</p>
            </div>
        @endif
    @else
        <div class="alert alert-warning text-center">
            <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Informasi Sesi</h5>
            <p class="mb-0">{{ $pesanSesi }}</p>
        </div>
    @endif
</div>

{{-- Tambahkan @push('scripts') di layout utama jika belum ada --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Pastikan skrip ini hanya berjalan jika tombolnya ada
        const absenButton = document.getElementById('btn-absen-sekarang');
        if (!absenButton) return;

        const statusMessage = document.getElementById('absensi-status-message');
        const spinner = absenButton.querySelector('.spinner-border');
        const initialView = document.getElementById('initial-view');
        const successView = document.getElementById('success-view');

        absenButton.addEventListener('click', function() {
            this.disabled = true;
            spinner.style.display = 'inline-block';
            showStatus('info', 'Sedang meminta lokasi GPS Anda...');

            if (!navigator.geolocation) {
                showStatus('danger', 'Browser Anda tidak mendukung Geolocation.');
                this.disabled = false;
                spinner.style.display = 'none';
                return;
            }

            // Meminta lokasi dengan timeout 15 detik
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const {
                        latitude,
                        longitude
                    } = position.coords;
                    showStatus('info', 'Lokasi didapatkan. Mengirim data ke server...');
                    sendDataToServer(latitude, longitude);
                },
                () => {
                    showStatus('danger',
                        'Gagal mendapatkan lokasi. Pastikan GPS aktif dan Anda memberikan izin akses lokasi pada browser.'
                    );
                    this.disabled = false;
                    spinner.style.display = 'none';
                }, {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );
        });

        async function sendDataToServer(latitude, longitude) {
            try {
                const response = await fetch("{{ route('tenaga_kerja.absensi.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        latitude,
                        longitude
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    showStatus('success', result.message);
                    initialView.style.display = 'none';
                    successView.style.display = 'block';
                } else {
                    showStatus('danger', result.message || 'Terjadi kesalahan saat validasi.');
                    absenButton.disabled = false;
                }

            } catch (error) {
                showStatus('danger', 'Gagal terhubung ke server. Periksa koneksi internet Anda.');
                absenButton.disabled = false;
            } finally {
                spinner.style.display = 'none';
            }
        }

        function showStatus(type, message) {
            statusMessage.style.display = 'block';
            statusMessage.className = `alert alert-${type} text-center`;
            statusMessage.innerHTML = `<i class="fas fa-info-circle me-2"></i> ${message}`;
        }
    });
</script>
