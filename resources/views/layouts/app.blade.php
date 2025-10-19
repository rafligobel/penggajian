<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penggajian & Kepegawaian Al-Azhar 43</title>
    <link rel="icon" href="{{ asset('logo/logoalazhar.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css' rel='stylesheet'>

    {{-- CSS DENGAN PERBAIKAN z-index DAN PUSH CONTENT --}}
    <style>
        body {
            overflow-x: hidden;
            background-color: #f8f9fa;
        }

        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        /* --- Sidebar & Overlay Styles --- */
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: #ffffff;
            transition: margin-left 0.35s ease-in-out;
            /* === PERBAIKAN #1: z-index diturunkan agar di bawah modal === */
            z-index: 1030;
            border-right: 1px solid #dee2e6;
        }

        .wrapper.sidebar-hidden #sidebar {
            margin-left: -250px;
        }

        #sidebar ul.components {
            padding: 0;
            border-top: 1px solid #dee2e6;
        }

        #sidebar ul li a {
            padding: 12px 18px;
            font-size: 1em;
            display: block;
            color: #343a40;
            text-decoration: none;
            font-weight: 500;
            border-left: 3px solid transparent;
        }

        #sidebar ul li a:hover {
            color: #0d6efd;
            background: #e9ecef;
            /* border-left: 3px solid #0d6efd; */
        }

        #sidebar ul li.active>a,
        a[aria-expanded="true"] {
            color: #ffffff;
            background: #0d6efd;
            /* border-left: 3px solid #0d6efd; */
        }

        #sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            /* === PERBAIKAN #1: z-index diturunkan agar di bawah modal === */
            z-index: 1029;
            cursor: pointer;
        }

        /* === PERBAIKAN #2: Aturan untuk PUSH CONTENT di DESKTOP === */
        #content {
            width: 100%;
            padding: 24px;
            min-height: 100vh;
            transition: margin-left 0.35s ease-in-out;
            /* Tambahkan transisi */
            /* Default margin saat sidebar terbuka */
        }

        /* Aturan saat sidebar ditutup */
        .wrapper.sidebar-hidden #content {
            margin-left: 0;
            /* Konten melebar mengisi ruang */
        }

        /* ======================================================= */

        /* Hapus latar belakang biru saat dropdown aktif (Tidak Berubah) */
        .navbar-nav .nav-link[data-bs-toggle="dropdown"][aria-expanded="true"] {
            background-color: transparent !important;
            box-shadow: none !important;
            color: inherit !important;
        }

        /* --- Aturan Responsive (< 768px) --- */
        @media (max-width: 768px) {
            #sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                margin-left: -250px;
                box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            }

            .wrapper.sidebar-toggled #sidebar {
                margin-left: 0;
            }

            .wrapper.sidebar-toggled #sidebar-overlay {
                display: block;
            }

            #content {
                padding: 15px;
                /* === PERBAIKAN #2: Pastikan di mobile margin selalu 0 === */
                margin-left: 0 !important;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        @auth
            @include('layouts.navigation')
        @endauth

        <div id="sidebar-overlay"></div>

        <div id="content">
            @auth
                <nav class="navbar navbar-expand-lg navbar-light bg-white mb-4 shadow-sm rounded-3">
                    <div class="container-fluid">
                        @if (in_array(auth()->user()->role, ['admin', 'bendahara', 'superadmin']))
                            <button type="button" data-sidebar-toggle class="btn btn-outline-primary me-3">
                                <i class="fa-solid fa-bars"></i>
                            </button>
                        @endif

                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                            @if (in_array(auth()->user()->role, ['admin', 'bendahara']))
                                <li class="nav-item dropdown">
                                    {{-- Atribut untuk melepaskan dropdown (Tidak Berubah) --}}
                                    <a class="nav-link position-relative" href="#" id="notificationDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false"
                                        data-bs-container="body" data-bs-strategy="fixed">
                                        <i class="fas fa-bell fs-5"></i>
                                        @if (auth()->user()->unreadNotifications->count())
                                            <span class="badge rounded-pill bg-danger position-absolute"
                                                style="top: 0; right: -5px; font-size: 0.6em;">
                                                {{ auth()->user()->unreadNotifications->count() }}
                                            </span>
                                        @endif
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2"
                                        style="max-height: 400px; overflow-y: auto; width: 380px;"
                                        aria-labelledby="notificationDropdown">

                                        <li>
                                            <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                                Notifikasi <a href="{{ route('notifications.index') }}"
                                                    class="badge bg-primary fw-normal">Lihat Semua</a></h6>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider mt-0">
                                        </li>
                                        @forelse (auth()->user()->unreadNotifications->take(5) as $notification)
                                            <li class="notification-item">
                                                <a class="dropdown-item d-flex align-items-start py-2"
                                                    href="{{ route('notifications.markAsRead', $notification->id) }}">
                                                    <div
                                                        class="icon-circle {{ $notification->data['is_error'] ?? false ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} me-3">
                                                        <i
                                                            class="fas {{ $notification->data['is_error'] ?? false ? 'fa-exclamation-triangle' : 'fa-file-pdf' }}"></i>
                                                    </div>
                                                    <div class="notification-content" style="white-space: normal;">
                                                        <p class="mb-0 small fw-bold">{{ $notification->data['message'] }}
                                                        </p>
                                                        <div class="small text-muted" style="font-size: 0.75rem;">
                                                            {{ $notification->created_at->diffForHumans() }}</div>
                                                    </div>
                                                </a>
                                            </li>
                                        @empty
                                            <li>
                                                <p class="dropdown-item text-center text-muted small py-3 mb-0"
                                                    style="cursor: default;">Tidak ada notifikasi baru.</p>
                                            </li>
                                        @endforelse
                                        <li>
                                            <hr class="dropdown-divider mb-0 mt-2">
                                        </li>
                                        <li><a class="dropdown-item text-center small text-primary py-2"
                                                href="{{ route('notifications.index') }}">Lihat Riwayat Notifikasi</a></li>
                                    </ul>
                                </li>
                            @endif
                            @include('layouts.partials._profile-dropdown')
                        </ul>
                    </div>
                </nav>
            @endauth

            <main>@yield('content')</main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    {{-- JavaScript Anda sudah benar dan tidak perlu diubah --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const wrapper = document.querySelector('.wrapper');
            const sidebarToggler = document.querySelector('[data-sidebar-toggle]');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            const isMobile = () => window.innerWidth <= 768;

            function toggleSidebar() {
                if (isMobile()) {
                    wrapper.classList.toggle('sidebar-toggled');
                } else {
                    wrapper.classList.toggle('sidebar-hidden');
                    localStorage.setItem('sidebarState', wrapper.classList.contains('sidebar-hidden') ? 'hidden' :
                        'visible');
                }
            }

            function setInitialState() {
                if (isMobile()) {
                    wrapper.classList.remove('sidebar-toggled', 'sidebar-hidden');
                } else {
                    if (localStorage.getItem('sidebarState') === 'hidden') {
                        wrapper.classList.add('sidebar-hidden');
                    } else {
                        wrapper.classList.remove('sidebar-hidden');
                    }
                }
            }
            if (sidebarToggler) sidebarToggler.addEventListener('click', toggleSidebar);
            if (sidebarOverlay) sidebarOverlay.addEventListener('click', () => {
                if (isMobile()) wrapper.classList.remove('sidebar-toggled');
            });
            setInitialState();
            window.addEventListener('resize', setInitialState);
        });
    </script>
    @stack('scripts')
</body>

</html>
