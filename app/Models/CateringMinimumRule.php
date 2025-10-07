<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CateringMinimumRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'rule_name',
        'region_name',
        'city',
        'center_lat',
        'center_long',
        'radius_km',
        'min_order_value',
        'delivery_fee',
        'free_delivery_threshold',
        'max_delivery_distance_km',
        'operating_hours',
        'special_conditions',
        'is_active',
        'status',
        'applied_orders_count',
        'total_revenue_impact',
        'created_by_admin',
        'admin_notes',
    ];

    protected $casts = [
        'center_lat' => 'decimal:8',
        'center_long' => 'decimal:8',
        'radius_km' => 'decimal:2',
        'min_order_value' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'free_delivery_threshold' => 'decimal:2',
        'max_delivery_distance_km' => 'decimal:2',
        'operating_hours' => 'array',
        'special_conditions' => 'array',
        'is_active' => 'boolean',
        'applied_orders_count' => 'integer',
        'total_revenue_impact' => 'decimal:2',
    ];

    /**
     * Get the provider (user) that owns the rule
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Scope for active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    /**
     * Scope for general rules (not provider-specific)
     */
    public function scopeGeneral($query)
    {
        return $query->whereNull('provider_id');
    }

    /**
     * Scope for provider-specific rules
     */
    public function scopeProviderSpecific($query, int $providerId = null)
    {
        $query = $query->whereNotNull('provider_id');
        
        if ($providerId) {
            $query = $query->where('provider_id', $providerId);
        }
        
        return $query;
    }

    /**
     * Scope for rules in a specific city
     */
    public function scopeInCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Scope for rules covering a specific location
     */
    public function scopeCoveringLocation($query, float $lat, float $long, float $radius = null)
    {
        // Using Haversine formula for distance calculation
        $query = $query->selectRaw("*, (
            6371 * acos(
                cos(radians(?))
                * cos(radians(center_lat))
                * cos(radians(center_long) - radians(?))
                + sin(radians(?))
                * sin(radians(center_lat))
            )
        ) AS distance", [$lat, $long, $lat]);

        if ($radius !== null) {
            $query = $query->havingRaw('distance <= ?', [$radius]);
        }

        return $query->orderBy('distance');
    }

    /**
     * Get status label in Arabic
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            'suspended' => 'معلق',
            default => 'غير محدد'
        };
    }

    /**
     * Get computed ruled ID for API responses
     */
    public function getRuleIdAttribute(): string
    {
        return 'RULE-CTR-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if location is within rule coverage
     */
    public function coversLocation(float $lat, float $long): bool
    {
        $distance = $this->calculateDistance($this->center_lat, $this->center_long, $lat, $long);
        return $distance <= $this->radius_km;
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if current time is within operating hours
     */
    public function isWithinOperatingHours(): bool
    {
        $currentDay = strtolower(date('l')); // e.g., 'monday'
        $currentTime = date('H:i'); // Current time in HH:MM format

        $daysMap = [
            'sunday' => 'الأحد',
            'monday' => 'الإثنين', 
            'tuesday' => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday' => 'الخميس',
            'friday' => 'الجمعة',
            'saturday' => 'السبت',
        ];

        $dayKey = array_search($currentDay, $daysMap);
        
        if (!$dayKey || !isset($this->operating_hours[$dayKey])) {
            return false;
        }

        $daySchedule = $this->operating_hours[$dayKey];
        
        if (!($daySchedule['is_active'] ?? false)) {
            return false;
        }

        $startTime = $daySchedule['start'] ?? '00:00';
        $endTime = $daySchedule['end'] ?? '23:59';

        // Handle overnight hours (e.g., 22:00-02:00)
        if ($startTime > $endTime) {
            return $currentTime >= $startTime || $currentTime <= $endTime;
        }

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    /**
     * Get total coverage area in km²
     */
    public function getCoverageAreaAttribute(): float
    {
        return pi() * pow($this->radius_km, 2);
    }

    /**
     * Check if order meets minimum requirements
     */
    public function meetsMinimumRequirements(float $orderValue, float $deliveryDistanceKm = 0): array
    {
        $requirements = [
            'order_value_met' => $orderValue >= $this->min_order_value,
            'distance_acceptable' => $deliveryDistanceKm <= $this->max_delivery_distance_km,
            'within_coverage' => $this->isWithinOperatingHours(),
            'delivery_fee' => $orderValue >= $this->free_delivery_threshold ? 0 : $this->delivery_fee,
            'qualified_for_free_delivery' => $orderValue >= $this->free_delivery_threshold,
        ];

        $requirements['all_requirements_met'] = 
            $requirements['order_value_met'] && 
            $requirements['distance_acceptable'] && 
            $requirements['within_coverage'];

        return $requirements;
    }
}