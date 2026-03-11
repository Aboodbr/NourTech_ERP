<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Invoice;
use Exception;
use Illuminate\Support\Facades\DB;

class SalesService
{
    protected $inventoryService;

    /**
     * حقن خدمة المخازن لكي نتمكن من الخصم والإضافة عند ترحيل الفواتير
     */
    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * 1. إنشاء فاتورة مبيعات جديدة (مسودة)
     */
    public function createInvoice(array $data, array $items)
    {
        return DB::transaction(function () use ($data, $items) {
            $totalAmount = 0;
            $invoiceItems = [];

            foreach ($items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $lineTotal;

                $invoiceItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $lineTotal,
                ];
            }

            if (empty($data['invoice_number'])) {
                $data['invoice_number'] = 'INV-'.time();
            }

            $data['total_amount'] = $totalAmount;
            $data['status'] = 'draft';

            $invoice = Invoice::create($data);
            $invoice->items()->createMany($invoiceItems);

            return $invoice;
        });
    }

    /**
     * 2. تحديث فاتورة مبيعات (مسودة)
     */
    public function updateInvoice(Invoice $invoice, array $data, array $items)
    {
        if ($invoice->status === 'approved') {
            throw new Exception('لا يمكن تعديل فاتورة تم ترحيلها مسبقاً.');
        }

        return DB::transaction(function () use ($invoice, $data, $items) {
            $totalAmount = 0;
            $newItems = [];

            foreach ($items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $lineTotal;

                $newItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $lineTotal,
                ];
            }

            $data['total_amount'] = $totalAmount;
            $invoice->update($data);

            $invoice->items()->delete();
            $invoice->items()->createMany($newItems);

            return $invoice;
        });
    }

    /**
     * 3. ترحيل الفاتورة (خصم الكميات من المخزن واعتماد الفاتورة نهائياً)
     */
    public function approveInvoice(Invoice $invoice)
    {
        if ($invoice->status === 'approved') {
            throw new Exception('هذه الفاتورة مرحلة بالفعل!');
        }

        if ($invoice->items->isEmpty()) {
            throw new Exception('لا يمكن ترحيل فاتورة فارغة بدون أصناف.');
        }

        return DB::transaction(function () use ($invoice) {
            foreach ($invoice->items as $item) {

                $qtyToDeduct = -abs($item->quantity);

                // 🔴 تمرير المُعاملات بالترتيب الصحيح لدالة moveStock
                $this->inventoryService->moveStock(
                    $item->product_id,                           // 1. int $productId
                    $invoice->warehouse_id,                      // 2. int $warehouseId
                    $qtyToDeduct,                                // 3. float $quantity
                    TransactionType::SALE,                       // 4. TransactionType $type
                    $invoice,                                    // 5. ?Model $reference (الفاتورة كمرجع)
                    'فاتورة مبيعات رقم: '.$invoice->invoice_number // 6. ?string $notes
                );
            }

            $invoice->update(['status' => 'approved']);

            return $invoice;
        });
    }
}
