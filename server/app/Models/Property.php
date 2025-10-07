<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Property extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'service_id',
        'property_name',
        'type',
        'category',
        'images',
        'unit_code',
        'area_sqm',
        'down_payment_percentage',
        'is_refundable_insurance',
        'cancellation_policy',
        'description',
        'allowed_category',
        'room_details',
        'facilities',
        'access_instructions',
        'checkin_time',
        'checkout_time',
        'city_id',
        'region_id',
        'neigbourhood_id',
        'direction',
        'nightly_price',
        'weekly_price',
        'monthly_price',
        'children_allowed',
        'max_children',
        'children_age_min',
        'children_age_max',
    ];

    protected $casts = [
        'images' => 'array',
        'room_details' => 'array',
        'facilities' => 'array',
        'city_id' => 'integer',
        'region_id' => 'integer',
        'neigbourhood_id' => 'integer',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    // Structured relations
    public function bedrooms()
    {
        return $this->hasMany(\App\Models\PropertyBedroom::class);
    }

    public function livingRooms()
    {
        return $this->hasMany(\App\Models\PropertyLivingRoom::class);
    }

    public function pools()
    {
        return $this->hasMany(\App\Models\PropertyPool::class);
    }

    public function kitchens()
    {
        return $this->hasMany(\App\Models\PropertyKitchen::class);
    }

    public function bathrooms()
    {
        return $this->hasMany(\App\Models\PropertyBathroom::class);
    }

    public function facilities()
    {
        return $this->belongsToMany(\App\Models\Facility::class, 'facility_property');
    }

    public function legalDocuments()
    {
        return $this->hasMany(\App\Models\PropertyLegalDocument::class);
    }

    public function pricingRules()
    {
        return $this->hasMany(\App\Models\PropertyPricingRule::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('property_images');
        $this->addMediaCollection('property_videos');
        // Section-specific collections for validation enforcement
        $this->addMediaCollection('bedroom_photos');
        $this->addMediaCollection('kitchen_photos');
        $this->addMediaCollection('pool_photos');
        $this->addMediaCollection('bathroom_photos');
        $this->addMediaCollection('living_room_photos');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        // $this->addMediaConversion('thumb')->width(100)->height(100);
    }
}
