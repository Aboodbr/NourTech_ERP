<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\ReturnTransaction;
use App\Models\Setting;
use App\Models\FinancialTransaction;
use App\Models\Customer;
use App\Models\Supplier;
use Exception;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function createReturn(array $data, array $items)
    {
        return DB::transaction(function () use ($data, $items) {
            $totalAmount = 0;
            $returnItems = [];

            foreach ($items as $item) {
                // Ensure quantity is positive
                $qty = abs($item['quantity']);
                $lineTotal = $qty * $item['unit_price'];
                $totalAmount += $lineTotal;

                $returnItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $item['unit_price'],
                    'total_price' => $lineTotal,
                ];
            }

            $data['amount'] = $totalAmount;
            $data['status'] = 'draft';

            if (empty($data['return_date'])) {
                $data['return_date'] = date('Y-m-d');
            }

            $returnTx = ReturnTransaction::create($data);
            $returnTx->items()->createMany($returnItems);

            return $returnTx;
        });
    }

    public function approveReturn(ReturnTransaction $returnTx)
    {
        if ($returnTx->status === 'approved') {
            throw new Exception('تم ترحيل هذا المرتجع مسبقاً!');
        }

        if ($returnTx->items->isEmpty()) {
            throw new Exception('لا يمكن ترحيل مرتجع بدون أصناف.');
        }

        return DB::transaction(function () use ($returnTx) {
            $transactionType = $returnTx->type === 'sales_return'
                ? TransactionType::SALES_RETURN
                : TransactionType::PURCHASE_RETURN;

            foreach ($returnTx->items as $item) {
                // Sales Return adds to stock (positive qty)
                // Purchase Return deducts from stock (negative qty)
                $qtyToMove = $returnTx->type === 'sales_return'
                    ? abs($item->quantity)
                    : -abs($item->quantity);

                $this->inventoryService->moveStock(
                    $item->product_id,
                    $returnTx->warehouse_id,
                    $qtyToMove,
                    $transactionType,
                    $returnTx,
                    'مرتجع رقم: ' . $returnTx->id
                );
            }

            // Handle Financial aspect if treasury is selected
            if ($returnTx->treasury_id) {
                $finType = $returnTx->type === 'sales_return' ? 'expense' : 'income'; // We pay customer back (expense) or supplier pays us (income)

                FinancialTransaction::create([
                    'treasury_id' => $returnTx->treasury_id,
                    'amount' => $returnTx->amount,
                    'type' => $finType,
                    'transaction_date' => date('Y-m-d'),
                    'model_type' => $returnTx->model_type,
                    'model_id' => $returnTx->model_id,
                    'description' => ($returnTx->type === 'sales_return' ? 'رد قيمة مرتجع مبيعات' : 'استرداد قيمة مرتجع مشتريات') . ' #' . $returnTx->id,
                    'user_id' => auth()->id() ?? 1,
                ]);

                // Update Treasury Balance
                $treasury = $returnTx->treasury;
                if ($finType === 'income') {
                    $treasury->increment('balance', $returnTx->amount);
                } else {
                    $treasury->decrement('balance', $returnTx->amount);
                }
            } else {
                // If no treasury is selected, affect Customer/Supplier balance
                if ($returnTx->model_type === Customer::class) {
                    $customer = Customer::find($returnTx->model_id);
                    if ($customer) {
                        // Customer owes us less (credit)
                        $customer->decrement('balance', $returnTx->amount);
                    }
                } elseif ($returnTx->model_type === Supplier::class) {
                    $supplier = Supplier::find($returnTx->model_id);
                    if ($supplier) {
                        // We owe supplier less (debit)
                        $supplier->decrement('balance', $returnTx->amount);
                    }
                }
            }

            $returnTx->update(['status' => 'approved']);

            return $returnTx;
        });
    }
}