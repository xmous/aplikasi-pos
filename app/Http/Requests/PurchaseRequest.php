<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'supplier_invoice' => ['nullable', 'string', 'max:255'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'additional_cost' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'in:cash,qris,transfer,debit,ewallet'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_unit_id' => ['nullable', 'exists:product_units,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expired_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Tambahkan minimal satu barang pembelian.',
            'items.min' => 'Tambahkan minimal satu barang pembelian.',
            'items.max' => 'Maksimal 200 baris barang dalam satu transaksi.',
            'items.*.product_id.required' => 'Barang wajib dipilih.',
            'items.*.quantity.gt' => 'Jumlah barang harus lebih besar dari nol.',
            'items.*.unit_cost.min' => 'Harga satuan tidak boleh kurang dari nol.',
            'due_date.after_or_equal' => 'Jatuh tempo tidak boleh sebelum tanggal pembelian.',
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'gudang',
            'supplier_id' => 'supplier',
            'purchase_date' => 'tanggal pembelian',
            'due_date' => 'tanggal jatuh tempo',
            'supplier_invoice' => 'invoice supplier',
            'paid_amount' => 'bayar awal',
            'items.*.product_id' => 'barang',
            'items.*.product_unit_id' => 'satuan',
            'items.*.quantity' => 'jumlah',
            'items.*.unit_cost' => 'harga satuan',
            'items.*.discount' => 'diskon barang',
            'items.*.batch_number' => 'nomor batch',
            'items.*.expired_at' => 'tanggal kedaluwarsa',
        ];
    }
}
