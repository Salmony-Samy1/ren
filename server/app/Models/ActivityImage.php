<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityImage extends Model
{
    use SoftDeletes;

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
