<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnTransaction extends Model
{
    protected $fillable = [
        'type',
        'return_date',
        'model_type',
        'model_id',
        'warehouse_id',
        'treasury_id',
        'notes',
        'status',
        'total_amount',

    ];

    public function items()
    {
        return $this->hasMany(ReturnTransactionItem::class);
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function treasury()
    {
        return $this->belongsTo(Treasury::class);
    }
}
