<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title', 'image_url', 'link_url', 'placement', 'active', 'sort_order', 'starts_at', 'ends_at'
    ];
}

