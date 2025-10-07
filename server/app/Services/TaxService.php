<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Setting;

class TaxService
{
    /**
     * حساب الضرائب للحجز
     */
    public function calculateTax(Booking $booking): array
    {
        $service = $booking->service;
        $taxRate = $this->getTaxRate($service);
        
        $subtotal = $booking->subtotal;
        $taxAmount = ($subtotal * $taxRate) / 100;
        
        return [
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_tax' => $taxAmount,
            'subtotal' => $subtotal,
            'total_with_tax' => $subtotal + $taxAmount
        ];
    }

    /**
     * الحصول على نسبة الضريبة حسب نوع الخدمة
     */
    public function getTaxRate(Service $service): float
    {
        // الحصول من الإعدادات العامة
        $defaultTaxRate = (float) get_setting('default_tax_rate', 15);
        
        // الحصول من إعدادات نوع الخدمة
        $serviceTypeTaxRate = $this->getServiceTypeTaxRate($service);
        
        // الحصول من إعدادات المنطقة
        $regionTaxRate = $this->getRegionTaxRate($service);
        
        // استخدام أعلى نسبة ضريبة
        return max($defaultTaxRate, $serviceTypeTaxRate, $regionTaxRate);
    }

    /**
     * الحصول على نسبة الضريبة حسب نوع الخدمة
     */
    private function getServiceTypeTaxRate(Service $service): float
    {
        if ($service->event) {
            return (float) get_setting('event_tax_rate', 15);
        } elseif ($service->cateringItem) {
            return (float) get_setting('catering_tax_rate', 15);
        } elseif ($service->restaurant) {
            return (float) get_setting('restaurant_tax_rate', 15);
        } elseif ($service->property) {
            return (float) get_setting('property_tax_rate', 15);
        }
        
        return 0;
    }

    /**
     * الحصول على نسبة الضريبة حسب المنطقة
     */
    private function getRegionTaxRate(Service $service): float
    {
        try {
            $region = $service->user->companyProfile->region ?? null;
            
            if ($region) {
                return (float) get_setting("tax_rate_region_{$region->id}", 15);
            }
        } catch (\Exception $e) {
            // تجاهل الأخطاء وإرجاع القيمة الافتراضية
        }
        
        return 0;
    }

    /**
     * حساب الضريبة للخدمات المتعددة
     */
    public function calculateTaxForMultipleServices(array $services, array $quantities): array
    {
        $totalTax = 0;
        $totalSubtotal = 0;
        $taxBreakdown = [];

        foreach ($services as $index => $service) {
            $quantity = $quantities[$index] ?? 1;
            $subtotal = $service->price * $quantity;
            $taxRate = $this->getTaxRate($service);
            $taxAmount = ($subtotal * $taxRate) / 100;

            $totalTax += $taxAmount;
            $totalSubtotal += $subtotal;

            $taxBreakdown[] = [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount
            ];
        }

        return [
            'total_subtotal' => $totalSubtotal,
            'total_tax' => $totalTax,
            'total_with_tax' => $totalSubtotal + $totalTax,
            'tax_breakdown' => $taxBreakdown
        ];
    }

    /**
     * حساب الضريبة مع الخصومات
     */
    public function calculateTaxWithDiscounts(Booking $booking): array
    {
        $taxData = $this->calculateTax($booking);
        $discount = $booking->discount ?? 0;
        
        // الضريبة تُحسب على المبلغ بعد الخصم
        $subtotalAfterDiscount = $taxData['subtotal'] - $discount;
        $taxAmountAfterDiscount = ($subtotalAfterDiscount * $taxData['tax_rate']) / 100;
        
        return [
            'original_subtotal' => $taxData['subtotal'],
            'discount_amount' => $discount,
            'subtotal_after_discount' => $subtotalAfterDiscount,
            'tax_rate' => $taxData['tax_rate'],
            'tax_amount' => $taxAmountAfterDiscount,
            'total_with_tax' => $subtotalAfterDiscount + $taxAmountAfterDiscount
        ];
    }

    /**
     * تحديث إعدادات الضرائب
     */
    public function updateTaxSettings(array $settings): bool
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
     * الحصول على إعدادات الضرائب
     */
    public function getTaxSettings(): array
    {
        return [
            'default_tax_rate' => (float) get_setting('default_tax_rate', 15),
            'event_tax_rate' => (float) get_setting('event_tax_rate', 15),
            'catering_tax_rate' => (float) get_setting('catering_tax_rate', 15),
            'restaurant_tax_rate' => (float) get_setting('restaurant_tax_rate', 15),
            'property_tax_rate' => (float) get_setting('property_tax_rate', 15),
            'tax_inclusive_pricing' => (bool) get_setting('tax_inclusive_pricing', false),
            'show_tax_breakdown' => (bool) get_setting('show_tax_breakdown', true),
        ];
    }

    /**
     * التحقق من صحة نسبة الضريبة
     */
    public function validateTaxRate(float $taxRate): bool
    {
        return $taxRate >= 0 && $taxRate <= 100;
    }

    /**
     * حساب الضريبة للفترة الزمنية
     */
    public function calculateTaxForPeriod(string $period, User $user = null): array
    {
        $startDate = $this->getStartDate($period);
        $endDate = now();

        $query = \App\Models\Invoice::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($user) {
            $query->where('user_id', $user->id);
        }

        $totalTax = $query->sum('tax_amount');
        $totalAmount = $query->sum('total_amount');
        $invoiceCount = $query->count();

        return [
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_tax' => $totalTax,
            'total_amount' => $totalAmount,
            'invoice_count' => $invoiceCount,
            'average_tax_rate' => $totalAmount > 0 ? ($totalTax / $totalAmount) * 100 : 0
        ];
    }

    /**
     * الحصول على تاريخ البداية حسب الفترة
     */
    private function getStartDate(string $period): \Carbon\Carbon
    {
        return match($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subQuarter(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };
    }
}
