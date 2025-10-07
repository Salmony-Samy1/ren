<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CommissionService
{
    /**
     * حساب العمولة للحجز
     */
    public function calculateCommission(Booking $booking): array
    {
        $service = $booking->service;
        $provider = $service->user;
        
        // الحصول على إعدادات العمولة
        $commissionSettings = $this->getCommissionSettings();
        
        // حساب العمولة الأساسية
        $baseCommission = $this->calculateBaseCommission($booking, $commissionSettings);
        
        // حساب العمولة الإضافية حسب نوع الخدمة
        $serviceTypeCommission = $this->calculateServiceTypeCommission($service, $booking, $commissionSettings);
        
        // حساب العمولة حسب حجم المبيعات
        $volumeCommission = $this->calculateVolumeCommission($provider, $booking, $commissionSettings);
        
        // حساب العمولة حسب التقييم
        $ratingCommission = $this->calculateRatingCommission($service, $commissionSettings);
        
        // العمولة الإجمالية
        $totalCommission = $baseCommission + $serviceTypeCommission + $volumeCommission + $ratingCommission;
        
        // التحقق من الحد الأدنى والأعلى للعمولة
        $totalCommission = $this->applyCommissionLimits($totalCommission, $booking, $commissionSettings);
        $bookingSubtotal = max(0.01, (float) $booking->subtotal);
        // خصم قيمة النقاط المستخدمة (إن وجدت) من عمولة المنصة بحسب سياسة PRD
        $pointsValue = (float) ($booking->points_value ?? 0.0);
        if ($pointsValue > 0) {
            // تقل عمولة المنصة أولاً حتى الصفر، ثم لا تؤثر على صافي المزود
            $deduct = min($pointsValue, $totalCommission);
            $totalCommission -= $deduct;
        }

        return [
            'base_commission' => $baseCommission,
            'service_type_commission' => $serviceTypeCommission,
            'volume_commission' => $volumeCommission,
            'rating_commission' => $ratingCommission,
            'total_commission' => $totalCommission,
            'commission_rate' => ($totalCommission / $bookingSubtotal) * 100,
            'provider_amount' => $booking->subtotal - $totalCommission,
            'platform_amount' => $totalCommission,
        ];
    }

    /**
     * حساب العمولة الأساسية
     */
    private function calculateBaseCommission(Booking $booking, array $settings): float
    {
        $commissionType = $settings['commission_type'] ?? 'percentage';
        $commissionAmount = $settings['commission_amount'] ?? 5;
        
        if ($commissionType === 'percentage') {
            return ($booking->subtotal * $commissionAmount) / 100;
        } else {
            return $commissionAmount;
        }
    }

    /**
     * حساب العمولة حسب نوع الخدمة
     */
    private function calculateServiceTypeCommission(Service $service, Booking $booking, array $settings): float
    {
        $serviceTypeRates = $settings['service_type_rates'] ?? [];
        $serviceType = $this->getServiceType($service);
        
        $rate = $serviceTypeRates[$serviceType] ?? 0;
        return ($booking->subtotal * $rate) / 100;
    }

    /**
     * حساب العمولة حسب حجم المبيعات
     */
    private function calculateVolumeCommission(User $provider, Booking $booking, array $settings): float
    {
        $volumeRates = $settings['volume_rates'] ?? [];
        $monthlySales = $this->getProviderMonthlySales($provider);
        
        $rate = 0;
        foreach ($volumeRates as $threshold => $commissionRate) {
            if ($monthlySales >= $threshold) {
                $rate = $commissionRate;
            }
        }
        
        return ($booking->subtotal * $rate) / 100;
    }

    /**
     * حساب العمولة حسب التقييم
     */
    private function calculateRatingCommission(Service $service, array $settings): float
    {
        $ratingRates = $settings['rating_rates'] ?? [];
        $averageRating = $service->reviews()->approved()->avg('rating') ?? 0;
        
        $rate = 0;
        foreach ($ratingRates as $rating => $commissionRate) {
            if ($averageRating >= $rating) {
                $rate = $commissionRate;
            }
        }
        
        return $rate;
    }

    /**
     * تطبيق حدود العمولة
     */
    private function applyCommissionLimits(float $commission, Booking $booking, array $settings): float
    {
        $minCommission = $settings['min_commission'] ?? 0;
        $maxCommission = $settings['max_commission'] ?? 50;
        $maxCommissionAmount = $settings['max_commission_amount'] ?? null;
        
        // الحد الأدنى
        $commission = max($commission, $minCommission);
        
        // الحد الأعلى كنسبة مئوية
        $maxCommissionPercent = ($booking->subtotal * $maxCommission) / 100;
        $commission = min($commission, $maxCommissionPercent);
        
        // الحد الأعلى كمبلغ ثابت
        if ($maxCommissionAmount) {
            $commission = min($commission, $maxCommissionAmount);
        }
        
        return $commission;
    }

    /**
     * الحصول على نوع الخدمة
     */
    private function getServiceType(Service $service): string
    {
        if ($service->event) return 'event';
        if ($service->cateringItem) return 'catering';
        if ($service->restaurant) return 'restaurant';
        if ($service->property) return 'property';
        return 'other';
    }

    /**
     * الحصول على مبيعات المزود الشهرية
     */
    private function getProviderMonthlySales(User $provider): float
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        return Booking::whereHas('service', function ($query) use ($provider) {
            $query->where('user_id', $provider->id);
        })
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
        ->where('status', 'completed')
        ->sum('subtotal');
    }

    /**
     * الحصول على إعدادات العمولة
     */
    public function getCommissionSettings(): array
    {
        return [
            'commission_type' => get_setting('commission_type', 'percentage'),
            'commission_amount' => (float)get_setting('commission_amount', 5),
            'service_type_rates' => [
                'event' => (float)get_setting('event_commission_rate', 3),
                'catering' => (float)get_setting('catering_commission_rate', 4),
                'restaurant' => (float)get_setting('restaurant_commission_rate', 5),
                'property' => (float)get_setting('property_commission_rate', 6),
            ],
            'volume_rates' => [
                1000 => (float)get_setting('volume_1000_rate', 1),
                5000 => (float)get_setting('volume_5000_rate', 2),
                10000 => (float)get_setting('volume_10000_rate', 3),
            ],
            'rating_rates' => [
                4.0 => (float)get_setting('rating_4_rate', 1),
                4.5 => (float)get_setting('rating_4_5_rate', 2),
                5.0 => (float)get_setting('rating_5_rate', 3),
            ],
            'min_commission' => (float)get_setting('min_commission', 0),
            'max_commission' => (float)get_setting('max_commission', 50),
            'max_commission_amount' => (float)get_setting('max_commission_amount', 100),
        ];
    }

    /**
     * تحديث إعدادات العمولة
     */
    public function updateCommissionSettings(array $settings): bool
    {
        try {
            foreach ($settings as $key => $value) {
                set_setting($key, $value);
            }
            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * معالجة العمولة للحجز المكتمل
     */
    public function processCommission(Booking $booking): bool
    {
        try {
            DB::beginTransaction();
            
            $commissionData = $this->calculateCommission($booking);
            
            // تحديث الفاتورة بالعمولة
            $invoice = $booking->invoice;
            if ($invoice) {
                $invoice->update([
                    'commission_amount' => $commissionData['total_commission'],
                    'provider_amount' => $commissionData['provider_amount'],
                    'platform_amount' => $commissionData['platform_amount'],
                ]);
            }
            
            // إضافة العمولة إلى محفظة المزود
            $provider = $booking->service->user;
            $provider->deposit($commissionData['provider_amount'], [
                'description' => "Commission for booking #{$booking->id}",
                'booking_id' => $booking->id,
                'commission_data' => $commissionData
            ]);
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return false;
        }
    }

    /**
     * الحصول على إحصائيات العمولة
     */
    public function getCommissionStats(string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);
        $endDate = Carbon::now();
        
        $stats = [
            'total_commission' => Invoice::whereBetween('created_at', [$startDate, $endDate])
                ->sum('commission_amount'),
            'total_bookings' => Booking::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->count(),
            'average_commission' => Invoice::whereBetween('created_at', [$startDate, $endDate])
                ->avg('commission_amount'),
            'commission_by_service_type' => $this->getCommissionByServiceType($startDate, $endDate),
            'top_providers_by_commission' => $this->getTopProvidersByCommission($startDate, $endDate),
        ];
        return $stats;
    }

    /**
     * الحصول على تاريخ البداية حسب الفترة
     */
    private function getStartDate(string $period): Carbon
    {
        return match($period) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'quarter' => Carbon::now()->subQuarter(),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonth(),
        };
    }

    /**
     * الحصول على العمولة حسب نوع الخدمة
     */
    private function getCommissionByServiceType(Carbon $startDate, Carbon $endDate): array
    {
        return Invoice::join('bookings', 'invoices.booking_id', '=', 'bookings.id')
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->leftJoin('events', 'events.service_id', '=', 'services.id')
            ->leftJoin('catering_items', 'catering_items.service_id', '=', 'services.id')
            ->leftJoin('restaurants', 'restaurants.service_id', '=', 'services.id')
            ->leftJoin('properties', 'properties.service_id', '=', 'services.id')
            ->whereBetween('invoices.created_at', [$startDate, $endDate])
            ->selectRaw('
                CASE
                    WHEN events.id IS NOT NULL THEN "event"
                    WHEN catering_items.id IS NOT NULL THEN "catering"
                    WHEN restaurants.id IS NOT NULL THEN "restaurant"
                    WHEN properties.id IS NOT NULL THEN "property"
                    ELSE "other"
                END as service_type,
                SUM(invoices.commission_amount) as total_commission,
                COUNT(*) as bookings_count
            ')
            ->groupBy('service_type')
            ->get()
            ->toArray();
    }

    /**
     * الحصول على أفضل المزودين بالعمولة
     */
    private function getTopProvidersByCommission(Carbon $startDate, Carbon $endDate): array
    {
        return Invoice::join('bookings', 'invoices.booking_id', '=', 'bookings.id')
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->join('users', 'services.user_id', '=', 'users.id')
            ->whereBetween('invoices.created_at', [$startDate, $endDate])
            ->selectRaw('
                users.id,
                users.full_name,
                users.email,
                SUM(invoices.commission_amount) as total_commission,
                COUNT(*) as bookings_count
            ')
            ->groupBy('users.id', 'users.full_name', 'users.email')
            ->orderBy('total_commission', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
