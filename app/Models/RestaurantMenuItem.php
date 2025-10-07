<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class RestaurantMenuItem extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'description',
        'price',
        'is_active',
        'restaurant_id',
        'restaurant_menu_category_id',
    ];

    protected $casts = [
        'restaurant_id' => 'integer',
        'restaurant_menu_category_id' => 'integer',
        'price' => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * Define the media collection for the menu item's image.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('menu_item_images')->singleFile();
    }

    /**
     * Get the image URL for the menu item.
     */
    public function getImageUrlAttribute()
    {
        return $this->getFirstMediaUrl('menu_item_images');
    }

    /**
     * The restaurant this menu item belongs to.
     */
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category()
    {
        return $this->belongsTo(RestaurantMenuCategory::class, 'restaurant_menu_category_id');
    }

}