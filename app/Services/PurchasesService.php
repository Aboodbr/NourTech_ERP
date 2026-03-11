<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Class PurchasesService
 * Encapsulates the core business logic related to Purchase Operations.
 * Interacts with InventoryService to execute stock movements when receiving goods.
 */
class PurchasesService
{
    /**
     * Inject InventoryService to handle stock additions upon invoice approval.
     *
     * @param InventoryService $inventoryService
     */
    public function __construct(protected InventoryService $inventoryService) {}

    /**
     * Create a new Purchase Invoice (Draft mode).
     * Calculates totals and inserts line items in a single transaction.
     *
     * @param array $data Invoice header data (supplier_id, warehouse_id, etc.)
     * @param array $items Array of invoice line items (product_id, quantity, unit_price)
     * @return \App\Models\PurchaseInvoice
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
     * Update an existing draft Purchase Invoice.
     * Recalculates totals and rebuilds line items.
     *
     * @param PurchaseInvoice $invoice The invoice being updated.
     * @param array $data Invoice header data.
     * @param array $items Array of new invoice line items.
     * @return void
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
     * Approve Invoice (Add quantities to warehouse and finalize the invoice).
     * Uses InventoryService to ensure data consistency and accuracy in stock tracking.
     *
     * @param PurchaseInvoice $invoice The invoice to approve.
     * @return bool
     * @throws Exception If the invoice is already approved or has no items.
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
