<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = ['code','name','rate','fee_percent'];
    
    protected $casts = [
        'rate' => 'decimal:6',
        'fee_percent' => 'decimal:2',
    ];
}

