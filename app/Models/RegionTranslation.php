<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegionTranslation extends Model
{
    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
