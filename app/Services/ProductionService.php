<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\BillOfMaterial;
use App\Models\ProductionOrder;
use Exception;
use Illuminate\Support\Facades\DB;

class ProductionService
{
    // حقن خدمة المخازن لاستخدامها
    public function __construct(protected InventoryService $inventoryService) {}

    /**
     * تنفيذ أمر تصنيع (تحويل الخامات لمنتج تام)
     */
    public function completeOrder(ProductionOrder $order)
    {
        if ($order->status === 'completed') {
            throw new Exception('هذا الأمر تم تنفيذه مسبقاً!');
        }

        return DB::transaction(function () use ($order) {

            // 1. جلب معادلة التصنيع (Recipe) الخاصة بالمنتج
            $bom = BillOfMaterial::where('product_id', $order->product_id)
                ->with('items')
                ->first();

            if (! $bom) {
                throw new Exception('لا توجد معادلة تصنيع (BOM) لهذا المنتج. لا يمكن إتمام العملية.');
            }

            // 2. خصم المواد الخام (Loop على المكونات)
            foreach ($bom->items as $item) {
                // الكمية الإجمالية المطلوبة = كمية القطعة الواحدة × عدد البوتاجازات المطلوب
                $totalRequiredQty = $item->quantity * $order->quantity;

                // استدعاء خدمة المخازن للصرف (تمرير الـ IDs بالترتيب)
                $this->inventoryService->moveStock(
                    $item->raw_material_id,           // 1. ID المادة الخام
                    $order->warehouse_id,             // 2. ID المخزن
                    -$totalRequiredQty,               // 3. الكمية بالسالب (صرف)
                    TransactionType::PRODUCTION_OUT,  // 4. نوع الحركة
                    $order,                           // 5. المرجع (أمر التصنيع)
                    "صرف تشغيل لأمر إنتاج رقم {$order->order_number}" // 6. البيان
                );
            }

            // 3. إضافة المنتج التام للمخزن
            $this->inventoryService->moveStock(
                $order->product_id,               // 1. ID المنتج التام
                $order->warehouse_id,             // 2. ID المخزن
                $order->quantity,                 // 3. الكمية بالموجب (إضافة)
                TransactionType::PRODUCTION_IN,   // 4. نوع الحركة
                $order,                           // 5. المرجع (أمر التصنيع)
                "إتمام إنتاج لأمر رقم {$order->order_number}" // 6. البيان
            );

            // 4. تحديث حالة الأمر
            $order->update(['status' => 'completed']);

            return true;
        });
    }
}
