<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Exports\SalesExport;
use App\Http\Requests\SaleRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\SalesService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        // 1. Eager Loading لمنع N+1
        $query = Invoice::with(['customer', 'warehouse']);

        // 2. Query Optimization (البحث)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where('invoice_number', 'like', "%{$searchTerm}%")
                ->orWhereHas('customer', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%");
                });
        }
        if ($request->filled('from_date')) {
            $query->whereDate('invoice_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('invoice_date', '<=', $request->to_date);
        }

        // 3. التصدير (تحميل كامل)
        if ($request->has('export')) {
            $exportData = $query->latest()->get();
            if ($request->export == 'excel') {
                return Excel::download(new SalesExport($exportData), 'مبيعات_'.date('Y-m-d').'.xlsx');
            }
            if ($request->export == 'pdf') {
                $html = view('layouts.print_list')
                    ->with('title', 'سجل فواتير المبيعات')
                    ->with('table', view('sales.components._pdf_table', ['invoices' => $exportData])->render())
                    ->render();
                $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'autoScriptToLang' => true, 'autoLangToFont' => true]);
                $mpdf->WriteHTML($html);

                return response($mpdf->Output('', 'S'))->header('Content-Type', 'application/pdf');
            }
        }

        // 4. عرض الواجهة مع Pagination
        $invoices = $query->latest()->paginate(15)->withQueryString();

        return view('sales.index', compact('invoices'));
    }

    public function create()
    {
        // 5. Caching لتقليل ضربات الداتا بيز
        $customers = Cache::remember('customers_list', 86400, fn () => Customer::select('id', 'name')->get());
        $warehouses = Cache::remember('active_warehouses', 86400, fn () => Warehouse::select('id', 'name')->get());
        // جلب المنتجات التامة للمبيعات
        $products = Product::where('type', ProductType::FINISHED_GOOD)->select('id', 'name', 'sku')->get();

        return view('sales.create', compact('customers', 'warehouses', 'products'));
    }

    public function store(SaleRequest $request, SalesService $service)
    {
        DB::beginTransaction();
        try {
            $service->createInvoice($request->only(['customer_id', 'warehouse_id', 'invoice_date', 'notes']), $request->items);
            DB::commit();

            return response()->json(['message' => 'تم حفظ الفاتورة كمسودة بنجاح'], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function edit(Invoice $invoice)
    {
        if ($invoice->status == 'approved') {
            return redirect()->route('sales.index')->with('error', 'لا يمكن تعديل فاتورة مرحلة');
        }

        $customers = Cache::remember('customers_list', 86400, fn () => Customer::select('id', 'name')->get());
        $warehouses = Cache::remember('active_warehouses', 86400, fn () => Warehouse::select('id', 'name')->get());
        $products = Product::where('type', ProductType::FINISHED_GOOD)->select('id', 'name', 'sku')->get();

        $invoice->load('items');

        return view('sales.edit', compact('invoice', 'customers', 'warehouses', 'products'));
    }

    public function update(SaleRequest $request, Invoice $invoice, SalesService $service)
    {
        DB::beginTransaction();
        try {
            $service->updateInvoice($invoice, $request->only(['customer_id', 'warehouse_id', 'invoice_date', 'notes']), $request->items);
            DB::commit();

            return response()->json(['message' => 'تم تحديث الفاتورة بنجاح'], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show($id)
    {
        try {
            $invoice = Invoice::with(['items.product', 'customer', 'warehouse'])->findOrFail($id);

            return response()->json($invoice);
        } catch (Exception $e) {
            return response()->json(['message' => 'حدث خطأ: '.$e->getMessage()], 500);
        }
    }

    public function approve(Invoice $invoice, SalesService $service)
    {
        DB::beginTransaction();
        try {
            $service->approveInvoice($invoice);
            DB::commit();

            return response()->json(['message' => 'تم ترحيل الفاتورة وخصم الكميات من المخزن!'], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->status == 'approved') {
            return response()->json(['message' => 'ممنوع الحذف للفواتير المرحلة'], 403);
        }
        $invoice->delete();

        return response()->json(['message' => 'تم الحذف'], 200);
    }

    public function print($id)
    {
        $invoice = Invoice::with(['items.product', 'customer', 'warehouse'])->findOrFail($id);

        return view('sales.print', compact('invoice'));
    }
}
