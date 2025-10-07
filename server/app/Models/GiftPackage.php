<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftPackage extends Model
{
    protected $fillable = [
        'name', 'amount', 'image_url', 'active', 'description', 'sort_order'
    ];
}

