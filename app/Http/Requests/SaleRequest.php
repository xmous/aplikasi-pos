<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('sales.create') ?? false; }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required','exists:warehouses,id'],
            'customer_id' => ['nullable','exists:customers,id'],
            'promotion_id' => ['nullable','exists:promotions,id'],
            'manual_discount' => ['nullable','integer','min:0'],
            'redeem_points' => ['nullable','integer','min:0'],
            'payments' => ['nullable','array'],
            'payments.*.method' => ['required_with:payments.*.amount','in:cash,qris,transfer,debit,ewallet'],
            'payments.*.amount' => ['nullable','integer','min:0'],
            'payments.*.reference_number' => ['nullable','max:255'],
            'due_date' => ['nullable','date','after_or_equal:today'],
            'notes' => ['nullable','string','max:2000'],
            'items' => ['required','array','min:1'],
            'items.*.product_id' => ['required','exists:products,id'],
            'items.*.product_unit_id' => ['nullable','exists:product_units,id'],
            'items.*.quantity' => ['required','numeric','gt:0'],
            'items.*.discount' => ['nullable','integer','min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'manual_discount.integer' => 'Diskon transaksi hanya boleh berisi angka bulat.',
            'redeem_points.integer' => 'Poin yang digunakan harus berupa angka bulat.',
            'redeem_points.min' => 'Poin yang digunakan tidak boleh kurang dari nol.',
            'payments.*.amount.integer' => 'Nominal pembayaran hanya boleh berisi angka.',
            'payments.*.amount.min' => 'Nominal pembayaran tidak boleh kurang dari nol.',
            'items.*.discount.integer' => 'Diskon barang hanya boleh berisi angka bulat.',
        ];
    }
}
