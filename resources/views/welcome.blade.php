<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang - Sistem Informasi Kepegawaian & Penggajian Al-Azhar 43</title>
    <link rel="icon" href="{{ asset('logo/logoalazhar.png') }}" type="image/png">

    {{-- Bootstrap 5 & Font Awesome Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        body {
            background-color: #f0f2f5;
            /* Warna latar belakang sedikit abu-abu */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .brand-title {
            font-weight: 700;
            color: #0d6efd;
            /* Warna biru primer Bootstrap */
        }

        .brand-subtitle {
            color: #6c757d;
            /* Warna abu-abu (muted) */
        }

        .card-menu {
            border: none;
            /* Hapus border default */
            border-radius: 0.75rem;
            /* Border lebih bulat */
            transition: all 0.3s ease;
            height: 100%;
        }

        .card-menu:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
        }

        .card-menu .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>

    <div class="container text-center">
        <header class="mb-2">
            <img src="{{ asset('logo/logoalazhar.png') }}" alt="Logo Al-Azhar" style="max-height: 90px;" class="mb-3">
            <h1 class="brand-title h2">Sistem Informasi Penggajian & Kepegawaian</h1>
            <h2 class="brand-subtitle h5 fw-normal">Sekolah Islam Al-Azhar 43 Gorontalo</h2>
        </header>

        <main class="row justify-content-center g-4">
            {{-- Kartu Login untuk Tenaga Kerja (Absensi & Slip Gaji) --}}
            <div class="col-11 col-sm-8 col-md-6 col-lg-4 shadow-sm">
                <a href="{{ route('login') }}" class="btn btn-primary w-100 mt-3">Selnjutnya..</a>

                {{-- <div class="card card-menu shadow-sm">
                    {{-- <div class="card-body p-4">
                        <div>
                            <i class="fas fa-user-check card-icon text-primary"></i>
                            <h5 class="card-title fw-bold">Portal Tenaga Kerja</h5>
                            {{-- <p class="card-text text-muted small">Masuk untuk melakukan absensi, melihat laporan, dan
                                        mengunduh slip gaji.</p> 
                        </div>
                        <a href="{{ route('login') }}" class="btn btn-primary w-100 mt-3">Login</a>
                    </div>
                </div> --}}
            </div>

            {{-- Kartu Login untuk Admin/Bendahara --}}
            {{-- <div class="col-11 col-sm-8 col-md-6 col-lg-4">
                <div class="card card-menu shadow-sm">
                    <div class="card-body p-4">
                        <div>
                            <i class="fas fa-user-shield card-icon text-success"></i>
                            <h5 class="card-title fw-bold">Admin / Bendahara</h5>
                            <p class="card-text text-muted small">Login khusus untuk pengelola sistem dan manajemen
                                penggajian.</p>
                        </div>
                        <a href="{{ route('login') }}" class="btn btn-success w-100 mt-3">Login Pengelola</a>
                    </div>
                </div>
            </div> --}}

            {{-- Kartu Simulasi Gaji (Publik) --}}
            {{-- <div class="col-11 col-sm-8 col-md-6 col-lg-4">
                <div class="card card-menu shadow-sm">
                    <div class="card-body p-4">
                        <div>
                            <i class="fas fa-calculator card-icon text-warning"></i>
                            <h5 class="card-title fw-bold">Simulasi Gaji</h5>
                            <p class="card-text text-muted small">Gunakan fitur ini untuk menghitung estimasi gaji Anda
                                secara mandiri.</p>
                        </div>
                        <a href="{{ route('simulasi.index') }}" class="btn btn-warning w-100 text-white mt-3">Mulai
                            Simulasi</a>
                    </div>
                </div>
            </div> --}}
        </main>
    </div>

</body>

</html>
