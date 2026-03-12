<?php

use App\Http\Controllers\BOMController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TreasuryController;
use Illuminate\Support\Facades\Route;

Route::resource('inventory', InventoryController::class)->except(['show']);
Route::post('inventory/move', [InventoryController::class, 'move'])->name('inventory.move');
// صفحة التصنيع الرئيسية
Route::get('/production', [ProductionController::class, 'index'])->name('production.index');
// حفظ أمر جديد
Route::post('/production/store', [ProductionController::class, 'store'])->name('production.store');
// تنفيذ أمر (تحويله لمكتمل)
Route::post('/production/{order}/complete', [ProductionController::class, 'complete'])->name('production.complete');

Route::resource('bom', BomController::class);

Route::post('sales/{invoice}/approve', [SalesController::class, 'approve'])->name('sales.approve');
Route::get('sales/{invoice}/print', [SalesController::class, 'print'])->name('sales.print');
Route::resource('sales', SalesController::class)->parameters([
    'sales' => 'invoice',
]);
Route::resource('customers', CustomerController::class);

Route::post('purchases/{purchase}/approve', [PurchaseController::class, 'approve'])->name('purchases.approve');
Route::get('purchases/{purchase}/print', [PurchaseController::class, 'print'])->name('purchases.print');
Route::resource('purchases', PurchaseController::class);

Route::resource('suppliers', SupplierController::class);

/*
Route::get('/treasury', [TreasuryController::class, 'index'])->name('treasury.index');
Route::get('/treasury/create', [TreasuryController::class, 'create'])->name('treasury.create');
Route::post('/treasury/store', [TreasuryController::class, 'store'])->name('treasury.store');
*/
Route::resource('treasury', TreasuryController::class)->except(['show']);

Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('account-statement', [ReportController::class, 'accountStatement'])->name('account_statement');
    Route::get('item-movement', [ReportController::class, 'itemMovement'])->name('item_movement');
    Route::get('shortages', [ReportController::class, 'shortages'])->name('shortages');
});
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/settings', [App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
Route::post('/settings', [App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');

Route::get('/returns/invoice-items', [App\Http\Controllers\ReturnTransactionController::class, 'getInvoiceItems'])->name('returns.invoice-items');
Route::post('/returns/{return}/approve', [App\Http\Controllers\ReturnTransactionController::class, 'approve'])->name('returns.approve');
Route::resource('returns', App\Http\Controllers\ReturnTransactionController::class)->except(['edit', 'update']);
