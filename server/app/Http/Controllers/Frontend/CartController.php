<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\UpsertCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\BookingService;
use App\Services\CartPricingService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartPricingService $pricing, private readonly BookingService $booking)
    {
        $this->middleware('auth:api');
    }

    public function setAddress(\App\Http\Requests\Cart\SetCartAddressRequest $request)
    {
        $cart = $this->getOpenCart();
        $address = \App\Models\UserAddress::findOrFail($request->validated()['address_id']);
        if ($address->user_id !== auth()->id()) { abort(403); }
        $cart->delivery_address_id = $address->id;
        $cart->save();
        return response()->json(['success' => true, 'data' => $cart->fresh()->load(['items.service','user.addresses'])]);
    }

    protected function getOpenCart(): Cart
    {
        $user = auth()->user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id, 'status' => 'open']);
        $cart->load('items.service');
        return $cart;
    }

    public function index()
    {
        $cart = $this->getOpenCart();
        $this->pricing->recomputeCart($cart);
        return response()->json(['success' => true, 'data' => $cart->load(['items.service:id,name,price_amount,category_id','user.addresses'])]);
    }

    public function add(UpsertCartItemRequest $request)
    {
        $cart = $this->getOpenCart();
        $data = $request->validated();

        // optional availability check per-item
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $service = \App\Models\Service::findOrFail($data['service_id']);
            if (!$this->booking->isServiceAvailable($service, $data['start_date'], $data['end_date'])) {
                return response()->json(['success' => false, 'message' => 'Service not available for selected dates'], 422);
            }
        }

        $item = new CartItem($data);
        $item->cart_id = $cart->id;
        $item->quantity = $data['quantity'] ?? 1;
        $item->save();

        $this->pricing->recomputeCart($cart->fresh('items.service'));
        return response()->json(['success' => true, 'data' => $cart->load(['items.service:id,name,price_amount,category_id','user.addresses'])]);
    }

    public function updateItem(CartItem $item, UpsertCartItemRequest $request)
    {
        $cart = $this->getOpenCart();
        if ($item->cart_id !== $cart->id) { abort(403); }
        $data = $request->validated();

        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $service = \App\Models\Service::findOrFail($data['service_id']);
            if (!$this->booking->isServiceAvailable($service, $data['start_date'], $data['end_date'])) {
                return response()->json(['success' => false, 'message' => 'Service not available for selected dates'], 422);
            }
        }

        $item->fill($data);
        $item->save();
        $this->pricing->recomputeCart($cart->fresh('items.service'));
        return response()->json(['success' => true, 'data' => $cart->load(['items.service:id,name,price_amount,category_id','user.addresses'])]);
    }

    public function removeItem(CartItem $item)
    {
        $cart = $this->getOpenCart();
        if ($item->cart_id !== $cart->id) { abort(403); }
        $item->delete();
        $this->pricing->recomputeCart($cart->fresh('items.service'));
        return response()->json(['success' => true, 'data' => $cart->load(['items.service:id,name,price_amount,category_id','user.addresses'])]);
    }
}

