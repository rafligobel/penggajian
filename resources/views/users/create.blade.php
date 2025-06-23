@extends('layouts.app')
@section('content')
    <div class="container py-4">
        <h3 class="fw-bold text-primary mb-4">Tambah Pengguna Baru</h3>
        <form action="{{ route('users.store') }}" method="POST">
            @include('users._form')
        </form>
    </div>
@endsection
