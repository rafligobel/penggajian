<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penggajian Al-Azhar 43</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

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
            background: #343a40;
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
            background: #495057;
        }

        .sidebar-header h3 {
            color: #fff;
            margin-bottom: 0;
        }

        #sidebar ul.components {
            padding: 20px 0;
            border-bottom: 1px solid #47748b;
        }

        #sidebar ul li a {
            padding: 10px;
            font-size: 1.1em;
            display: block;
            color: #dee2e6;
            text-decoration: none;
        }

        #sidebar ul li a:hover {
            color: #fff;
            background: #495057;
        }

        #sidebar ul li.active>a,
        a[aria-expanded="true"] {
            color: #fff;
            background: #0d6efd;
        }

        ul ul a {
            font-size: 0.9em !important;
            padding-left: 30px !important;
            background: #424950;
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
            @include('layouts.navigation')
        @endauth

        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 shadow-sm">
                <div class="container-fluid">
                    @guest
                        <a href="{{ url('/') }}" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Kembali</a>
                    @endguest
                    @auth
                        <button type="button" data-toggle="sidebar" class="btn btn-primary"><i
                                class="fa-solid fa-bars"></i></button>

                        <div class="d-flex align-items-center ms-auto">
                            <ul class="navbar-nav flex-row">
                                <li class="nav-item dropdown me-2">
                                    <a class="nav-link" href="#" id="notificationDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-bell"></i>
                                        @if (auth()->user()->unreadNotifications->count())
                                            <span class="badge rounded-pill bg-danger"
                                                style="position: absolute; top: 5px; right: 0;">
                                                {{ auth()->user()->unreadNotifications->count() }}
                                            </span>
                                        @endif
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0"
                                        aria-labelledby="notificationDropdown" style="width: 380px;">
                                        <li
                                            class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2">
                                            <h6 class="mb-0">Notifikasi</h6>
                                            @if (auth()->user()->unreadNotifications->isNotEmpty())
                                                <form action="{{ route('notifications.markAllAsRead') }}" method="POST"
                                                    class="mb-0">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-link btn-sm p-0 text-decoration-none">Tandai semua
                                                        dibaca</button>
                                                </form>
                                            @endif
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider my-0">
                                        </li>

                                        <div style="max-height: 400px; overflow-y: auto;">
                                            @forelse (auth()->user()->unreadNotifications->take(5) as $notification)
                                                <li class="notification-item">
                                                    @if (empty($notification->data['is_error']))
                                                        <a class="dropdown-item d-flex align-items-start py-2"
                                                            href="{{ route('notifications.markAsRead', $notification->id) }}"
                                                            target="_blank">
                                                            <div class="icon-circle bg-success-subtle text-success me-3">
                                                                <i class="fas fa-file-pdf"></i>
                                                            </div>
                                                            <div class="notification-content">
                                                                <p class="mb-0 fw-semibold" style="white-space: normal;">
                                                                    {{ $notification->data['message'] }}</p>
                                                                <small
                                                                    class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                                            </div>
                                                        </a>
                                                    @else
                                                        <div
                                                            class="dropdown-item d-flex align-items-start py-2 non-clickable">
                                                            <div class="icon-circle bg-danger-subtle text-danger me-3">
                                                                <i class="fas fa-exclamation-triangle"></i>
                                                            </div>
                                                            <div class="notification-content">
                                                                <p class="mb-0 fw-semibold" style="white-space: normal;">
                                                                    {{ $notification->data['message'] }}</p>
                                                                <small
                                                                    class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </li>
                                            @empty
                                                <li>
                                                    <p class="text-muted text-center my-4">Tidak ada notifikasi baru.</p>
                                                </li>
                                            @endforelse
                                        </div>

                                        <li>
                                            <hr class="dropdown-divider my-0">
                                        </li>
                                        <li class="text-center py-1 bg-light">
                                            <a href="{{ route('notifications.index') }}"
                                                class="dropdown-item text-primary fw-bold">
                                                Lihat Semua Notifikasi
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ Auth::user()->name }}
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                                                <i class="fas fa-user-circle me-2"></i>Profile
                                            </a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <form action="{{ route('logout') }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item">Logout</button>
                                            </form>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    @endauth
                </div>
            </nav>

            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggler = document.querySelector('[data-toggle="sidebar"]');

            if (localStorage.getItem('sidebarState') === 'closed') {
                sidebar.classList.add('active');
            } else {
                sidebar.classList.remove('active');
            }

            if (sidebarToggler) {
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
