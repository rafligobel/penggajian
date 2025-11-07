@extends('layouts.app')

@section('content')
    <div class="container">
        <h3>Edit Pegawai</h3>

        <form action="{{ route('karyawan.update', $karyawan->id) }}" method="POST" enctype="multipart/form-data">
            @method('PUT')
            @include('karyawan.form', ['karyawan' => $karyawan])
        </form>
    </div>
@endsection
