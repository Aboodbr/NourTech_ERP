<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Enums\TransactionType;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * تنفيذ حركة مخزنية
     */
    public function moveStock(
        int $productId,
        int $warehouseId,
        float $quantity,
        TransactionType $type,
        ?Model $reference = null,
        ?string $notes = null
    ): ?InventoryTransaction {

        // تجاهل الأصناف الخدمية
        $product = Product::find($productId);
        if ($product && $product->type === ProductType::SERVICE) {
            return null;
        }

        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $type, $reference, $notes) {

            // قفل السجل لمنع التضارب
            $stock = Stock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            // إنشاء سجل جديد إذا لم يوجد
            if (! $stock) {
                $stock = Stock::create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 0,
                ]);
            }

            // التحقق من كفاية الرصيد للصرف
            if ($quantity < 0) {
                if (($stock->quantity + $quantity) < 0) {
                    $required = abs($quantity);
                    $productName = Product::where('id', $productId)->value('name') ?? 'غير معروف';
                    $warehouseName = Warehouse::where('id', $warehouseId)->value('name') ?? 'غير معروف';

                    throw new Exception(
                        "عفواً، الرصيد غير كافٍ لإتمام العملية! \n".
                        "الصنف: {$productName} \n".
                        "المخزن: {$warehouseName} \n".
                        "الرصيد المتاح: {$stock->quantity} \n".
                        "الكمية المطلوبة: {$required}"
                    );
                }
            }

            // تحديث الرصيد
            $stock->increment('quantity', $quantity);

            // تسجيل حركة الأرشيف
            return InventoryTransaction::create([
                'stock_id' => $stock->id,
                'type' => $type,
                'quantity' => $quantity,
                'user_id' => auth()->id(),
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * التحويل بين المخازن
     */
    public function transfer(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $quantity,
        ?string $notes = null
    ) {
        return DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $quantity, $notes) {

            // جلب أسماء المخازن
            $fromWarehouseName = Warehouse::where('id', $fromWarehouseId)->value('name');
            $toWarehouseName = Warehouse::where('id', $toWarehouseId)->value('name');

            // الخصم من المصدر
            $this->moveStock(
                $productId,
                $fromWarehouseId,
                -$quantity,
                TransactionType::TRANSFER,
                null,
                "تحويل صادر إلى: {$toWarehouseName} - $notes"
            );

            // الإضافة للمستلم
            $this->moveStock(
                $productId,
                $toWarehouseId,
                $quantity,
                TransactionType::TRANSFER,
                null,
                "تحويل وارد من: {$fromWarehouseName} - $notes"
            );
        });
    }
}
