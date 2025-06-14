<nav id="sidebar">
    <div class="sidebar-header">
        <h3>Penggajian</h3>
        <small class="text-white-50">Al-Azhar 43</small>
    </div>

    <ul class="list-unstyled components">
        {{-- Role: Guest / Non-Login --}}
        @guest
            <li>
                <a href="{{ url('/') }}">
                    <i class="fas fa-home"></i> Kembali ke Beranda
                </a>
            </li>
            <li>
                <a href="{{ route('simulasi.index') }}">
                    <i class="fas fa-calculator"></i> Simulasi Gaji
                </a>
            </li>
            <li>
                <a href="{{ route('absensi.form') }}">
                    <i class="fas fa-user-check"></i> Absensi
                </a>
            </li>
        @endguest

        {{-- Role: Admin --}}
        @auth
            @if (auth()->user()->role === 'admin')
                <li class="{{ Request::routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="{{ Request::routeIs('karyawan.*') ? 'active' : '' }}">
                    <a href="#karyawanSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ Request::routeIs('karyawan.*') ? 'true' : 'false' }}" class="dropdown-toggle">
                        <i class="fas fa-users"></i> Kelola Karyawan
                    </a>
                    <ul class="collapse list-unstyled {{ Request::routeIs('karyawan.*') ? 'show' : '' }}"
                        id="karyawanSubmenu">
                        <li class="{{ Request::routeIs('karyawan.index') ? 'active' : '' }}">
                            <a href="{{ route('karyawan.index') }}">Daftar Karyawan</a>
                        </li>
                        <li class="{{ Request::routeIs('karyawan.create') ? 'active' : '' }}">
                            <a href="{{ route('karyawan.create') }}">Tambah Karyawan</a>
                        </li>
                    </ul>
                </li>
                <li class="{{ Request::routeIs('aturan.index') ? 'active' : '' }}">
                    <a href="{{ route('aturan.index') }}">
                        <i class="fas fa-file-invoice-dollar"></i> Aturan Gaji
                    </a>
                </li>
            @endif

            {{-- Role: Bendahara --}}
            @if (auth()->user()->role === 'bendahara')
                <li class="{{ Request::routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="{{ Request::routeIs('sesi-absensi.*') ? 'active' : '' }}">
                    <a href="{{ route('sesi-absensi.index') }}">
                        <i class="fas fa-user-clock"></i> Kelola Sesi Absensi
                    </a>
                </li>
                <li class="{{ Request::routeIs('gaji.*') ? 'active' : '' }}">
                    <a href="#gajiSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ Request::routeIs('gaji.*') ? 'true' : 'false' }}" class="dropdown-toggle">
                        <i class="fas fa-money-check-alt"></i> Kelola Gaji
                    </a>
                    <ul class="collapse list-unstyled {{ Request::routeIs('gaji.*') ? 'show' : '' }}" id="gajiSubmenu">
                        <li class="{{ Request::routeIs('gaji.index') ? 'active' : '' }}">
                            <a href="{{ route('gaji.index') }}">Daftar Gaji</a>
                        </li>
                        <li class="{{ Request::routeIs('gaji.create') ? 'active' : '' }}">
                            <a href="{{ route('gaji.create') }}">Tambah Gaji</a>
                        </li>
                    </ul>
                </li>
                <li class="{{ Request::routeIs('karyawan.index', 'karyawan.show') ? 'active' : '' }}">
                    <a href="{{ route('karyawan.index') }}">
                        <i class="fas fa-user-tie"></i> Daftar Karyawan
                    </a>
                </li>
                <li class="{{ Request::routeIs('laporan.absensi.index') ? 'active' : '' }}">
                    <a href="{{ route('laporan.absensi.index') }}">
                        <i class="fas fa-calendar-check"></i> Rekap Absensi
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-chart-line"></i> Laporan
                    </a>
                </li>
            @endif

            {{-- Logout for authenticated users (regardless of role) --}}
            <li>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-link text-white-50" type="submit"
                        style="padding-left: 10px; text-decoration: none;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </li>
        @endauth
    </ul>
</nav>
