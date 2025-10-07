<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppRating extends Model
{
    protected $fillable = [
        'user_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];
}
