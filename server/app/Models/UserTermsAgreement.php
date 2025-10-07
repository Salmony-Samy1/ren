<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTermsAgreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'legal_page_id',
        'status',
        'admin_notes',
        'accepted_at',
        'rejected_at',
        'reviewed_by',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function legalPage(): BelongsTo
    {
        return $this->belongsTo(LegalPage::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
