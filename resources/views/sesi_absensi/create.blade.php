{{-- File: resources/views/sesi_absensi/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Buat Sesi Absensi Baru</h3>
    <form action="{{ route('sesi-absensi.store') }}" method="POST">
        @csrf
        @include('sesi_absensi._form')
    </form>
</div>
@endsection