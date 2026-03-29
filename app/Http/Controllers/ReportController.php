<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\AccountStatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Mpdf\Mpdf;

class ReportController extends Controller
{
    // =========================================================================
    // 1. تقرير كشف الحساب
    // =========================================================================
    public function accountStatement(Request $request, AccountStatementService $service)
    {
        $customers = Cache::remember('customers_list', 86400, fn () => Customer::select('id', 'name')->get());
        $suppliers = Cache::remember('suppliers_list', 86400, fn () => Supplier::select('id', 'name')->get());

        $statementData = null;

        if ($request->filled('party_type') && $request->filled('party_id') && $request->filled('from_date') && $request->filled('to_date')) {
            $statementData = $service->generateStatement(
                $request->party_type,
                $request->party_id,
                $request->from_date,
                $request->to_date
            );

            // التصدير (Excel / PDF)
            if ($request->filled('export')) {
                if ($request->export == 'excel') {
                    return $this->exportToCsv($statementData['transactions'], 'Account_Statement');
                }
                if ($request->export == 'pdf') {
                    return $this->generatePdfFromData($statementData['transactions'], 'كشف حساب تفصيلي - '.$statementData['party']->name);
                }
            }
        }

        return view('reports.account_statement', compact('customers', 'suppliers', 'statementData'));
    }

    // =========================================================================
    // 2. تقرير حركة صنف
    // =========================================================================
    public function itemMovement(Request $request)
    {
        $products = Cache::remember('products_list_basic', 86400, fn () => Product::select('id', 'name', 'sku')->get());

        $movements = collect();
        $product = null;

        if ($request->filled('product_id')) {
            $product = Product::findOrFail($request->product_id);
            $productId = $request->product_id;

            $query = InventoryTransaction::with(['stock.warehouse', 'user', 'reference'])
                ->whereHas('stock', function ($q) use ($productId) {
                    $q->where('product_id', $productId);
                });

            if ($request->filled('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // التصدير (Excel / PDF)
            if ($request->filled('export')) {
                $exportData = $query->latest()->get();
                if ($request->export == 'excel') {
                    return $this->exportItemMovementCsv($exportData, $product->name);
                }
                if ($request->export == 'pdf') {
                    return $this->generatePdfMovement($exportData, $product->name);
                }
            }

            $movements = $query->latest()->paginate(50)->withQueryString();
        }

        return view('reports.item_movement', compact('products', 'movements', 'product'));
    }

    // =========================================================================
    // 3. تقرير النواقص
    // =========================================================================
    public function shortages(Request $request)
    {
        $query = Product::withSum('stocks', 'quantity')
            ->whereRaw('IFNULL((SELECT SUM(quantity) FROM stocks WHERE stocks.product_id = products.id), 0) <= min_stock');

        // البحث العام
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('sku', 'like', "%{$searchTerm}%");
            });
        }

        $query->orderBy('stocks_sum_quantity', 'asc');

        // التصدير (Excel / PDF)
        if ($request->filled('export')) {
            $exportData = $query->get();
            if ($request->export == 'excel') {
                return $this->exportShortagesCsv($exportData);
            }
            if ($request->export == 'pdf') {
                return $this->generatePdfShortages($exportData);
            }
        }

        $shortages = $query->paginate(50)->withQueryString();

