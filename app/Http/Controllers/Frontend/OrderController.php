<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RestaurantMenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api','phone.verified']);
    }

    public function show(Order $order)
    {
        $this->authorizeOrder($order);
        return response()->json(['success' => true, 'data' => $order->load('items')]);
    }

    public function createDraft(Request $request)
    {
        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
        ]);
        $order = Order::create([
            'user_id' => auth()->id(),
            'status' => 'draft',
            'subtotal' => 0,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => 0,
            'payable_total' => 0,
            'coupon_code' => null,
            'coupon_discount' => 0,
            'points_used' => 0,
            'points_value' => 0,
            'idempotency_key' => $request->header('Idempotency-Key'),
            'meta' => ['service_id' => $data['service_id']],
        ]);
        return response()->json(['success' => true, 'data' => $order], 201);
    }

    public function upsertItem(Request $request, Order $order)
    {
        $this->authorizeOrder($order);
        $data = $request->validate([
            'menu_item_id' => 'required|exists:restaurant_menu_items,id',
            'quantity' => 'required|integer|min:1',
            'extras' => 'sometimes|array',
        ]);
        $menuItem = RestaurantMenuItem::findOrFail($data['menu_item_id']);
        // Ensure item belongs to the same service of the order (stored in meta)
        $serviceId = $order->meta['service_id'] ?? null;
        if ($serviceId && (int)$serviceId !== (int)$menuItem->restaurant->service_id) {
            abort(422, 'Menu item does not belong to the same service');
        }
        return DB::transaction(function () use ($order, $menuItem, $data) {
            $item = $order->items()->updateOrCreate(
                ['menu_item_id' => $menuItem->id],
                [
                    'quantity' => $data['quantity'],
                    'extras' => $data['extras'] ?? null,
                    'unit_price' => $menuItem->price,
                    'line_total' => $menuItem->price * $data['quantity'],
                ]
            );
            $this->recalculateTotals($order);
            return response()->json(['success' => true, 'data' => $order->fresh('items')]);
        });
    }

    public function removeItem(Order $order, int $orderItemId)
    {
        $this->authorizeOrder($order);
        $item = $order->items()->where('id', $orderItemId)->firstOrFail();
        $item->delete();
        $this->recalculateTotals($order);
        return response()->json(['success' => true, 'data' => $order->fresh('items')]);
    }

    public function applyCoupon(Request $request, Order $order)
    {
        $this->authorizeOrder($order);
        $request->validate(['coupon_code' => 'required|string']);
        // Integrate with existing coupon service if desired
        $order->update(['coupon_code' => $request->coupon_code]);
        $this->recalculateTotals($order);
        return response()->json(['success' => true, 'data' => $order->fresh('items')]);
    }

    private function recalculateTotals(Order $order): void
    {
        $subtotal = (float) $order->items()->sum('line_total');
        $taxRate = (float) get_setting('tax_rate', 15);
        $taxTotal = round(($subtotal * $taxRate) / 100, 2);
        $discount = 0; // coupon/points can be applied here
        $grand = max(0, round($subtotal + $taxTotal - $discount, 2));
        $order->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discount,
            'grand_total' => $grand,
            'payable_total' => $grand,
        ]);
    }

    private function authorizeOrder(Order $order): void
    {
        if ($order->user_id !== auth()->id()) { abort(403); }
    }
}

