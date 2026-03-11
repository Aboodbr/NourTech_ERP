<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\FinancialTransaction;
use App\Models\Supplier;
use App\Models\Treasury;
use Exception;
use Illuminate\Support\Facades\Auth;

class TreasuryService
{
    public function processTransaction(array $data)
    {
        $treasury = Treasury::findOrFail($data['treasury_id']);

        if ($data['type'] === 'expense' && $treasury->balance < $data['amount']) {
            throw new Exception('عفواً، رصيد الخزينة الحالي لا يكفي لعملية الصرف!');
        }

        $modelType = null;
        $modelId = null;

        if ($data['type'] === 'income') {
            $treasury->increment('balance', $data['amount']);
            if (! empty($data['customer_id'])) {
                $modelType = Customer::class;
                $modelId = $data['customer_id'];
            }
        } else {
            $treasury->decrement('balance', $data['amount']);
            if (! empty($data['supplier_id'])) {
                $modelType = Supplier::class;
                $modelId = $data['supplier_id'];
            }
        }

        return FinancialTransaction::create([
            'treasury_id' => $data['treasury_id'],
            'amount' => $data['amount'],
            'type' => $data['type'],
            'transaction_date' => $data['transaction_date'],
            'model_type' => $modelType,
            'model_id' => $modelId,
            'description' => $data['description'],
            'user_id' => Auth::id() ?? 1,
        ]);
    }

    // 🔴 دالة التعديل (عكس السند القديم ثم تطبيق الجديد)
    public function updateTransaction(FinancialTransaction $transaction, array $data)
    {
        // 1. عكس تأثير السند القديم على الخزنة القديمة
        $oldTreasury = Treasury::findOrFail($transaction->treasury_id);
        if ($transaction->type === 'income') {
            if ($oldTreasury->balance < $transaction->amount) {
                throw new Exception('الرصيد الحالي للخزينة لا يكفي لعكس سند القبض القديم.');
            }
            $oldTreasury->decrement('balance', $transaction->amount);
        } else {
            $oldTreasury->increment('balance', $transaction->amount);
        }

        // 2. تطبيق تأثير السند الجديد على الخزنة الجديدة
        $newTreasury = Treasury::findOrFail($data['treasury_id']);
        if ($data['type'] === 'expense' && $newTreasury->balance < $data['amount']) {
            throw new Exception('عفواً، رصيد الخزينة الجديد لا يكفي لعملية الصرف!');
        }

        $modelType = null;
        $modelId = null;

        if ($data['type'] === 'income') {
            $newTreasury->increment('balance', $data['amount']);
            if (! empty($data['customer_id'])) {
                $modelType = Customer::class;
                $modelId = $data['customer_id'];
            }
        } else {
            $newTreasury->decrement('balance', $data['amount']);
            if (! empty($data['supplier_id'])) {
                $modelType = Supplier::class;
                $modelId = $data['supplier_id'];
            }
        }

        // 3. تحديث البيانات
        $transaction->update([
            'treasury_id' => $data['treasury_id'],
            'amount' => $data['amount'],
            'type' => $data['type'],
            'transaction_date' => $data['transaction_date'],
            'model_type' => $modelType,
            'model_id' => $modelId,
            'description' => $data['description'],
        ]);

        return $transaction;
    }

    // 🔴 دالة الحذف (عكس التأثير المحاسبي قبل الحذف)
    public function deleteTransaction(FinancialTransaction $transaction)
    {
        $treasury = Treasury::findOrFail($transaction->treasury_id);

        if ($transaction->type === 'income') {
            if ($treasury->balance < $transaction->amount) {
                throw new Exception('لا يمكن حذف هذا السند لأن رصيد الخزينة سيصبح بالسالب!');
            }
            $treasury->decrement('balance', $transaction->amount);
        } else {
            $treasury->increment('balance', $transaction->amount);
        }

        $transaction->delete();
    }
}
