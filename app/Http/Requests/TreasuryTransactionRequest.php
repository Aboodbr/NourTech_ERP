<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TreasuryTransactionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'treasury_id' => 'required|exists:treasuries,id',
            'type' => 'required|in:income,expense', // قبض أو صرف
            'amount' => 'required|numeric|min:1',
            'transaction_date' => 'required|date',
            // نطلب العميل في حالة القبض، والمورد في حالة الصرف
            'customer_id' => 'nullable|exists:customers,id|required_if:type,income',
            'supplier_id' => 'nullable|exists:suppliers,id|required_if:type,expense',
            'description' => 'required|string|max:500', // البيان
        ];
    }

    public function messages()
    {
        return [
            'amount.min' => 'المبلغ يجب أن يكون أكبر من الصفر.',
            'customer_id.required_if' => 'يجب اختيار العميل في حالة سند القبض.',
            'supplier_id.required_if' => 'يجب اختيار المورد في حالة سند الصرف.',
            'description.required' => 'يرجى كتابة البيان (سبب الحركة).',
        ];
    }
}
