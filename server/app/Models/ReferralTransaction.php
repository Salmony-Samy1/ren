<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralTransaction extends Model
{
    protected $fillable = [
        'referrer_id',
        'referred_user_id', 
        'booking_id',
        'commission_amount',
        'commission_rate',
        'commission_type',
        'status',
        'processed_at',
        'paid_at',
        'notes'
    ];

    protected $casts = [
        'commission_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime'
    ];

    /**
     * Get the referrer user
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get the referred user
     */
    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /**
     * Get the associated booking
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved transactions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for paid transactions
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Mark transaction as approved
     */
    public function approve(): bool
    {
        return $this->update([
            'status' => 'approved',
            'processed_at' => now()
        ]);
    }

    /**
     * Mark transaction as paid
     */
    public function markAsPaid(): bool
    {
        return $this->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);
    }
}
