@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4 text-center text-primary fw-bold">Rekap Absensi Bulanan</h3>

        <div class="card shadow-lg mb-5 border-0 rounded-4">
            <div class="card-body p-4">
                <form method="GET" action="{{ route('laporan.absensi.index') }}"
                    class="row g-3 align-items-end justify-content-center">
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="bulan" class="form-label text-muted fw-semibold">Pilih Bulan:</label>
                        <input type="month" class="form-control form-control-lg rounded-pill shadow-sm" id="bulan"
                            name="bulan" value="{{ $selectedMonth }}">
                    </div>
                    <div class="col-12 col-md-3 col-lg-2">
                        <button type="submit"
                            class="btn btn-primary btn-lg w-100 rounded-pill shadow-sm">Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="rekap-absensi-container" class="mt-4">
            <p id="loading-message" class="text-center text-info fs-5" style="display: none;"><i
                    class="fas fa-spinner fa-spin me-2"></i>Memuat data...</p>
            <div id="rekap-cards-container" class="row row-cols-1 g-4" style="display:none;">
                <h4 id="judul-bulan" class="text-center py-3 bg-primary text-white mb-4 rounded-4 shadow-sm"></h4>
            </div>
            <p id="no-data-message" style="display:none;" class="text-center text-muted fs-5 mt-4">Tidak ada data absensi
                untuk bulan yang dipilih.</p>
        </div>

    </div>

    <style>
        .attendance-day {
            border-radius: 8px;
            padding: 8px 5px;
            margin: 3px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            text-align: center;
            min-width: 50px;
            flex-grow: 1;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .attendance-day:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .attendance-day .day-number {
            font-weight: bold;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }

        /* Status colors */
        .status-hadir {
            background-color: #d4edda;
            color: #155724;
        }

        .status-absen {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-izin {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-sakit {
            background-color: #cfe2ff;
            color: #084298;
        }

        .status-libur {
            background-color: #e2e3e5;
            color: #495057;
        }

        .status-na {
            background-color: #f0f0f0;
            color: #6c757d;
        }

        .employee-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
        }

        .employee-card-header {
            background-color: #e9f5ff;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #d0e7ff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .employee-card-header h5 {
            margin-bottom: 0;
            font-weight: 600;
            color: #34495e;
        }

        .employee-card-body {
            padding: 1rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            gap: 5px;
        }

        .employee-card-footer {
            background-color: #f8f9fa;
            padding: 0.75rem 1.5rem;
            border-top: 1px solid #e9ecef;
            text-align: right;
            font-weight: 500;
        }

        /* Responsive adjustments */
        @media (max-width: 575.98px) {
            .attendance-day {
                min-width: 45px;
                font-size: 0.7rem;
                padding: 6px 3px;
                margin: 2px;
            }

            .attendance-day .day-number {
                font-size: 0.8rem;
            }
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
            margin-bottom: 5px;
        }

        .legend-color-box {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-right: 8px;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectedMonthInput = document.getElementById('bulan');
            const currentSelectedMonth = selectedMonthInput.value;
            const rekapCardsContainer = document.getElementById('rekap-cards-container');
            const loadingMessage = document.getElementById('loading-message');
            const noDataMessage = document.getElementById('no-data-message');
            const judulBulan = document.getElementById('judul-bulan');


            function fetchRekapData(bulan) {
                loadingMessage.style.display = 'block';
                rekapCardsContainer.style.display = 'none';
                noDataMessage.style.display = 'none';
                rekapCardsContainer.innerHTML = ''; // Clear previous cards
                judulBulan.style.display = 'none'; // Hide title while loading

                fetch(`{{ route('laporan.absensi.data') }}?bulan=${bulan}`)
                    .then(response => response.json())
                    .then(data => {
                        loadingMessage.style.display = 'none';
                        if (data.rekap && data.rekap.length > 0) {
                            rekapCardsContainer.style.display = 'flex';
                            judulBulan.textContent = `Rekap Absensi Bulan: ${data.nama_bulan}`;
                            judulBulan.style.display = 'block'; // Show title after data loads
                            rekapCardsContainer.prepend(judulBulan);

                            data.rekap.forEach((employee) => {
                                const employeeCardCol = document.createElement('div');
                                employeeCardCol.classList.add(
                                'col'); // Bootstrap grid column for each card

                                let dailyDataHtml = '';
                                for (const dayNum in employee.harian) {
                                    const record = employee.harian[dayNum];
                                    let statusText = record.status;
                                    let statusClass = '';

                                    switch (record.status.toUpperCase()) {
                                        case 'HADIR':
                                        case 'H':
                                            statusClass = 'status-hadir';
                                            statusText = 'Hadir';
                                            break;
                                        case 'ABSEN':
                                        case 'A':
                                            statusClass = 'status-absen';
                                            statusText = 'Absen';
                                            break;
                                        case 'IZIN':
                                        case 'I':
                                            statusClass = 'status-izin';
                                            statusText = 'Izin';
                                            break;
                                        case 'SAKIT':
                                        case 'S':
                                            statusClass = 'status-sakit';
                                            statusText = 'Sakit';
                                            break;
                                        case 'LIBUR':
                                        case 'L':
                                            statusClass = 'status-libur';
                                            statusText = 'Libur';
                                            break;
                                        default:
                                            statusClass = 'status-na';
                                            statusText = 'Tidak Ada Data';
                                            break;
                                    }

                                    // Corrected template literal for attendance-day
                                    dailyDataHtml += `
                                        <div class="attendance-day ${statusClass}"
                                             title="${statusText}${record.jam ? ' (' + record.jam + ')' : ''}">
                                            <span class="day-number">${parseInt(dayNum)}</span>
                                            <span class="status-label">${record.status}</span>
                                            <span class="time-label text-muted small">${record.jam}</span>
                                        </div>
                                    `;
                                }

                                employeeCardCol.innerHTML = `
                                    <div class="card employee-card h-100">
                                        <div class="employee-card-header">
                                            <div>
                                                <h5 class="mb-1">${employee.nama}</h5>
                                                <p class="text-muted mb-0 small">${employee.nip}</p>
                                            </div>
                                            <span class="badge bg-primary text-white p-2">Hadir: ${employee.total_hadir} Hari</span>
                                        </div>
                                        <div class="employee-card-body">
                                            ${dailyDataHtml}
                                        </div>
                                    </div>
                                `;
                                rekapCardsContainer.appendChild(employeeCardCol);
                            });

                            // Add a legend/key at the bottom
                            const legendHtml = `
                                <div class="col-12 mt-4">
                                    <div class="card shadow-sm rounded-4 p-3">
                                        <h5 class="text-primary mb-3">Keterangan Status Absensi:</h5>
                                        <div class="d-flex flex-wrap">
                                            <div class="legend-item"><div class="legend-color-box status-hadir"></div> Hadir</div>
                                            <div class="legend-item"><div class="legend-color-box status-absen"></div> Absen</div>
                                            <div class="legend-item"><div class="legend-color-box status-izin"></div> Izin</div>
                                            <div class="legend-item"><div class="legend-color-box status-sakit"></div> Sakit</div>
                                            <div class="legend-item"><div class="legend-color-box status-libur"></div> Libur</div>
                                            <div class="legend-item"><div class="legend-color-box status-na"></div> Tidak Ada Data</div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            rekapCardsContainer.insertAdjacentHTML('beforeend', legendHtml);


                        } else {
                            noDataMessage.style.display = 'block';
                            judulBulan.style.display = 'none'; // Ensure title is hidden if no data
                        }
                    })
                    .catch(error => {
                        loadingMessage.style.display = 'none';
                        noDataMessage.textContent = 'Gagal memuat data. Silakan coba lagi.';
                        noDataMessage.style.display = 'block';
                        judulBulan.style.display = 'none'; // Ensure title is hidden on error
                        console.error('Error fetching rekap data:', error);
                    });
            }

            if (currentSelectedMonth) {
                fetchRekapData(currentSelectedMonth);
            }

            selectedMonthInput.addEventListener('change', (event) => {
                fetchRekapData(event.target.value);
            });
        });
    </script>
@endsection
