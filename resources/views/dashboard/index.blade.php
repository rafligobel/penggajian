@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-center align-items-center" style="min-height: 15vh;">
        <div class="text-center">
            @if (Auth::check())
                <h3 class="fs-3 ">Selamat datang, <strong>{{ Auth::user()->name }}</strong>!</h3>
            @else
                <h3 class="fs-3">Selamat datang, silakan lihat ringkasan gaji berikut ini.</h3>
            @endif
        </div>
    </div>


    <p class="mb-3 mt-1 fs-4">Ringkasan Gaji</p>

    <div class="row">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Karyawan</h5>
                    <p class="card-text fs-3">{{ $totalKaryawan }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Gaji Keseluruhan</h5>
                    <p class="card-text fs-4">Rp {{ number_format($totalGaji, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Gaji Bulan Ini</h5>
                    <p class="card-text fs-4">Rp {{ number_format($totalGajiBulanIni, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
