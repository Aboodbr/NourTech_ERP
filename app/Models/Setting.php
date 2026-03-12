<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'company_name',
        'company_logo',
        'currency',
        'timezone',
        'default_warehouse',
        'default_treasury'
    ];
}
