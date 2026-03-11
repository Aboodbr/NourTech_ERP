<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillOfMaterialItem extends Model
{
    // تحديد اسم الجدول في قاعدة البيانات
    protected $table = 'bill_of_material_items';

    // الحقول المسموح بإدخال البيانات فيها
    protected $fillable = [
        'bill_of_material_id',
        'raw_material_id',
        'quantity',
    ];

    /**
     * العلاقة مع رأس المعادلة (BOM)
     */
    public function billOfMaterial()
    {
        return $this->belongsTo(BillOfMaterial::class, 'bill_of_material_id');
    }

    /**
     * العلاقة مع المادة الخام (Product)
     */
    public function rawMaterial()
    {
        return $this->belongsTo(Product::class, 'raw_material_id');
    }
}
