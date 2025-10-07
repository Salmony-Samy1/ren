<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use SoftDeletes, Translatable;
    public $translatedAttributes = ['name'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    protected function casts()
    {
        return [
            'status' => 'boolean',
        ];
    }
}
