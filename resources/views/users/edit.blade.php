@extends('layouts.app')
@section('content')
    <div class="container py-4">
        <h3 class="fw-bold text-primary mb-4">Edit Pengguna: {{ $user->name }}</h3>
        <form action="{{ route('users.update', $user) }}" method="POST">
            @method('PUT')
            @include('users._form')
        </form>
    </div>
@endsection
