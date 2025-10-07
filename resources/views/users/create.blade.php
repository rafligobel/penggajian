@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>Tambah Pengguna Baru</h1>
        </div>

        <div class="section-body">
            <div class="card">
                {{-- Arahkan form ke route 'users.store' dengan method POST --}}
                <form action="{{ route('users.store') }}" method="POST">
                    @csrf
                    <div class="card-header">
                        <h4>Formulir Tambah Pengguna</h4>
                    </div>
                    <div class="card-body">
                        {{-- Menampilkan error validasi jika ada --}}
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="name">Nama</label>
                            <input type="text" name="name" id="name" class="form-control"
                                value="{{ old('name') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control"
                                value="{{ old('email') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select name="role" id="role" class="form-control" required>
                                <option value="">Pilih Role</option>
                                {{-- <option value="superadmin">Super Admin</option> --}}
                                <option value="admin">Admin</option>
                                <option value="bendahara">Bendahara</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
