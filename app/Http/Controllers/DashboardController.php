<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Treasury;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. إجمالي المبيعات (المرحلة)
        $totalSales = Invoice::where('status', 'approved')->sum('total_amount');

        // 2. إجمالي المشتريات (المرحلة)
        $totalPurchases = PurchaseInvoice::where('status', 'approved')->sum('total_amount');

        // 3. الرصيد الحالي لجميع الخزن النشطة
        $totalTreasury = Treasury::where('is_active', true)->sum('balance');

        // 4. عدد النواقص (الأصناف التي وصلت لحد التنبيه)
        $shortagesCount = Product::whereRaw('IFNULL((SELECT SUM(quantity) FROM stocks WHERE stocks.product_id = products.id), 0) <= min_stock')->count();

        // 5. آخر 5 فواتير مبيعات
        $recentSales = Invoice::with('customer')->latest()->take(5)->get();

        return view('dashboard', compact(
            'totalSales', 'totalPurchases', 'totalTreasury', 'shortagesCount', 'recentSales'
        ));
    }
}
