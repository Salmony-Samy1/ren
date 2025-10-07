<?php

namespace App\Services;

use App\Models\User;

class WalletService
{
    public function __construct()
    {
    }

    public function getBalance(User $user)
    {
        return (double)$user->balance;
    }

    public function deposit(User $user, $amount)
    {
        $user->deposit($amount);
        return $this->getBalance($user);
    }

    public function transactions(User $user)
    {
        return $user->walletTransactions;
    }

    public function withdraw(User $user, $amount, $message = '')
    {
        $user->withdraw($amount, ['message' => $message]);
        return $this->getBalance($user);
    }

    public function transfer(User $from, User $to, $amount, $message = '', ?string $currency = null)
    {
        $fromCur = optional($from->wallet)->currency ?? get_setting('default_wallet_currency','SAR');
        $toCur = optional($to->wallet)->currency ?? get_setting('default_wallet_currency','SAR');
        $amountFrom = (float) $amount;
        $amountTo = (float) $amount;
        if ($currency && strtoupper($currency) !== strtoupper($toCur)) {
            $converted = app(\App\Services\ExchangeRateService::class)->convert($amountFrom, $currency, $toCur);
            if ($converted === null) {
                abort(400, 'Exchange rate not configured');
            }
            $amountTo = $converted;
        } elseif (strtoupper($fromCur) !== strtoupper($toCur)) {
            $converted = app(\App\Services\ExchangeRateService::class)->convert($amountFrom, $fromCur, $toCur);
            if ($converted === null) {
                abort(400, 'Exchange rate not configured');
            }
            $amountTo = $converted;
        }

        $from->withdraw($amountFrom, [ 'message' => $message, 'currency' => $currency ?: $fromCur, 'amount_counter_currency' => $amountTo, 'counter_currency' => $toCur ]);
        $to->deposit($amountTo, [ 'message' => $message, 'currency' => $toCur, 'amount_counter_currency' => $amountFrom, 'counter_currency' => $currency ?: $fromCur ]);
        return $this->getBalance($from);
    }
}
