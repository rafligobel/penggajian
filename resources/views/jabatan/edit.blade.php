@extends('layouts.app')

@section('title', 'Edit Jabatan')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Formulir Edit Jabatan</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('jabatan.update', $jabatan) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            @include('jabatan.form', ['jabatan' => $jabatan])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
