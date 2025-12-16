<div class="modal fade" id="absensiModal" tabindex="-1" aria-labelledby="absensiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="absensiModalLabel">Form Absensi Kehadiran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Konten Modal Dimulai --}}
                <div class="p-4"> {{-- Padding bisa disesuaikan --}}

                    {{-- 
                      [PERBAIKAN] Pesan session('success_modal') / session('info_modal') dihapus.
                      Kita akan menggunakan div #location-info-modal untuk menampilkan
                      semua feedback dari AJAX (loading, sukses, atau error).
                    --}}

                    {{-- Cek jika sesi dibuka (gunakan variabel yang dikirim dari controller saat menampilkan modal) --}}
                    @if ($isSesiDibuka ?? false)
                        {{-- Cek jika sudah absen --}}
                        @if ($sudahAbsen ?? false)
                            <div class="alert alert-success text-center">
                                <h5 class="alert-heading"><i class="fas fa-check-circle"></i> Terima Kasih!</h5>
                                <p class="mb-0">Anda sudah berhasil melakukan absensi hari ini.</p>
                            </div>
                        @else
                            {{-- 
                              Status pengambilan lokasi dan form absensi.
                              Div #location-info-modal ini akan menjadi pusat feedback.
                            --}}
                            <div id="location-info-modal" class="alert alert-info text-center">
                                <i class="fas fa-map-marker-alt"></i> Sedang mengambil data lokasi Anda... Mohon tunggu.
                            </div>
                            <form id="absensi-form-modal" method="POST"
                                action="{{ route('tenaga_kerja.absensi.store') }}">
                                @csrf
                                {{-- 
                                  Input ini tidak lagi dikirim ke server (AJAX akan mengirim lat/lon),
                                  tapi kita biarkan untuk debugging di sisi klien jika perlu.
                                --}}
                                <input type="hidden" name="koordinat_display" id="koordinat-modal">

                                <div class="d-grid mt-4">
                                    {{-- Tombol submit, dinonaktifkan awal --}}
                                    <button type="submit" id="submit-button-modal" class="btn btn-primary btn-lg"
                                        disabled>
                                        <span id="button-text-modal">Absen Sekarang</span>
                                        {{-- Spinner loading --}}
                                        <span id="loading-spinner-modal" class="spinner-border spinner-border-sm"
                                            role="status" aria-hidden="true" style="display: none;"></span>
                                    </button>
                                </div>
                            </form>
                        @endif
                    @else
                        {{-- Tampilan jika sesi tidak dibuka --}}
                        <div class="alert alert-warning text-center">
                            <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Informasi Sesi</h5>
                            <p class="mb-0">{{ $pesanSesi ?? 'Sesi absensi tidak tersedia saat ini.' }}</p>
                        </div>
                    @endif

                </div>
                {{-- Konten Modal Berakhir --}}
            </div>
        </div>
    </div>
</div>

