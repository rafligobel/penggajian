@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
            {{-- Lebar kolom diperkecil dari md-5 menjadi md-4 --}}
            <div class="col-md-4">

                <!-- Logo diperkecil -->
                <div class="text-center mb-3">
                    <a href="/">
                        <x-application-logo style="width: 70px; height: 70px; margin: auto;" />
                    </a>
                </div>

                <div class="card border-0 shadow-sm">
                    {{-- Padding diperkecil dari p-4 menjadi p-3 --}}
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold">Selamat Datang</h3>
                            <p class="text-muted small">Silakan login untuk melanjutkan</p>
                        </div>

                        <!-- Session Status -->
                        @if (session('status'))
                            <div class="alert alert-success mb-4">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <!-- Alamat Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Alamat Email</label>
                                {{-- Kelas form-control-lg dihapus --}}
                                <input id="email" type="email"
                                    class="form-control @error('email') is-invalid @enderror" name="email"
                                    value="{{ old('email') }}" required autofocus>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                {{-- Kelas form-control-lg dihapus --}}
                                <input id="password" type="password"
                                    class="form-control @error('password') is-invalid @enderror" name="password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Remember Me & Lupa Password -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                    <label class="form-check-label" for="remember">
                                        Ingat Saya
                                    </label>
                                </div>
                                @if (Route::has('password.request'))
                                    <a class="small" href="{{ route('password.request') }}">
                                        Lupa Password?
                                    </a>
                                @endif
                            </div>

                            <!-- Tombol Login -->
                            <div class="d-grid">
                                {{-- Kelas btn-lg dihapus --}}
                                <button type="submit" class="btn btn-primary">
                                    Login
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                @if (Route::has('register'))
                    <div class="text-center mt-3">
                        <p class="text-muted">Belum punya akun? <a href="{{ route('register') }}">Daftar di sini</a></p>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection
