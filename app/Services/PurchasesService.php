<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB; // تمت إضافة الـ Cache

/**
 * Class PurchasesService
 * Encapsulates the core business logic related to Purchase Operations.
 * Interacts with InventoryService to execute stock movements when receiving goods.
 */
class PurchasesService
{
    /**
     * Inject InventoryService to handle stock additions upon invoice approval.
     */
    public function __construct(protected InventoryService $inventoryService) {}

    /**
     * Create a new Purchase Invoice (Draft mode).
     * Calculates totals and inserts line items in a single transaction.
     *
     * @param  array  $data  Invoice header data (supplier_id, warehouse_id, etc.)
     * @param  array  $items  Array of invoice line items (product_id, quantity, unit_price)
     * @return \App\Models\PurchaseInvoice
     */
    public function createInvoice(array $data, array $items)
    {
        // 1. استخدام قفل الكاش لمنع التداخل (Race Condition) عند توليد رقم الفاتورة
        $lock = Cache::lock('generate_purchase_invoice_number', 5);

        try {
            // ننتظر حتى يتاح القفل (بحد أقصى 3 ثوانٍ) لمنع فشل الطلبات المتزامنة
            $lock->block(3);

            return DB::transaction(function () use ($data, $items) {
                // توليد الرقم التسلسلي للفاتورة
                // نبحث عن أي فاتورة تبدأ بـ PUR- ونجلب الأكبر بناءً على الرقم الذي بعد الشرطة
                $latestRecord = PurchaseInvoice::where('invoice_number', 'LIKE', 'PUR-%')
                    ->orderByRaw('CAST(SUBSTRING(invoice_number, 5) AS UNSIGNED) DESC')
                    ->first();

                if ($latestRecord) {
                    // نستخرج الرقم الموجود بعد 'PUR-' (أي من الخانة الرابعة)
                    $lastNumber = (int) substr($latestRecord->invoice_number, 4);
                    $nextSequence = $lastNumber + 1;
                } else {
                    $nextSequence = 1;
                }

                $invoiceNumber = 'PUR-'.str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
                $invoiceNumber = 'PUR-'.str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

                // 2. إنشاء الرأس
                $invoice = PurchaseInvoice::create([
                    'invoice_number' => $invoiceNumber,
                    'supplier_id' => $data['supplier_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'invoice_date' => $data['invoice_date'],
                    'notes' => $data['notes'] ?? null,
                    'status' => 'draft',
                ]);

                $totalAmount = 0;

                // 3. إضافة الأصناف
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

        } finally {
            // تحرير القفل دائماً حتى لو حدث خطأ، ليتمكن الآخرون من إنشاء فواتير
            $lock?->release();
        }
    }

    /**
     * Update an existing draft Purchase Invoice.
     * Recalculates totals and rebuilds line items.
     *
     * @param  PurchaseInvoice  $invoice  The invoice being updated.
     * @param  array  $data  Invoice header data.
     * @param  array  $items  Array of new invoice line items.
     * @return \App\Models\PurchaseInvoice
     *
     * @throws Exception If the invoice is already approved.
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
                    'purchase_invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $lineTotal,
                ]);
            }

            $invoice->update(['total_amount' => $totalAmount]);

            // إرجاع الفاتورة مع بيانات الأصناف المحدثة
            return $invoice->load('items');
        });
    }

    /**
     * Approve Invoice (Add quantities to warehouse and finalize the invoice).
     * Uses InventoryService to ensure data consistency and accuracy in stock tracking.
     *
     * @param  PurchaseInvoice  $invoice  The invoice to approve.
     * @return bool
     *
     * @throws Exception If the invoice is already approved or has no items.
     */
    public function approveInvoice(PurchaseInvoice $invoice)
    {
        if ($invoice->status === 'approved') {
            throw new Exception('هذه الفاتورة مرحلة مسبقاً.');
        }

        // التحقق من وجود أصناف قبل محاولة الترحيل
        if ($invoice->items->isEmpty()) {
            throw new Exception('لا يمكن ترحيل فاتورة لا تحتوي على أصناف.');
        }

        return DB::transaction(function () use ($invoice) {
            foreach ($invoice->items as $item) {

                // التحقق من أن الكمية المدخلة منطقية وأكبر من الصفر
                if ($item->quantity <= 0) {
                    throw new Exception("الكمية للصنف رقم {$item->product_id} يجب أن تكون أكبر من صفر.");
                }

                $this->inventoryService->moveStock(
                    $item->product_id,
                    $invoice->warehouse_id,
                    $item->quantity, // تمرير الكمية كما هي لأننا تأكدنا أنها موجبة
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
