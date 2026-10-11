<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('pos:info', function () {
    $this->info('Laravel 12 POS & Stok siap digunakan.');
})->purpose('Menampilkan status modul POS');
