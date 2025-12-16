<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Absensi
    |--------------------------------------------------------------------------
    |
    | Mengatur titik koordinat kantor pusat dan radius maksimum absensi.
    |
    */

    'office_latitude' => env('OFFICE_LATITUDE', 0.5470607464766618),
    'office_longitude' => env('OFFICE_LONGITUDE', 123.060781772253),
    
    // [DIKEMBALIKAN] Radius dikembalikan ke 50 meter sesuai aturan kantor
    'max_radius' => env('MAX_ATTENDANCE_RADIUS', 50), 
];
