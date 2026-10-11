<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CashierShiftController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\ManualController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockOpnameController;
use Illuminate\Support\Facades\Route;

Route::prefix('pos')->middleware(['auth', 'verified', 'active', 'session.idle'])->group(function () {
    Route::get('/', DashboardController::class)->middleware('can:dashboard.view')->name('pos.dashboard');

    Route::get('/kasir', [SaleController::class, 'index'])->middleware('can:sales.create')->name('sales.pos');
    Route::post('/kasir', [SaleController::class, 'store'])->middleware(['can:sales.create','throttle:pos-write'])->name('sales.store');
    Route::get('/penjualan/{sale}', [SaleController::class, 'show'])->middleware('can:sales.view')->name('sales.show');
    Route::get('/penjualan/{sale}/thermal-text', [SaleController::class, 'thermalText'])->middleware('can:sales.view')->name('sales.thermal-text');
    Route::post('/penjualan/{sale}/pembayaran', [SaleController::class, 'addPayment'])->middleware(['can:sales.payment','throttle:pos-write'])->name('sales.payments.store');

    Route::get('/shift', [CashierShiftController::class, 'index'])->middleware('can:shifts.manage')->name('shifts.index');
    Route::post('/shift/buka', [CashierShiftController::class, 'open'])->middleware(['can:shifts.manage','throttle:pos-write'])->name('shifts.open');
    Route::post('/shift/{cashierShift}/tutup', [CashierShiftController::class, 'close'])->middleware(['can:shifts.manage','throttle:pos-write'])->name('shifts.close');

    Route::get('/products/import-template', ProductImportController::class)
        ->middleware(['can:products.create', 'throttle:pos-export'])
        ->name('products.import-template');
    Route::view('/products', 'pages.products')->middleware('can:products.view')->name('products.index');
    Route::get('/master/{type}', [MasterDataController::class, 'index'])->name('master.index');

    Route::middleware(['can:purchases.view'])->group(function () {
        Route::get('/pembelian', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/pembelian/create', [PurchaseController::class, 'create'])->middleware('can:purchases.create')->name('purchases.create');
        Route::post('/pembelian', [PurchaseController::class, 'store'])->middleware(['can:purchases.create','throttle:pos-write'])->name('purchases.store');
        Route::get('/pembelian/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
        Route::post('/pembelian/{purchase}/pembayaran', [PurchaseController::class, 'addPayment'])->middleware(['can:purchases.payment','throttle:pos-write'])->name('purchases.payments.store');
    });

    Route::get('/stok/mutasi', [StockController::class, 'movements'])->middleware('can:stock.view')->name('stocks.movements');
    Route::get('/stok/batch', [StockController::class, 'batches'])->middleware('can:stock.view')->name('stocks.batches');
    Route::get('/stok/penyesuaian', [StockController::class, 'adjustment'])->middleware('can:stock.adjust')->name('stocks.adjustment');
    Route::post('/stok/penyesuaian', [StockController::class, 'storeAdjustment'])->middleware(['can:stock.adjust','throttle:pos-write'])->name('stocks.adjustment.store');

    Route::middleware('can:opname.view')->group(function () {
        Route::get('/stok-opname', [StockOpnameController::class, 'index'])->name('opnames.index');
        Route::get('/stok-opname/create', [StockOpnameController::class, 'create'])->middleware('can:opname.create')->name('opnames.create');
        Route::post('/stok-opname', [StockOpnameController::class, 'store'])->middleware(['can:opname.create','throttle:pos-write'])->name('opnames.store');
        Route::get('/stok-opname/{opname}', [StockOpnameController::class, 'show'])->name('opnames.show');
        Route::post('/stok-opname/{opname}/submit', [StockOpnameController::class, 'submit'])->middleware(['can:opname.create','throttle:pos-write'])->name('opnames.submit');
        Route::post('/stok-opname/{opname}/approve', [StockOpnameController::class, 'approve'])->middleware(['can:opname.approve','throttle:pos-write'])->name('opnames.approve');
        Route::delete('/stok-opname/{opname}', [StockOpnameController::class, 'destroy'])->middleware(['can:opname.create','throttle:pos-write'])->name('opnames.destroy');
    });

    Route::get('/retur-pembelian', [PurchaseReturnController::class, 'index'])->middleware('can:purchase_returns.create')->name('purchase-returns.index');
    Route::get('/retur-pembelian/{purchaseReturn}', [PurchaseReturnController::class, 'show'])->middleware('can:purchase_returns.create')->name('purchase-returns.show');
    Route::get('/pembelian/{purchase}/retur', [PurchaseReturnController::class, 'create'])->middleware('can:purchase_returns.create')->name('purchase-returns.create');
    Route::post('/pembelian/{purchase}/retur', [PurchaseReturnController::class, 'store'])->middleware(['can:purchase_returns.create','throttle:pos-write'])->name('purchase-returns.store');

    Route::get('/retur-penjualan', [SaleReturnController::class, 'index'])->middleware('can:sale_returns.create')->name('sale-returns.index');
    Route::get('/retur-penjualan/{saleReturn}', [SaleReturnController::class, 'show'])->middleware('can:sale_returns.create')->name('sale-returns.show');
    Route::get('/penjualan/{sale}/retur', [SaleReturnController::class, 'create'])->middleware('can:sale_returns.create')->name('sale-returns.create');
    Route::post('/penjualan/{sale}/retur', [SaleReturnController::class, 'store'])->middleware(['can:sale_returns.create','throttle:pos-write'])->name('sale-returns.store');

    Route::view('/expenses', 'pages.expenses')->middleware('can:expenses.view')->name('expenses.index');
    Route::view('/promotions', 'pages.promotions')->middleware('can:promotions.manage')->name('promotions.index');

    Route::view('/users', 'pages.users')->middleware('can:users.manage')->name('users.index');
    Route::view('/roles', 'pages.roles')->middleware('can:roles.manage')->name('roles.index');

    Route::get(
        '/reports/analytics/detail',
        [ReportController::class, 'analyticsDetail']
    )
        ->middleware([
            'can:dashboard.view',
            'throttle:pos-export',
        ])
        ->name('reports.analytics.detail');

    Route::get('/reports/analytics', [ReportController::class, 'analytics'])
        ->middleware('can:dashboard.view')
        ->name('reports.analytics');
    Route::view('/reports/sales', 'reports.sales')->middleware('can:reports.sales')->name('reports.sales');
    Route::view('/reports/stocks', 'reports.stocks')->middleware('can:reports.stocks')->name('reports.stocks');
    Route::get('/reports/profit', [ReportController::class, 'profit'])->middleware('can:reports.profit')->name('reports.profit');
    Route::get('/reports/debts', [ReportController::class, 'debts'])->middleware('can:reports.debts')->name('reports.debts');
    Route::view('/reports/operations', 'reports.operations')->middleware('can:reports.operations')->name('reports.operations');
    Route::get('/reports/sales.xlsx', [ReportController::class, 'salesExcel'])->middleware(['can:reports.export','throttle:pos-export'])->name('reports.sales.excel');
    Route::get('/reports/stocks.xlsx', [ReportController::class, 'stocksExcel'])->middleware(['can:reports.export','throttle:pos-export'])->name('reports.stocks.excel');
    Route::get('/reports/sales.pdf', [ReportController::class, 'salesPdf'])->middleware(['can:reports.export','throttle:pos-export'])->name('reports.sales.pdf');
    Route::get('/reports/stocks.pdf', [ReportController::class, 'stocksPdf'])->middleware(['can:reports.export','throttle:pos-export'])->name('reports.stocks.pdf');
    Route::get('/reports/profit.xlsx', [ReportController::class, 'profitExcel'])->middleware(['can:reports.export','throttle:pos-export'])->name('reports.profit.excel');
    Route::get('/reports/profit.pdf', [ReportController::class, 'profitPdf'])->middleware(['can:reports.export','throttle:pos-export'])->name('reports.profit.pdf');
    Route::get('/reports/debts.xlsx', [ReportController::class, 'debtsExcel'])->middleware(['can:reports.export','throttle:pos-export'])->name('reports.debts.excel');
    Route::get('/reports/debts.pdf', [ReportController::class, 'debtsPdf'])->middleware(['can:reports.export','throttle:pos-export'])->name('reports.debts.pdf');
    Route::get('/reports/operations/{type}.xlsx', [ReportController::class, 'operationalExcel'])->middleware(['can:reports.export','throttle:pos-export'])->name('reports.operations.excel');
    Route::get('/reports/operations/{type}.pdf', [ReportController::class, 'operationalPdf'])->middleware(['can:reports.export','throttle:pos-export'])->name('reports.operations.pdf');

    Route::get('/manual', [ManualController::class, 'index'])->name('manual.index');
    Route::get('/manual/download/{format}', [ManualController::class, 'download'])
        ->whereIn('format', ['pdf', 'docx'])
        ->name('manual.download');

    Route::get('/settings', [SettingController::class, 'edit'])->middleware('can:settings.manage')->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->middleware(['can:settings.manage','throttle:pos-write'])->name('settings.update');
    Route::get('/activity-logs', ActivityLogController::class)->middleware('can:audit.view')->name('activity.index');
});
