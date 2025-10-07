<?php

namespace App\Models;

use App\Enums\CompanyLegalDocType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MainServiceRequiredDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'main_service_id',
        'country_id',
        'document_type',
        'is_required',
        'description',
        'description_en',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'document_type' => CompanyLegalDocType::class,
        'is_required' => 'boolean',
    ];

    /**
     * Get the main service that owns the required document.
     */
    public function mainService(): BelongsTo
    {
        return $this->belongsTo(MainService::class);
    }

    /**
     * Get the country that owns the required document.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Scope to filter by main service.
     */
    public function scopeForMainService($query, int $mainServiceId)
    {
        return $query->where('main_service_id', $mainServiceId);
    }

    /**
     * Scope to filter by country.
     */
    public function scopeForCountry($query, int $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope to filter by document type.
     */
    public function scopeForDocumentType($query, string $documentType)
    {
        return $query->where('document_type', $documentType);
    }

    /**
     * Scope to get only required documents.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope to get only optional documents.
     */
    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }
}