<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\SavedCard;
use App\Services\Payments\TapGatewayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SavedCardsController extends Controller
{
    public function __construct(private readonly TapGatewayService $tapGatewayService)
    {
        $this->middleware('auth:api');
    }

    /**
     * عرض جميع البطاقات المحفوظة للمستخدم
     */
    public function index(): JsonResponse
    {
        $cards = SavedCard::where('user_id', auth()->id())
            ->where('expiry_year', '>=', date('Y'))
            ->orWhere(function($query) {
                $query->where('expiry_year', '=', date('Y'))
                      ->where('expiry_month', '>=', date('n'));
            })
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cards->map(function($card) {
                return [
                    'id' => $card->id,
                    'card_id' => $card->card_id,
                    'masked_number' => $card->masked_number,
                    'brand' => $card->brand,
                    'expiry_date' => $card->expiry_date,
                    'is_default' => $card->is_default,
                    'is_expired' => $card->isExpired(),
                ];
            })
        ]);
    }

    /**
     * إنشاء token من بطاقة محفوظة (MIT Flow)
     */
    public function createToken(Request $request): JsonResponse
    {
        $request->validate([
            'card_id' => 'required|exists:saved_cards,card_id',
        ]);

        $card = SavedCard::where('card_id', $request->card_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($card->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Card has expired'
            ], 400);
        }

        $result = $this->tapGatewayService->createTokenFromSavedCard(
            $card->customer_id,
            $card->card_id
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'token' => $result['token'],
                'message' => 'Token created successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to create token'
        ], 400);
    }

    /**
     * تعيين بطاقة كافتراضية
     */
    public function setDefault(Request $request): JsonResponse
    {
        $request->validate([
            'card_id' => 'required|exists:saved_cards,card_id',
        ]);

        $card = SavedCard::where('card_id', $request->card_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $card->setAsDefault();

        return response()->json([
            'success' => true,
            'message' => 'Default card updated successfully'
        ]);
    }

    /**
     * حذف بطاقة محفوظة
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'card_id' => 'required|exists:saved_cards,card_id',
        ]);

        $card = SavedCard::where('card_id', $request->card_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $card->delete();

        return response()->json([
            'success' => true,
            'message' => 'Card deleted successfully'
        ]);
    }

    /**
     * الحصول على إعدادات Tap للـ Frontend
     */
    public function getTapConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'public_key' => $this->tapGatewayService->getPublicKey(),
                'apple_pay_config' => $this->tapGatewayService->getApplePayConfig(),
            ]
        ]);
    }
}
