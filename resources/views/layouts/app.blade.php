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
            /* Dark background from existing navbar */
            color: #fff;
            transition: all 0.3s;
        }

        /* Kelas .active akan menyembunyikan sidebar dengan mendorongnya ke kiri */
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
            /* Primary blue from Bootstrap */
        }

        ul ul a {
            font-size: 0.9em !important;
            padding-left: 30px !important;
            background: #424950;
        }

        /* Styling untuk mode mobile */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }

            /* Di mobile, kelas .active justru memunculkan sidebar */
            #sidebar.active {
                margin-left: 0;
            }

            #sidebarCollapse span {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">

        @auth
            @include('layouts.navigation')
        @endauth
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
                <div class="container-fluid">
                    @guest
                        <a href="{{ url('/') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    @endguest
                    @auth
                        <button type="button" data-toggle="sidebar" class="btn btn-primary">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div class="d-flex w-100 justify-content-end">

                            <ul class="navbar-nav">
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ Auth::user()->name }}
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
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

            // --- LOGIKA BARU UNTUK MENYIMPAN STATUS SIDEBAR ---

            // 1. Saat halaman dimuat, periksa status dari localStorage
            // Kelas 'active' berarti sidebar tertutup/tersembunyi.
            if (localStorage.getItem('sidebarState') === 'closed') {
                sidebar.classList.add('active');
            } else {
                sidebar.classList.remove('active');
            }

            // 2. Tambahkan event listener pada tombol toggle
            if (sidebarToggler) {
                sidebarToggler.addEventListener('click', function() {
                    // Toggle sidebar seperti biasa
                    sidebar.classList.toggle('active');

                    // 3. Simpan status baru ke localStorage
                    if (sidebar.classList.contains('active')) {
                        localStorage.setItem('sidebarState',
                            'closed'); // Jika sidebar punya kelas active, berarti tertutup
                    } else {
                        localStorage.setItem('sidebarState', 'open'); // Jika tidak, berarti terbuka
                    }
                });
            }

            // --- SCRIPT MODAL KONFIRMASI HAPUS (TIDAK BERUBAH) ---
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
