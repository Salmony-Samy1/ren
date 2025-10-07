<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReferralService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(private readonly ReferralService $referralService)
    {
    }

    /**
     * عرض إحصائيات الإحالة العامة
     */
    public function statistics()
    {
        $stats = [
            'total_users_with_referrals' => \App\Models\User::whereHas('referrals')->count(),
            'total_referrals' => \App\Models\User::whereNotNull('referred_by')->count(),
            'top_referrers' => $this->referralService->getTopReferrers(10),
            'recent_referrals' => \App\Models\User::whereNotNull('referred_by')
                ->with('referredBy:id,full_name')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(['id', 'full_name', 'email', 'referred_by', 'created_at']),
            'referral_conversion_rate' => $this->calculateReferralConversionRate(),
        ];

        return format_response(true, 'تم جلب إحصائيات الإحالة بنجاح', $stats);
    }

    /**
     * عرض قائمة أفضل المحيلين
     */
    public function topReferrers(Request $request)
    {
        $limit = $request->get('limit', 20);
        $topReferrers = $this->referralService->getTopReferrers($limit);

        return format_response(true, 'تم جلب أفضل المحيلين بنجاح', $topReferrers);
    }

    /**
     * عرض تفاصيل محيل محدد
     */
    public function showReferrer($userId)
    {
        $user = \App\Models\User::with(['referrals' => function($query) {
            $query->select('id', 'full_name', 'email', 'referred_by', 'created_at')
                ->withCount('bookings');
        }])->findOrFail($userId);

        $stats = $this->referralService->getUserReferralStats($user);

        return format_response(true, 'تم جلب تفاصيل المحيل بنجاح', [
            'user' => $user,
            'stats' => $stats
        ]);
    }

    /**
     * حساب معدل تحويل الإحالة
     */
    private function calculateReferralConversionRate(): float
    {
        $totalReferrals = \App\Models\User::whereNotNull('referred_by')->count();
        $activeReferrals = \App\Models\User::whereNotNull('referred_by')
            ->whereHas('bookings')
            ->count();

        if ($totalReferrals === 0) {
            return 0;
        }

        return round(($activeReferrals / $totalReferrals) * 100, 2);
    }
}
