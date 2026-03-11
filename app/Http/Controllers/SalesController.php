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

/**
 * Class SalesController
 * Handles all HTTP requests related to Sales Invoices.
 * Adheres to Single Responsibility Principle by delegating business logic
 * (like creating and approving invoices) to the SalesService.
 */
class SalesController extends Controller
{
    /**
     * Display a paginated list of sales invoices with search and export capabilities.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Symfony\Component\HttpFoundation\Response
     */
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

    /**
     * Show the form for creating a new sales invoice.
     * Uses caching for reference data to optimize performance.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // 5. Caching لتقليل ضربات الداتا بيز
        $customers = Cache::remember('customers_list', 86400, fn () => Customer::select('id', 'name')->get());
        $warehouses = Cache::remember('active_warehouses', 86400, fn () => Warehouse::select('id', 'name')->get());
        // جلب المنتجات التامة للمبيعات
        $products = Product::where('type', ProductType::FINISHED_GOOD)->select('id', 'name', 'sku')->get();

        return view('sales.create', compact('customers', 'warehouses', 'products'));
    }

    /**
     * Store a newly created sales invoice as a draft.
     * Delegates creation logic to SalesService.
     *
     * @param SaleRequest $request Validated request data.
     * @param SalesService $service The service handling the business logic.
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Show the form for editing a draft sales invoice.
     * Prevents editing if the invoice is already approved.
     *
     * @param Invoice $invoice The invoice to edit.
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
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

    /**
     * Update an existing draft sales invoice.
     * Delegates update logic to SalesService.
     *
     * @param SaleRequest $request Validated request data.
     * @param Invoice $invoice The invoice being updated.
     * @param SalesService $service The service handling the business logic.
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Fetch the details of a specific sales invoice via AJAX.
     *
     * @param int $id The ID of the invoice.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $invoice = Invoice::with(['items.product', 'customer', 'warehouse'])->findOrFail($id);

            return response()->json($invoice);
        } catch (Exception $e) {
            return response()->json(['message' => 'حدث خطأ: '.$e->getMessage()], 500);
        }
    }

    /**
     * Approve and finalize a sales invoice.
     * This triggers the deduction of stock quantities via the SalesService.
     *
     * @param Invoice $invoice The invoice to approve.
     * @param SalesService $service The service handling the business logic.
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Delete a draft sales invoice.
     * Prevents deletion if the invoice is already approved.
     *
     * @param Invoice $invoice The invoice to delete.
     * @return \Illuminate\Http\JsonResponse
     */
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
