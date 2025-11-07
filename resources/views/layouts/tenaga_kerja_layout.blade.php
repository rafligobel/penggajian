<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Tenaga Kerja - Al-Azhar 43</title>

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('logo/logoalazhar.png') }}" type="image/png">

    {{-- Stylesheets --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            background-color: #f4f7f6;
        }

        /* <-- [PERBAIKAN] Kurung kurawal '}' yang hilang ditambahkan di sini */

        .navbar-brand img {
            max-height: 38px;
        }

        .profile-btn {
            color: #495057;
            font-weight: 500;
        }

        #dataSayaModal .modal-body {
            max-height: 70vh;
            /* Atur tinggi maksimal (misal: 70% dari tinggi layar) */
            overflow-y: auto;
            /* Tambahkan scrollbar jika kontennya melebihi max-height */
        }
    </style>
</head>

<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ route('tenaga_kerja.dashboard') }}">
                    <img src="{{ asset('logo/logoalazhar.svg') }}" alt="Logo">
                    <span class="fw-bold ms-3">Penggajian dan Kepegawaian</span>
                </a>

                {{-- Gunakan partial yang sama untuk dropdown profil --}}
                <ul class="navbar-nav ms-auto">
                    @include('layouts.partials._profile-dropdown')
                </ul>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
