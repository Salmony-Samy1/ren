<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'order_id',
        'booking_id',
        'invoice_number',
        'total_amount',
        'tax_amount',
        'discount_amount',
        'commission_amount',
        'provider_amount',
        'platform_amount',
        'currency',
        'points_used',
        'points_value',
        'invoice_type',
        'status',
        'payment_method',
        'transaction_id',
        'commission_breakdown',
        'due_date',
        'paid_at',
        'cancelled_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'commission_breakdown' => 'array',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the user that owns the invoice.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the booking associated with the invoice (legacy, optional).
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the order associated with the invoice (preferred).
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the invoice items for the invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Scope for customer invoices (without commission details)
     */
    public function scopeCustomerInvoices($query)
    {
        return $query->where('invoice_type', 'customer');
    }

    /**
     * Scope for provider invoices (with commission details)
     */
    public function scopeProviderInvoices($query)
    {
        return $query->where('invoice_type', 'provider');
    }

    /**
     * Scope for paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for pending invoices
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for cancelled invoices
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if invoice is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(): bool
    {
        return $this->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);
    }

    /**
     * Mark invoice as cancelled
     */
    public function markAsCancelled(string $reason = null): bool
    {
        return $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'notes' => $reason
        ]);
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_amount, 2) . ' ريال';
    }

    /**
     * Get formatted tax amount
     */
    public function getFormattedTaxAttribute(): string
    {
        return number_format($this->tax_amount, 2) . ' ريال';
    }

    /**
     * Get formatted commission amount
     */
    public function getFormattedCommissionAttribute(): string
    {
        return number_format($this->commission_amount, 2) . ' ريال';
    }

    /**
     * Get commission breakdown for display
     */
    public function getCommissionBreakdownDisplayAttribute(): array
    {
        if (!$this->commission_breakdown) {
            return [];
        }

        $breakdown = [];
        foreach ($this->commission_breakdown as $key => $value) {
            $breakdown[ucfirst(str_replace('_', ' ', $key))] = number_format($value, 2) . ' ريال';
        }

        return $breakdown;
    }
}
