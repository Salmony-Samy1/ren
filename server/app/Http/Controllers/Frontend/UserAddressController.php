<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserAddress\StoreUserAddressRequest;
use App\Http\Requests\UserAddress\UpdateUserAddressRequest;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class UserAddressController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api','phone.verified']);
    }

    public function index()
    {
        $addresses = auth()->user()->addresses()->latest()->get();
        return response()->json(['success' => true, 'data' => $addresses]);
    }

    public function store(StoreUserAddressRequest $request)
    {
        $user = auth()->user();
        $data = $request->validated();
        return DB::transaction(function () use ($user, $data) {
            if (($data['is_default'] ?? false) === true) {
                UserAddress::where('user_id', $user->id)->update(['is_default' => false]);
            }
            $addr = UserAddress::create(array_merge($data, ['user_id' => $user->id]));
            return response()->json(['success' => true, 'data' => $addr], 201);
        });
    }
    public function show(UserAddress $address)
    {
        $user = auth()->user();
        if ((int)$address->user_id !== (int)$user->id) { abort(403); }
        return response()->json(['success' => true, 'data' => $address]);
    }


    public function update(UpdateUserAddressRequest $request, UserAddress $address)
    {
        $user = auth()->user();
        if ((int)$address->user_id !== (int)$user->id) { abort(403); }
        $data = $request->validated();
        return DB::transaction(function () use ($address, $data, $user) {
            if (array_key_exists('is_default', $data) && $data['is_default'] === true) {
                UserAddress::where('user_id', $user->id)->update(['is_default' => false]);
            }
            $address->update($data);
            return response()->json(['success' => true, 'data' => $address]);
        });
    }

    public function destroy(UserAddress $address)
    {
        $user = auth()->user();
        if ((int)$address->user_id !== (int)$user->id) { abort(403); }
        $address->delete();
        return response()->json(['success' => true]);
    }

    public function setDefault(UserAddress $address)
    {
        $user = auth()->user();
        if ((int)$address->user_id !== (int)$user->id) { abort(403); }
        DB::transaction(function () use ($user, $address) {
            UserAddress::where('user_id', $user->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });
        return response()->json(['success' => true, 'data' => $address->fresh()]);
    }
}

