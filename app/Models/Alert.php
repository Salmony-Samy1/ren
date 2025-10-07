<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Alert extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',     // User who this alert belongs to
        'type',        // e.g. service.outage, complaint.urgent, kpi.threshold
        'severity',    // info|warning|critical
        'title',
        'description',
        'meta',        // json
        'status',      // open|acknowledged|resolved
        'acknowledged_at',
        'acknowledged_by',
        'raised_by',
        'is_read',     // Whether the user has read this alert
    ];

    protected $casts = [
        'meta' => 'array',
        'acknowledged_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    /**
     * Get the user that owns the alert.
     */
    public function user(): BelongsTo 
    { 
        return $this->belongsTo(User::class); 
    }

    /**
     * Get the user who acknowledged the alert.
     */
    public function acknowledgedBy(): BelongsTo 
    { 
        return $this->belongsTo(User::class, 'acknowledged_by'); 
    }

    /**
     * Get the user who raised the alert.
     */
    public function raisedBy(): BelongsTo 
    { 
        return $this->belongsTo(User::class, 'raised_by'); 
    }

    /**
     * Scope a query to only include unread alerts.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope a query to only include read alerts.
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope a query to only include alerts by severity.
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope a query to only include alerts by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include open alerts.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope a query to only include acknowledged alerts.
     */
    public function scopeAcknowledged($query)
    {
        return $query->where('status', 'acknowledged');
    }

    /**
     * Scope a query to only include resolved alerts.
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Mark the alert as read.
     */
    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }

    /**
     * Mark the alert as unread.
     */
    public function markAsUnread(): bool
    {
        return $this->update(['is_read' => false]);
    }

    /**
     * Acknowledge the alert.
     */
    public function acknowledge(User $user): bool
    {
        return $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id
        ]);
    }

    /**
     * Resolve the alert.
     */
    public function resolve(): bool
    {
        return $this->update(['status' => 'resolved']);
    }
}

