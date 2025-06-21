<section>
    <header>
        <h4 class="card-title text-danger">Hapus Akun</h4>
        <p class="text-muted">Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen.</p>
    </header>

    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-user-deletion">
        Hapus Akun
    </button>

    <div class="modal fade" id="confirm-user-deletion" tabindex="-1" aria-labelledby="confirmUserDeletionLabel"
        aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmUserDeletionLabel">Konfirmasi Hapus Akun</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <p class="text-muted">
                            Apakah Anda yakin? Setelah akun dihapus, semua data akan hilang selamanya. Harap masukkan
                            password Anda untuk mengonfirmasi.
                        </p>

                        <div class="mt-3">
                            <label for="password_delete" class="form-label visually-hidden">Password</label>
                            <input id="password_delete" name="password" type="password"
                                class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                                placeholder="Password" />
                            @error('password', 'userDeletion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger ms-2">Ya, Hapus Akun Saya</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
