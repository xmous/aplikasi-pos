<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class MasterDataController extends Controller
{
    private const PERMISSIONS = [
        'categories' => 'categories.manage',
        'units' => 'units.manage',
        'suppliers' => 'suppliers.manage',
        'customers' => 'customers.manage',
        'warehouses' => 'warehouses.manage',
    ];

    public function index(string $type): View
    {
        abort_unless(isset(self::PERMISSIONS[$type]), 404);
        abort_unless(request()->user()->can(self::PERMISSIONS[$type]), 403);

        return view('pages.master', compact('type'));
    }
}
