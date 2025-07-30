<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang - Penggajian Al-Azhar 43</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .brand-title {
            font-weight: 700;
            font-size: 1.8rem;
        }

        .brand-subtitle {
            color: #555;
            font-size: 1rem;
        }

        .card-menu {
            transition: all 0.2s ease;
        }

        .card-menu:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 576px) {
            .brand-title {
                font-size: 1.4rem;
            }

            .brand-subtitle {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>

    <div class="container text-center">
        <!-- Header -->
        <div class="mb-1">
            <img src="{{ asset('logo/logoalazhar.png') }}" alt="Logo Perusahaan" style="max-height: 100px;">
        </div>
        <div class="mb-4">
            <h1 class="brand-title">Sistem Informasi Kepegawaian & Penggajian</h1>
            <h5 class="brand-subtitle">Sekolah Al-Azhar 43 Gorontalo</h1>
        </div>

        <!-- Menu -->
        <div class="row justify-content-center">
            <div class="col-10 col-sm-6 col-md-4 col-lg-3 mb-3">
                <div class="card card-menu shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Admin / Bendahara</h5>
                        <p class="card-text text-muted">Login untuk mengelola sistem.</p>
                        <a href="{{ route('login') }}" class="btn btn-primary w-100">Login</a>
                    </div>
                </div>
            </div>

            <div class="col-10 col-sm-6 col-md-4 col-lg-3 mb-3">
                <div class="card card-menu shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Simulasi Gaji</h5>
                        <p class="card-text text-muted">Hitung estimasi gaji Anda.</p>
                        <a href="{{ route('simulasi.index') }}" class="btn btn-success w-100">Mulai</a>
                    </div>
                </div>
            </div>

            <div class="col-10 col-sm-6 col-md-4 col-lg-3 mb-3">
                <div class="card card-menu shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Absensi</h5>
                        <p class="card-text text-muted">Isi absensi tanpa login.</p>
                        <a href="{{ route('absensi.form') }}" class="btn btn-warning w-100 text-white">Isi Absensi</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
