<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class City extends Model
{
    use HasFactory, Translatable, SoftDeletes;
    public $translatedAttributes = ['name'];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }


    protected $fillable = ['is_active'];

    protected function casts()
    {
        return [
            'status' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