        return view('reports.shortages', compact('shortages'));
    }

    public function toggleShortageOrdered(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:products,id',
            'is_ordered' => 'required|boolean',
        ]);

        $product = Product::findOrFail($request->id);
        $product->is_ordered = $request->is_ordered;
        $product->save();

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // دوال تصدير Excel (CSV)
    // =========================================================================

    private function exportToCsv($data, $fileName)
    {
        return response()->streamDownload(function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['التاريخ', 'رقم المرجع', 'البيان', 'مدين', 'دائن', 'الرصيد']);
            foreach ($data as $row) {
                fputcsv($file, [$row['date'], $row['ref_no'], $row['description'], $row['debit'], $row['credit'], $row['balance']]);
            }
            fclose($file);
        }, "{$fileName}_".date('Y-m-d').'.csv');
    }

    private function exportItemMovementCsv($data, $productName)
    {
        return response()->streamDownload(function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['التاريخ', 'المخزن', 'نوع الحركة', 'الكمية', 'المستخدم']);
            foreach ($data as $row) {
                fputcsv($file, [
                    $row->created_at->format('Y-m-d H:i'),
                    $row->stock->warehouse->name ?? '-',
                    $row->type,
                    $row->quantity,
                    $row->user->name ?? '-',
                ]);
            }
            fclose($file);
        }, "Movement_{$productName}_".date('Y-m-d').'.csv');
    }

    private function exportShortagesCsv($data)
    {
        return response()->streamDownload(function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['كود الصنف', 'اسم الصنف', 'الحد الأدنى', 'الرصيد الفعلي']);
            foreach ($data as $row) {
                fputcsv($file, [$row->sku, $row->name, $row->min_stock, $row->stocks_sum_quantity ?? 0]);
            }
            fclose($file);
        }, 'Shortages_'.date('Y-m-d').'.csv');
    }

    // =========================================================================
    // 🔴 دوال تصدير PDF (استخدام Mpdf مع ملف layouts.print_list) 🔴
    // =========================================================================

    private function generatePdf($htmlContent, $title)
    {
        // استخدام ملف الـ Layout الخاص بالطباعة وتمرير المتغيرات له
        $html = view('layouts.print_list')
            ->with('title', $title)
            ->with('table', $htmlContent)
            ->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-P',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'))->header('Content-Type', 'application/pdf');
    }

    private function generatePdfFromData($data, $title)
    {
        $table = "<table class='table table-bordered table-striped text-center' dir='rtl'>
                    <thead class='table-dark'>
                        <tr>
                            <th>التاريخ</th>
                            <th>المستند</th>
                            <th>رقم المرجع</th>
                            <th>البيان</th>
                            <th>مدين</th>
                            <th>دائن</th>
                            <th>الرصيد</th>
                        </tr>
                    </thead>
                    <tbody>";

        foreach ($data as $row) {
            $document = $row['document'] ?? '-';
            $description = $row['description'] ?? '-';
            $debit = $row['debit'] > 0 ? number_format($row['debit'], 2) : '-';
            $credit = $row['credit'] > 0 ? number_format($row['credit'], 2) : '-';
            $balance = number_format($row['balance'], 2);

            $table .= "<tr>
                        <td>{$row['date']}</td>
                        <td>{$document}</td>
                        <td class='fw-bold'>{$row['ref_no']}</td>
                        <td>{$description}</td>
                        <td class='text-danger'>{$debit}</td>
                        <td class='text-success'>{$credit}</td>
                        <td dir='ltr' class='fw-bold bg-light'>{$balance}</td>
                       </tr>";
        }
        $table .= '</tbody></table>';

        return $this->generatePdf($table, $title);
    }

    private function generatePdfMovement($data, $productName)
    {
        $table = "<table class='table table-bordered table-striped text-center' dir='rtl'>
                    <thead class='table-dark'>
                        <tr>
                            <th>التاريخ</th>
                            <th>المخزن</th>
                            <th>نوع الحركة</th>
                            <th>الكمية</th>
                            <th>المستخدم</th>
                        </tr>
                    </thead>
                    <tbody>";

        foreach ($data as $row) {
            $date = $row->created_at->format('Y-m-d H:i');
            $warehouse = $row->stock->warehouse->name ?? '-';
            $type = $row->type ?? '-';
            $qty = $row->quantity;
            $user = $row->user->name ?? 'نظام';

            $table .= "<tr>
                        <td dir='ltr'>{$date}</td>
                        <td>{$warehouse}</td>
                        <td>{$type}</td>
                        <td dir='ltr' class='fw-bold'>{$qty}</td>
                        <td>{$user}</td>
                       </tr>";
        }
        $table .= '</tbody></table>';

        return $this->generatePdf($table, 'تقرير حركة صنف: '.$productName);
    }

    private function generatePdfShortages($data)
    {
        $table = "<table class='table table-bordered table-striped text-center' dir='rtl'>
                    <thead class='table-dark'>
                        <tr>
                            <th>كود الصنف</th>
                            <th>اسم الصنف</th>
                            <th>الحد الأدنى</th>
                            <th>الرصيد الفعلي</th>
                        </tr>
                    </thead>
                    <tbody>";

        foreach ($data as $row) {
            $stock = $row->stocks_sum_quantity ?? 0;
            $stockFormatted = number_format($stock, 1);

            $table .= "<tr>
                        <td class='fw-bold'>{$row->sku}</td>
                        <td>{$row->name}</td>
                        <td>{$row->min_stock}</td>
                        <td dir='ltr' class='text-danger fw-bold'>{$stockFormatted}</td>
                       </tr>";
        }
        $table .= '</tbody></table>';

        return $this->generatePdf($table, 'تقرير النواقص الحالي');
    }
}
