<?php

namespace App\Exports;

use App\Models\Sale;
use App\Support\ExcelCell;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesExport implements FromCollection, WithHeadings
{
    public function __construct(private array $filters) {}

    public function collection(): Collection
    {
        return $this->query()
            ->with(['cashier', 'customer', 'payments'])
            ->withSum('returns', 'total')
            ->orderBy('sold_at')
            ->get()
            ->map(fn (Sale $sale) => ExcelCell::row([
                $sale->number,
                $sale->sold_at->format('d/m/Y H:i'),
                $sale->cashier?->name,
                $sale->customer?->name ?? 'Umum',
                $sale->payments->pluck('method')->unique()
                    ->map(fn (string $method) => config('pos.payment_methods.'.$method, $method))
                    ->join(' + '),
                (float) $sale->subtotal,
                (float) $sale->promotion_discount,
                (float) $sale->manual_discount,
                (float) $sale->total,
                (float) ($sale->returns_sum_total ?? 0),
                $sale->net_total,
                (float) $sale->paid_amount,
                $sale->balance,
                $this->paymentLabel($sale->payment_status),
                $this->saleStatusLabel($sale->status),
                $sale->due_date?->format('d/m/Y'),
            ]));
    }

    public function headings(): array
    {
        return [
            'Nomor',
            'Tanggal',
            'Kasir',
            'Pelanggan',
            'Metode Bayar',
            'Subtotal Item',
            'Diskon Promo',
            'Diskon Manual',
            'Total Kotor',
            'Retur',
            'Penjualan Bersih',
            'Dibayar',
            'Sisa Piutang',
            'Status Bayar',
            'Status Transaksi',
            'Jatuh Tempo',
        ];
    }

    private function query(): Builder
    {
        return Sale::query()
            ->whereBetween('sold_at', [$this->filters['from'], $this->filters['to']])
            ->when($this->filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.$search.'%';
                $query->where(function (Builder $builder) use ($like): void {
                    $builder
                        ->where('number', 'like', $like)
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', $like))
                        ->orWhereHas('items', fn (Builder $item) => $item->where('product_name', 'like', $like)->orWhere('sku', 'like', $like));
                });
            })
            ->when($this->filters['payment_status'] ?? null, fn (Builder $query, string $value) => $query->where('payment_status', $value))
            ->when($this->filters['payment_method'] ?? null, fn (Builder $query, string $value) => $query->whereHas('payments', fn (Builder $payment) => $payment->where('method', $value)))
            ->when($this->filters['cashier_id'] ?? null, fn (Builder $query, int|string $value) => $query->where('cashier_id', $value))
            ->when($this->filters['status'] ?? null, fn (Builder $query, string $value) => $query->where('status', $value));
    }

    private function paymentLabel(string $status): string
    {
        return match ($status) {
            'paid' => 'Lunas',
            'partial' => 'Sebagian',
            'unpaid' => 'Belum Bayar',
            default => $status,
        };
    }

    private function saleStatusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'Selesai',
            'partially_returned' => 'Retur Sebagian',
            'returned' => 'Retur Penuh',
            default => $status,
        };
    }
}
