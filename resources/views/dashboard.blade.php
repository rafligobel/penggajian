@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="mb-4">Dashboard {{ ucfirst($role) }}</h2>

        @if ($role == 'admin' || $role == 'bendahara')
            <div class="row">
                <div class="col-md-4">
                    <div class="card text-bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Karyawan</h5>
                            <p class="card-text fs-4">{{ $jumlahKaryawan }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Gaji Bulan Ini</h5>
                            <p class="card-text fs-4">Rp {{ number_format($totalGajiBulanIni, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-bg-warning mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Slip Gaji Dicetak</h5>
                            <p class="card-text fs-4">{{ $jumlahSlipGaji }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info">Anda tidak memiliki akses ke ringkasan gaji.</div>
        @endif

    </div>
@endsection
