<?php

namespace App\Exports;

use App\Models\Purchase;
use App\Models\Sale;
use App\Support\ExcelCell;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DebtReportExport implements FromArray, WithHeadings
{
    public function __construct(private array $filters) {}

    public function headings(): array
    {
        return [
            'Jenis',
            'Nomor Transaksi',
            'Pihak',
            'Tanggal',
            'Jatuh Tempo',
            'Total Bersih',
            'Sudah Dibayar',
            'Sisa',
            'Status Jatuh Tempo',
        ];
    }

    public function array(): array
    {
        $receivables = $this->receivables()
            ->withSum('returns', 'total')
            ->orderBy('due_date')
            ->get()
            ->map(fn (Sale $sale) => ExcelCell::row([
                'Piutang Pelanggan',
                $sale->number,
                $sale->customer?->name ?? 'Pelanggan Umum',
                $sale->sold_at->format('d/m/Y'),
                $sale->due_date?->format('d/m/Y'),
                $sale->net_total,
                (float) $sale->paid_amount,
                $sale->balance,
                $this->dueLabel($sale->due_date),
            ]));

        $payables = $this->payables()
            ->withSum('returns', 'total')
            ->orderBy('due_date')
            ->get()
            ->map(fn (Purchase $purchase) => ExcelCell::row([
                'Utang Supplier',
                $purchase->number,
                $purchase->supplier?->name ?? 'Tanpa Supplier',
                $purchase->purchase_date->format('d/m/Y'),
                $purchase->due_date?->format('d/m/Y'),
                $purchase->net_total,
                (float) $purchase->paid_amount,
                $purchase->balance,
                $this->dueLabel($purchase->due_date),
            ]));

        return $receivables->concat($payables)->all();
    }

    private function receivables(): Builder
    {
        return Sale::query()
            ->with('customer')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->when($this->filters['search'] !== '', function (Builder $query): void {
                $like = '%'.$this->filters['search'].'%';
                $query->where(function (Builder $search) use ($like): void {
                    $search
                        ->where('number', 'like', $like)
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', $like)->orWhere('phone', 'like', $like));
                });
            })
            ->when($this->filters['due_status'] === 'overdue', fn (Builder $query) => $query->whereNotNull('due_date')->whereDate('due_date', '<', today()))
            ->when($this->filters['due_status'] === 'due_7', fn (Builder $query) => $query->whereBetween('due_date', [today(), today()->addDays(7)]))
            ->when($this->filters['due_status'] === 'no_due', fn (Builder $query) => $query->whereNull('due_date'));
    }

    private function payables(): Builder
    {
        return Purchase::query()
            ->with('supplier')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->when($this->filters['search'] !== '', function (Builder $query): void {
                $like = '%'.$this->filters['search'].'%';
                $query->where(function (Builder $search) use ($like): void {
                    $search
                        ->where('number', 'like', $like)
                        ->orWhere('supplier_invoice', 'like', $like)
                        ->orWhereHas('supplier', fn (Builder $supplier) => $supplier->where('name', 'like', $like)->orWhere('phone', 'like', $like));
                });
            })
            ->when($this->filters['due_status'] === 'overdue', fn (Builder $query) => $query->whereNotNull('due_date')->whereDate('due_date', '<', today()))
            ->when($this->filters['due_status'] === 'due_7', fn (Builder $query) => $query->whereBetween('due_date', [today(), today()->addDays(7)]))
            ->when($this->filters['due_status'] === 'no_due', fn (Builder $query) => $query->whereNull('due_date'));
    }

    private function dueLabel($date): string
    {
        if (! $date) {
            return 'Tanpa jatuh tempo';
        }

        if ($date->lt(today())) {
            return 'Terlambat '.abs(today()->diffInDays($date, false)).' hari';
        }

        if ($date->isToday()) {
            return 'Jatuh tempo hari ini';
        }

        return today()->diffInDays($date).' hari lagi';
    }
}
