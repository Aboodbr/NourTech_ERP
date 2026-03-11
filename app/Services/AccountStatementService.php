<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\FinancialTransaction;
use App\Models\Invoice; // المبيعات
use App\Models\PurchaseInvoice; // المشتريات
use App\Models\Supplier; // الخزينة

class AccountStatementService
{
    public function generateStatement($partyType, $partyId, $fromDate, $toDate)
    {
        $transactions = collect();
        $totalDebit = 0;
        $totalCredit = 0;

        if ($partyType === 'customer') {
            // جلب المبيعات (العميل مدين بها)
            $invoices = Invoice::where('customer_id', $partyId)
                ->where('status', 'approved')
                ->whereBetween('invoice_date', [$fromDate, $toDate])
                ->get();

            foreach ($invoices as $inv) {
                $transactions->push(['date' => $inv->invoice_date, 'document' => 'فاتورة مبيعات', 'ref_no' => $inv->invoice_number, 'debit' => $inv->total_amount, 'credit' => 0, 'description' => $inv->notes]);
            }

            // جلب الدفعات/القبض (العميل دائن بها)
            $receipts = FinancialTransaction::where('model_type', Customer::class)
                ->where('model_id', $partyId)
                ->where('type', 'income') // income حسب الداتا بيز الخاصة بك
                ->whereBetween('transaction_date', [$fromDate, $toDate])
                ->get();

            foreach ($receipts as $rec) {
                $transactions->push(['date' => $rec->transaction_date, 'document' => 'سند قبض', 'ref_no' => '#'.$rec->id, 'debit' => 0, 'credit' => $rec->amount, 'description' => $rec->description]);
            }

        } elseif ($partyType === 'supplier') {
            // جلب المشتريات (المورد دائن بها)
            $purchases = PurchaseInvoice::where('supplier_id', $partyId)
                ->where('status', 'approved')
                ->whereBetween('invoice_date', [$fromDate, $toDate])
                ->get();

            foreach ($purchases as $pur) {
                $transactions->push(['date' => $pur->invoice_date, 'document' => 'فاتورة مشتريات', 'ref_no' => $pur->invoice_number, 'debit' => 0, 'credit' => $pur->total_amount, 'description' => $pur->notes]);
            }

            // جلب المدفوعات/الصرف (المورد مدين بها)
            $payments = FinancialTransaction::where('model_type', Supplier::class)
                ->where('model_id', $partyId)
                ->where('type', 'expense') // expense حسب الداتا بيز الخاصة بك
                ->whereBetween('transaction_date', [$fromDate, $toDate])
                ->get();

            foreach ($payments as $pay) {
                $transactions->push(['date' => $pay->transaction_date, 'document' => 'سند صرف', 'ref_no' => '#'.$pay->id, 'debit' => $pay->amount, 'credit' => 0, 'description' => $pay->description]);
            }
        }

        // الترتيب الزمني وحساب الرصيد التراكمي
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
