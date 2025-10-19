<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Slip Gaji</title>
    <style>
        /* Style untuk memastikan kompatibilitas di berbagai email client */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            border: 1px solid #dddddd;
        }

        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eeeeee;
        }

        .header h1 {
            margin: 0;
            color: #0d6efd;
        }

        .content {
            padding: 20px 0;
        }

        .content p {
            margin: 0 0 10px;
        }

        .footer {
            text-align: left;
            padding-top: 20px;
            border-top: 1px solid #eeeeee;
            font-size: 0.9em;
            color: #777777;
        }

        .logo {
            max-width: 100px;
            /* Atur ukuran logo sesuai kebutuhan */
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            {{-- Anda bisa menambahkan logo di sini jika ada --}}
            {{-- <img src="{{ $message->embed(public_path('/logo/logoalazhar.png')) }}" alt="Logo" class="logo"> --}}
            <h1>Slip Gaji Anda</h1>
        </div>

        <div class="content">
            <p>Yth. <strong>{{ $nama }}</strong>,</p>
            <p>Terlampir adalah slip gaji Anda untuk periode <strong>{{ $periode }}</strong>.</p>
            <p>Silakan periksa lampiran pada email ini untuk melihat rincian slip gaji Anda.</p>
            <p>Terima kasih atas dedikasi dan kerja keras Anda.</p>
        </div>

        <div class="footer">
            <p>Salam Hormat,</p>
            <p>
                <strong>Bendahara Sekolah Islam Al Azhar 43 Gorontalo</strong><br>
                Sistem Informasi Penggajian & Kepegawaian
            </p>
        </div>
    </div>
</body>

</html>
