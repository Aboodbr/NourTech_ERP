<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Exports\PurchaseExport;
use App\Http\Requests\PurchaseRequest;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchasesService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;

/**
 * Class PurchaseController
 * Handles all HTTP requests related to Purchase Invoices.
 * Adheres to Single Responsibility Principle by delegating business logic
 * (like creating and approving invoices) to the PurchasesService.
 */
class PurchaseController extends Controller
{
    /**
     * Display a paginated list of purchase invoices with search and export capabilities.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        // 1. Eager Loading لمنع N+1
        $query = PurchaseInvoice::with(['supplier', 'warehouse']);

        // 2. Query Optimization (البحث)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where('invoice_number', 'like', "%{$searchTerm}%")
                ->orWhereHas('supplier', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%");
                });
        }
        if ($request->filled('from_date')) {
            $query->whereDate('invoice_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('invoice_date', '<=', $request->to_date);
        }

        // 3. التصدير (بدون Pagination للتحميل الكامل)
        if ($request->has('export')) {
            $exportData = $query->latest()->get();
            if ($request->export == 'excel') {
                return Excel::download(new PurchaseExport($exportData), 'مشتريات_'.date('Y-m-d').'.xlsx');
            }
            if ($request->export == 'pdf') {
                $html = view('layouts.print_list')
                    ->with('title', 'سجل فواتير المشتريات')
                    ->with('table', view('purchases.components._pdf_table', ['invoices' => $exportData])->render())
                    ->render();
                $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'autoScriptToLang' => true, 'autoLangToFont' => true]);
                $mpdf->WriteHTML($html);

                return response($mpdf->Output('', 'S'))->header('Content-Type', 'application/pdf');
            }
        }

        // 4. عرض الواجهة مع Pagination (لحماية الـ RAM)
        $invoices = $query->latest()->paginate(15)->withQueryString();

        return view('purchases.index', compact('invoices'));
    }

    /**
     * Show the form for creating a new purchase invoice.
     * Uses caching for reference data to optimize performance.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // 5. Caching: تخزين الموردين والمخازن في الكاش لتقليل الضغط على قاعدة البيانات
        $suppliers = Cache::remember('suppliers_list', 86400, fn () => Supplier::select('id', 'name')->get());
        $warehouses = Cache::remember('active_warehouses', 86400, fn () => Warehouse::select('id', 'name')->get());

        // جلب المواد الخام فقط (بدون كاش لأنها قد تتغير باستمرار)
        $products = Product::where('type', ProductType::RAW_MATERIAL)->select('id', 'name', 'sku')->get();

        return view('purchases.create', compact('suppliers', 'warehouses', 'products'));
    }

    /**
     * Store a newly created purchase invoice as a draft.
     * Delegates creation logic to PurchasesService.
     *
     * @param PurchaseRequest $request Validated request data.
     * @param PurchasesService $service The service handling the business logic.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PurchaseRequest $request, PurchasesService $service)
    {
        DB::beginTransaction();
        try {
            $service->createInvoice($request->only(['supplier_id', 'warehouse_id', 'invoice_date', 'notes']), $request->items);
            DB::commit();

            return response()->json(['message' => 'تم حفظ فاتورة الشراء بنجاح'], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Show the form for editing a draft purchase invoice.
     * Prevents editing if the invoice is already approved.
     *
     * @param PurchaseInvoice $purchase The invoice to edit.
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(PurchaseInvoice $purchase)
    {
        if ($purchase->status == 'approved') {
            return redirect()->route('purchases.index')->with('error', 'لا يمكن تعديل فاتورة مرحلة');
        }

        $suppliers = Cache::remember('suppliers_list', 86400, fn () => Supplier::select('id', 'name')->get());
        $warehouses = Cache::remember('active_warehouses', 86400, fn () => Warehouse::select('id', 'name')->get());
        $products = Product::where('type', ProductType::RAW_MATERIAL)->select('id', 'name', 'sku')->get();

        $purchase->load('items');

        return view('purchases.edit', compact('purchase', 'suppliers', 'warehouses', 'products'));
    }

    /**
     * Update an existing draft purchase invoice.
     * Delegates update logic to PurchasesService.
     *
     * @param PurchaseRequest $request Validated request data.
     * @param PurchaseInvoice $purchase The invoice being updated.
     * @param PurchasesService $service The service handling the business logic.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(PurchaseRequest $request, PurchaseInvoice $purchase, PurchasesService $service)
    {
        DB::beginTransaction();
        try {
            $service->updateInvoice($purchase, $request->only(['supplier_id', 'warehouse_id', 'invoice_date', 'notes']), $request->items);
            DB::commit();

            return response()->json(['message' => 'تم تحديث الفاتورة بنجاح'], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Fetch the details of a specific purchase invoice via AJAX.
     *
     * @param int $id The ID of the invoice.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $purchase = PurchaseInvoice::with(['items.product', 'supplier', 'warehouse'])->findOrFail($id);

            return response()->json($purchase);
        } catch (Exception $e) {
            return response()->json(['message' => 'حدث خطأ: '.$e->getMessage()], 500);
        }
    }

    /**
     * Approve and finalize a purchase invoice.
     * This triggers the addition of stock quantities via the PurchasesService.
     *
     * @param PurchaseInvoice $purchase The invoice to approve.
     * @param PurchasesService $service The service handling the business logic.
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(PurchaseInvoice $purchase, PurchasesService $service)
    {
        DB::beginTransaction();
        try {
            $service->approveInvoice($purchase);
            DB::commit();

            return response()->json(['message' => 'تم ترحيل الفاتورة وإضافة الكميات للمخزن!'], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete a draft purchase invoice.
     * Prevents deletion if the invoice is already approved.
     *
     * @param PurchaseInvoice $purchase The invoice to delete.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(PurchaseInvoice $purchase)
    {
        if ($purchase->status == 'approved') {
            return response()->json(['message' => 'ممنوع الحذف للفواتير المرحلة'], 403);
        }
        $purchase->delete();

        return response()->json(['message' => 'تم الحذف'], 200);
    }

    public function print($id)
    {
        $invoice = PurchaseInvoice::with(['items.product', 'supplier', 'warehouse'])->findOrFail($id);

        return view('purchases.print', compact('invoice'));
    }
}
