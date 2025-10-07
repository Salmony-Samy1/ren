<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RestaurantTableType extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'capacity_people' => 'integer',
        'price_per_person' => 'float',
        'count' => 'integer',
        'conditions' => 'array',
        'amenities' => 'array',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}

