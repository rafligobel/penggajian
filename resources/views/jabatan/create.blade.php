@extends('layouts.app')

@section('title', 'Tambah Jabatan Baru')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Formulir Tambah Jabatan</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('jabatan.store') }}" method="POST">
                            @csrf
                            @include('jabatan.form')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
