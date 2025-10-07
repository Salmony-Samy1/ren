<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyLegalDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'document_type',
        'document_name',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'description',
        'is_verified',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'file_size' => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}