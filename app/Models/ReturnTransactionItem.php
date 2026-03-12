<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnTransactionItem extends Model
{
    protected $fillable = [
        'return_transaction_id',
        'product_id',
        'quantity',
        'unit_price',
        'total',
    ];

    public function transaction()
    {
        return $this->belongsTo(ReturnTransaction::class, 'return_transaction_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
