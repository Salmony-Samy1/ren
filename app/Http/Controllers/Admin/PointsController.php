<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PointsController extends Controller
{
    public function __construct(private readonly PointsService $pointsService)
    {
    }

    /**
     * عرض إعدادات النقاط
     */
    public function settings()
    {
        $settings = $this->pointsService->getPointsSettings();
        
        return format_response(true, 'تم جلب الإعدادات بنجاح', $settings);
    }

    /**
     * تحديث إعدادات النقاط
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loyalty_points' => 'required|integer|min:0',
            'first_booking_points' => 'required|integer|min:0',
            'review_points' => 'required|integer|min:0',
            'referral_points' => 'required|integer|min:0',
            'provider_loyalty_points' => 'nullable|integer|min:0',
            'service_creation_points' => 'nullable|integer|min:0',
            'points_expiry_days' => 'nullable|integer|min:1',
            'points_to_wallet_rate' => 'required|numeric|min:0',
            'min_points_for_conversion' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return format_response(false, 'بيانات غير صحيحة', $validator->errors(), 422);
        }

        $success = $this->pointsService->updatePointsSettings($request->all());

        if ($success) {
            return format_response(true, 'تم تحديث إعدادات النقاط بنجاح');
        }

        return format_response(false, 'حدث خطأ أثناء تحديث الإعدادات', code: 500);
    }

    /**
     * عرض إحصائيات النقاط العامة
     */
    public function statistics()
    {
        // إحصاءات مبنية على Points Ledger
        $now = now();
        $earnedActive = \App\Models\PointsLedger::where('type', 'earn')
            ->where(function($q) use ($now){ $q->whereNull('expires_at')->orWhere('expires_at', '>', $now); })
            ->sum('points');
        $spent = \App\Models\PointsLedger::where('type', 'spend')->sum('points');
        $expired = \App\Models\PointsLedger::where('type', 'expire')->sum('points');

        $distinctUsers = \App\Models\PointsLedger::distinct('user_id')->count('user_id');

        // احسب الرصيد لكل مستخدم عبر استعلام GroupBy
        $balances = \DB::table('points_ledger')
            ->selectRaw("user_id,
                SUM(CASE WHEN type='earn' AND (expires_at IS NULL OR expires_at > ?) THEN points ELSE 0 END) AS earned,
                SUM(CASE WHEN type='spend' THEN points ELSE 0 END) AS spent,
                SUM(CASE WHEN type='expire' THEN points ELSE 0 END) AS expired",
                [$now])
            ->groupBy('user_id')
            ->get()
            ->map(fn($r) => max(0, (int)$r->earned - (int)$r->spent - (int)$r->expired));

        $totalBalance = (int) $balances->sum();
        $avgBalance = $distinctUsers > 0 ? ($totalBalance / $distinctUsers) : 0;

        // أفضل 10 من حيث الرصيد
        $top = \DB::table('points_ledger')
            ->selectRaw("user_id,
                SUM(CASE WHEN type='earn' AND (expires_at IS NULL OR expires_at > ?) THEN points ELSE 0 END) AS earned,
                SUM(CASE WHEN type='spend' THEN points ELSE 0 END) AS spent,
                SUM(CASE WHEN type='expire' THEN points ELSE 0 END) AS expired",
                [$now])
            ->groupBy('user_id')
            ->get()
            ->map(fn($r) => (object) [
                'user_id' => $r->user_id,
                'balance' => max(0, (int)$r->earned - (int)$r->spent - (int)$r->expired),
            ])
            ->sortByDesc('balance')
            ->take(10)
            ->values();

        // جلب بيانات المستخدمين للأعلى رصيداً
        $topUsers = \App\Models\User::whereIn('id', collect($top)->pluck('user_id'))
            ->get(['id','full_name','email'])
            ->map(function($u) use ($top){
                $b = collect($top)->firstWhere('user_id', $u->id)->balance ?? 0;
                return ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'balance' => $b];
            })
            ->sortByDesc('balance')
            ->values();

        $stats = [
            'total_earned_active' => (int) $earnedActive,
            'total_spent' => (int) $spent,
            'total_expired' => (int) $expired,
            'total_active_balance' => (int) $totalBalance,
            'total_users_with_points' => (int) $distinctUsers,
            'average_balance_per_user' => (float) $avgBalance,
            'top_points_balances' => $topUsers,
        ];

        return format_response(true, 'تم جلب الإحصائيات بنجاح', $stats);
    }
}
