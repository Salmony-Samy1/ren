<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gifts\CreateGiftRequest;
use App\Http\Requests\Gifts\RespondGiftRequest;
use App\Http\Resources\GiftResource;
use App\Models\Gift;
use App\Services\GiftService;

class GiftController extends Controller
{
    public function __construct(private readonly GiftService $giftService)
    {
    }

    public function index()
    {
        $gift = $this->giftService->getGiftPackages();
        return response()->json(['success' => true, 'data' => $gift] );
    }

    public function offer(CreateGiftRequest $request)
    {
        $gift = $this->giftService->createOffer(auth()->user(), $request->validated());

        return format_response(true, __('gift.offer.created'), new GiftResource($gift->load(['sender:id,full_name,public_id', 'recipient:id,full_name,public_id', 'package','service:id,name'])));
    }

    public function accept(Gift $gift, RespondGiftRequest $request)
    {
        return response()->json(['success' => false, 'message' => 'Deprecated: Gifts are instant now'], 410);
    }

    public function reject(Gift $gift, RespondGiftRequest $request)
    {
        return response()->json(['success' => false, 'message' => 'Deprecated: Gifts are instant now'], 410);
    }

    public function listReceived()
    {
        $gifts = Gift::with(['sender:id,full_name,public_id', 'package','service:id,name'])
            ->where('recipient_id', auth()->id())
            ->orderByDesc('id')
            ->paginate(20);
        return format_response(true, __('Received gifts'), GiftResource::collection($gifts));
    }

    public function listSent()
    {
        $gifts = Gift::with(['recipient:id,full_name,public_id', 'package','service:id,name'])
            ->where('sender_id', auth()->id())
            ->orderByDesc('id')
            ->paginate(20);
        return format_response(true, __('Sent gifts'), GiftResource::collection($gifts));
    }
}

