<?php

namespace App\Traits;

trait ManagesImageEncoding
{
    /**
     * Mengonversi file gambar (PNG, JPG, atau SVG) menjadi format data URI Base64.
     * Metode ini cerdas dan akan menggunakan tipe MIME yang benar berdasarkan ekstensi file.
     *
     * @param string $path Path absolut ke file gambar.
     * @return string Data URI Base64, atau string kosong jika file tidak ada.
     */
    protected function getImageAsBase64DataUri(string $path): string
    {
        if (!file_exists($path)) {
            return ''; // Kembalikan string kosong jika file tidak ditemukan
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeType = '';

        switch ($extension) {
            case 'svg':
                $mimeType = 'image/svg+xml';
                break;
            case 'png':
                $mimeType = 'image/png';
                break;
            case 'jpg':
            case 'jpeg':
                $mimeType = 'image/jpeg';
                break;
            default:
                return ''; // Kembalikan kosong jika format tidak didukung
        }

        $data = file_get_contents($path);
        return 'data:' . $mimeType . ';base64,' . base64_encode($data);
    }
}
