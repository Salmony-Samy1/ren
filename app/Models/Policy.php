<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category',
        'department',
        'content',
        'priority',
        'tags', // stored as JSON
        'effective_date',
        'review_date',
        'version',
        'last_updated',
        'status',
        'author',
    ];

    protected $casts = [
        'tags' => 'array',
        'effective_date' => 'date',
        'review_date' => 'date',
        'last_updated' => 'date',
    ];
}
