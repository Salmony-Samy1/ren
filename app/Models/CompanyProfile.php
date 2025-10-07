<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CompanyProfile extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $guarded = ['id'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    public function mainService(): BelongsTo
    {
        return $this->belongsTo(MainService::class);
    }
    public function legalDocuments(): HasMany
    {
        return $this->hasMany(CompanyLegalDocument::class);
    }
    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'user_id', 'user_id');
    }
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'service_id', 'id')->whereHas('service', function($query) {
            $query->where('user_id', $this->user_id);
        });
    }
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'service_id', 'id')->whereHas('service', function($query) {
            $query->where('user_id', $this->user_id);
        });
    }
    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class, 'service_id', 'id')->whereHas('service', function($query) {
            $query->where('user_id', $this->user_id);
        });
    }
    public function cateringItems(): HasMany
    {
        return $this->hasMany(CateringItem::class, 'service_id', 'id')->whereHas('service', function($query) {
            $query->where('user_id', $this->user_id);
        });
    }
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'user_id', 'user_id');
    }
    public function hobbies(): BelongsToMany
    {
        return $this->belongsToMany(Hobby::class, 'company_profile_hobby');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id', 'user_id');
    }
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_id', 'user_id');
    }
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'user_id', 'user_id');
    }
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class, 'user_id', 'user_id');
    }
    public function wishedServices()
    {
        return $this->morphedByMany(Service::class, 'wishable', 'wishes');
    }
    public function wishedActivities()
    {
        return $this->morphedByMany(Activity::class, 'wishable', 'wishes');
    }
    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'user1_id', 'user_id')->orWhere('user2_id', $this->user_id);
    }
    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id', 'user_id');
    }
    public function follows(): HasMany
    {
        return $this->hasMany(Follow::class, 'following_id', 'user_id');
    }
    public function followers(): HasMany
    {
        return $this->hasMany(Follow::class, 'user_id', 'user_id');
    }
    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'user_id', 'user_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('company_logo')->singleFile();
    }

}
