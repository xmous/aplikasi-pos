<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductRowsImport implements WithMultipleSheets
{
    public Collection $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    public function sheets(): array
    {
        // Hanya baca sheet pertama: "Import Barang".
        // Sheet Referensi dan Petunjuk tidak ikut diproses sebagai data barang.
        return [
            0 => new ProductImportDataSheet($this),
        ];
    }

    public function setRows(Collection $rows): void
    {
        $this->rows = $rows;
    }
}
