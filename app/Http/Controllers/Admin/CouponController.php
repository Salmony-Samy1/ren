<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $q = Coupon::query();
        if ($search = $request->query('search')) {
            $q->where('code', 'like', "%$search%");
        }
        return response()->json($q->orderByDesc('id')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'type' => 'required|in:percent,fixed',
            'amount' => 'required|numeric|min:0',
            'min_total' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'status' => 'required|in:active,inactive',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'meta' => 'nullable|array',
        ]);
        $coupon = Coupon::create($data);
        return response()->json($coupon, 201);
    }

    public function show(Coupon $coupon)
    {
        return response()->json($coupon->load('redemptions'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $request->validate([
            'type' => 'sometimes|in:percent,fixed',
            'amount' => 'sometimes|numeric|min:0',
            'min_total' => 'sometimes|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'status' => 'sometimes|in:active,inactive',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'meta' => 'nullable|array',
        ]);
        $coupon->update($data);
        return response()->json($coupon);
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

