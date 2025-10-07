<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Category extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, Translatable, InteractsWithMedia;
    protected $fillable = [
        'status',
        'icon',
        'main_service_id',
    ];
    
    public $translatedAttributes = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Form::class, 'category_id');
    }

    public function mainService()
    {
        return $this->belongsTo(MainService::class);
    }
}
