<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImportDataSheet implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public function __construct(
        private ProductRowsImport $parent
    ) {}

    public function collection(Collection $rows): void
    {
        $this->parent->setRows($rows);
    }
}
