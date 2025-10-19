<li class="nav-item dropdown">
    {{-- PERBAIKAN: Tambahkan dua atribut di baris ini --}}
    <a id="navbarDropdown" class="nav-link dropdown-toggle profile-btn" href="#" role="button"
        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bs-container="body"
        data-bs-strategy="fixed">
        <i class="fas fa-user-circle me-1"></i> {{ Auth::user()->name }}
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
