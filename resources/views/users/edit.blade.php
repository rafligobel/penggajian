@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Edit Pengguna</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Whoops!</strong> Ada beberapa masalah dengan input Anda.<br><br>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form ini mengirim data ke method 'update'. Perhatikan $user di dalam route(). --}}
        <form method="POST" action="{{ route('users.update', $user) }}"> @csrf
            @method('PUT')

            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12">
                    <div class="form-group">
                        <strong>Nama:</strong>
                        <input type="text" name="name" value="{{ $user->name }}" class="form-control"
                            placeholder="Nama">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12">
                    <div class="form-group">
                        <strong>Email:</strong>
                        <input type="email" name="email" value="{{ $user->email }}" class="form-control"
                            placeholder="Email">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12">
                    <div class="form-group">
                        <strong>Password (opsional):</strong>
                        <input type="password" name="password" class="form-control"
                            placeholder="Isi untuk mengubah password">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12">
                    <div class="form-group">
                        <strong>Konfirmasi Password:</strong>
                        <input type="password" name="password_confirmation" class="form-control"
                            placeholder="Konfirmasi password baru">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12 text-center mt-3">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a class="btn btn-secondary" href="{{ route('users.index') }}">Batal</a>
                </div>
            </div>
        </form>
    </div>
@endsection
