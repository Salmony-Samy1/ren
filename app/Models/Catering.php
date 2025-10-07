<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Catering extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $guarded = [];

    protected $casts = [
        'images' => 'array',
        'description' => 'array',
        'available_stock' => 'integer',
        'fulfillment_methods' => 'array',
        'min_order_amount' => 'decimal:2',
        'max_order_amount' => 'decimal:2',
        'preparation_time' => 'integer',
        'delivery_radius_km' => 'integer',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function items()
    {
        return $this->hasMany(CateringItem::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('catering_images');
        $this->addMediaCollection('catering_videos');
    }

}

