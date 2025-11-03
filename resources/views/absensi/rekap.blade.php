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
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .cell-nama-karyawan {
            padding-left: 20px !important;
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

        /* --- MODIFIKASI: CSS untuk status libur ditambahkan --- */
        .status-libur {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        /* --- END MODIFIKASI --- */
    </style>

    <div class="container ">
        <h3 class=" text-center text-primary fw-bold">Rekap Absensi Bulanan</h3>

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
                        <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                    </div>
                </form>
                {{-- Keterangan Warna --}}
                <div class="d-flex justify-content-center align-items-center mt-3 pt-2 border-top small text-muted">
                    <div class="d-flex align-items-center me-4">
                        <div class="me-2"
                            style="width: 15px; height: 15px; background-color: #d1e7dd; border-radius: 3px; border: 1px solid #bce0ce;">
                        </div>
                        <span>Hadir</span>
                    </div>
                    {{-- MODIFIKASI: Menambahkan Keterangan Libur --}}
                    <div class="d-flex align-items-center me-4">
                        <div class="me-2"
                            style="width: 15px; height: 15px; background-color: #f8f9fa; border-radius: 3px; border: 1px solid #e9ecef;">
                        </div>
                        <span>Libur</span>
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

        <h5 id="judul-bulan" class="text-center mb-1" style="display:none;"></h5>
        <p id="info-hari-kerja" class="text-center text-muted small mb-3" style="display:none;"></p>

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
                        <th class="text-start" style="padding-left: 20px;">Nama Karyawan</th>
                        <th style="width: 15%;">NIP</th>
                        <th style="width: 8%;">Hadir</th>
                        <th style="width: 8%;">Alpha</th>
                    </tr>
                </thead>
                <tbody id="rekap-tbody"></tbody>
            </table>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('filter-form');
            const bulanInput = document.getElementById('bulan');
            const searchInput = document.getElementById('search-input');
            const rekapTbody = document.getElementById('rekap-tbody');
            const judulBulan = document.getElementById('judul-bulan');
            const infoHariKerja = document.getElementById('info-hari-kerja');
            const loadingMessage = document.getElementById('loading-message');
            const noDataMessage = document.getElementById('no-data-message');

            let allRekapData = [];
            let currentDaysInMonth = 0; // MODIFIKASI: Variabel untuk simpan jumlah hari

            function renderTable(dataToRender) {
                rekapTbody.innerHTML = '';

                if (!dataToRender || dataToRender.length === 0) {
                    noDataMessage.style.display = 'block';
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
                            <small class="d-block text-muted">${k.email || ''}</small>
                        </td>
                        <td class="text-center align-middle">${k.nip}</td>
                        <td class="text-center align-middle text-success fw-bold">${k.summary.total_hadir}</td>
                        <td class="text-center align-middle text-danger fw-bold">${k.summary.total_alpha}</td>
                    `;

                    const detailRow = document.createElement('tr');
                    detailRow.id = `detail-row-${k.nip}`;
                    detailRow.className = 'detail-row';

                    // --- MODIFIKASI: Logika untuk render kalender detail diubah total ---
                    let detailGridHtml = '<div class="detail-grid">';

                    // Pastikan kita punya data jumlah hari dan detail absensi
                    if (currentDaysInMonth > 0 && k.detail) {

                        // Loop dari hari 1 s/d hari terakhir di bulan tsb
                        for (let day = 1; day <= currentDaysInMonth; day++) {

                            // Ambil data untuk hari H
                            const detailHari = k.detail[day];

                            // Tentukan status default (Libur)
                            let statusClass = 'status-libur';
                            let jam = '-';
                            let statusText = '...'; // Status default jika data hari tsb tidak ada

                            // Jika ada data detail untuk hari itu
                            if (detailHari) {
                                jam = detailHari.jam || '-';
                                statusText = detailHari.status || '...';

                                // Set class berdasarkan status, mirip di laporan_absensi.blade.php
                                if (detailHari.status === 'H') {
                                    statusClass = 'status-hadir';
                                } else if (detailHari.status === 'A') {
                                    statusClass = 'status-absen';
                                }
                                // Jika status 'L' atau lainnya, akan tetap 'status-libur'
                            }

                            // Buat elemen kalender harian
                            detailGridHtml += `
                            <div class="attendance-day ${statusClass}" title="Jam: ${jam}">
                                <span class="day-number">${day}</span>
                                <span>${statusText}</span>
                            </div>
                            `;
                        }
                    }
                    detailGridHtml += '</div>';
                    // --- END MODIFIKASI ---

                    detailRow.innerHTML = `<td colspan="5" class="detail-cell">${detailGridHtml}</td>`;
                    rekapTbody.appendChild(summaryRow);
                    rekapTbody.appendChild(detailRow);
                });
            }

            function filterAndRender() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                if (!searchTerm) {
                    renderTable(allRekapData);
                    return;
                }
                const filteredData = allRekapData.filter(k => {
                    return k.nama.toLowerCase().includes(searchTerm) || k.nip.toLowerCase().includes(
                        searchTerm);
                });
                renderTable(filteredData);
            }

            function fetchData() {
                const bulan = bulanInput.value;
                if (!bulan) return;

                // --- MODIFIKASI: Hitung dan simpan jumlah hari di bulan terpilih ---
                try {
                    const [year, month] = bulan.split('-').map(Number);
                    // new Date(year, month, 0) akan memberikan tanggal terakhir dari bulan SEBELUMNYA.
                    // JavaScript month adalah 0-indexed (0=Jan, 1=Feb, ...). 
                    // Jadi 'month' dari input (misal 10 untuk Okt) adalah month ke-9 di JS.
                    // new Date(year, month, 0) artinya kita minta hari ke-0 dari bulan *berikutnya* (Nov),
                    // yang mana adalah hari terakhir dari bulan ini (Okt).
                    currentDaysInMonth = new Date(year, month, 0).getDate();
                } catch (e) {
                    console.error("Format bulan salah:", bulan);
                    currentDaysInMonth = 0; // Reset jika error
                }
                // --- END MODIFIKASI ---

                loadingMessage.style.display = 'block';
                rekapTbody.innerHTML = '';
                noDataMessage.style.display = 'none';
                judulBulan.style.display = 'none';
                infoHariKerja.style.display = 'none';
                searchInput.value = '';

                fetch(`{{ route('absensi.rekap.data') }}?bulan=${bulan}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        loadingMessage.style.display = 'none';
                        judulBulan.textContent = `Rekapitulasi Bulan: ${data.nama_bulan}`;
                        judulBulan.style.display = 'block';
                        infoHariKerja.textContent = `Total Hari Kerja Efektif: ${data.total_hari_kerja} hari`;
                        infoHariKerja.style.display = 'block';

                        allRekapData = data.rekap || [];
                        renderTable(allRekapData); // renderTable sekarang akan menggunakan currentDaysInMonth
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

            filterForm.addEventListener('submit', e => {
                e.preventDefault();
                fetchData();
            });

            searchInput.addEventListener('input', filterAndRender);

            // Muat data saat halaman pertama kali dibuka
            fetchData();
        });
    </script>
@endsection
