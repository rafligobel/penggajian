@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const detailModalEl = document.getElementById('detailModal');
            const editModalEl = document.getElementById('editModal');
            const responseMessageEl = document.getElementById('ajax-response-message');

            const formatRupiah = (angka) => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(angka || 0);

            function showResponseMessage(message, isSuccess = true) {
                responseMessageEl.textContent = message;
                responseMessageEl.className = isSuccess ? 'alert alert-info' : 'alert alert-danger';
                responseMessageEl.style.display = 'block';
                setTimeout(() => {
                    responseMessageEl.style.display = 'none';
                }, 5000);
            }

            function handleAjaxRequest(button) {
                const url = button.dataset.url;
                const originalHtml = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json().then(data => ({
                        status: response.status,
                        body: data
                    })))
                    .then(({
                        status,
                        body
                    }) => {
                        showResponseMessage(body.message, status === 200);
                        if (status === 200) {
                            const modalInstance = bootstrap.Modal.getInstance(detailModalEl);
                            modalInstance.hide();
                        }
                    }).catch(error => {
                        console.error('Error:', error);
                        showResponseMessage('Terjadi kesalahan. Silakan cek konsol browser.', false);
                    }).finally(() => {
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                    });
            }

            detailModalEl.addEventListener('click', function(event) {
                const downloadBtn = event.target.closest('.btn-download-slip');
                if (downloadBtn) handleAjaxRequest(downloadBtn);

                const emailBtn = event.target.closest('.btn-send-email');
                if (emailBtn) handleAjaxRequest(emailBtn);
            });

            function populateDetailModal(data, modal) {
                const detailContent = modal.querySelector('#detail-content');
                modal.querySelector('#detailModalLabel').textContent = `Detail Gaji: ${data.karyawan.nama}`;

                let updateInfo =
                    '<p class="text-muted fst-italic mb-0">Data gaji bulan ini belum pernah disimpan.</p>';
                if (data.updated_at) {
                    const updatedAt = new Date(data.updated_at);
                    const formattedDate = updatedAt.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    updateInfo = `<p class="text-muted mb-0">Terakhir diperbarui: ${formattedDate} WITA</p>`;
                }

                const createRincianHtml = (items) => items.map(item =>
                    `<div class="row mb-2"><div class="col-7">${item.label}</div><div class="col-5 text-end">${formatRupiah(item.value)}</div></div>`
                ).join('');

                const pendapatanTetap = [{
                    label: 'Gaji Pokok',
                    value: data.gaji_pokok
                }, {
                    label: 'Tunjangan Jabatan',
                    value: data.tunj_jabatan
                }, {
                    label: 'Tunjangan Anak',
                    value: data.tunj_anak
                }, {
                    label: 'Tunjangan Komunikasi',
                    value: data.tunj_komunikasi
                }, {
                    label: 'Tunjangan Pengabdian',
                    value: data.tunj_pengabdian
                }, {
                    label: 'Tunjangan Kinerja',
                    value: data.tunj_kinerja
                }, ];

                const pendapatanTidakTetap = [{
                    label: `Tunjangan Kehadiran (${data.jumlah_kehadiran} hari)`,
                    value: data.tunj_kehadiran
                }, {
                    label: 'Lembur',
                    value: data.lembur
                }, {
                    label: 'Kelebihan Jam',
                    value: data.kelebihan_jam
                }, ];

                const namaJabatan = data.karyawan.jabatan ? data.karyawan.jabatan.nama_jabatan :
                    'Jabatan tidak diatur';

                detailContent.innerHTML = `
                    <div class="row"><div class="col-md-6"><p class="mb-1"><strong>Periode:</strong> ${new Date(data.bulan + '-01').toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })}</p><p><strong>Jabatan:</strong> ${namaJabatan}</p></div></div><hr>
                    <div class="row">
                        <div class="col-lg-6 mb-4 mb-lg-0"><h5 class="mb-3">A. Pendapatan Tetap</h5>${createRincianHtml(pendapatanTetap)}</div>
                        <div class="col-lg-6"><h5 class="mb-3">B. Pendapatan Tidak Tetap</h5>${createRincianHtml(pendapatanTidakTetap)}<hr><h5 class="mb-3">C. Potongan</h5><div class="row mb-2"><div class="col-7">Potongan Lain-lain</div><div class="col-5 text-end text-danger">(${formatRupiah(data.potongan)})</div></div></div>
                    </div><hr class="my-4">
                    <div class="bg-light p-3 rounded"><div class="row align-items-center"><div class="col-7"><h5 class="mb-0">GAJI BERSIH</h5></div><div class="col-5 text-end"><h5 class="mb-0 fw-bold">${formatRupiah(data.gaji_bersih)}</h5></div></div></div>
                    <div class="mt-4 border-top pt-2 text-center small">${updateInfo}</div>`;

                const downloadBtn = modal.querySelector('.btn-download-slip');
                const emailBtn = modal.querySelector('.btn-send-email');

                if (data.id) {
                    downloadBtn.disabled = false;
                    downloadBtn.dataset.url = `/gaji/${data.id}/download`;
                    downloadBtn.removeAttribute('title');

                    if (data.karyawan.email) {
                        emailBtn.disabled = false;
                        emailBtn.dataset.url = `/gaji/${data.id}/send-email`;
                        emailBtn.removeAttribute('title');
                    } else {
                        emailBtn.disabled = true;
                        emailBtn.setAttribute('title', 'Karyawan tidak memiliki email.');
                    }
                } else {
                    downloadBtn.disabled = true;
                    emailBtn.disabled = true;
                    downloadBtn.setAttribute('title', 'Simpan data terlebih dahulu.');
                    emailBtn.setAttribute('title', 'Simpan data terlebih dahulu.');
                }
            }

            function populateEditModal(data, modal) {
                const formContent = modal.querySelector('#edit-form-content');
                modal.querySelector('#editModalLabel').textContent = `Edit Gaji: ${data.karyawan.nama}`;

                const gajiPokokFromJabatan = data.karyawan.jabatan ? data.karyawan.jabatan.gaji_pokok : 0;
                const tunjanganJabatanFromJabatan = data.karyawan.jabatan ? data.karyawan.jabatan.tunj_jabatan : 0;

                const fields = [{
                    name: 'gaji_pokok',
                    label: 'Gaji Pokok (Sesuai Jabatan)',
                    value: gajiPokokFromJabatan,
                    readonly: true
                }, {
                    name: 'tunj_jabatan',
                    label: 'Tunjangan Jabatan (Sesuai Jabatan)',
                    value: tunjanganJabatanFromJabatan,
                    readonly: true
                }, {
                    name: 'tunj_anak',
                    label: 'Tunjangan Anak',
                    value: data.tunj_anak
                }, {
                    name: 'tunj_komunikasi',
                    label: 'Tunjangan Komunikasi',
                    value: data.tunj_komunikasi
                }, {
                    name: 'tunj_pengabdian',
                    label: 'Tunjangan Pengabdian',
                    value: data.tunj_pengabdian
                }, {
                    name: 'tunj_kinerja',
                    label: 'Tunjangan Kinerja',
                    value: data.tunj_kinerja
                }, {
                    name: 'lembur',
                    label: 'Lembur',
                    value: data.lembur
                }, {
                    name: 'kelebihan_jam',
                    label: 'Kelebihan Jam',
                    value: data.kelebihan_jam
                }, ];

                let fieldsHtml = fields.map(f =>
                    `<div class="col-md-6 mb-3">
                        <label class="form-label">${f.label}</label>
                        <input type="number" name="${f.name}" class="form-control" value="${f.value || 0}" ${f.readonly ? 'readonly' : ''}>
                    </div>`
                ).join('');

                formContent.innerHTML =
                    `<input type="hidden" name="karyawan_id" value="${data.karyawan.id}">
                     <input type="hidden" name="bulan" value="${data.bulan}">
                     <div class="alert alert-info">
                         <p class="mb-1"><strong>Periode: ${data.bulan}</strong></p>
                         <p class="mb-1">Jumlah Kehadiran: <strong>${data.jumlah_kehadiran} hari</strong></p>
                         <p class="mb-0">Tunjangan Kehadiran (Otomatis): <strong>${formatRupiah(data.tunj_kehadiran || 0)}</strong></p>
                     </div>
                     <div class="row">${fieldsHtml}
                         <div class="col-md-6 mb-3">
                             <label class="form-label">Potongan</label>
                             <input type="number" name="potongan" class="form-control" value="${data.potongan || 0}">
                         </div>
                     </div>`;
            }

            document.querySelectorAll('.btn-detail').forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const gajiData = JSON.parse(row.getAttribute('data-gaji-json'));
                    populateDetailModal(gajiData, detailModalEl);
                });
            });

            document.querySelectorAll('.btn-edit').forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const gajiData = JSON.parse(row.getAttribute('data-gaji-json'));
                    populateEditModal(gajiData, editModalEl);
                });
            });
        });
    </script>
@endpush
