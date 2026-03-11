<?php

namespace App\Http\Controllers;

use App\Models\ReturnTransaction;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Treasury;
use App\Services\ReturnService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = ReturnTransaction::with(['model', 'warehouse', 'treasury']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $returns = $query->latest()->paginate(15);

        return view('returns.index', compact('returns'));
    }

    public function create()
    {
        $customers = Customer::select('id', 'name')->get();
        $suppliers = Supplier::select('id', 'name')->get();
        $warehouses = Warehouse::select('id', 'name')->get();
        $treasuries = Treasury::select('id', 'name')->get();

        return view('returns.create', compact('customers', 'suppliers', 'warehouses', 'treasuries'));
    }

    public function store(Request $request, ReturnService $service)
    {
        $request->validate([
            'type' => 'required|in:sales_return,purchase_return',
            'warehouse_id' => 'required|exists:warehouses,id',
            'treasury_id' => 'nullable|exists:treasuries,id',
            'return_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $modelType = $request->type === 'sales_return' ? Customer::class : Supplier::class;
        $modelId = $request->type === 'sales_return' ? $request->customer_id : $request->supplier_id;

        $data = [
            'type' => $request->type,
            'return_date' => $request->return_date,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'warehouse_id' => $request->warehouse_id,
            'treasury_id' => $request->treasury_id,
            'notes' => $request->notes,
        ];

        DB::beginTransaction();
        try {
            $service->createReturn($data, $request->items);
            DB::commit();

            return response()->json(['message' => 'تم حفظ المرتجع بنجاح'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show($id)
    {
        try {
            $returnTx = ReturnTransaction::with(['items.product', 'model', 'warehouse', 'treasury'])->findOrFail($id);
            return response()->json($returnTx);
        } catch (Exception $e) {
            return response()->json(['message' => 'حدث خطأ: '.$e->getMessage()], 500);
        }
    }

    public function approve(ReturnTransaction $return, ReturnService $service)
    {
        DB::beginTransaction();
        try {
            $service->approveReturn($return);
            DB::commit();

            return response()->json(['message' => 'تم ترحيل المرتجع وتحديث المخزون بنجاح!'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(ReturnTransaction $return)
    {
        if ($return->status == 'approved') {
            return response()->json(['message' => 'ممنوع الحذف للمرتجعات المرحلة'], 403);
        }
        $return->delete();

        return response()->json(['message' => 'تم الحذف'], 200);
    }

    public function getInvoiceItems(Request $request)
    {
        $type = $request->type;
        $invoiceId = $request->invoice_id;

        if ($type === 'sales_return') {
            $invoice = Invoice::with('items.product')->where('invoice_number', $invoiceId)->first();
        } else {
            $invoice = PurchaseInvoice::with('items.product')->where('invoice_number', $invoiceId)->first();
        }

        if (!$invoice) {
            return response()->json(['message' => 'الفاتورة غير موجودة'], 404);
        }

        return response()->json([
            'model_id' => $type === 'sales_return' ? $invoice->customer_id : $invoice->supplier_id,
            'warehouse_id' => $invoice->warehouse_id,
            'items' => $invoice->items
        ]);
    }
}
