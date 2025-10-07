<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CommissionRule;
use App\Models\FeeStructure;
use App\Models\ReferralTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdvancedCommissionService
{
    /**
     * حساب العمولة المتقدمة للحجز مع نظام القواعد الجديد
     */
    public function calculateAdvancedCommission(Booking $booking): array
    {
        $baseCommission = 0;
        $appliedRules = [];
        $additionalFees = 0;

        // الحصول على القواعد النشطة مرتبة بالأولوية
        $rules = CommissionRule::active()
            ->byPriority()
            ->get();

        // تطبيق القواعد حسب الأولوية
        foreach ($rules as $rule) {
            $commissionAmount = $this->applyCommissionRule($rule, $booking);
            
            if ($commissionAmount > 0) {
                $baseCommission += $commissionAmount;
                $appliedRules[] = [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->rule_name,
                    'amount' => $commissionAmount,
                    'rule_type' => $rule->rule_type
                ];
            }
        }

        // حساب الرسوم الإضافية
        $fees = FeeStructure::active()
            ->where('fee_type', '!=', 'referral')
            ->get();

        foreach ($fees as $fee) {
            if ($this->isFeeApplicable($fee, $booking)) {
                $feeAmount = $fee->calculateFee($booking->subtotal);
                $additionalFees += $feeAmount;
            }
        }

        $totalCommission = $baseCommission;
        $platformAmount = $totalCommission + $additionalFees;
        $providerAmount = $booking->total - $platformAmount;

        return [
            'base_commission' => $baseCommission,
            'additional_fees' => $additionalFees,
            'total_commission' => $totalCommission,
            'platform_amount' => max(0, $platformAmount),
            'provider_amount' => max(0, $providerAmount),
            'applied_rules' => $appliedRules,
            'commission_rate' => $baseCommission > 0 ? ($baseCommission / $booking->subtotal) * 100 : 0
        ];
    }

    /**
     * تطبيق قاعدة عمولة محددة
     */
    private function applyCommissionRule(CommissionRule $rule, Booking $booking): float
    {
        $matches = match($rule->rule_type) {
            'service_type' => $this->matchesServiceTypeRule($rule, $booking),
            'volume_based' => $this->matchesVolumeBasedRule($rule, $booking),
            'rating_based' => $this->matchesRatingBasedRule($rule, $booking),
            'referral_based' => false, // يتم التعامل مع العمولات الإحالية منفصل
            default => false
        };

        if (!$matches) {
            return 0;
        }

        // حساب مبلغ العمولة
        $commissionAmount = match($rule->commission_type) {
            'percentage' => ($booking->subtotal * $rule->commission_value) / 100,
            'fixed_amount' => $rule->commission_value,
            default => 0
        };

        // تطبيق الحدود
        if ($rule->min_commission && $commissionAmount < $rule->min_commission) {
            $commissionAmount = $rule->min_commission;
        }

        if ($rule->max_commission && $commissionAmount > $rule->max_commission) {
            $commissionAmount = $rule->max_commission;
        }

        return $commissionAmount;
    }

    /**
     * التحقق من تطابق قاعدة نوع الخدمة
     */
    private function matchesServiceTypeRule(CommissionRule $rule, Booking $booking): bool
    {
        $parameters = $rule->rule_parameters ?? [];
        $applicableServices = $parameters['service_types'] ?? [];

        if (empty($applicableServices)) {
            return true; // تطبق على جميع الخدمات
        }

        $serviceType = $this->getServiceType($booking->service);
        
        return in_array($serviceType, $applicableServices);
    }

    /**
     * التحقق من تطابق قاعدة حجم المبيعات
     */
    private function matchesVolumeBasedRule(CommissionRule $rule, Booking $booking): bool
    {
        $parameters = $rule->rule_parameters ?? [];
        
        if (!isset($parameters['threshold_amount'])) {
            return false;
        }

        $providerMonthlySales = $this->getProviderMonthlySales($booking->service->user);
        
        return $providerMonthlySales >= $parameters['threshold_amount'];
    }

    /**
     * التحقق من تطابق قاعدة التقييم
     */
    private function matchesRatingBasedRule(CommissionRule $rule, Booking $booking): bool
    {
        $parameters = $rule->rule_parameters ?? [];
        
        if (!isset($parameters['min_rating'])) {
            return false;
        }

        $averageRating = $booking->service->reviews()->approved()->avg('rating') ?? 0;
        
        return $averageRating >= $parameters['min_rating'];
    }

    /**
     * التحقق من إمكانية تطبيق رسوم معينة
     */
    private function isFeeApplicable(FeeStructure $fee, Booking $booking): bool
    {
        $applicableServices = $fee->applicable_services ?? [];

        if (empty($applicableServices)) {
            return true; // تطبق على جميع الخدمات
        }

        $serviceType = $this->getServiceType($booking->service);
        
        return in_array($serviceType, $applicableServices);
    }

    /**
     * معالجة العمولات الإحالية المتقدمة
     */
    public function processReferralCommission(Booking $booking): bool
    {
        try {
            DB::beginTransaction();

            $referredUser = $booking->user;
            
            // التحقق من وجود محيل
            if (!$referredUser->referred_by) {
                DB::commit();
                return true; // لا توجد إحالة، لا مشكلة
            }

            $referrer = User::find($referredUser->referred_by);
            
            if (!$referrer) {
                DB::commit();
                return true; // المحيل غير موجود
            }

            // الحصول على نسبة العمولة الإحالية
            $referralCommissionRate = $this->getReferralCommissionRate();
            
            if ($referralCommissionRate <= 0) {
                DB::commit();
                return true; // لا توجد عمولة إحالية مفعلة
            }

            // حساب العمولة الإحالية
            $referralCommission = ($booking->subtotal * $referralCommissionRate) / 100;

            // إنشاء معاملة إحالية
            ReferralTransaction::create([
                'referrer_id' => $referrer->id,
                'referred_user_id' => $referredUser->id,
                'booking_id' => $booking->id,
                'commission_amount' => $referralCommission,
                'commission_rate' => $referralCommissionRate,
                'commission_type' => 'booking_commission',
                'status' => 'pending',
                'notes' => "Commission for booking #{$booking->id} from referred user"
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
     * الحصول على نسبة العمولة الإحالية من الإعدادات
     */
    private function getReferralCommissionRate(): float
    {
        return (float) get_setting('referral_commission_rate', 0);
    }

    /**
     * الموافقة على عمولة إحالية
     */
    public function approveReferralCommission(int $transactionId): bool
    {
        try {
            $transaction = ReferralTransaction::findOrFail($transactionId);
            
            if ($transaction->status !== 'pending') {
                return false;
            }

            DB::beginTransaction();

            // الموافقة على المعاملة
            $transaction->approve();

            // إضافة العمولة إلى محفظة المحيل
            $transaction->referrer->deposit($transaction->commission_amount, [
                'description' => "Referral commission for booking #{$transaction->booking_id}",
                'source' => 'referral_commission',
                'transaction_id' => $transaction->id
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
     * الحصول على نوع الخدمة
     */
    private function getServiceType($service): string
    {
        if ($service->event) return 'event';
        if ($service->catering) return 'catering';
        if ($service->restaurant) return 'restaurant';
        if ($service->property) return 'property';
        
        return $service->category ?? 'unknown';
    }

    /**
     * الحصول على مبيعات المزود الشهرية
     */
    private function getProviderMonthlySales(User $provider): float
    {
        return Booking::whereHas('service', function ($query) use ($provider) {
                $query->where('user_id', $provider->id);
            })
            ->whereStatus('completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('subtotal');
    }
}
