<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\FinancialTransaction;
use App\Models\Invoice; // المبيعات
use App\Models\PurchaseInvoice; // المشتريات
use App\Models\ReturnTransaction; // 🔴 إضافة موديل المرتجعات
use App\Models\Supplier;

class AccountStatementService
{
    public function generateStatement($partyType, $partyId, $fromDate, $toDate)
    {
        $transactions = collect();
        $totalDebit = 0;
        $totalCredit = 0;

        if ($partyType === 'customer') {
            // 1. جلب المبيعات (العميل مدين بها)
            $invoices = Invoice::where('customer_id', $partyId)
                ->where('status', 'approved')
                ->whereBetween('invoice_date', [$fromDate, $toDate])
                ->get();

            foreach ($invoices as $inv) {
                $transactions->push(['date' => $inv->invoice_date, 'document' => 'فاتورة مبيعات', 'ref_no' => $inv->invoice_number, 'debit' => $inv->total_amount, 'credit' => 0, 'description' => $inv->notes]);
            }

            // 2. جلب الدفعات/القبض (العميل دائن بها)
            $receipts = FinancialTransaction::where('model_type', Customer::class)
                ->where('model_id', $partyId)
                ->where('type', 'income')
                ->whereBetween('transaction_date', [$fromDate, $toDate])
                ->get();

            foreach ($receipts as $rec) {
                $transactions->push(['date' => $rec->transaction_date, 'document' => 'سند قبض', 'ref_no' => '#'.$rec->id, 'debit' => 0, 'credit' => $rec->amount, 'description' => $rec->description]);
            }

            // 3. 🔴 جلب مرتجعات المبيعات (العميل دائن بها لأنها تخفض مديونيته)
            $salesReturns = ReturnTransaction::where('model_type', Customer::class)
                ->where('model_id', $partyId)
                ->where('type', 'sales_return')
                ->where('status', 'approved')
                ->whereBetween('return_date', [$fromDate, $toDate])
                ->get();

            foreach ($salesReturns as $rt) {
                $transactions->push(['date' => $rt->return_date, 'document' => 'مرتجع مبيعات', 'ref_no' => 'RT-'.$rt->id, 'debit' => 0, 'credit' => $rt->total_amount, 'description' => $rt->notes ?? 'رد بضاعة مباعة']);
            }

        } elseif ($partyType === 'supplier') {
            // 1. جلب المشتريات (المورد دائن بها)
            $purchases = PurchaseInvoice::where('supplier_id', $partyId)
                ->where('status', 'approved')
                ->whereBetween('invoice_date', [$fromDate, $toDate])
                ->get();

            foreach ($purchases as $pur) {
                $transactions->push(['date' => $pur->invoice_date, 'document' => 'فاتورة مشتريات', 'ref_no' => $pur->invoice_number, 'debit' => 0, 'credit' => $pur->total_amount, 'description' => $pur->notes]);
            }

            // 2. جلب المدفوعات/الصرف (المورد مدين بها)
            $payments = FinancialTransaction::where('model_type', Supplier::class)
                ->where('model_id', $partyId)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$fromDate, $toDate])
                ->get();

            foreach ($payments as $pay) {
                $transactions->push(['date' => $pay->transaction_date, 'document' => 'سند صرف', 'ref_no' => '#'.$pay->id, 'debit' => $pay->amount, 'credit' => 0, 'description' => $pay->description]);
            }

            // 3. 🔴 جلب مرتجعات المشتريات (المورد مدين بها لأنها تخفض مديونيتنا له)
            $purchaseReturns = ReturnTransaction::where('model_type', Supplier::class)
                ->where('model_id', $partyId)
                ->where('type', 'purchase_return')
                ->where('status', 'approved')
                ->whereBetween('return_date', [$fromDate, $toDate])
                ->get();

            foreach ($purchaseReturns as $rt) {
                $transactions->push(['date' => $rt->return_date, 'document' => 'مرتجع مشتريات', 'ref_no' => 'RT-'.$rt->id, 'debit' => $rt->total_amount, 'credit' => 0, 'description' => $rt->notes ?? 'رد بضاعة مشتراة']);
            }
        }

        // الترتيب الزمني (الأقدم فالأحدث لكي يكون الرصيد التراكمي صحيحاً)
        $sortedTransactions = $transactions->sortBy('date')->values();
        $runningBalance = 0;
        $statement = [];

        foreach ($sortedTransactions as $trans) {
            $totalDebit += $trans['debit'];
            $totalCredit += $trans['credit'];
            $runningBalance += ($trans['debit'] - $trans['credit']);

            $trans['balance'] = $runningBalance;
            $statement[] = $trans;
        }

        return [
            'party' => $partyType === 'customer' ? Customer::find($partyId) : Supplier::find($partyId),
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'closing_balance' => $runningBalance,
            'transactions' => $statement,
        ];
    }
}
