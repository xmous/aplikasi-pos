<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Store;

class ThermalReceiptService
{
    public function text(Sale $sale, int $width = 32): string
    {
        $sale->loadMissing(['items', 'payments', 'cashier']);
        $store = Store::query()->find($sale->store_id);

        $lines = [
            $this->center($store?->name ?? 'TOKO', $width),
            $this->center($store?->address ?? '', $width),
            str_repeat('-', $width),
            $sale->number,
            $sale->sold_at->format('d/m/Y H:i').' / '.$sale->cashier->name,
        ];

        if ($sale->customer_id && $sale->customer) {
            $lines[] = 'MEMBER: '.mb_substr(
                $sale->customer->name,
                0,
                max(0, $width - 8)
            );
        }

        $lines[] = str_repeat('-', $width);

        foreach ($sale->items as $item) {
            $lines[] = mb_substr($item->product_name, 0, $width);
            $left = rtrim(rtrim(number_format((float)$item->quantity, 3, '.', ''), '0'), '.').' x '.number_format((float)$item->unit_price, 0, ',', '.');
            $right = number_format((float)$item->subtotal, 0, ',', '.');
            $lines[] = $this->columns($left, $right, $width);
        }

        $lines[] = str_repeat('-', $width);

        if ((float) $sale->points_discount > 0) {
            $lines[] = $this->columns(
                'POIN '.$sale->points_redeemed,
                '-'.number_format(
                    (float) $sale->points_discount,
                    0,
                    ',',
                    '.'
                ),
                $width
            );
        }

        $lines[] = $this->columns('TOTAL', number_format((float)$sale->total, 0, ',', '.'), $width);
        $lines[] = $this->columns('BAYAR', number_format((float)$sale->paid_amount, 0, ',', '.'), $width);
        $lines[] = $this->columns('KEMBALI', number_format((float)$sale->change_amount, 0, ',', '.'), $width);

        if ((int) $sale->points_earned > 0) {
            $pointLabel = $sale->points_awarded_at
                ? 'POIN DAPAT'
                : 'POIN PENDING';

            $lines[] = $this->columns(
                $pointLabel,
                number_format(
                    max(
                        0,
                        (int) $sale->points_earned
                            - (int) $sale->points_reversed
                    ),
                    0,
                    ',',
                    '.'
                ),
                $width
            );
        }

        $lines[] = str_repeat('-', $width);

        foreach ($this->centeredLines(
            $store?->receipt_footer ?? 'Terima kasih',
            $width
        ) as $line) {
            $lines[] = $line;
        }

        foreach ($this->centeredLines(
            $store?->receipt_policy_text
                ?? 'Barang yang sudah dibeli mengikuti ketentuan retur toko.',
            $width
        ) as $line) {
            $lines[] = $line;
        }

        return implode(
            PHP_EOL,
            array_filter($lines, fn ($line) => $line !== '')
        );
    }

    private function center(string $value, int $width): string
    {
        $value = mb_substr(trim($value), 0, $width);
        $padding = max(
            0,
            (int) floor(($width - mb_strlen($value)) / 2)
        );

        return str_repeat(' ', $padding).$value;
    }

    private function centeredLines(
        ?string $value,
        int $width
    ): array {
        $remaining = trim((string) $value);

        if ($remaining === '') {
            return [];
        }

        $lines = [];

        while (mb_strlen($remaining) > $width) {
            $candidate = mb_substr($remaining, 0, $width);
            $breakAt = mb_strrpos($candidate, ' ');

            if ($breakAt === false || $breakAt < 1) {
                $breakAt = $width;
            }

            $line = trim(mb_substr($remaining, 0, $breakAt));
            $lines[] = $this->center($line, $width);
            $remaining = trim(mb_substr($remaining, $breakAt));
        }

        if ($remaining !== '') {
            $lines[] = $this->center($remaining, $width);
        }

        return $lines;
    }

    private function columns(string $left, string $right, int $width): string
    {
        $space = max(1, $width - mb_strlen($left) - mb_strlen($right));
        return mb_substr($left, 0, max(1, $width - mb_strlen($right) - 1)).str_repeat(' ', $space).$right;
    }
}
