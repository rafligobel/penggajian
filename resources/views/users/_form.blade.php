@csrf
<div class="card shadow-sm border-0">
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-3">
            <label for="name" class="form-label">Nama Lengkap</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $user->name) }}"
                required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Alamat Email</label>
            <input type="email" name="email" id="email" class="form-control"
                value="{{ old('email', $user->email) }}" required>
        </div>
        <div class="mb-3">
            <label for="role" class="form-label">Peran (Role)</label>
            <select name="role" id="role" class="form-select" required>
                {{-- Opsi "Tenaga Kerja" dihilangkan karena prosesnya terpisah --}}
                <option value="admin" @selected(old('role', $user->role) == 'admin')>Admin</option>
                <option value="bendahara" @selected(old('role', $user->role) == 'bendahara')>Bendahara</option>
            </select>
            <small class="text-muted">Untuk membuat akun Tenaga Kerja, silakan melalui menu "Data Karyawan".</small>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" id="password" class="form-control"
                {{ $user->exists ? '' : 'required' }}>
            @if ($user->exists)
                <small class="text-muted">Kosongkan jika tidak ingin mengubah password.</small>
            @endif
        </div>
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                {{ $user->exists ? '' : 'required' }}>
        </div>
    </div>
    <div class="card-footer bg-light">
        {{-- Tombol submit akan disesuaikan di view create/edit --}}
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</div>
