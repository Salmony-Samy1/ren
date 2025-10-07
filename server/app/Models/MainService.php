<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MainService extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'name_en',
        'description_en',
        'status',
        'default_country_id',
    ];
    
    /**
     * Get the categories for the main service.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function categories()
    {
        return $this->hasMany(Category::class)->orderBy('id', 'asc');
    }

    /**
     * Get the required documents for the main service.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requiredDocuments(): HasMany
    {
        return $this->hasMany(MainServiceRequiredDocument::class);
    }

    /**
     * Get the default country for the main service.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function defaultCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'default_country_id');
    }

    /**
     * Get required documents for a specific country.
     *
     * @param int $countryId
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requiredDocumentsForCountry(int $countryId): HasMany
    {
        return $this->hasMany(MainServiceRequiredDocument::class)
            ->where('country_id', $countryId);
    }

    /**
     * Get required documents for a specific country (query scope).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $countryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRequiredDocumentsForCountry($query, int $countryId)
    {
        return $query->with(['requiredDocuments' => function ($query) use ($countryId) {
            $query->where('country_id', $countryId);
        }]);
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('service_image')
            ->singleFile();

        $this
            ->addMediaCollection('service_video')
            ->singleFile();
    }

    
}
