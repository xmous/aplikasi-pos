<?php

namespace App\Http\Controllers;

use App\Exports\ProductImportTemplateExport;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImportController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        Gate::authorize('products.create');

        return Excel::download(
            new ProductImportTemplateExport(),
            'template-import-barang-'.now()->format('Ymd').'.xlsx'
        );
    }
}
