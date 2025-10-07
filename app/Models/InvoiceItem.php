<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'total',
        'tax_rate',
        'tax_amount',
        'commission_breakdown',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'commission_breakdown' => 'array',
        'tax_rate' => 'float',
        'tax_amount' => 'float',
        'unit_price' => 'float',
        'total' => 'float',
        'quantity' => 'integer',
    ];

    /**
     * Get the invoice that owns the item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get formatted unit price
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2) . ' ريال';
    }

    /**
     * Get formatted total
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total, 2) . ' ريال';
    }

    /**
     * Get formatted tax amount
     */
    public function getFormattedTaxAmountAttribute(): string
    {
        return number_format($this->tax_amount, 2) . ' ريال';
    }

    /**
     * Calculate total with tax
     */
    public function getTotalWithTaxAttribute(): float
    {
        return $this->total + $this->tax_amount;
    }

    /**
     * Get formatted total with tax
     */
    public function getFormattedTotalWithTaxAttribute(): string
    {
        return number_format($this->total_with_tax, 2) . ' ريال';
    }

    /**
     * Check if item has commission
     */
    public function hasCommission(): bool
    {
        return !empty($this->commission_breakdown);
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
