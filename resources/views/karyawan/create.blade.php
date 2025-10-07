@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Tambah Pegawai</h3>
    <form action="{{ route('karyawan.store') }}" method="POST">
        @include('karyawan.form')
    </form>
</div>
@endsection
