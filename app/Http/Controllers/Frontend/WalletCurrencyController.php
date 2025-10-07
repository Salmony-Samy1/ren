<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\SetCurrencyRequest;

class WalletCurrencyController extends Controller
{
    public function set(SetCurrencyRequest $request)
    {
        $user = auth('api')->user();
        $currency = strtoupper($request->validated()['currency']);
        $wallet = $user->wallet;
        if (!$wallet) { return format_response(false, 'Wallet not found', code: 404); }
        // Prevent frequent changes to avoid abuse; optional throttling handled by route
        // For security: disallow customers changing currency directly unless enabled
        if (!get_setting('wallet_allow_user_set_currency', false)) {
            return format_response(false, 'Changing wallet currency is disabled', code: 403);
        }
        $wallet->currency = $currency;
        $wallet->save();
        return format_response(true, __('Updated successfully'), ['currency' => $currency]);
    }
}