<script>
    // Gunakan fungsi closure (() => { ... })(); untuk isolasi scope
    (() => {
        const absensiModalElement = document.getElementById('absensiModal');
        let locationRequested = false; // Flag untuk mencegah permintaan lokasi berulang

        // [PERBAIKAN] Variabel untuk menyimpan koordinat di scope yang lebih tinggi
        let userLat = 0;
        let userLon = 0;

        // Fungsi utama untuk inisialisasi Geolocation di dalam modal
        function initializeAbsensiGeolocation() {
            if (locationRequested) return;
            locationRequested = true;

            const application_info = {
                office_lat: parseFloat("{{ env('OFFICE_LATITUDE', 0) }}"),
                office_lon: parseFloat("{{ env('OFFICE_LONGITUDE', 0) }}"),
            };

            const locationInfo = document.getElementById('location-info-modal');
            const submitButton = document.getElementById('submit-button-modal');
            const koordinatInput = document.getElementById('koordinat-modal');
            const buttonText = document.getElementById('button-text-modal');
            const loadingSpinner = document.getElementById('loading-spinner-modal');
            const absensiForm = document.getElementById('absensi-form-modal');

            // Reset tampilan elemen ke kondisi awal
            if (locationInfo) {
                locationInfo.className = 'alert alert-info text-center';
                locationInfo.innerHTML =
                    '<i class="fas fa-map-marker-alt"></i> Sedang mengambil data lokasi Anda... Mohon tunggu.';
            }
            // Pastikan form dan tombol terlihat (jika sebelumnya disembunyikan oleh 'success')
            if (absensiForm) absensiForm.style.display = 'block';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.style.display = 'block';
            }
            if (buttonText) buttonText.textContent = 'Absen Sekarang';
            if (loadingSpinner) loadingSpinner.style.display = 'none';
            if (koordinatInput) koordinatInput.value = '';

            // [PERBAIKAN] Reset variabel koordinat
            userLat = 0;
            userLon = 0;

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    showPositionModal,
                    showErrorModal, {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
            } else {
                if (locationInfo) {
                    locationInfo.className = 'alert alert-danger text-center';
                    locationInfo.innerHTML =
                        '<i class="fas fa-exclamation-triangle"></i> Geolocation tidak didukung oleh browser ini.';
                }
                if (submitButton) submitButton.disabled = true;
            }

            // Callback jika lokasi berhasil didapatkan
            function showPositionModal(position) {
                // [PERBAIKAN] Simpan ke variabel scope yang lebih tinggi
                userLat = position.coords.latitude;
                userLon = position.coords.longitude;
                const koordinat = `${userLat},${userLon}`;

                if (koordinatInput) koordinatInput.value = koordinat; // (untuk display/debug)

                const distance = calculateDistanceModal(userLat, userLon, application_info.office_lat,
                    application_info.office_lon);

                if (locationInfo) {
                    locationInfo.className = 'alert alert-success text-center';
                    locationInfo.innerHTML =
                        `<i class="fas fa-check-circle"></i> Lokasi berhasil dideteksi. Perkiraan jarak: <strong>${distance.toFixed(0)} meter</strong>.`;
                }
                if (submitButton) submitButton.disabled = false;
            }

            // Callback jika terjadi error saat mengambil lokasi
            function showErrorModal(error) {
                let errorMessage = '';
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = "Izin akses lokasi ditolak oleh pengguna.";
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = "Informasi lokasi tidak tersedia saat ini.";
                        break;
                    case error.TIMEOUT:
                        errorMessage = "Waktu permintaan lokasi habis.";
                        break;
                    default:
                        errorMessage = "Terjadi kesalahan yang tidak diketahui saat mendapatkan lokasi.";
                        break;
                }
                if (locationInfo) {
                    locationInfo.className = 'alert alert-danger text-center';
                    locationInfo.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${errorMessage}`;
                }
                if (submitButton) submitButton.disabled = true;
            }

            // Fungsi helper untuk menghitung jarak (Haversine Formula)
            function calculateDistanceModal(lat1, lon1, lat2, lon2) {
                if (typeof lat1 !== 'number' || typeof lon1 !== 'number' || typeof lat2 !== 'number' ||
                    typeof lon2 !== 'number') return 0;

                const R = 6371e3; // Radius bumi dalam meter
                const φ1 = lat1 * Math.PI / 180;
                const φ2 = lat2 * Math.PI / 180;
                const Δφ = (lat2 - lat1) * Math.PI / 180;
                const Δλ = (lon2 - lon1) * Math.PI / 180;

                const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                    Math.cos(φ1) * Math.cos(φ2) *
                    Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

                return R * c; // Jarak dalam meter
            }

            // [PERBAIKAN BESAR] Mengganti submit standar dengan AJAX (Fetch API)
            if (absensiForm) {
                const handleFormSubmitModal = (event) => {
                    event.preventDefault(); // Hentikan submit form standar

                    // Validasi jika koordinat belum didapat
                    if (userLat === 0 || userLon === 0) {
                        if (locationInfo) {
                            locationInfo.className = 'alert alert-danger text-center';
                            locationInfo.innerHTML =
                                `<i class="fas fa-exclamation-triangle"></i> Koordinat lokasi belum didapatkan. Mohon tunggu.`;
                        }
                        return;
                    }

                    // Tampilkan loading saat submit
                    if (submitButton) submitButton.disabled = true;
                    if (buttonText) buttonText.textContent = 'Memproses...';
                    if (loadingSpinner) loadingSpinner.style.display = 'inline-block';

                    // Siapkan data untuk dikirim ke backend
                    const formData = new FormData();
                    formData.append('latitude', userLat); // Kirim 'latitude'
                    formData.append('longitude', userLon); // Kirim 'longitude'
                    const csrfToken = absensiForm.querySelector('input[name="_token"]');
                    if (csrfToken) {
                        formData.append('_token', csrfToken.value);
                    }

                    // Kirim request AJAX
                    fetch(absensiForm.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json', // Harapkan JSON
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            // Cek header content-type
                            const contentType = response.headers.get("content-type");
                            if (contentType && contentType.indexOf("application/json") !== -1) {
                                return response.json().then(data => {
                                    if (!response.ok) {
                                        throw { responseData: data, status: response.status };
                                    }
                                    return data;
                                });
                            } else {
                                // Jika bukan JSON (misal HTML error dari server)
                                return response.text().then(text => {
                                    console.error("Non-JSON Response:", text);
                                    throw { 
                                        status: response.status, 
                                        message: "Respon server tidak valid (bukan JSON). Cek Console untuk detail.",
                                        rawResponse: text // Simpan untuk debug
                                    };
                                });
                            }
                        })
                        .then(data => {
                            // Handle response sukses dari server
                            if (data.status === 'success') {
                                locationInfo.className = 'alert alert-success text-center';
                                locationInfo.innerHTML =
                                    `<i class="fas fa-check-circle"></i> ${data.message}`;
                                // Sembunyikan form dan tombol
                                if (absensiForm) absensiForm.style.display = 'none';
                            } else {
                                // Handle response error dari server (misal: "Anda di luar radius")
                                handleAjaxError(data.message || 'Terjadi kesalahan.');
                            }
                        })
                        .catch(error => {
                            // Handle network error atau error parsing
                            let errorMsg = 'Gagal terhubung ke server. Periksa koneksi Anda.';
                            // Cek jika error dari response JSON (misal: validasi 422)
                            if (error.responseData && error.responseData.message) {
                                errorMsg = error.responseData.message;
                            }
                            console.error('Error:', error);
                            handleAjaxError(errorMsg);
                        });
                }; // akhir handleFormSubmitModal

                // Fungsi helper untuk menangani error AJAX dan mengembalikan tombol
                function handleAjaxError(message) {
                    if (locationInfo) {
                        locationInfo.className = 'alert alert-danger text-center';
                        locationInfo.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
                    }

                    // Cek jika errornya adalah 'sudah absen' atau 'sesi ditutup'
                    if (message && (message.includes('sudah melakukan absensi') || message.includes(
                            'Sesi absensi sedang tidak dibuka'))) {
                        if (absensiForm) absensiForm.style.display = 'none'; // Sembunyikan form
                    } else {
                        // Aktifkan kembali tombol jika errornya bisa dicoba lagi (misal, GPS 0,0)
                        if (submitButton) submitButton.disabled = false;
                        if (buttonText) buttonText.textContent = 'Coba Absen Lagi';
                        if (loadingSpinner) loadingSpinner.style.display = 'none';
                    }
                }

                // Hapus listener lama (jika ada) dan tambahkan yang baru
                absensiForm.removeEventListener('submit', handleFormSubmitModal);
                absensiForm.addEventListener('submit', handleFormSubmitModal);
            }
        } // Akhir dari fungsi initializeAbsensiGeolocation

        // Pastikan elemen modal ada sebelum menambahkan listener
        if (absensiModalElement) {
            // Picu pengambilan lokasi HANYA saat modal selesai ditampilkan
            absensiModalElement.addEventListener('shown.bs.modal', initializeAbsensiGeolocation);

            // Reset flag 'locationRequested' saat modal ditutup
            absensiModalElement.addEventListener('hidden.bs.modal', function() {
                locationRequested = false;
                userLat = 0; // Reset koordinat saat modal ditutup
                userLon = 0;
            });
        } else {
            console.warn(
                'Elemen modal dengan ID "absensiModal" tidak ditemukan. Script Geolocation mungkin tidak terpicu dengan benar.'
            );
        }

    })(); // Panggil fungsi closure
</script>
