<?php

namespace App\Exports;

use App\Models\CashierShift;
use App\Models\Expense;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Support\ExcelCell;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OperationalReportExport implements FromArray, WithHeadings
{
    public function __construct(
        private string $type,
        private string $from,
        private string $to,
        private string $search = ''
    ) {}

    public function title(): string
    {
        return match ($this->type) {
            'purchases' => 'Laporan Pembelian',
            'movements' => 'Laporan Mutasi Stok',
            'expiry' => 'Laporan Batch Kedaluwarsa',
            'shifts' => 'Laporan Shift Kasir',
            'expenses' => 'Laporan Pengeluaran',
            'sale_returns' => 'Laporan Retur Penjualan',
            'purchase_returns' => 'Laporan Retur Pembelian',
            'opnames' => 'Laporan Stok Opname',
        };
    }

    public function headings(): array
    {
        return match ($this->type) {
            'purchases' => [
                'Nomor', 'Tanggal', 'Supplier', 'Gudang', 'Total Kotor',
                'Retur', 'Total Bersih', 'Dibayar', 'Sisa Utang', 'Status',
            ],
            'movements' => [
                'Waktu', 'Barang', 'SKU', 'Gudang', 'Batch', 'Tipe',
                'Arah', 'Jumlah', 'Stok Sebelum', 'Stok Setelah', 'Petugas', 'Catatan',
            ],
            'expiry' => [
                'Barang', 'SKU', 'Gudang', 'Batch', 'Kedaluwarsa',
                'Sisa Hari', 'Stok', 'Satuan', 'Nilai Modal', 'Status',
            ],
            'shifts' => [
                'Kasir', 'Buka', 'Tutup', 'Status', 'Kas Awal', 'Kas Sistem',
                'Kas Fisik', 'Selisih', 'Catatan',
            ],
            'expenses' => [
                'Tanggal', 'Kategori', 'Metode', 'Petugas', 'Nominal',
                'Status Shift', 'Catatan',
            ],
            'sale_returns' => [
                'Nomor Retur', 'Transaksi', 'Pelanggan', 'Waktu', 'Item',
                'Nilai Retur', 'Pengurang Piutang', 'Refund Aktual',
                'Metode Penyelesaian', 'Petugas', 'Catatan',
            ],
            'purchase_returns' => [
                'Nomor Retur', 'Pembelian', 'Supplier', 'Waktu', 'Item',
                'Total', 'Petugas', 'Catatan',
            ],
            'opnames' => [
                'Nomor', 'Tanggal', 'Gudang', 'Petugas', 'Jumlah Barang',
                'Barang Berbeda', 'Total Selisih', 'Status', 'Penyetuju', 'Catatan',
            ],
        };
    }

    public function array(): array
    {
        return $this->rawRows()
            ->map(fn (array $row) => ExcelCell::row($row))
            ->all();
    }

    public function rawRows(): Collection
    {
        $term = '%'.$this->search.'%';

        return match ($this->type) {
            'purchases' => Purchase::query()
                ->with(['supplier', 'warehouse'])
                ->withSum('returns', 'total')
                ->whereBetween('purchase_date', [$this->from, $this->to])
                ->when($this->search !== '', function (Builder $query) use ($term): void {
                    $query->where(function (Builder $search) use ($term): void {
                        $search
                            ->where('number', 'like', $term)
                            ->orWhere('supplier_invoice', 'like', $term)
                            ->orWhereHas('supplier', fn (Builder $supplier) => $supplier->where('name', 'like', $term));
                    });
                })
                ->orderBy('purchase_date')
                ->get()
                ->map(fn (Purchase $row) => [
                    $row->number,
                    $row->purchase_date->format('d/m/Y'),
                    $row->supplier?->name ?? '-',
                    $row->warehouse?->name ?? '-',
                    (float) $row->total,
                    (float) ($row->returns_sum_total ?? 0),
                    $row->net_total,
                    (float) $row->paid_amount,
                    $row->balance,
                    $this->paymentLabel($row->payment_status),
                ]),

            'movements' => StockMovement::query()
                ->with(['product.unit', 'warehouse', 'batch', 'user'])
                ->whereBetween('created_at', [$this->from.' 00:00:00', $this->to.' 23:59:59'])
                ->when($this->search !== '', function (Builder $query) use ($term): void {
                    $query->where(function (Builder $search) use ($term): void {
                        $search
                            ->where('notes', 'like', $term)
                            ->orWhereHas('product', fn (Builder $product) => $product->where('name', 'like', $term)->orWhere('sku', 'like', $term))
                            ->orWhereHas('warehouse', fn (Builder $warehouse) => $warehouse->where('name', 'like', $term));
                    });
                })
                ->orderBy('created_at')
                ->get()
                ->map(fn (StockMovement $row) => [
                    $row->created_at->format('d/m/Y H:i:s'),
                    $row->product->name,
                    $row->product->sku,
                    $row->warehouse->name,
                    $row->batch?->batch_number ?? 'GENERAL',
                    $this->movementTypeLabel($row->type),
                    $row->direction === 'in' ? 'Masuk' : 'Keluar',
                    (float) $row->quantity,
                    (float) $row->stock_before,
                    (float) $row->stock_after,
                    $row->user?->name ?? 'Sistem',
                    $row->notes,
                ]),

            'expiry' => ProductBatch::query()
                ->with(['product.unit', 'warehouse'])
                ->where('quantity', '>', 0)
                ->whereNotNull('expired_at')
                ->whereBetween('expired_at', [$this->from, $this->to])
                ->when($this->search !== '', function (Builder $query) use ($term): void {
                    $query->where(function (Builder $search) use ($term): void {
                        $search
                            ->where('batch_number', 'like', $term)
                            ->orWhereHas('product', fn (Builder $product) => $product->where('name', 'like', $term)->orWhere('sku', 'like', $term))
                            ->orWhereHas('warehouse', fn (Builder $warehouse) => $warehouse->where('name', 'like', $term));
                    });
                })
                ->orderBy('expired_at')
                ->get()
                ->map(function (ProductBatch $row): array {
                    $days = today()->diffInDays($row->expired_at, false);

                    return [
                        $row->product->name,
                        $row->product->sku,
                        $row->warehouse->name,
                        $row->batch_number,
                        $row->expired_at->format('d/m/Y'),
                        $days,
                        (float) $row->quantity,
                        $row->product->unit?->symbol,
                        (float) $row->quantity * (float) $row->product->average_cost,
                        $days < 0 ? 'Kedaluwarsa' : ($days <= 30 ? 'Segera Kedaluwarsa' : 'Masih Aman'),
                    ];
                }),

            'shifts' => CashierShift::query()
                ->with('user')
                ->whereBetween('opened_at', [$this->from.' 00:00:00', $this->to.' 23:59:59'])
                ->when($this->search !== '', fn (Builder $query) => $query->whereHas('user', fn (Builder $user) => $user->where('name', 'like', $term)))
                ->orderBy('opened_at')
                ->get()
                ->map(fn (CashierShift $row) => [
                    $row->user?->name,
                    $row->opened_at->format('d/m/Y H:i:s'),
                    $row->closed_at?->format('d/m/Y H:i:s'),
                    $row->status === 'open' ? 'Terbuka' : 'Ditutup',
                    (float) $row->opening_cash,
                    (float) $row->expected_cash,
                    $row->actual_cash !== null ? (float) $row->actual_cash : null,
                    $row->cash_difference !== null ? (float) $row->cash_difference : null,
                    $row->notes,
                ]),

            'expenses' => Expense::query()
                ->with(['creator', 'cashierShift'])
                ->whereBetween('expense_date', [$this->from, $this->to])
                ->when($this->search !== '', function (Builder $query) use ($term): void {
                    $query->where(function (Builder $search) use ($term): void {
                        $search
                            ->where('category', 'like', $term)
                            ->orWhere('notes', 'like', $term)
                            ->orWhereHas('creator', fn (Builder $user) => $user->where('name', 'like', $term));
                    });
                })
                ->orderBy('expense_date')
                ->get()
                ->map(fn (Expense $row) => [
                    $row->expense_date->format('d/m/Y'),
                    $row->category,
                    config('pos.payment_methods.'.$row->payment_method, $row->payment_method),
                    $row->creator?->name,
                    (float) $row->amount,
                    $row->cashierShift
                        ? ($row->cashierShift->status === 'open' ? 'Shift Aktif' : 'Shift Ditutup')
                        : 'Tidak melalui kas',
                    $row->notes,
                ]),

            'sale_returns' => SaleReturn::query()
                ->with(['sale.customer', 'creator'])
                ->withCount('items')
                ->whereBetween('returned_at', [$this->from.' 00:00:00', $this->to.' 23:59:59'])
                ->when($this->search !== '', function (Builder $query) use ($term): void {
                    $query->where(function (Builder $search) use ($term): void {
                        $search
                            ->where('number', 'like', $term)
                            ->orWhereHas('sale', fn (Builder $sale) => $sale->where('number', 'like', $term));
                    });
                })
                ->orderBy('returned_at')
                ->get()
                ->map(fn (SaleReturn $row) => [
                    $row->number,
                    $row->sale->number,
                    $row->sale->customer?->name ?? 'Pelanggan Umum',
                    $row->returned_at->format('d/m/Y H:i:s'),
                    $row->items_count,
                    (float) $row->total,
                    max(0, (float) $row->total - (float) $row->refund_amount),
                    (float) $row->refund_amount,
                    $row->refund_method === 'credit_adjustment'
                        ? 'Pengurang Piutang'
                        : config('pos.payment_methods.'.$row->refund_method, $row->refund_method),
                    $row->creator?->name,
                    $row->notes,
                ]),

            'purchase_returns' => PurchaseReturn::query()
                ->with(['purchase.supplier', 'creator'])
                ->withCount('items')
                ->whereBetween('returned_at', [$this->from.' 00:00:00', $this->to.' 23:59:59'])
                ->when($this->search !== '', function (Builder $query) use ($term): void {
                    $query->where(function (Builder $search) use ($term): void {
                        $search
                            ->where('number', 'like', $term)
                            ->orWhereHas('purchase', fn (Builder $purchase) => $purchase->where('number', 'like', $term));
                    });
                })
                ->orderBy('returned_at')
                ->get()
                ->map(fn (PurchaseReturn $row) => [
                    $row->number,
                    $row->purchase->number,
                    $row->purchase->supplier?->name ?? '-',
                    $row->returned_at->format('d/m/Y H:i:s'),
                    $row->items_count,
                    (float) $row->total,
                    $row->creator?->name,
                    $row->notes,
                ]),

            'opnames' => StockOpname::query()
                ->with(['warehouse', 'creator', 'approver'])
                ->withCount('items')
                ->withCount(['items as difference_items_count' => fn (Builder $items) => $items->where('difference', '!=', 0)])
                ->withSum('items as difference_sum', 'difference')
                ->whereBetween('opname_date', [$this->from, $this->to])
                ->when($this->search !== '', function (Builder $query) use ($term): void {
                    $query->where(function (Builder $search) use ($term): void {
                        $search
                            ->where('number', 'like', $term)
                            ->orWhereHas('warehouse', fn (Builder $warehouse) => $warehouse->where('name', 'like', $term));
                    });
                })
                ->orderBy('opname_date')
                ->get()
                ->map(fn (StockOpname $row) => [
                    $row->number,
                    $row->opname_date->format('d/m/Y'),
                    $row->warehouse->name,
                    $row->creator?->name,
                    $row->items_count,
                    $row->difference_items_count,
                    (float) ($row->difference_sum ?? 0),
                    match ($row->status) {
                        'draft' => 'Draft',
                        'submitted' => 'Menunggu Persetujuan',
                        'approved' => 'Disetujui',
                        default => $row->status,
                    },
                    $row->approver?->name,
                    $row->notes,
                ]),
        };
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

    private function movementTypeLabel(string $type): string
    {
        return match ($type) {
            'purchase' => 'Pembelian',
            'sale' => 'Penjualan',
            'adjustment' => 'Penyesuaian',
            'stock_opname' => 'Stok Opname',
            'purchase_return' => 'Retur Pembelian',
            'sale_return' => 'Retur Penjualan',
            default => str($type)->replace('_', ' ')->title()->toString(),
        };
    }
}
