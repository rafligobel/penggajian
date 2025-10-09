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

    <style>
        /* Custom CSS for sidebar */
        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: #f8f8f8;
            color: #fff;
            transition: all 0.3s;
        }

        #sidebar.active {
            margin-left: -250px;
        }

        #content {
            width: 100%;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
        }

        .sidebar-header {
            padding: 20px;
            background: #f8f8f8;
        }

        .sidebar-header h3 {
            color: #fff;
            margin-bottom: 0;
        }

        #sidebar ul.components {
            padding: 20px 0;
            border-top: 1px solid #cacaca;
        }

        #sidebar ul li a {
            padding: 10px;
            font-size: 1.1em;
            display: block;
            color: #1b1b1b;
            text-decoration: none;
        }

        #sidebar ul li a:hover {
            color: #f8f8f8;
            background: #0080ff;
        }

        #sidebar ul li.active>a,
        a[aria-expanded="true"] {
            color: #fff;
            background: #0d6efd;
        }

        ul ul a {
            font-size: 0.9em !important;
            padding-left: 30px !important;
            background: #f8f8f8;
        }

        /* -- PERBAIKAN CSS UNTUK NOTIFIKASI -- */
        .notification-item a:hover {
            background-color: #f8f9fa;
        }

        /* --- TAMBAHKAN KODE CSS BARU DI SINI --- */
        .notification-item a.dropdown-item {
            white-space: normal;
            /* Memastikan teks panjang bisa wrap */
        }

        .icon-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notification-content {
            line-height: 1.3;
        }

        .dropdown-item.non-clickable {
            cursor: default;
        }

        .dropdown-item.non-clickable:hover {
            background-color: transparent;
        }

        /* --- AKHIR DARI KODE CSS BARU --- */

        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }

            /* ... (sisa CSS Anda) ... */
        }
    </style>
</head>

<body>
    <div class="wrapper">
        @auth
            {{-- Sidebar akan di-include di sini --}}
            @include('layouts.navigation')
        @endauth

        {{-- Konten Utama --}}
        <div id="content">
            @auth
                {{-- Top Navbar (Header) --}}
                <nav class="navbar navbar-expand-lg navbar-light bg-white mb-4 shadow-sm rounded-3">
                    <div class="container-fluid">

                        {{-- Tombol untuk toggle sidebar, hanya muncul untuk admin & bendahara --}}
                        @if (in_array(auth()->user()->role, ['admin', 'bendahara', 'superadmin']))
                            <button type="button" data-sidebar-toggle class="btn btn-outline-primary me-3">
                                <i class="fa-solid fa-bars"></i>
                            </button>
                        @endif

                        {{-- Navbar items di kanan --}}
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                            {{-- Notifikasi, hanya untuk admin & bendahara --}}
                            @if (in_array(auth()->user()->role, ['admin', 'bendahara']))
                                <li class="nav-item dropdown">
                                    <a class="nav-link position-relative" href="#" id="notificationDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-bell fs-5"></i>
                                        @if (auth()->user()->unreadNotifications->count())
                                            <span class="badge rounded-pill bg-danger position-absolute"
                                                style="top: 0; right: -5px; font-size: 0.6em;">
                                                {{ auth()->user()->unreadNotifications->count() }}
                                            </span>
                                        @endif
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2"
                                        aria-labelledby="notificationDropdown"
                                        style="width: 380px; max-height: 400px; overflow-y: auto;">
                                        <li>
                                            <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                                Notifikasi
                                                <a href="{{ route('notifications.index') }}"
                                                    class="badge bg-primary fw-normal">Lihat Semua</a>
                                            </h6>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider mt-0">
                                        </li>

                                        @forelse (auth()->user()->unreadNotifications->take(5) as $notification)
                                            <li class="notification-item">
                                                <a class="dropdown-item d-flex align-items-start py-2"
                                                    href="{{ route('notifications.markAsRead', $notification->id) }}"
                                                    target="_blank">

                                                    <div
                                                        class="icon-circle {{ $notification->data['is_error'] ?? false ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} me-3">
                                                        <i
                                                            class="fas {{ $notification->data['is_error'] ?? false ? 'fa-exclamation-triangle' : 'fa-file-pdf' }}"></i>
                                                    </div>

                                                    <div class="notification-content">
                                                        <p class="mb-0 small fw-bold">{{ $notification->data['message'] }}
                                                        </p>
                                                        <div class="small text-muted" style="font-size: 0.75rem;">
                                                            {{ $notification->created_at->diffForHumans() }}
                                                        </div>
                                                    </div>
                                                </a>
                                            </li>
                                        @empty
                                            <li>
                                                <a class="dropdown-item non-clickable text-center text-muted small py-3">
                                                    Tidak ada notifikasi baru.
                                                </a>
                                            </li>
                                        @endforelse

                                        <li>
                                            <hr class="dropdown-divider mb-0">
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-center small text-primary py-2"
                                                href="{{ route('notifications.index') }}">
                                                Lihat Riwayat Notifikasi
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            @endif

                            {{-- Profile Dropdown --}}
                            @include('layouts.partials._profile-dropdown')

                        </ul>
                    </div>
                </nav>
            @endauth

            {{-- Tempat untuk konten halaman spesifik --}}
            <main>
                @yield('content')
            </main>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggler = document.querySelector('[data-sidebar-toggle]');

            if (sidebarToggler) {
                // Set initial state from localStorage
                sidebarToggler.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    if (sidebar.classList.contains('active')) {
                        localStorage.setItem('sidebarState', 'closed');
                    } else {
                        localStorage.setItem('sidebarState', 'open');
                    }
                });

                // Add click event listener
                sidebarToggler.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    if (sidebar.classList.contains('active')) {
                        localStorage.setItem('sidebarState', 'closed');
                    } else {
                        localStorage.setItem('sidebarState', 'open');
                    }
                });
            }

            const deleteModal = document.getElementById('deleteConfirmationModal');
            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const deleteUrl = button.getAttribute('data-url');
                    const deleteForm = document.getElementById('delete-form');
                    deleteForm.setAttribute('action', deleteUrl);
                });
            }
        });
    </script>
    @stack('scripts')
</body>

</html>
