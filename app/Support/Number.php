<?php

namespace App\Support;

final class Number
{
    public static function quantity(
        float|int|string|null $value,
        int $precision = 3
    ): string {
        $precision = max(0, min(6, $precision));
        $number = round((float) ($value ?? 0), $precision);

        if (abs($number) < (10 ** -max(1, $precision))) {
            $number = 0;
        }

        $formatted = number_format(
            $number,
            $precision,
            ',',
            '.'
        );

        return rtrim(rtrim($formatted, '0'), ',');
    }
}
