<?php

namespace App\Models;

use App\Enums\CompanyLegalDocType;
use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyLegalDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'doc_type' => CompanyLegalDocType::class,
        'status' => ReviewStatus::class,
        'expires_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function companyProfile(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class);
    }

    public function mainService(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            MainService::class,
            MainServiceRequiredDocument::class,
            'id', // Foreign key on main_service_required_documents table
            'id', // Foreign key on main_services table
            'main_service_required_document_id', // Local key on company_legal_documents table
            'main_service_id' // Local key on main_service_required_documents table
        );
    }

    public function mainServiceRequiredDocument(): BelongsTo
    {
        return $this->belongsTo(MainServiceRequiredDocument::class);
    }

    public function country(): BelongsTo
    {
        return $this->hasOneThrough(
            Country::class,
            MainServiceRequiredDocument::class,
            'id', // Foreign key on main_service_required_documents table
            'id', // Foreign key on countries table
            'main_service_required_document_id', // Local key on company_legal_documents table
            'country_id' // Local key on main_service_required_documents table
        );
    }
}

