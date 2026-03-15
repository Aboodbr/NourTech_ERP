<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Customer;
use App\Models\FinancialTransaction;
use App\Models\ReturnTransaction;
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
                $qty = abs($item['quantity']);
                $lineTotal = $qty * $item['unit_price'];
                $totalAmount += $lineTotal;

                $returnItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $item['unit_price'],
                    'total' => $lineTotal,
                ];
            }

            // 🔴 تم التعديل لتطابق المايجريشن
            $data['total_amount'] = $totalAmount;
            $data['status'] = 'pending';

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
                $qtyToMove = $returnTx->type === 'sales_return'
                    ? abs($item->quantity)
                    : -abs($item->quantity);

                $this->inventoryService->moveStock(
                    $item->product_id,
                    $returnTx->warehouse_id,
                    $qtyToMove,
                    $transactionType,
                    $returnTx,
                    'مرتجع رقم: '.$returnTx->id
                );
            }

            // 🔴 تأمين محاسبي وجلب الإجمالي الصحيح
            $exactAmount = $returnTx->total_amount ?? $returnTx->items->sum('total');

            if ($returnTx->treasury_id) {
                $finType = $returnTx->type === 'sales_return' ? 'expense' : 'income';

                FinancialTransaction::create([
                    'treasury_id' => $returnTx->treasury_id,
                    'amount' => $exactAmount,
                    'type' => $finType,
                    'transaction_date' => date('Y-m-d'),
                    'model_type' => $returnTx->model_type,
                    'model_id' => $returnTx->model_id,
                    'description' => ($returnTx->type === 'sales_return' ? 'رد قيمة مرتجع مبيعات' : 'استرداد قيمة مرتجع مشتريات').' #'.$returnTx->id,
                    'user_id' => auth()->id() ?? 1,
                ]);

                $treasury = $returnTx->treasury;
                if ($finType === 'income') {
                    $treasury->increment('balance', $exactAmount);
                } else {
                    $treasury->decrement('balance', $exactAmount);
                }
            } else {
                if ($returnTx->model_type === Customer::class) {
                    $customer = Customer::find($returnTx->model_id);
                    if ($customer) {
                        $customer->decrement('balance', $exactAmount);
                    }
                } elseif ($returnTx->model_type === Supplier::class) {
                    $supplier = Supplier::find($returnTx->model_id);
                    if ($supplier) {
                        $supplier->decrement('balance', $exactAmount);
                    }
                }
            }

            $returnTx->update(['status' => 'approved']);

            return $returnTx;
        });
    }
}
