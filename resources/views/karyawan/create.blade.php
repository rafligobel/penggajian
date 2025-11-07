@extends('layouts.app')

@section('content')
    <div class="container">
        <h3 class="mt-4 mb-3 fw-bold text-primary">Tambah Pegawai</h3>

        <form action="{{ route('karyawan.store') }}" method="POST" enctype="multipart/form-data">
            @include('karyawan.form')
        </form>
    </div>
@endsection
