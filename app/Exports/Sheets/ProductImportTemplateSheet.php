<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductImportTemplateSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function array(): array
    {
        return [
            [
                'sku',
                'nama_barang',
                'kategori',
                'satuan_dasar',
                'barcode',
                'harga_beli',
                'harga_jual',
                'harga_grosir',
                'minimal_grosir',
                'stok_minimum',
                'lokasi_rak',
                'kelola_stok',
                'punya_kedaluwarsa',
                'aktif',
                'konversi_1_satuan',
                'konversi_1_faktor',
                'konversi_1_barcode',
                'konversi_1_harga_beli',
                'konversi_1_harga_jual',
                'konversi_2_satuan',
                'konversi_2_faktor',
                'konversi_2_barcode',
                'konversi_2_harga_beli',
                'konversi_2_harga_jual',
                'konversi_3_satuan',
                'konversi_3_faktor',
                'konversi_3_barcode',
                'konversi_3_harga_beli',
                'konversi_3_harga_jual',
            ],
            [
                'BRG-001',
                'Nugget Ayam 500gr',
                'FROZEN',
                'pcs',
                '899000000001',
                20000,
                25000,
                23000,
                10,
                5,
                'Rak A-01',
                'YA',
                'YA',
                'YA',
                'pack',
                10,
                '899000000011',
                195000,
                240000,
                'dus',
                24,
                '899000000012',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ],
            [
                'BRG-002',
                'Air Mineral 600ml',
                'MINUMAN',
                'botol',
                '899000000002',
                2500,
                4000,
                null,
                null,
                12,
                'Rak B-02',
                'YA',
                'TIDAK',
                'YA',
                'dus',
                24,
                '899000000021',
                57000,
                90000,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ],
        ];
    }

    public function title(): string
    {
        return 'Import Barang';
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:AC1');
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle('A1:AC1')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('173F75');
        $sheet->getStyle('A1:AC1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('A2:AC3')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF8E1');
        $sheet->getStyle('A:AC')->getAlignment()->setVertical('center');
        $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('E:E')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('Q:Q')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('V:V')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('AA:AA')->getNumberFormat()->setFormatCode('@');

        return [];
    }
}
