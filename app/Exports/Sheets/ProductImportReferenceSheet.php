<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductImportReferenceSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        private Collection $categories,
        private Collection $units
    ) {}

    public function array(): array
    {
        $rows = [
            ['REFERENSI KATEGORI', null, null, 'REFERENSI SATUAN', null],
            ['Kode', 'Nama', 'Status', 'Nama', 'Simbol'],
        ];

        $maxRows = max($this->categories->count(), $this->units->count());

        for ($index = 0; $index < $maxRows; $index++) {
            $category = $this->categories->get($index);
            $unit = $this->units->get($index);

            $rows[] = [
                $category?->code,
                $category?->name,
                $category ? ($category->is_active ? 'Aktif' : 'Nonaktif') : null,
                $unit?->name,
                $unit?->symbol,
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Referensi';
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A3');
        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('D1:E1');
        $sheet->getStyle('A1:E1')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('173F75');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('A2:E2')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('DCEAFF');
        $sheet->getStyle('A2:E2')->getFont()->setBold(true);

        return [];
    }
}
