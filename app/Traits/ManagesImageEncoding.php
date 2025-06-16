<?php

namespace App\Traits;

/**
 * Trait untuk fungsionalitas terkait encoding gambar.
 */
trait ManagesImageEncoding
{
    /**
     * Mengonversi gambar dari path di server menjadi format data URI Base64.
     *
     * @param string $path Path absolut ke file gambar.
     * @return string Data URI Base64, atau string kosong jika file tidak ada.
     */
    protected function encodeImageToBase64(string $path): string
    {
        if (!file_exists($path)) {
            return '';
        }

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}