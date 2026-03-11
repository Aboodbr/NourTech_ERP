<?php

namespace App\Services;

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
     * تنفيذ حركة مخزنية (صرف أو إضافة)
     *
     * @param  int  $productId  معرف المنتج
     * @param  int  $warehouseId  معرف المخزن
     * @param  float  $quantity  الكمية (موجب للزيادة، سالب للنقص)
     * @param  TransactionType  $type  نوع الحركة (شراء، بيع، تصنيع...)
     * @param  Model|null  $reference  المرجع (فاتورة Invoice، أو أمر شغل ProductionOrder، أو null للحركات اليدوية)
     * @param  string|null  $notes  ملاحظات
     */
    public function moveStock(
        int $productId,
        int $warehouseId,
        float $quantity,
        TransactionType $type,
        ?Model $reference = null,
        ?string $notes = null
    ): InventoryTransaction {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $type, $reference, $notes) {

            // 1. البحث عن سجل الرصيد مع قفله (Lock) لمنع التضارب أثناء الحفظ المزدوج
            $stock = Stock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            // إذا لم يكن المنتج موجوداً في هذا المخزن من قبل، ننشئ له سجلاً برصيد 0
            if (! $stock) {
                $stock = Stock::create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 0,
                ]);
            }

            // 2. التحقق من كفاية الرصيد (فقط في حالة الصرف/السحب)
            if ($quantity < 0) {
                // نحسب الرصيد المتوقع بعد العملية
                if (($stock->quantity + $quantity) < 0) {
                    $required = abs($quantity);

                    // الاستعلام عن الأسماء فقط عند حدوث الخطأ لتوفير موارد قاعدة البيانات
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

            // 3. تحديث الرصيد الفعلي
            $stock->increment('quantity', $quantity);

            // 4. تسجيل الحركة في الأرشيف (History)
            return InventoryTransaction::create([
                'stock_id' => $stock->id,
                'type' => $type,
                'quantity' => $quantity,
                'user_id' => auth()->id(), // المستخدم الحالي
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * دالة التحويل بين المخازن
     */
    public function transfer(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $quantity,
        ?string $notes = null
    ) {
        return DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $quantity, $notes) {

            // للحصول على أسماء المخازن في الملاحظات (يمكن الاستغناء عنها لو أردت أداءً أسرع)
            $fromWarehouseName = Warehouse::where('id', $fromWarehouseId)->value('name');
            $toWarehouseName = Warehouse::where('id', $toWarehouseId)->value('name');

            // خصم من المصدر
            $this->moveStock(
                $productId,
                $fromWarehouseId,
                -$quantity,
                TransactionType::TRANSFER,
                null,
                "تحويل صادر إلى: {$toWarehouseName} - $notes"
            );

            // إضافة للمستلم
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
