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
                <li class="{{ Request::routeIs('dashboard') ? '' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="{{ Request::routeIs('karyawan.*') ? '' : '' }}">
                    <a href="#karyawanSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ Request::routeIs('karyawan.*') ? 'true' : 'false' }}" class="dropdown-toggle">
                        <i class="fas fa-users"></i> Kelola Karyawan
                    </a>
                    <ul class="collapse list-unstyled {{ Request::routeIs('karyawan.*') ? 'show' : '' }}"
                        id="karyawanSubmenu">
                        <li class="{{ Request::routeIs('karyawan.index') ? '' : '' }}">
                            <a href="{{ route('karyawan.index') }}">Daftar Karyawan</a>
                        </li>
                        <li class="{{ Request::routeIs('karyawan.create') ? '' : '' }}">
                            <a href="{{ route('karyawan.create') }}">Tambah Karyawan</a>
                        </li>
                    </ul>
                </li>
                <li class="{{ Request::routeIs('aturan.index') ? '' : '' }}">
                    <a href="{{ route('aturan.index') }}">
                        <i class="fas fa-file-invoice-dollar"></i> Aturan Gaji
                    </a>
                </li>
            @endif

            {{-- PERUBAHAN DI SINI: Role Bendahara --}}
            @if (auth()->user()->role === 'bendahara')
                <li class="{{ Request::routeIs('dashboard') ? '' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>

                {{-- Menu Dropdown Kelola Gaji --}}
                <li class="{{ Request::routeIs('gaji.*') ? '' : '' }}">
                    <a href="{{ route('gaji.index') }}">
                        <i class="fas fa-money-check-alt"></i> Kelola Gaji
                    </a>
                </li>

                {{-- Menu Dropdown Baru Kelola Absensi --}}
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

                <li>
                    <a href="#">
                        <i class="fas fa-chart-line"></i> Laporan
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
