@extends('layouts.app')

@section('content')
    <div class="container">
        <h3 class="mb-4">Hasil Simulasi Gaji</h3>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Komponen</th>
                    <th>Nilai (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($input as $key => $value)
                    <tr>
                        <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                        <td>Rp {{ number_format($value, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="table-success">
                    <th>Total Gaji Bersih</th>
                    <th>Rp {{ number_format($gaji_bersih, 0, ',', '.') }}</th>
                </tr>
            </tbody>
        </table>

        <a href="{{ route('simulasi.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
@endsection
