{{-- File: resources/views/sesi_absensi/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Edit Sesi Absensi</h3>
    <form action="{{ route('sesi-absensi.update', $sesiAbsensi->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('sesi_absensi._form')
    </form>
</div>
@endsection