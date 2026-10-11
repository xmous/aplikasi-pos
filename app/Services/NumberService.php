<?php

namespace App\Services;

class NumberService
{
    public static function make(string $prefix): string
    {
        return strtoupper($prefix).'-'.now()->format('Ymd-His').'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
    }
}
