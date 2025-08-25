{{-- ============== MODALS ============== --}}

{{-- Modal untuk Detail Gaji --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">Detail Gaji</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="detail-content">Memuat data...</div>
            </div>
            <div class="modal-footer justify-content-between">
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
                <div>
                    <button type="button" class="btn btn-success btn-send-email" disabled>
                        <i class="fas fa-envelope"></i> Kirim ke Email
                    </button>
                    <button type="button" class="btn btn-danger btn-download-slip" disabled>
                        <i class="fas fa-file-pdf"></i> Cetak PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal untuk Edit Gaji --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="editGajiForm" action="{{ route('gaji.save') }}" method="POST">
                @csrf
                <input type="hidden" name="tarif_kehadiran_hidden" id="tarif_kehadiran_hidden"
                    value="{{ $tarifKehadiran }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Gaji Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="edit-form-content"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
