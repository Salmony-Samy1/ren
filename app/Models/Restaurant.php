<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Restaurant extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $guarded = [];

    protected $casts = [
        'images' => 'array',
        'working_hours' => 'array',
        'available_tables_map' => 'array',
        'grace_period_minutes' => 'integer',
    ];

    /**
     * Get the service that owns the restaurant.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function tables()
    {
        return $this->hasMany(RestaurantTable::class);
    }

    // Backward compatibility for older clients if needed
    public function tableTypes()
    {
        return $this->hasMany(RestaurantTableType::class);
    }

    public function menuItems()
    {
        return $this->hasMany(RestaurantMenuItem::class);
    }

    /**
     * Register media collections.
     *
     * This method is required by the HasMedia interface.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('restaurant_images')
             ->singleFile();

        $this->addMediaCollection('restaurant_videos')
             ->singleFile();
    }

    /**
     * Register media conversions for the model.
     *
     * @param Media|null $media
     * @return void
     */
    public function registerMediaConversions(Media $media = null): void
    {
        // $this->addMediaConversion('thumb')->width(100)->height(100);
    }

    // ========================================
    // DYNAMIC ACCESSORS FOR SINGLE SOURCE OF TRUTH
    // ========================================

    /**
     * Get total tables count dynamically from restaurant_tables
     * This replaces the removed 'total_tables' field
     * 
     * @return int
     */
    public function getTotalTablesAttribute(): int
    {
        return $this->tables()->sum('quantity');
    }

    /**
     * Get total capacity (people) dynamically from restaurant_tables
     * This replaces the removed 'standard_capacity_per_table' calculation
     * 
     * @return int
     */
    public function getTotalCapacityAttribute(): int
    {
        return $this->tables()->get()->sum(function($table) {
            return $table->quantity * $table->capacity_people;
        });
    }

    /**
     * Get default price per person for Normal tables
     * This replaces the removed 'standard_price_per_person' field
     * 
     * @return float
     */
    public function getDefaultPricePerPersonAttribute(): float
    {
        $normalTable = $this->tables()->where('type', 'Normal')->first();
        return $normalTable ? (float)$normalTable->price_per_person : 0.0;
    }

    /**
     * Get VIP price per person from VIP tables
     * This replaces the removed 'vip_price_per_person' field
     * 
     * @return float
     */
    public function getVipPricePerPersonAttribute(): float
    {
        $vipTable = $this->tables()->where('type', 'VIP')->first();
        return $vipTable ? (float)$vipTable->price_per_person : 0.0;
    }

    /**
     * Get standard table count from Normal tables
     * This replaces the removed 'standard_table_count' field
     * 
     * @return int
     */
    public function getStandardTableCountAttribute(): int
    {
        return $this->tables()->where('type', 'Normal')->sum('quantity');
    }

    // ========================================
    // BUSINESS LOGIC METHODS
    // ========================================

    /**
     * Check if restaurant uses per-person pricing (Normal tables)
     * 
     * @return bool
     */
    public function usesPerPersonPricing(): bool
    {
        return $this->tables()->where('type', 'Normal')->exists();
    }

    /**
     * Check if restaurant uses per-table pricing (VIP tables)
     * 
     * @return bool
     */
    public function usesPerTablePricing(): bool
    {
        return $this->tables()->where('type', 'VIP')->exists();
    }

    /**
     * Get pricing strategy for this restaurant
     * 
     * @return string 'per_person'|'per_table'|'mixed'
     */
    public function getPricingStrategy(): string
    {
        $hasNormal = $this->usesPerPersonPricing();
        $hasVip = $this->usesPerTablePricing();

        if ($hasNormal && $hasVip) {
            return 'mixed';
        } elseif ($hasNormal) {
            return 'per_person';
        } elseif ($hasVip) {
            return 'per_table';
        }

        return 'per_person'; // Default fallback
    }

    /**
     * Get the appropriate table for booking based on type
     * 
     * @param string $type 'Normal'|'VIP'
     * @return RestaurantTable|null
     */
    public function getTableByType(string $type): ?RestaurantTable
    {
        return $this->tables()->where('type', $type)->first();
    }
}
