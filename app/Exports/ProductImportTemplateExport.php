<?php

namespace App\Exports;

use App\Exports\Sheets\ProductImportInstructionsSheet;
use App\Exports\Sheets\ProductImportReferenceSheet;
use App\Exports\Sheets\ProductImportTemplateSheet;
use App\Models\ProductCategory;
use App\Models\Unit;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductImportTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        $categories = ProductCategory::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['code', 'name', 'is_active']);

        $units = Unit::query()
            ->orderBy('name')
            ->get(['name', 'symbol']);

        return [
            new ProductImportTemplateSheet(),
            new ProductImportReferenceSheet($categories, $units),
            new ProductImportInstructionsSheet(),
        ];
    }
}
