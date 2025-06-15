@extends('layouts.app')

@section('content')
    {{-- CSS untuk styling --}}
    <style>
        .table-minimalis {
            border-collapse: separate;
            border-spacing: 0 5px;
        }

        .table-minimalis th {
            border: none;
            font-weight: 600;
            color: #6c757d;
            padding-top: 0;
            padding-bottom: 10px;
        }

        .summary-row {
            background-color: #fff;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .summary-row:hover {
            background-color: #f8f9fa;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        }

        .summary-row.expanded {
            background-color: #e9f5ff;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .summary-row td {
            vertical-align: middle;
            border: none;
        }

        /* --- PERUBAHAN DI SINI --- */
        /* Kelas baru untuk mengatur jarak pada sel nama karyawan */
        .cell-nama-karyawan {
            padding: 12px 20px !important;
            /* Atur jarak vertikal dan horizontal */
            text-align: left;
        }

        .detail-row {
            display: none;
        }

        .detail-cell {
            background-color: #fdfdff;
            padding: 1.5rem !important;
            border-left: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .detail-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .attendance-day {
            border-radius: 6px;
            padding: 5px;
            width: 48px;
            height: 48px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            text-align: center;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .attendance-day .day-number {
            font-weight: bold;
            font-size: 0.9rem;
        }

        .status-hadir {
            background-color: #d1e7dd;
            color: #0a3622;
        }

        .status-absen {
            background-color: #f8d7da;
            color: #58151c;
        }
    </style>

    <div class="container py-4">
        <h3 class="mb-4 text-center text-primary fw-bold">Rekap Absensi Bulanan</h3>

        {{-- Form Filter --}}
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <form id="filter-form" class="row g-3 align-items-end justify-content-center">
                    <div class="col-md-4">
                        <label for="bulan" class="form-label fw-bold">Pilih Bulan</label>
                        <input type="month" class="form-control" id="bulan" name="bulan"
                            value="{{ $selectedMonth }}">
                    </div>
                    <div class="col-md-5">
                        <label for="search-input" class="form-label fw-bold">Cari Karyawan (Nama / NIP)</label>
                        <input type="text" class="form-control" id="search-input" placeholder="Ketik untuk mencari...">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Ganti Bulan</button>
                    </div>
                </form>
                {{-- KETERANGAN WARNA BARU --}}
                <div class="d-flex justify-content-center align-items-center mt-3 pt-2 border-top small text-muted">
                    <div class="d-flex align-items-center me-4">
                        <div class="me-2"
                            style="width: 15px; height: 15px; background-color: #d1e7dd; border-radius: 3px; border: 1px solid #bce0ce;">
                        </div>
                        <span>Hadir</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="me-2"
                            style="width: 15px; height: 15px; background-color: #f8d7da; border-radius: 3px; border: 1px solid #f1c2c5;">
                        </div>
                        <span>Alpha</span>
                    </div>
                </div>
            </div>
        </div>

        <h5 id="judul-bulan" class="text-center mb-3" style="display:none;"></h5>

        <div id="rekap-content" class="table-responsive">
            <p id="loading-message" class="text-center text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Memuat
                data...
            </p>
            <p id="no-data-message" class="text-center text-muted mt-4" style="display:none;">Tidak ada data untuk
                bulan
                yang dipilih.</p>

            <table class="table table-borderless table-minimalis">
                <thead>
                    <tr class="text-center">
                        <th style="width: 5%;">No.</th>
                        <th class="text-left">Nama Karyawan</th>
                        <th style="width: 15%;">NIP</th>
                        <th style="width: 8%;">Hadir</th>
                        {{-- <th style="width: 8%;">Sakit</th>
                        <th style="width: 8%;">Izin</th> --}}
                        <th style="width: 8%;">Alpha</th>
                    </tr>
                </thead>
                <tbody id="rekap-tbody"></tbody>
            </table>
        </div>
    </div>

    {{-- <td class="text-center align-middle">${k.ringkasan.sakit}</td>
    <td class="text-center align-middle">${k.ringkasan.izin}</td> --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('filter-form');
            const bulanInput = document.getElementById('bulan');
            const searchInput = document.getElementById('search-input'); // New search input
            const rekapTbody = document.getElementById('rekap-tbody');
            const judulBulan = document.getElementById('judul-bulan');
            const loadingMessage = document.getElementById('loading-message');
            const noDataMessage = document.getElementById('no-data-message');

            let allRekapData = []; // To store the master list of data for the selected month

            // Renders the table with the provided data
            function renderTable(dataToRender) {
                rekapTbody.innerHTML = '';

                if (!dataToRender || dataToRender.length === 0) {
                    noDataMessage.style.display = 'block';
                    // Jika tidak ada hasil pencarian, tampilkan pesan yang sesuai
                    if (searchInput.value) {
                        noDataMessage.textContent = 'Karyawan tidak ditemukan.';
                    } else {
                        noDataMessage.textContent = 'Tidak ada data untuk bulan yang dipilih.';
                    }
                    return;
                }
                noDataMessage.style.display = 'none';

                dataToRender.forEach((k, index) => {
                    const summaryRow = document.createElement('tr');
                    summaryRow.className = 'summary-row';
                    summaryRow.dataset.target = `detail-row-${k.nip}`;

                    summaryRow.innerHTML = `
                <td class="text-center align-middle">${index + 1}</td>
                <td class="cell-nama-karyawan">
                    <b>${k.nama}</b>
                    <small class="d-block text-muted">  </small>
                </td>
                <td class="text-center align-middle">${k.nip}</td>
                <td class="text-center align-middle">${k.ringkasan.hadir}</td>
                <td class="text-center align-middle">${k.ringkasan.alpha}</td>
            `;

                    const detailRow = document.createElement('tr');
                    detailRow.id = `detail-row-${k.nip}`;
                    detailRow.className = 'detail-row';

                    let detailGridHtml = '<div class="detail-grid">';
                    for (const day in k.detail) {
                        const statusClass = k.detail[day].status === 'H' ? 'status-hadir' :
                            'status-absen';
                        const jam = k.detail[day].jam;
                        detailGridHtml += `
                    <div class="attendance-day ${statusClass}" title="Jam: ${jam}">
                        <span class="day-number">${day}</span>
                        <span>${k.detail[day].status}</span>
                    </div>
                `;
                    }
                    detailGridHtml += '</div>';

                    detailRow.innerHTML = `<td colspan="7" class="detail-cell">${detailGridHtml}</td>`;

                    rekapTbody.appendChild(summaryRow);
                    rekapTbody.appendChild(detailRow);
                });
            }

            // Filters and then renders the table
            function filterAndRender() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const filteredData = allRekapData.filter(k => {
                    return k.nama.toLowerCase().includes(searchTerm) || k.nip.toLowerCase().includes(
                        searchTerm);
                });
                renderTable(filteredData);
            }

            // Fetches data from the server
            function fetchData() {
                const bulan = bulanInput.value;
                if (!bulan) return;

                loadingMessage.style.display = 'block';
                rekapTbody.innerHTML = '';
                noDataMessage.style.display = 'none';
                judulBulan.style.display = 'none';
                searchInput.value = ''; // Clear search on new month fetch

                fetch(`{{ route('laporan.absensi.data') }}?bulan=${bulan}`)
                    .then(response => response.json())
                    .then(data => {
                        loadingMessage.style.display = 'none';
                        judulBulan.textContent = `Rekapitulasi Bulan: ${data.nama_bulan}`;
                        judulBulan.style.display = 'block';
                        allRekapData = data.rekap || []; // Store the master list
                        renderTable(allRekapData); // Initial render with all data
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        loadingMessage.style.display = 'none';
                        noDataMessage.textContent = 'Terjadi kesalahan saat memuat data.';
                        noDataMessage.style.display = 'block';
                    });
            }

            rekapTbody.addEventListener('click', function(e) {
                const summaryRow = e.target.closest('.summary-row');
                if (!summaryRow) return;

                const targetId = summaryRow.dataset.target;
                const detailRow = document.getElementById(targetId);

                if (detailRow) {
                    const isExpanded = detailRow.style.display === 'table-row';
                    detailRow.style.display = isExpanded ? 'none' : 'table-row';
                    summaryRow.classList.toggle('expanded', !isExpanded);
                }
            });

            // Event listener for month change
            filterForm.addEventListener('submit', e => {
                e.preventDefault();
                fetchData();
            });

            // Event listener for real-time search
            searchInput.addEventListener('input', filterAndRender);

            // Initial data fetch on page load
            fetchData();
        });
    </script>
@endsection
