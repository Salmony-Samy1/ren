<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CateringDelivery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_id',
        'service_id',
        'provider_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'scheduled_delivery_at',
        'actual_delivery_at',
        'status',
        'delivery_address',
        'delivery_street',
        'delivery_building',
        'delivery_district',
        'delivery_city',
        'delivery_lat',
        'delivery_long',
        'delivery_fee',
        'free_delivery_applied',
        'estimated_duration_minutes',
        'delivery_notes',
        'admin_notes',
        'driver_id',
        'driver_name',
        'driver_phone',
        'vehicle_plate',
        'delivery_person_name',
    ];

    protected $casts = [
        'scheduled_delivery_at' => 'datetime',
        'actual_delivery_at' => 'datetime',
        'is_active' => 'boolean',
        'free_delivery_applied' => 'boolean',
        'delivery_fee' => 'decimal:2',
        'delivery_lat' => 'decimal:8',
        'delivery_long' => 'decimal:8',
    ];

    /**
     * Get the booking that owns the delivery
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the service that owns the delivery
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the provider (user) that owns the delivery
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Get the customer through the booking
     */
    public function customer(): BelongsTo
    {
        return $this->hasOneThrough(User::class, Booking::class, 'id', 'id', 'booking_id', 'user_id');
    }

    /**
     * Scope for active deliveries
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'preparing', 'out_for_delivery']);
    }

    /**
     * Scope for completed deliveries
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope for delivery in a specific city
     */
    public function scopeInCity($query, string $city)
    {
        return $query->where('delivery_city', $city);
    }

    /**
     * Scope for deliveries by provider
     */
    public function scopeByProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Get status label in Arabic
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'scheduled' => 'مجدول',
            'preparing' => 'قيد التحضير',
            'out_for_delivery' => 'في الطريق للتوصيل',
            'delivered' => 'تم التوصيل',
            'cancelled' => 'ملغي',
            default => $this->status
        };
    }

    /**
     * Get the formatted delivery address
     */
    public function getFullDeliveryAddressAttribute(): string
    {
        $parts = array_filter([
            $this->delivery_address,
            $this->delivery_street,
            $this->delivery_building,
            $this->delivery_district,
            $this->delivery_city
        ]);

        return implode('، ', $parts);
    }
}