<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class CateringItem extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $guarded = [];
    
    protected $fillable = [
        'service_id', 'meal_name', 'price', 'servings_count', 
        'availability_schedule', 'delivery_included', 'offer_duration', 
        'available_stock', 'description', 'packages', 'category_id'
    ];


    protected $casts = [
        'packages' => 'array',
        'availability_schedule' => 'array',

    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('catering_item_photos');

        $this->addMediaCollection('catering_item_videos');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function catering()
    {
        return $this->belongsTo(Catering::class);
    }

    public function category()
    {
        return $this->belongsTo(CateringItemCategory::class, 'category_id');
    }
}