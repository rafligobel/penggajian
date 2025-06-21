@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h3 class="mb-4 fw-bold text-primary">Profil Pengguna</h3>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                {{-- Kartu untuk update informasi profil --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                {{-- Kartu untuk update password --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                {{-- Kartu untuk hapus akun --}}
                <div class="card shadow-sm border-danger">
                    <div class="card-body p-4">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
