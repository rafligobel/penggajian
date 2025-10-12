@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>Tambah Pengguna Baru</h1>
        </div>

        <div class="section-body">
            {{-- Arahkan form ke route 'users.store' dengan method POST --}}
            <form action="{{ route('users.store') }}" method="POST">
                {{-- Cukup panggil partial form yang sudah dibuat --}}
                @include('users._form', ['user' => new \App\Models\User()])
            </form>
        </div>
    </section>
@endsection
