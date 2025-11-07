{{-- 
  PASTIKAN BLOK INI ADA DI ATAS '<li>'
  Logika untuk mengambil foto karyawan 
--}}
@php
    // Ambil model Karyawan yang terhubung dengan User yang sedang login
    $karyawan = Auth::user()->karyawan;

    // Tentukan URL foto
    $fotoUrl = null;
    if ($karyawan && $karyawan->foto) {
        // Gunakan path 'uploads' yang sudah kita perbaiki
        $fotoUrl = asset('uploads/foto_pegawai/' . $karyawan->foto);
    }
@endphp

<li class="nav-item dropdown">
    <a id="navbarDropdown" class="nav-link dropdown-toggle profile-btn" href="#" role="button"
        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bs-container="body"
        data-bs-strategy="fixed">

        {{-- Blok @if ini sekarang aman karena $fotoUrl sudah didefinisikan --}}
        @if ($fotoUrl)
            <img src="{{ $fotoUrl }}" class="rounded-circle me-2"
                style="width: 30px; height: 30px; object-fit: cover;" alt="Foto Profil">
        @else
            {{-- Fallback ke ikon jika tidak ada foto --}}
            <i class="fas fa-user-circle me-1"></i>
        @endif

        {{ Auth::user()->name }}
    </a>

    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
        <a class="dropdown-item" href="{{ route('profile.edit') }}">
            <i class="fas fa-user-edit fa-fw me-2"></i> {{ __('Profile') }}
        </a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fas fa-sign-out-alt fa-fw me-2"></i> {{ __('Logout') }}
        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>
</li>
