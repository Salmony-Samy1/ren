<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CateringSpecialEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_id',
        'provider_id',
        'customer_id',
        'event_name',
        'event_type',
        'description',
        'client_name',
        'client_phone',
        'client_email',
        'event_datetime',
        'guest_count',
        'estimated_budget',
        'confirmed_budget',
        'status',
        'progress_percentage',
        'venue_name',
        'full_address',
        'event_city',
        'event_lat',
        'event_long',
        'planning_start_date',
        'preparation_days',
        'special_requirements',
        'menu_items',
        'timeline',
        'contact_persons',
        'admin_notes',
        'created_by_admin',
    ];

    protected $casts = [
        'event_datetime' => 'datetime',
        'planning_start_date' => 'datetime',
        'estimated_budget' => 'decimal:2',
        'confirmed_budget' => 'decimal:2',
        'guest_count' => 'integer',
        'progress_percentage' => 'integer',
        'preparation_days' => 'integer',
        'special_requirements' => 'array',
        'menu_items' => 'array',
        'timeline' => 'array',
        'contact_persons' => 'array',
        'event_lat' => 'decimal:8',
        'event_long' => 'decimal:8',
    ];

    /**
     * Get the service that owns the event
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the provider (user) that owns the event
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Get the customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Scope for active events
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['inquiry', 'planning', 'confirmed', 'in_progress']);
    }

    /**
     * Scope for events by type
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope for events by provider
     */
    public function scopeByProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Scope for upcoming events
     */
    public function scopeUpcoming($query)
    {
        return $query->where('event_datetime', '>', now());
    }

    /**
     * Get status label in Arabic
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'inquiry' => 'استفسار',
            'planning' => 'تخطيط',
            'confirmed' => 'مؤكد',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            default => $this->status
        };
    }

    /**
     * Get event type label in Arabic
     */
    public function getEventTypeLabelAttribute(): string
    {
        return match($this->event_type) {
            'wedding' => 'زواج',
            'conference' => 'مؤتمر',
            'gala' => 'حفل',
            'corporate' => 'شركاتي',
            'charity' => 'خيري',
            'private_celebration' => 'احتفال خاص',
            default => $this->event_type
        };
    }

    /**
     * Get the primary contact person
     */
    public function getPrimaryContactAttribute(): ?array
    {
        if (!$this->contact_persons) {
            return null;
        }

        foreach ($this->contact_persons as $contact) {
            if (isset($contact['is_primary']) && $contact['is_primary']) {
                return $contact;
            }
        }

        return $this->contact_persons[0] ?? null;
    }

    /**
     * Check if event is overdue for milestones
     */
    public function hasOverdueMilestones(): bool
    {
        if (!$this->timeline) {
            return false;
        }

        foreach ($this->timeline as $milestone) {
            if (!$milestone['completed'] && strtotime($milestone['due_date']) < time()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get computed event ID for API responses
     */
    public function getEventIdAttribute(): string
    {
        return 'SEV-CTR-' . date('Y') . '-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }
}