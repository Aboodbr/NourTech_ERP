<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Invoice;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Class SalesService
 * Encapsulates the core business logic related to Sales Operations.
 * Interacts with InventoryService to execute necessary stock movements.
 */
class SalesService
{
    protected $inventoryService;

    /**
     * Inject InventoryService to handle stock deductions upon invoice approval.
     *
     * @param InventoryService $inventoryService
     */
    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * 1. Create a new Sales Invoice (Draft mode).
     * Calculates totals and inserts line items in a single transaction.
     *
     * @param array $data Invoice header data (customer_id, warehouse_id, etc.)
     * @param array $items Array of invoice line items (product_id, quantity, unit_price)
     * @return \App\Models\Invoice
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
     * 2. Update an existing draft Sales Invoice.
     * Recalculates totals and rebuilds line items.
     *
     * @param Invoice $invoice The invoice being updated.
     * @param array $data Invoice header data.
     * @param array $items Array of new invoice line items.
     * @return \App\Models\Invoice
     * @throws Exception If the invoice is already approved.
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
     * 3. Approve Invoice (Deduct quantities from warehouse and finalize the invoice).
     * Uses InventoryService to ensure data consistency and accuracy in stock tracking.
     *
     * @param Invoice $invoice The invoice to approve.
     * @return \App\Models\Invoice
     * @throws Exception If the invoice is already approved or has no items.
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
