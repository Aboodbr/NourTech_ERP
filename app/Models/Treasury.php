<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Treasury extends Model
{
    protected $guarded = [];

    // المعاملات التي تمت على هذه الخزنة
    public function transactions()
    {
        return $this->hasMany(FinancialTransaction::class);
    }
}
