<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    protected $guarded = [];

    // الخزنة
    public function treasury()
    {
        return $this->belongsTo(Treasury::class);
    }

    // المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // الجهة (عميل أو مورد) - علاقة Polymorphic
    public function model()
    {
        return $this->morphTo();
    }
}
