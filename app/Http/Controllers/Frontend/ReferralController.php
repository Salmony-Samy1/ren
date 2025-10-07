<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\ReferralService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(private readonly ReferralService $referralService)
    {
    }

    /**
     * عرض إحصائيات الإحالة للمستخدم
     */
    public function stats()
    {
        $user = auth()->user();
        $stats = $this->referralService->getUserReferralStats($user);
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * إنشاء كود إحالة جديد
     */
    public function generateCode()
    {
        $user = auth()->user();
        $code = $this->referralService->generateReferralCode($user);
        
        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء كود الإحالة بنجاح',
            'data' => [
                'referral_code' => $code,
                'referral_link' => $this->referralService->generateReferralLink($user)
            ]
        ]);
    }

    /**
     * التحقق من صحة كود إحالة
     */
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10'
        ]);

        $isValid = $this->referralService->validateReferralCode($request->code);
        
        if ($isValid) {
            $referrer = $this->referralService->findUserByReferralCode($request->code);
            
            return response()->json([
                'success' => true,
                'message' => 'كود الإحالة صحيح',
                'data' => [
                    'referrer_name' => ($referrer->full_name ?? trim(($referrer->first_name ?? '').' '.($referrer->last_name ?? ''))) ?: ($referrer->name ?? null),
                    'is_valid' => true
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'كود الإحالة غير صحيح',
            'data' => [
                'is_valid' => false
            ]
        ], 400);
    }

    /**
     * عرض قائمة الإحالات
     */
    public function referrals(Request $request)
    {
        $user = auth()->user();
        $limit = $request->get('limit', 20);
        
        $referrals = $user->referrals()
            ->select('id', \DB::raw("COALESCE(full_name, name) as name"), 'email', 'created_at')
            ->withCount('bookings')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $referrals
        ]);
    }

    /**
     * عرض معلومات الإحالة
     */
    public function show()
    {
        $user = auth()->user();
        
        $data = [
            'referral_code' => $user->referral_code ?: $this->referralService->generateReferralCode($user),
            'referral_link' => $this->referralService->generateReferralLink($user),
            'total_referrals' => $user->referrals()->count(),
            'total_earnings' => \App\Models\PointsLedger::where('user_id', $user->id)
                ->where('type', 'earn')
                ->where('source', 'referral_bonus')
                ->sum('points'),
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
