<?php

namespace App\Http\Controllers\Frontend;

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
     *      
     */
    public function index()
    {
        $user = auth()->user();
        $stats = $this->pointsService->getUserPointsStats($user);

        // Ensure consistent payload fields for API shape
        $stats['total_points'] = $this->pointsService->getUserPoints($user);

        $latestMovement = \App\Models\PointsLedger::where('user_id', $user->id)->max('created_at');
        $lastModified = $latestMovement ? \Carbon\Carbon::parse($latestMovement) : ($user->updated_at ?? now());
        $etag = \App\Support\HttpCache::makeEtag(['points',$user->id,$stats['total_points'] ?? 0,$lastModified?->timestamp]);
        if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, optional($lastModified)->copy())) {
            return $resp304;
        }

        return \App\Support\HttpCache::withValidators(
            response()->json([
                'success' => true,
                'data' => $stats
            ]),
            $etag,
            optional($lastModified)->copy(),
            60
        );
    }

    /**
     * عرض سجل النقاط
     */
    public function history(Request $request)
    {
        $user = auth()->user();
        $limit = $request->get('limit', 20);
        
        // Retrieve paginated points history and convert it to a plain array
        $history = $this->pointsService->getUserPointsHistory($user, $limit)->toArray();

        // Retrieve user points statistics
        $stats = $this->pointsService->getUserPointsStats($user);
        $stats['total_points'] = $this->pointsService->getUserPoints($user);

        // The original paginator data had an object with numeric keys. We will ensure it's a proper array.
        if (isset($history['data'])) {
            $history['data'] = array_values($history['data']);
        }
        
        // Add the total stats inside the paginated data array as a 'total' key
        $history['total'] = $stats;
        
        $latestMovement = \App\Models\PointsLedger::where('user_id', $user->id)->max('created_at');
        $lastModified = $latestMovement ? \Carbon\Carbon::parse($latestMovement) : ($user->updated_at ?? now());
        $etag = \App\Support\HttpCache::makeEtag(['points_history',$user->id,count($history['data']),$lastModified?->timestamp]);
        if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, optional($lastModified)->copy())) {
            return $resp304;
        }

        // Return a cleaner JSON response with the paginated history, including the total stats.
        return \App\Support\HttpCache::withValidators(
            response()->json([
                'success' => true,
                'data' => $history
            ]),
            $etag,
            optional($lastModified)->copy(),
            60
        );
    }

    /**
     * تحويل النقاط إلى محفظة
     */
    public function convertToWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'points' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $points = $request->points;
        
        $minPoints = (int)get_setting('min_points_for_conversion', 100);
        if ($points < $minPoints) {
            return response()->json([
                'success' => false,
                'message' => "يجب أن يكون الحد الأدنى للنقاط للتحويل {$minPoints} نقاط"
            ], 400);
        }

        $current = $this->pointsService->getUserPoints($user);
        if ($current < $points) {
            return response()->json([
                'success' => false,
                'message' => 'النقاط غير كافية'
            ], 400);
        }

        $success = $this->pointsService->convertPointsToWallet($user, $points);

        if ($success) {
            $conversionRate = (float)get_setting('points_to_wallet_rate', 0.01);
            $walletAmount = $points * $conversionRate;

            return response()->json([
                'success' => true,
                'message' => "تم تحويل {$points} نقاط إلى {$walletAmount} ريال في المحفظة",
                'data' => [
                    'converted_points' => $points,
                    'wallet_amount' => $walletAmount,
                    'remaining_points' => $this->pointsService->getUserPoints($user)
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء التحويل'
        ], 500);
    }

    /**
     * عرض إعدادات النقاط
     */
    public function settings()
    {
        $settings = $this->pointsService->getPointsSettings();
        
        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }
}
