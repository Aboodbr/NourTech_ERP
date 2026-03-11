<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use Exception;
use Illuminate\Support\Facades\DB;

class PurchasesService
{
    public function __construct(protected InventoryService $inventoryService) {}

    /**
     * إنشاء فاتورة شراء (مسودة)
     */
    public function createInvoice(array $data, array $items)
    {
        return DB::transaction(function () use ($data, $items) {
            // 1. الرأس
            $invoice = PurchaseInvoice::create([
                'invoice_number' => 'PUR-'.date('Ymd').'-'.rand(100, 999),
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'invoice_date' => $data['invoice_date'],
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
            ]);

            $totalAmount = 0;

            // 2. الأصناف
            foreach ($items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $lineTotal;

                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $lineTotal,
                ]);
            }

            $invoice->update(['total_amount' => $totalAmount]);

            return $invoice;
        });
    }

    /**
     * تحديث الفاتورة (للتعديل)
     */
    public function updateInvoice(PurchaseInvoice $invoice, array $data, array $items)
    {
        if ($invoice->status === 'approved') {
            throw new Exception('لا يمكن تعديل فاتورة شراء مرحلة.');
        }

        return DB::transaction(function () use ($invoice, $data, $items) {
            // تحديث الرأس
            $invoice->update([
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'invoice_date' => $data['invoice_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            // حذف القديم وإضافة الجديد
            $invoice->items()->delete();

            $totalAmount = 0;
            foreach ($items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $lineTotal;

                $invoice->items()->create([
                    'purchase_invoice_id' => $invoice->id, // تصحيح الاسم هنا
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $lineTotal,
                ]);
            }
            $invoice->update(['total_amount' => $totalAmount]);
        });
    }

    /**
     * اعتماد الفاتورة (إضافة للمخزن)
     */
    public function approveInvoice(PurchaseInvoice $invoice)
    {
        if ($invoice->status === 'approved') {
            throw new Exception('هذه الفاتورة مرحلة مسبقاً.');
        }

        return DB::transaction(function () use ($invoice) {
            foreach ($invoice->items as $item) {

                $this->inventoryService->moveStock(
                    $item->product_id,
                    $invoice->warehouse_id,
                    abs($item->quantity),
                    TransactionType::PURCHASE,
                    $invoice,
                    "فاتورة شراء رقم {$invoice->invoice_number}"
                );
            }

            $invoice->update(['status' => 'approved']);

            return true;
        });
    }
}
