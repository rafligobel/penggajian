@extends('layouts.app')

@section('content')
    <div class="container">
        <h3>Edit Karyawan</h3>
        <form action="{{ route('karyawan.update', $karyawan->id) }}" method="POST">
            @method('PUT')
            @include('karyawan.form', ['karyawan' => $karyawan])
        </form>
    </div>
@endsection
