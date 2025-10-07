<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Wish extends Model
{
    public function wishable(): MorphTo
    {
        return $this->morphTo('wishable', 'wishable_type', 'wishable_id');
    }
}
