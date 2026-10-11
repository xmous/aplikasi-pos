<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProfitReportExport implements FromArray, WithHeadings
{
    public function __construct(
        private Carbon $from,
        private Carbon $to,
        private array $data
    ) {}

    public function headings(): array
    {
        return ['Komponen', 'Nilai', 'Keterangan'];
    }

    public function array(): array
    {
        return [
            ['Periode', null, $this->from->format('d/m/Y').' s.d. '.$this->to->format('d/m/Y')],
            ['Jumlah Transaksi Penjualan', $this->data['sales_transactions'], 'Transaksi pada periode'],
            ['Subtotal Item', $this->data['sales_subtotal'], 'Sebelum diskon transaksi'],
            ['Diskon Penjualan', -$this->data['sales_discounts'], 'Promo + diskon manual'],
            ['Penjualan Kotor', $this->data['gross_sales'], 'Total transaksi setelah diskon'],
            ['Retur Penjualan', -$this->data['returns'], $this->data['return_transactions'].' transaksi retur'],
            ['Penjualan Bersih', $this->data['net_revenue'], 'Penjualan kotor dikurangi retur'],
            ['HPP Penjualan', -$this->data['sales_cogs'], 'Harga modal item terjual'],
            ['Pemulihan HPP Retur', $this->data['returned_cogs'], 'Hanya barang yang kembali ke stok'],
            ['HPP Bersih', -$this->data['net_cogs'], 'HPP penjualan dikurangi pemulihan retur'],
            ['Laba Kotor', $this->data['gross_profit'], 'Penjualan bersih dikurangi HPP bersih'],
            ['Pengeluaran Operasional', -$this->data['expenses'], $this->data['expense_transactions'].' transaksi'],
            ['Laba Bersih', $this->data['net_profit'], 'Estimasi laba periode'],
            ['Margin Kotor (%)', $this->data['gross_margin'], 'Persentase dari penjualan bersih'],
            ['Margin Bersih (%)', $this->data['net_margin'], 'Persentase dari penjualan bersih'],
            ['Rata-rata Transaksi', $this->data['average_transaction'], 'Penjualan bersih per transaksi'],
        ];
    }
}
