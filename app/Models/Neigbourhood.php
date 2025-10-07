<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Neigbourhood extends Model
{
    use SoftDeletes, Translatable;
    public $translatedAttributes = ['name'];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    protected function casts()
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
