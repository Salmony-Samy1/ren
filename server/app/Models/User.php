<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Observers\UserObserver;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Traits\HasWallet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
// use App\Traits\Gamifiable;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Favorite;
use App\Models\Country;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements JWTSubject, Wallet, HasMedia
{
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($user) {
            if ($user->country_id && !$user->country_code) {
                $country = Country::find($user->country_id);
                if ($country) {
                    $user->country_code = $country->code;
                }
            }
        });
        
        static::updating(function ($user) {
            if ($user->isDirty('country_id') && !$user->isDirty('country_code')) {
                $country = Country::find($user->country_id);
                if ($country) {
                    $user->country_code = $country->code;
                }
            }
        });
    }

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, AuthenticationLoggable, HasRoles, HasWallet, HasApiTokens, InteractsWithMedia;

    // Ensure Spatie Permission uses the API guard for this model
    protected $guard_name = 'api';


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id', 'uuid'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::observe(UserObserver::class);
    }


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'type' => $this->type,
            'permissions' => $this->permissions()->pluck('name')->toArray(),
        ];
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function companyProfile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class);
    }

    public function getProfileAttribute()
    {
        return $this->type === 'customer'
            ? $this->customerProfile
            : $this->companyProfile;
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
        return $this->hasMany(Conversation::class, 'user1_id')->orWhere('user2_id', $this->id);
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            \App\Models\Review::class,
            \App\Models\Service::class 
        );
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function follows(): HasMany
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    public function followers(): HasMany
    {
        return $this->hasMany(Follow::class, 'user_id');
    }


    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(\App\Models\UserAddress::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(\App\Models\Alert::class);
    }

    public function pointsLedger(): HasMany
    {
        return $this->hasMany(\App\Models\PointsLedger::class);
    }

    public function serviceUsage(): HasMany
    {
        return $this->hasMany(\App\Models\ServiceUsage::class);
    }
    public function serviceUsageLogs(): HasMany
    {
        return $this->hasMany(\App\Models\ServiceUsageLog::class);
    }


    public function activities(): HasMany
    {
        return $this->hasMany(\App\Models\UserActivity::class);
    }

    /**
     * Get authentication logs for this user
     */
    public function authenticationLogs(): MorphMany
    {
        return $this->morphMany(\App\Models\AuthenticationLog::class, 'authenticatable');
    }

    /**
     * Generate unique referral code
     */
    public function generateReferralCode(): string
    {
        $code = strtoupper(substr(md5($this->id . time()), 0, 8));

        while (User::where('referral_code', $code)->exists()) {
            $code = strtoupper(substr(md5($this->id . time() . rand()), 0, 8));
        }

        return $code;
    }

    /**
     * User categories relationship
     */
    public function categories()
    {
        return $this->belongsToMany(\App\Models\Category::class, 'user_categories');
    }

    /**
     * User warnings relationship
     */
    public function warnings()
    {
        return $this->hasMany(\App\Models\UserWarning::class);
    }

    /**
     * User notifications relationship
     */
    public function notifications()
    {
        return $this->hasMany(\App\Models\UserNotification::class);
    }

    /**
     * User country relationship
     */
    public function country()
    {
        return $this->belongsTo(\App\Models\Country::class, 'country_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }


}
