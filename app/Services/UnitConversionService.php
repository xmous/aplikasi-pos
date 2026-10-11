<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Validation\ValidationException;

class UnitConversionService
{
    public function resolve(Product $product, ?int $productUnitId): array
    {
        if (! $productUnitId) {
            return [
                'product_unit' => null,
                'unit_id' => $product->unit_id,
                'factor' => 1.0,
                'selling_price' => (float) $product->selling_price,
                'purchase_price' => (float) $product->purchase_price,
                'symbol' => $product->unit?->symbol,
            ];
        }

        /** @var ProductUnit|null $unit */
        $unit = $product->conversionUnits()->with('unit')->whereKey($productUnitId)->where('is_active', true)->first();
        if (! $unit) {
            throw ValidationException::withMessages([
                'items' => "Satuan konversi {$product->name} tidak valid atau tidak aktif.",
            ]);
        }

        return [
            'product_unit' => $unit,
            'unit_id' => $unit->unit_id,
            'factor' => (float) $unit->conversion_factor,
            'selling_price' => $unit->sellingPrice(),
            'purchase_price' => $unit->purchasePrice(),
            'symbol' => $unit->unit?->symbol,
        ];
    }

    public function toBase(float $quantity, float $factor): float
    {
        if ($quantity <= 0 || $factor <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah dan faktor konversi harus lebih dari nol.']);
        }
        return round($quantity * $factor, 3);
    }
}
