<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductionOrder; // استدعاء السيرفس التي أنشأناها سابقاً
use App\Models\Warehouse;
use App\Services\ProductionService;
use Exception;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    /**
     * لوحة قيادة التصنيع
     */
    public function index()
    {

        // 1. إحصائيات سريعة للكروت
        $plannedCount = ProductionOrder::where('status', 'planned')->count();
        $completedCount = ProductionOrder::where('status', 'completed')->count();

        // 2. جلب أوامر الشغل مرتبة الأحدث أولاً
        $orders = ProductionOrder::with(['product', 'warehouse'])->latest()->get();

        // 3. بيانات للمودال (إنشاء أمر جديد)
        // ملاحظة هامة: نجلب فقط المنتجات التامة التي لها "معادلة تصنيع" (BOM)
        // لأننا لا نستطيع تصنيع شيء ليس له مكونات معروفة
        $productsList = Product::where('type', ProductType::FINISHED_GOOD)
            ->whereHas('billOfMaterial') // تأكد أن الموديل Product به علاقة billOfMaterial
            ->get();

        $warehouses = Warehouse::all();

        return view('production.index', compact('orders', 'productsList', 'warehouses', 'plannedCount', 'completedCount'));
    }

    /**
     * حفظ أمر شغل جديد (حالة: مخطط)
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric|min:1',
            'production_date' => 'required|date',
        ]);

        try {
            // توليد رقم أمر شغل تلقائي (PO-20231025-001)
            $orderNumber = 'PO-'.date('Ymd').'-'.rand(100, 999);

            ProductionOrder::create([
                'order_number' => $orderNumber,
                'product_id' => $request->product_id,
                'warehouse_id' => $request->warehouse_id,
                'quantity' => $request->quantity,
                'production_date' => $request->production_date,
                'status' => 'planned', // الحالة المبدئية
            ]);

            return response()->json(['message' => 'تم إنشاء أمر الشغل بنجاح'], 200);

        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * تنفيذ التصنيع (Action)
     * هذا يستدعي السيرفس لخصم الخامات وإضافة المنتج
     */
    public function complete(ProductionOrder $order, ProductionService $service)
    {
        try {
            $service->completeOrder($order);

            return response()->json(['message' => 'تم التصنيع وتحديث المخزون بنجاح!'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
