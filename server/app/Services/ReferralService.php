<?php

namespace App\Services;

use App\Models\User;
use App\Services\PointsService;
use Illuminate\Support\Str;

class ReferralService
{
    public function __construct(private readonly PointsService $pointsService)
    {
    }

    /**
     * إنشاء كود إحالة للمستخدم
     */
    public function generateReferralCode(User $user): string
    {
        if (!$user->referral_code) {
            $code = $user->generateReferralCode();
            $user->update(['referral_code' => $code]);
            return $code;
        }

        return $user->referral_code;
    }

    /**
     * البحث عن مستخدم بواسطة كود الإحالة
     */
    public function findUserByReferralCode(string $code): ?User
    {
        return User::where('referral_code', $code)->first();
    }

    /**
     * معالجة إحالة مستخدم جديد
     */
    public function processReferral(string $referralCode, User $newUser): bool
    {
        try {
            $referrer = $this->findUserByReferralCode($referralCode);
            
            if (!$referrer) {
                return false;
            }

            // لا يمكن للمستخدم أن يحيل نفسه
            if ($referrer->id === $newUser->id) {
                return false;
            }

            // تحديث المستخدم الجديد
            $newUser->update([
                'referred_by' => $referrer->id,
                'referral_code' => $this->generateReferralCode($newUser)
            ]);

            // منح نقاط للمحيل
            $this->pointsService->awardReferralPoints($referrer, $newUser);

            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * الحصول على إحصائيات الإحالة للمستخدم
     */
    public function getUserReferralStats(User $user): array
    {
        $referrals = $user->referrals()->count();
        $activeReferrals = $user->referrals()
            ->whereHas('bookings')
            ->count();

        $totalEarnings = \App\Models\PointsLedger::where('user_id', $user->id)
            ->where('type', 'earn')
            ->where('source', 'referral_bonus')
            ->sum('points');

        return [
            'total_referrals' => $referrals,
            'active_referrals' => $activeReferrals,
            'total_earnings' => $totalEarnings,
            'referral_code' => $user->referral_code,
            'recent_referrals' => $user->referrals()
                ->select('id', \DB::raw("COALESCE(full_name, full_name) as name"), 'email', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
        ];
    }

    /**
     * الحصول على قائمة أفضل المحيلين
     */
    public function getTopReferrers(int $limit = 10): array
    {
        return User::withCount('referrals')
            ->whereHas('referrals')
            ->orderBy('referrals_count', 'desc')
            ->limit($limit)
            ->get(['id', \DB::raw("COALESCE(full_name, name) as name"), 'email', 'referrals_count'])
            ->toArray();
    }

    /**
     * التحقق من صحة كود الإحالة
     */
    public function validateReferralCode(string $code): bool
    {
        return User::where('referral_code', $code)->exists();
    }

    /**
     * إنشاء رابط الإحالة
     */
    public function generateReferralLink(User $user): string
    {
        $code = $this->generateReferralCode($user);
        return config('app.frontend_url', 'https://gathro.com') . '/register?ref=' . $code;
    }
}
