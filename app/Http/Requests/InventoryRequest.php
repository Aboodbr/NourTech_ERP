<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مصرحاً له بعمل هذا الطلب
     */
    public function authorize()
    {
        return true; // مسموح للجميع حالياً
    }

    /**
     * قواعد التحقق (Validation Rules)
     */
    public function rules()
    {
        // جلب الـ ID الخاص بالصنف إذا كنا في حالة التعديل
        // 'inventory' هو اسم المتغير في مسار الراوت (Route)
        $inventoryId = $this->route('inventory') ? $this->route('inventory')->id : null;

        return [
            'sku' => [
                'required',
                'max:50',
                Rule::unique('products', 'sku')->ignore($inventoryId),
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:raw_material,finished_good',
            'unit' => 'required|string|max:50',
            'min_stock' => 'required|numeric|min:0',
        ];
    }

    /**
     * رسائل الخطأ المخصصة باللغة العربية
     */
    public function messages()
    {
        return [
            'sku.required' => 'كود المادة (SKU) مطلوب.',
            'sku.unique' => 'هذا الكود مسجل مسبقاً لمادة أخرى.',
            'name.required' => 'اسم المادة مطلوب.',
            'type.required' => 'يرجى تحديد نوع المادة.',
            'unit.required' => 'يرجى تحديد وحدة القياس (قطعة، كيلو، متر...).', // 🔴 رسالة الخطأ
            'min_stock.required' => 'حد التنبيه (النواقص) مطلوب.',
        ];
    }
}
