<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'user_id',
        'service_id',
        'start_date',
        'end_date',
        'booking_details',
        'tax',
        'subtotal',
        'discount',
        'points_used',
        'points_value',
        'total',
        'currency',
        'wallet_currency',
        'total_wallet_currency',
        'payment_method',
        'transaction_id',
        'status',
        'privacy',
        'idempotency_key',
        'order_id',
        'reference_code',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function invoice()
    {
        // Legacy: invoices were created per booking; now we use order-level invoices
        // Keep hasOne for backfill/legacy reuse
        return $this->hasOne(Invoice::class, 'booking_id');
    }

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'service_id' => 'integer',
            'user_id' => 'integer',
            'booking_details' => 'array',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'subtotal' => 'float',
            'tax' => 'float',
            'discount' => 'float',
            'points_used' => 'integer',
            'points_value' => 'float',
            'total' => 'float',
            'total_wallet_currency' => 'float',
            'currency' => 'string',
            'wallet_currency' => 'string',
            'privacy' => 'string',
            'idempotency_key' => 'string',
            'order_id' => 'integer',
        ];
    }
}
