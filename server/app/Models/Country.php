<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Country extends Model
{
    use HasFactory, SoftDeletes, Translatable;
    
    public $translatedAttributes = ['name'];
    
    protected $fillable = [
        'name_ar',
        'name_en',
        'code',
        'iso_code',
        'currency_code',
        'currency_name_ar',
        'currency_name_en',
        'currency_symbol',
        'exchange_rate',
        'flag_emoji',
        'timezone',
        'is_active',
        'sort_order'
    ];
    
    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];
    
    /**
     * Get users from this country
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'country_id');
    }
    
    /**
     * Get currency information
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }
    
    /**
     * Scope for active countries
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope for ordered countries
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_ar');
    }
    
    /**
     * Get country by code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
    
    /**
     * Get country by ISO code
     */
    public static function findByIsoCode(string $isoCode): ?self
    {
        return static::where('iso_code', $isoCode)->first();
    }
    
    /**
     * Get formatted phone number with country code
     */
    public function formatPhoneNumber(string $phone): string
    {
        // Remove any existing country code
        $phone = preg_replace('/^\+?\d{1,4}/', '', $phone);
        
        // Add country code
        return $this->code . $phone;
    }
    
    /**
     * Get currency symbol
     */
    public function getCurrencySymbolAttribute(): string
    {
        return $this->currency_symbol ?? $this->currency_code ?? '';
    }
    
    /**
     * Get display name based on locale
     */
    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Get main service requirements for this country.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mainServiceRequirements(): HasMany
    {
        return $this->hasMany(MainServiceRequiredDocument::class);
    }

    /**
     * Get main services that have this country as default.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function defaultMainServices(): HasMany
    {
        return $this->hasMany(MainService::class, 'default_country_id');
    }

    /**
     * Get required documents for a specific main service.
     *
     * @param int $mainServiceId
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requiredDocumentsForMainService(int $mainServiceId): HasMany
    {
        return $this->hasMany(MainServiceRequiredDocument::class)
            ->where('main_service_id', $mainServiceId);
    }
}
