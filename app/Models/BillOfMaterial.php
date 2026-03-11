<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillOfMaterial extends Model
{
    // تحديد اسم الجدول
    protected $table = 'bill_of_materials';

    protected $fillable = [
        'product_id',
        'name',
        'is_active',
    ];

    /**
     * العلاقة مع المنتج التام (Product)
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * العلاقة مع تفاصيل المعادلة (المواد الخام)
     */
    public function items()
    {
        return $this->hasMany(BillOfMaterialItem::class, 'bill_of_material_id');
    }
}
