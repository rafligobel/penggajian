@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="col-md-5">

                <!-- Logo -->
                <div class="text-center mb-4">
                    <a href="/">
                        {{-- Logo ini diambil dari komponen default Laravel Breeze. --}}
                        {{-- Sesuaikan ukurannya jika perlu. --}}
                        <x-application-logo style="width: 80px; height: 80px; margin: auto;" />
                    </a>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold">Lupa Password</h3>
                            <p class="text-muted">Masukkan alamat email Anda, dan kami akan mengirimkan link untuk mereset
                                password Anda.</p>
                        </div>

                        <!-- Session Status -->
                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf

                            <!-- Alamat Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Alamat Email</label>
                                <input id="email" type="email"
                                    class="form-control @error('email') is-invalid @enderror" name="email"
                                    value="{{ old('email') }}" required autofocus>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Tombol Kirim Link -->
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Kirim Link Reset Password
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <p class="text-muted"><a href="{{ route('login') }}">Kembali ke Login</a></p>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
