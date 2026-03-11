<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    // الحقول المسموح بإدخالها
    protected $fillable = [
        'name',
        'phone',
        'address',
    ];

    /**
     * علاقة العميل بفواتير المبيعات (العميل له عدة فواتير)
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }
}
