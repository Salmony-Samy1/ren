<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    protected $fillable = [
        'fee_name',
        'fee_type',
        'account_type',
        'amount',
        'applicable_services',
        'min_amount',
        'max_amount',
        'status',
        'effective_from',
        'effective_until',
        'description'
    ];

    protected $casts = [
        'applicable_services' => 'array',
        'amount' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'effective_from' => 'datetime',
        'effective_until' => 'datetime'
    ];

    /**
     * Scope for active fees
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('effective_from')
                  ->orWhere('effective_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', now());
            });
    }

    /**
     * Check if fee is currently effective
     */
    public function isEffective(): bool
    {
        $now = now();
        
        if ($this->status !== 'active') {
            return false;
        }
        
        if ($this->effective_from && $now->lt($this->effective_from)) {
            return false;
        }
        
        if ($this->effective_until && $now->gt($this->effective_until)) {
            return false;
        }
        
        return true;
    }

    /**
     * Calculate fee amount for a given transaction amount
     */
    public function calculateFee(float $transactionAmount): float
    {
        if (!$this->isEffective()) {
            return 0;
        }

        $feeAmount = match($this->account_type) {
            'percentage' => ($transactionAmount * $this->amount) / 100,
            'fixed_amount' => $this->amount,
            default => 0
        };

        // Apply min/max limits
        if ($this->min_amount && $feeAmount < $this->min_amount) {
            $feeAmount = $this->min_amount;
        }

        if ($this->max_amount && $feeAmount > $this->max_amount) {
            $feeAmount = $this->max_amount;
        }

        return $feeAmount;
    }
}
