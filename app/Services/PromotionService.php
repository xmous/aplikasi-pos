<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    public function calculate(?Promotion $promotion, Collection $items, float $subtotal): float
    {
        if (! $promotion) {
            return 0;
        }

        if (! $promotion->is_active || now()->lt($promotion->starts_at) || now()->gt($promotion->ends_at)) {
            throw ValidationException::withMessages(['promotion_id' => 'Promo tidak aktif atau sudah berakhir.']);
        }

        if ($subtotal < (float) $promotion->minimum_transaction) {
            throw ValidationException::withMessages([
                'promotion_id' => 'Minimum transaksi promo belum terpenuhi.',
            ]);
        }

        $eligibleIds = $promotion->products()->pluck('products.id');
        $eligibleSubtotal = $eligibleIds->isEmpty()
            ? $subtotal
            : (float) $items->whereIn('product_id', $eligibleIds)->sum('subtotal');

        $discount = $promotion->type === 'percent'
            ? $eligibleSubtotal * ((float) $promotion->value / 100)
            : min($eligibleSubtotal, (float) $promotion->value);

        if ($promotion->maximum_discount !== null) {
            $discount = min($discount, (float) $promotion->maximum_discount);
        }

        return round(min($discount, $subtotal), 2);
    }
}
