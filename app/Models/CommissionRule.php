<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionRule extends Model
{
    protected $fillable = [
        'rule_name',
        'rule_type',
        'commission_type',
        'commission_value',
        'rule_parameters',
        'min_commission',
        'max_commission',
        'status',
        'priority',
        'effective_from',
        'effective_until',
        'description'
    ];

    protected $casts = [
        'rule_parameters' => 'array',
        'commission_value' => 'decimal:2',
        'min_commission' => 'decimal:2',
        'max_commission' => 'decimal:2',
        'effective_from' => 'datetime',
        'effective_until' => 'datetime',
        'priority' => 'integer'
    ];

    /**
     * Scope for active rules
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
     * Scope ordered by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('commission_value', 'desc');
    }

    /**
     * Check if rule is currently effective
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
}
