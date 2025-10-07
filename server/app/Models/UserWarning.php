<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'warning',
        'type',
        'reason',
        'auto_resolve_days',
        'resolved_at',
        'resolved_by'
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'auto_resolve_days' => 'integer'
    ];

    /**
     * Get the user that owns the warning
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who resolved the warning
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Check if warning is resolved
     */
    public function isResolved(): bool
    {
        return !is_null($this->resolved_at);
    }

    /**
     * Scope for unresolved warnings
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope for resolved warnings
     */
    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }
}




