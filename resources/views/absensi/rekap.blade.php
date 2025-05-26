@extends('layouts.app')

@section('content')
    <div class="container">
        <h3 class="mb-4">Rekap Absensi Bulanan</h3>

        <form method="GET" action="{{ route('laporan.absensi.index') }}" class="row g-3 align-items-end mb-4">
            <div class="col-md-3">
                <label for="bulan" class="form-label">Pilih Bulan:</label>
                <input type="month" class="form-control" id="bulan" name="bulan" value="{{ $selectedMonth }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
            </div>
        </form>

        <div id="rekap-absensi-container">
            <p id="loading-message">Memuat data...</p>
            <div class="table-responsive" style="display:none;" id="table-container">
                <h4 id="judul-bulan" class="text-center mb-3"></h4>
                <table class="table table-bordered table-striped table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th rowspan="2" class="align-middle text-center">No</th>
                            <th rowspan="2" class="align-middle text-center">Nama Karyawan</th>
                            <th rowspan="2" class="align-middle text-center">NIP</th>
                            <th colspan="31" class="text-center" id="colspan-tanggal">Tanggal</th>
                            <th rowspan="2" class="align-middle text-center">Total Hadir</th>
                        </tr>
                        <tr id="tanggal-header-row">
                            {{-- Tanggal akan di-generate oleh JavaScript --}}
                        </tr>
                    </thead>
                    <tbody id="rekap-absensi-tbody">
                        {{-- Data rekap akan di-generate oleh JavaScript --}}
                    </tbody>
                    <tfoot id="rekap-absensi-tfoot" style="display:none;">
                        {{-- Total per hari bisa ditambahkan di sini jika perlu --}}
                    </tfoot>
                </table>
            </div>
            <p id="no-data-message" style="display:none;" class="text-center">Tidak ada data absensi untuk bulan yang
                dipilih.</p>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectedMonthInput = document.getElementById('bulan');
            const currentSelectedMonth = selectedMonthInput.value;
            const rekapContainer = document.getElementById('rekap-absensi-container');
            const tableContainer = document.getElementById('table-container');
            const tbody = document.getElementById('rekap-absensi-tbody');
            const tfoot = document.getElementById('rekap-absensi-tfoot');
            const tanggalHeaderRow = document.getElementById('tanggal-header-row');
            const colspanTanggal = document.getElementById('colspan-tanggal');
            const judulBulan = document.getElementById('judul-bulan');
            const loadingMessage = document.getElementById('loading-message');
            const noDataMessage = document.getElementById('no-data-message');


            function fetchRekapData(bulan) {
                loadingMessage.style.display = 'block';
                tableContainer.style.display = 'none';
                noDataMessage.style.display = 'none';
                tbody.innerHTML = ''; // Kosongkan tabel sebelum fetch baru
                tanggalHeaderRow.innerHTML = ''; // Kosongkan header tanggal

                fetch(`{{ route('laporan.absensi.data') }}?bulan=${bulan}`)
                    .then(response => response.json())
                    .then(data => {
                        loadingMessage.style.display = 'none';
                        if (data.rekap && data.rekap.length > 0) {
                            tableContainer.style.display = 'block';
                            judulBulan.textContent = `Rekap Absensi Bulan: ${data.nama_bulan}`;
                            colspanTanggal.setAttribute('colspan', data.jumlah_hari);

                            // Buat header tanggal
                            for (let i = 1; i <= data.jumlah_hari; i++) {
                                const th = document.createElement('th');
                                th.classList.add('text-center');
                                th.style.minWidth = '30px'; // Atur lebar minimum kolom tanggal
                                th.textContent = i;
                                tanggalHeaderRow.appendChild(th);
                            }

                            // Isi data absensi
                            data.rekap.forEach((item, index) => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                            <td class="text-center">${index + 1}</td>
                            <td>${item.nama}</td>
                            <td>${item.nip}</td>
                            ${Object.values(item.harian).map(status => `<td class="text-center">${status}</td>`).join('')}
                            <td class="text-center fw-bold">${item.total_hadir}</td>
                        `;
                                tbody.appendChild(tr);
                            });
                        } else {
                            noDataMessage.style.display = 'block';
                            judulBulan.textContent = '';
                        }
                    })
                    .catch(error => {
                        loadingMessage.style.display = 'none';
                        noDataMessage.textContent = 'Gagal memuat data. Silakan coba lagi.';
                        noDataMessage.style.display = 'block';
                        console.error('Error fetching rekap data:', error);
                    });
            }

            if (currentSelectedMonth) {
                fetchRekapData(currentSelectedMonth);
            }

            // Jika Anda ingin form submit tetap via GET dan reload halaman (seperti di setup awal)
            // maka script di atas akan berjalan setelah halaman reload.
            // Jika ingin AJAX tanpa reload halaman penuh, Anda perlu preventDefault pada form submit
            // dan panggil fetchRekapData. Namun setup saat ini sudah memadai.
        });
    </script>
@endsection
