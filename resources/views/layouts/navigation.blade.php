<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">Penggajian</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
            aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ms-auto">

                {{-- Role: Guest / Non-Login --}}
                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/') }}">Kembali</a>
                    </li>
                    {{-- <li class="nav-item">
                        <a class="nav-link" href="{{ route('simulasi.index') }}">Simulasi Gaji</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('absensi.form') }}">Absens</a>
                    </li> --}}

                @endguest

                {{-- Role: Admin --}}
                @auth


                    @if (auth()->user()->role === 'admin')
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('karyawan.index') }}">Kelola Karyawan</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('aturan.index') }}">Aturan Gaji</a>
                        </li>
                    @endif

                    {{-- Role: Bendahara --}}
                    @if (auth()->user()->role === 'bendahara')
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('gaji.index') }}">Kelola Gaji</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('karyawan.index') }}">Daftar Karyawan</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Laporan</a> {{-- nanti arahkan ke laporan --}}
                        </li>
                    @endif

                    {{-- Tombol Logout --}}
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-link nav-link" type="submit">Logout</button>
                        </form>
                    </li>
                @endauth

            </ul>
        </div>
    </div>
</nav>
