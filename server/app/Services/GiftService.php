<?php

namespace App\Services;

use App\Models\Gift;
use App\Models\GiftPackage;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GiftService
{
    public function __construct()
    {
    }

    public function getGiftPackages(): array
    {
        return GiftPackage::where('active', true)->get()->toArray();
    }

    public function createOffer(User $sender, array $data): Gift
    {
        return DB::transaction(function () use ($sender, $data) {
            $recipient = User::where('public_id', $data['recipient_public_id'])->firstOrFail();

            $amountService = match ($data['type']) {
                'package' => (float) GiftPackage::where('id', $data['gift_package_id'])->where('active', true)->value('amount'),
                'direct' => (float) $data['amount'],
                'voucher' => (float) (isset($data['amount']) ? $data['amount'] : (Service::where('id', $data['service_id'])->value('price_amount') ?? 0)),
            };

            if ($amountService <= 0) {
                abort(422, 'Invalid gift amount');
            }

            // Currency conversion between sender and recipient wallets (admin-defined rates)
            $senderCurrency = strtoupper(optional($sender->wallet)->currency ?? '');
            if ($senderCurrency === '' || $senderCurrency === 'DEFAULT') {
                $senderCurrency = (string) get_setting('default_wallet_currency','SAR');
            }
            $recipientCurrency = strtoupper(optional($recipient->wallet)->currency ?? '');
            if ($recipientCurrency === '' || $recipientCurrency === 'DEFAULT') {
                $recipientCurrency = (string) get_setting('default_wallet_currency','SAR');
            }
            // Sender is charged in senderCurrency; amountService is considered in senderCurrency for direct/package
            $amountWithdraw = $amountService; // sender's currency
            $amountDeposit = $amountService;  // recipient's currency (after conversion if needed)
            if (strtoupper($senderCurrency) !== strtoupper($recipientCurrency)) {
                $converted = app(\App\Services\ExchangeRateService::class)->convert($amountService, $senderCurrency, $recipientCurrency);
                if ($converted === null) {
                    abort(400, 'Exchange rate not configured');
                }
                $amountDeposit = $converted;
            }

            // Verify sender sufficient funds in sender currency
            if ($sender->balance < $amountWithdraw) {
                return response()->json([
                    'success'    => false,
                    'message'    => __('Insufficient wallet balance'),
                    'error' => 'INSUFFICIENT_FUNDS'
                ], 400);
            }

            // Create gift upfront as accepted (instant flow)
            $gift = Gift::create([
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'type' => $data['type'],
                'amount' => $amountService,
                'gift_package_id' => $data['type'] === 'package' ? $data['gift_package_id'] : null,
                'service_id' => $data['type'] === 'voucher' ? ($data['service_id'] ?? null) : null,
                'message' => $data['message'] ?? null,
                'status' => 'accepted',
                'accepted_at' => now(),
                'sender_currency' => $senderCurrency,
                'recipient_currency' => $recipientCurrency,
                'amount_recipient_currency' => $amountDeposit,
            ]);

            // Transfer funds immediately
            $sender->withdraw($amountWithdraw, [
                'description' => 'Gift sent to '.$recipient->id,
                'gift_id' => $gift->id,
                'currency' => $senderCurrency,
                'counter_currency' => $recipientCurrency,
                'amount_counter_currency' => $amountDeposit,
            ]);

            $recipient->deposit($amountDeposit, [
                'description' => 'Gift received from '.$sender->id,
                'gift_id' => $gift->id,
                'currency' => $recipientCurrency,
                'counter_currency' => $senderCurrency,
                'amount_counter_currency' => $amountWithdraw,
            ]);

            event(new \App\Events\GiftAccepted($gift));

            return $gift;
        });
    }

    public function accept(Gift $gift, User $recipient): Gift
    {
        abort(410, 'Gifts are now instant; accept endpoint is deprecated');
    }

    public function reject(Gift $gift, User $recipient): Gift
    {
        abort(410, 'Gifts are now instant; reject endpoint is deprecated');
    }
}

