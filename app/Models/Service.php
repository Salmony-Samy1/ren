<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use willvincent\Rateable\Rateable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Country;
use App\Models\Currency;


class Service extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes, Rateable;
    protected $fillable = [
        'name',
        'description',
        'category_id',
        'user_id',
        'address',
        'latitude',
        'longitude',
        'place_id',
        'city_id',
        'district',
        'gender_type',
        'country_code',
        'price_currency',
        'price_amount',
        'price_currency_id',
        'country_id',
        'available_from',
        'available_to',
        'operating_hours',
        'booking_hours',
        'rating_avg',
        'is_approved',
        'approved_at',
        'approval_notes',
    ];
    protected $table = 'services';

    protected $appends = ['is_booking'];
    
    protected $casts = [
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'price_amount' => 'decimal:2',
        'rating_avg' => 'decimal:2',
        'city_id' => 'integer',
        'category_id' => 'integer',
        'user_id' => 'integer',
        'price_currency_id' => 'integer',
        'country_id' => 'integer',

        // تحويل الحقول العشرية إلى float أو double
        'latitude' => 'double',
        'longitude' => 'double',
        'rating_avg' => 'double',
        'price_amount' => 'double',

        'is_approved' => 'boolean',
        'is_booking' => 'boolean',

        'approved_at' => 'datetime',
        'available_from' => 'date',
        'available_to' => 'date',

    ];

    public function event()
    {
        return $this->hasOne(Event::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wishedBy(): MorphMany
    {
        return $this->morphMany(User::class, 'wishable', 'wishable_type', 'wishable_id');
    }

    public function catering()
    {
        return $this->hasOne(Catering::class);
    }

    public function cateringItem()
    {
        return $this->hasOne(CateringItem::class);
    }

    public function restaurant()
    {
        return $this->hasOne(Restaurant::class);
    }

    public function property()
    {
        return $this->hasOne(Property::class);
    }
    
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
        $this->addMediaCollection('videos');
    }

    public function getIsBookingAttribute()
    {
        return $this->bookings()->count() > 0;
    }


    public function usersWhoFavorited(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'price_currency_id');
    }

}
