<nav id="sidebar">
    <div class="sidebar-header d-flex align-items-center p-3">
        <img src="{{ asset('logo/logoalazhar.png') }}" alt="Logo" style="height: 40px; margin-right: 15px;">
        <div>
            <h3 class="fs-5 mb-0">Penggajian</h3>
            <small class="text-white-50">Al-Azhar 43</small>
        </div>
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

        @auth
            @if (auth()->user()->role === 'superadmin,admin')
                <li class="{{ Request::routeIs('dashboard') ? '' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="{{ Request::routeIs('karyawan.*') ? '' : '' }}">
                    <a href="{{ route('karyawan.index') }}">
                        <i class="fas fa-users"></i> Kelola Karyawan
                    </a>
                </li>

                <li class="{{ Request::routeIs('users.*') ? '' : '' }}">
                    <a href="{{ route('users.index') }}">
                        <i class="fas fa-user-shield"></i> Manajemen Pengguna
                    </a>
                </li>

                {{-- <li class="{{ Request::routeIs('laporan.*') ? 'active' : '' }}">
                    <a href="{{ route('laporan.gaji.bulanan') }}">
                        <i class="fas fa-chart-line"></i> Lihat Laporan
                    </a>
                </li> --}}
            @endif

            {{-- @if (auth()->user()->role === 'admin')
                <li class="{{ Request::routeIs('dashboard') ? '' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="{{ Request::routeIs('karyawan.*') ? '' : '' }}">
                    <a href="{{ route('karyawan.index') }}">
                        <i class="fas fa-users"></i> Kelola Karyawan
                    </a>
                </li>

                <li class="{{ Request::routeIs('users.*') ? '' : '' }}">
                    <a href="{{ route('users.index') }}">
                        <i class="fas fa-user-shield"></i> Manajemen Pengguna
                    </a>
                </li>

                {{-- <li class="{{ Request::routeIs('laporan.*') ? 'active' : '' }}">
                    <a href="{{ route('laporan.gaji.bulanan') }}">
                        <i class="fas fa-chart-line"></i> Lihat Laporan
                    </a>
                </li> 
            @endif --}}

            {{-- Role Bendahara --}}
            @if (auth()->user()->role === 'bendahara')
                <li class="{{ Request::routeIs('dashboard') ? '' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>

                <li class="{{ Request::routeIs('gaji.*') ? '' : '' }}">
                    <a href="{{ route('gaji.index') }}">
                        <i class="fas fa-money-check-alt"></i> Kelola Gaji
                    </a>
                </li>

                <li class="{{ Request::routeIs(['sesi-absensi.*', 'laporan.absensi.index']) ? '' : '' }}">
                    <a href="#absensiSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ Request::routeIs(['sesi-absensi.*', 'laporan.absensi.index']) ? 'true' : 'false' }}"
                        class="dropdown-toggle">
                        <i class="fas fa-calendar-check"></i> Kelola Absensi
                    </a>
                    <ul class="collapse list-unstyled {{ Request::routeIs(['sesi-absensi.*', 'laporan.absensi.index']) ? 'show' : '' }}"
                        id="absensiSubmenu">
                        <li class="{{ Request::routeIs('sesi-absensi.*') ? '' : '' }}">
                            <a href="{{ route('sesi-absensi.index') }}">Sesi Absensi</a>
                        </li>
                        <li class="{{ Request::routeIs('laporan.absensi.index') ? '' : '' }}">
                            <a href="{{ route('laporan.absensi.index') }}">Rekap Absensi</a>
                        </li>
                    </ul>
                </li>

                <li class="{{ Request::routeIs('karyawan.index', 'karyawan.show') ? '' : '' }}">
                    <a href="{{ route('karyawan.index') }}">
                        <i class="fas fa-user-tie"></i> Daftar Karyawan
                    </a>
                </li>

                <li class="{{ Request::routeIs('laporan.*') ? '' : '' }}">
                    <a href="#laporanSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ Request::routeIs('laporan.*') ? 'true' : 'false' }}" class="dropdown-toggle">
                        <i class="fas fa-chart-line"></i> Laporan
                    </a>
                    <ul class="collapse list-unstyled {{ Request::routeIs('laporan.*') ? 'show' : '' }}"
                        id="laporanSubmenu">
                        <li class="{{ Request::routeIs('laporan.gaji.bulanan') ? '' : '' }}">
                            <a href="{{ route('laporan.gaji.bulanan') }}">Laporan Gaji Bulanan</a>
                        </li>
                        <li class="{{ Request::routeIs('laporan.per.karyawan') ? '' : '' }}">
                            <a href="{{ route('laporan.per.karyawan') }}">Laporan per Karyawan</a>
                        </li>
                        <li><a href="{{ route('laporan.absensi') }}">Laporan Absensi</a></li>
                    </ul>
                </li>
                <li class="{{ Request::routeIs('notifications.*') ? '' : '' }}">
                    <a href="{{ route('notifications.index') }}">
                        <i class="fas fa-bell"></i> Notifikasi
                    </a>
                </li>
                <li class="{{ Request::routeIs('tanda_tangan.*') ? '' : '' }}">
                    <a href="{{ route('tanda_tangan.index') }}">
                        <i class="fas fa-cog"></i> Pengaturan
                    </a>
                </li>
            @endif

            {{-- Tombol Logout untuk semua user yang sudah login --}}
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
