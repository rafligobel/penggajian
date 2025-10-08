<nav id="sidebar" class="sidebar-light bg-light mb-4 shadow-sm">
    <div class="sidebar-header d-flex align-items-center p-3 ">
        <img src="{{ asset('logo/logoalazhar.png') }}" alt="Logo" style="height: 40px; margin-right: 15px;">
        <div>
            <h3 class="fs-5 mb-0" style="color:rgb(0, 0, 0)">Penggajian & Kepegawaian</h3>
            {{-- <small class="text-white-50">Al-Azhar 43</small> --}}
        </div>
    </div>

    <ul class="list-unstyled components">
        {{-- Common Links for All Users --}}
        {{-- Role: Guest / Non-Login --}}
        {{-- @guest
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
        @endguest --}}


        @auth
            @if (auth()->user()->role === 'superadmin')
                <li class="{{ Request::routeIs('dashboard') ? '' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="{{ Request::routeIs('jabatan.*') ? 'active' : '' }}">
                    <a href="{{ route('jabatan.index') }}">
                        <i class="fas fa-briefcase"></i> Kelola Jabatan
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

            @if (auth()->user()->role === 'admin')
                <li class="{{ Request::routeIs('dashboard') ? '' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="{{ Request::routeIs('jabatan.*') ? 'active' : '' }}">
                    <a href="{{ route('jabatan.index') }}">
                        <i class="fas fa-briefcase"></i> Kelola Jabatan
                    </a>
                </li>
                <li class="{{ Request::routeIs('tunjangan-kehadiran.*') ? 'active' : '' }}">
                    <a href="{{ route('tunjangan-kehadiran.index') }}">
                        <i class="fas fa-calendar-check"></i>
                        <span>Tunjangan Kehadiran</span>
                    </a>
                </li>
                <li class="{{ Request::routeIs('karyawan.*') ? 'active' : '' }}">
                    <a href="{{ route('karyawan.index') }}">
                        <i class="fas fa-users"></i> Kelola Pegawai
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

            {{-- Role Bendahara --}}
            @if (auth()->user()->role === 'bendahara')
                <li class="{{ Request::routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>

                <li class="{{ Request::routeIs('gaji.*') ? 'active' : '' }}">
                    <a href="{{ route('gaji.index') }}">
                        <i class="fas fa-money-check-alt"></i> Kelola Gaji
                    </a>
                </li>

                <li class="{{ Request::routeIs(['sesi-absensi.*', 'absensi.rekap']) ? 'active' : '' }}">
                    <a href="#absensiSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ Request::routeIs(['sesi-absensi.*', 'absensi.rekap']) ? '' : 'false' }}"
                        class="dropdown-toggle">
                        <i class="fas fa-calendar-check"></i> Kelola Absensi
                    </a>
                    <ul class="collapse list-unstyled {{ Request::routeIs(['sesi-absensi.*', 'absensi.rekap']) ? 'show' : '' }}"
                        id="absensiSubmenu">
                        <li class="{{ Request::routeIs('sesi-absensi.*') ? 'active' : '' }}">
                            <a href="{{ route('sesi-absensi.index') }}">Sesi Absensi</a>
                        </li>
                        <li class="{{ Request::routeIs('absensi.rekap') ? 'active' : '' }}">
                            <a href="{{ route('absensi.rekap') }}">Rekap Absensi</a>
                        </li>
                    </ul>
                </li>

                {{-- <li class="{{ Request::routeIs('karyawan.index', 'karyawan.show') ? '' : '' }}">
                    <a href="{{ route('karyawan.index') }}">
                        <i class="fas fa-user-tie"></i> Daftar Karyawan
                    </a>
                </li> --}}
                <li class="{{ Request::routeIs('karyawan.*') ? 'active' : '' }}">
                    <a href="{{ route('karyawan.index') }}">
                        <i class="fas fa-users"></i> Daftar Pegawai
                    </a>
                </li>

                <li class="{{ Request::routeIs('laporan.*') ? 'active' : '' }}">
                    <a href="#laporanSubmenu" data-bs-toggle="collapse"
                        aria-expanded="{{ Request::routeIs('laporan.*') ? '' : 'false' }}" class="dropdown-toggle">
                        <i class="fas fa-chart-line"></i> Laporan
                    </a>
                    <ul class="collapse list-unstyled {{ Request::routeIs('laporan.*') ? 'show' : '' }}"
                        id="laporanSubmenu">
                        <li class="{{ Request::routeIs('laporan.gaji.bulanan') ? 'active' : '' }}">
                            <a href="{{ route('laporan.gaji.bulanan') }}">Laporan Gaji Bulanan</a>
                        </li>
                        <li class="{{ Request::routeIs('laporan.per.karyawan') ? 'active' : '' }}">
                            <a href="{{ route('laporan.per.karyawan') }}">Laporan per Pegawai</a>
                        </li>
                        <li class="{{ Request::routeIs('laporan.absensi') ? 'active' : '' }}">
                            <a href="{{ route('laporan.absensi') }}">Laporan Absensi</a>
                        </li>
                    </ul>
                </li>
                <li class="{{ Request::routeIs('notifications.*') ? 'active' : '' }}">
                    <a href="{{ route('notifications.index') }}">
                        <i class="fas fa-bell"></i> Notifikasi
                    </a>
                </li>
                <li class="{{ Request::routeIs('tanda_tangan.*') ? 'active' : '' }}">
                    <a href="{{ route('tanda_tangan.index') }}">
                        <i class="fas fa-cog"></i> Pengaturan
                    </a>
                </li>
            @endif

            {{-- Tombol Logout untuk semua user yang sudah login --}}
            {{-- <li>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn  " type="submit"
                        style="padding-left: 10px; text-decoration: none;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </li> --}}
        @endauth
    </ul>
</nav>
