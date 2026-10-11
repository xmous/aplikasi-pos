<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Autentikasi Dua Faktor
    |--------------------------------------------------------------------------
    |
    | false: seluruh UI dan challenge login disembunyikan.
    | true : fitur Fortify, UI akun, Pengguna, Dashboard, dan Panduan aktif.
    |
    | Setelah mengubah .env jalankan:
    | php artisan optimize:clear
    |
    */
    'two_factor_enabled' => env('POS_TWO_FACTOR_ENABLED', false),
];
