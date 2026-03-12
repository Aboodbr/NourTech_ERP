<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Enums\TransactionType;
use App\Exports\ProductExport;
use App\Http\Requests\InventoryMoveRequest;
use App\Http\Requests\InventoryRequest; // 🔴 استدعاء ملف الريكوست
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;

/**
 * Class InventoryController
 * Handles all HTTP requests related to Inventory Management.
 * Manages products, stock levels, and manual stock movements.
 */
class InventoryController extends Controller
{
    /**
     * Display a list of products and their current stock levels.
     * Includes search functionality and separates raw materials from finished goods.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        $query = Product::with('stocks.warehouse')->withSum('stocks', 'quantity');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('sku', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->has('export')) {
            $exportData = $query->latest()->get();

            if ($request->export == 'excel') {
                return Excel::download(new ProductExport($exportData), 'سجل_المخازن_'.date('Y-m-d').'.xlsx');
            }

            if ($request->export == 'pdf') {
                $html = view('layouts.print_list')->with('title', 'قائمة المخازن (جرد الأصناف)')->with('table', view('inventory.components._pdf_table', ['inventory' => $exportData])->render())->render();
                $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4-P', 'autoScriptToLang' => true, 'autoLangToFont' => true]);
                $mpdf->WriteHTML($html);

                return response($mpdf->Output('', 'S'))->header('Content-Type', 'application/pdf');
            }
        }

        $inventory = $query->latest()->paginate(20)->withQueryString();

        $totalRaw = Product::where('type', ProductType::RAW_MATERIAL)->count();
        $totalFinished = Product::where('type', ProductType::FINISHED_GOOD)->count();

        $warehouses = Cache::remember('active_warehouses', 86400, function () {
            return Warehouse::select('id', 'name')->get();
        });

        $productsList = Product::select('id', 'name', 'sku')->get();

        return view('inventory.index', compact('inventory', 'totalRaw', 'totalFinished', 'warehouses', 'productsList'));
    }

    /**
     * Show the form for creating a new product.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('inventory.create');
    }

    // 🔴 استخدام InventoryRequest بدلاً من Request العادي
    /**
     * Store a newly created product in storage.
     *
     * @param Request $request Validated request data.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(InventoryRequest $request)
    {
        DB::beginTransaction();
        try {
            // جلب البيانات التي تم التحقق منها فقط
            Product::create($request->validated());
            DB::commit();

            return redirect()->route('inventory.index')->with('success', 'تم إضافة المادة بنجاح');
        } catch (Exception $e) {
            DB::rollBack();

            return back()->with('error', 'حدث خطأ: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Show the form for editing the specified product.
     *
     * @param Product $inventory The product to edit.
     * @return \Illuminate\View\View
     */
    public function edit(Product $inventory)
    {
        return view('inventory.edit', compact('inventory'));
    }

    // 🔴 استخدام InventoryRequest
    /**
     * Update the specified product in storage.
     *
     * @param Request $request Validated request data.
     * @param Product $inventory The product to update.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(InventoryRequest $request, Product $inventory)
    {
        DB::beginTransaction();
        try {
            $inventory->update($request->validated());
            DB::commit();

            return redirect()->route('inventory.index')->with('success', 'تم تحديث البيانات بنجاح');
        } catch (Exception $e) {
            DB::rollBack();

            return back()->with('error', 'حدث خطأ: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified product from storage.
     *
     * @param Product $inventory The product to delete.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Product $inventory)
    {
        if ($inventory->stocks()->where('quantity', '>', 0)->exists()) {
            return response()->json(['message' => 'لا يمكن حذف مادة لها رصيد فعلي في المخازن!'], 403);
        }
        $inventory->delete();

        return response()->json(['message' => 'تم الحذف بنجاح'], 200);
    }

    /**
     * Perform a manual stock movement (addition or deduction).
     * Delegates the core inventory logic to InventoryService.
     *
     * @param Request $request Validated request data.
     * @param InventoryService $inventoryService The service handling the business logic.
     * @return \Illuminate\Http\JsonResponse
     */
    public function move(InventoryMoveRequest $request, InventoryService $inventoryService)
    {
        $data = $request->validated();
        try {
            $product = Product::findOrFail($data['product_id']);
            $warehouse = Warehouse::findOrFail($data['warehouse_id']);

            // هنا سيتم التعرف على manual_add و manual_deduct بنجاح
            $type = TransactionType::from($data['type']);

            $qty = abs((float) $data['quantity']);

            // 🔴 أضفنا TransactionType::MANUAL_DEDUCT هنا لكي يتم خصم الكمية
            if (in_array($type, [
                TransactionType::SALE,
                TransactionType::PRODUCTION_OUT,
                TransactionType::MANUAL_DEDUCT,
            ])) {
                $qty = -$qty;
            }

            $inventoryService->moveStock($product, $warehouse, $qty, $type, null, $data['notes'] ?? null);

            return response()->json(['message' => 'تم تسجيل الحركة بنجاح!'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
