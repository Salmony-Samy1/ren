<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryTranslation extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
