<?php

use App\Http\Controllers\BOMController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnTransactionController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TreasuryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Inventory
|--------------------------------------------------------------------------
*/
Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::post('move', [InventoryController::class, 'move'])->name('move');
});
Route::resource('inventory', InventoryController::class)->except(['show']);

/*
|--------------------------------------------------------------------------
| Production
|--------------------------------------------------------------------------
*/
Route::prefix('production')->name('production.')->group(function () {
    Route::get('/', [ProductionController::class, 'index'])->name('index');
    Route::post('store', [ProductionController::class, 'store'])->name('store');
    Route::post('{order}/complete', [ProductionController::class, 'complete'])->name('complete');
});

/*
|--------------------------------------------------------------------------
| BOM
|--------------------------------------------------------------------------
*/
Route::resource('bom', BOMController::class);

/*
|--------------------------------------------------------------------------
| Sales
|--------------------------------------------------------------------------
*/
Route::prefix('sales')->name('sales.')->group(function () {
    Route::post('{invoice}/approve', [SalesController::class, 'approve'])->name('approve');
    Route::get('{invoice}/print', [SalesController::class, 'print'])->name('print');
});
Route::resource('sales', SalesController::class)->parameters([
    'sales' => 'invoice',
]);

/*
|--------------------------------------------------------------------------
| Purchases
|--------------------------------------------------------------------------
*/
Route::prefix('purchases')->name('purchases.')->group(function () {
    Route::post('{purchase}/approve', [PurchaseController::class, 'approve'])->name('approve');
    Route::get('{purchase}/print', [PurchaseController::class, 'print'])->name('print');
});
Route::resource('purchases', PurchaseController::class);

/*
|--------------------------------------------------------------------------
| Customers & Suppliers
|--------------------------------------------------------------------------
*/
Route::resource('customers', CustomerController::class);
Route::resource('suppliers', SupplierController::class);

/*
|--------------------------------------------------------------------------
| Treasury
|--------------------------------------------------------------------------
*/
Route::resource('treasury', TreasuryController::class)->except(['show']);

/*
|--------------------------------------------------------------------------
| Reports
|--------------------------------------------------------------------------
*/
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('account-statement', [ReportController::class, 'accountStatement'])->name('account_statement');
    Route::get('item-movement', [ReportController::class, 'itemMovement'])->name('item_movement');
    Route::get('shortages', [ReportController::class, 'shortages'])->name('shortages');
});

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
*/
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingController::class, 'index'])->name('index');
    Route::post('/', [SettingController::class, 'update'])->name('update');
});

/*
|--------------------------------------------------------------------------
| Returns
|--------------------------------------------------------------------------
*/
Route::prefix('returns')->name('returns.')->group(function () {
    Route::get('invoice-items', [ReturnTransactionController::class, 'getInvoiceItems'])->name('invoice-items');
    Route::post('{return}/approve', [ReturnTransactionController::class, 'approve'])->name('approve');
});
Route::resource('returns', ReturnTransactionController::class)->except(['edit', 'update']);
