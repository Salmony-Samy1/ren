<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RestaurantTable extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $guarded = [];

    protected $casts = [
        'capacity_people' => 'integer',
        'price_per_person' => 'float',
        'price_per_table' => 'float',
        'quantity' => 'integer',
        'auto_re_availability_minutes' => 'integer',
        'conditions' => 'array',
        'amenities' => 'array',
        'media' => 'array',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * العلاقة مع الحجوزات المباشرة للطاولة
     */
    public function tableReservations()
    {
        return $this->hasMany(TableReservation::class);
    }

    /**
     * تسجيل collections للصور
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('table_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    /**
     * تسجيل conversions للصور
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->performOnCollections('table_images');
    }
}

