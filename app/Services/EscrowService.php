<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Booking;

class EscrowService
{
    public function __construct()
    {
    }

    public function getEscrowUser(): User
    {
        $escrowUserId = (int) get_setting('escrow_system_user_id', 0);
        if ($escrowUserId <= 0) {
            abort(500, 'Escrow system user not configured');
        }
        return User::findOrFail($escrowUserId);
    }

    public function holdFundsNetForBooking(Booking $booking, PaymentTransaction $tx): void
    {
        DB::transaction(function () use ($booking, $tx) {
            $escrow = $this->getEscrowUser();
            $payer = $tx->user()->lockForUpdate()->first();

            // Compute commission splits using CommissionService
            $commissionService = app(\App\Services\CommissionService::class);
            $commissionData = $commissionService->calculateCommission($booking);
            $providerAmount = (float) ($commissionData['provider_amount'] ?? 0);
            $platformAmount = (float) ($commissionData['platform_amount'] ?? 0);

            if ($tx->payment_method === 'wallet') {
                // Funds were captured to a clearing account in PaymentService.
                // Move provider net from clearing -> escrow; move platform cut from clearing -> admin wallet.
                $admin = \App\Models\User::where('type', 'admin')->first();
                $clearingUserId = (int) get_setting('wallet_clearing_user_id');
                if ($clearingUserId <= 0) {
                    // Fallback: use escrow account itself as clearing when not configured (test env)
                    $clearingUserId = (int) get_setting('escrow_system_user_id');
                }
                $clearing = $clearingUserId > 0 ? \App\Models\User::find($clearingUserId) : null;
                if (!$clearing) {
                    abort(500, 'Wallet clearing account not configured');
                }
                // If clearing is same as escrow, funds are already at escrow from PaymentService fallback
                if ($providerAmount > 0 && $clearing->id !== $escrow->id) {
                    $clearing->transfer($escrow, $providerAmount, [
                        'description' => 'Hold provider net in escrow for booking #' . $booking->id,
                        'payment_transaction_id' => $tx->id,
                        'booking_id' => $booking->id,
                    ]);
                }
                if ($admin && $platformAmount > 0) {
                    $clearing->transfer($admin, $platformAmount, [
                        'description' => 'Platform commission for booking #' . $booking->id,
                        'payment_transaction_id' => $tx->id,
                        'booking_id' => $booking->id,
                    ]);
                }
            } else {
                // External gateway: deposit incoming funds to escrow/admin split to reflect gateway capture
                if ($providerAmount > 0) {
                    $escrow->deposit($providerAmount, [
                        'description' => 'Escrow deposit (provider net) for booking #' . $booking->id,
                        'payment_transaction_id' => $tx->id,
                        'booking_id' => $booking->id,
                        'source' => 'external_gateway',
                    ]);
                }
                if ($platformAmount > 0) {
                    $admin = \App\Models\User::where('type', 'admin')->first();
                    if ($admin) {
                        $admin->deposit($platformAmount, [
                            'description' => 'Platform commission for booking #' . $booking->id,
                            'payment_transaction_id' => $tx->id,
                            'booking_id' => $booking->id,
                        ]);
                    }
                }
            }

            $tx->update([
                'settlement_status' => 'held',
                'held_amount' => $providerAmount,
                'notes' => json_encode([
                    'points_used' => (int) ($booking->points_used ?? 0),
                    'points_value' => (float) ($booking->points_value ?? 0.0),
                ])
            ]);

            // Persist commission snapshot on invoice if present (order-level)
            if ($booking->order_id) {
                $inv = \App\Models\Invoice::where('order_id', $booking->order_id)->first();
                if ($inv) {
                    $inv->update([
                        'commission_amount' => $commissionData['total_commission'] ?? 0,
                        'provider_amount' => $providerAmount,
                        'platform_amount' => $platformAmount,
                    ]);
                }
            }
        });
    }

    public function releaseToProvider(PaymentTransaction $tx): void
    {
        DB::transaction(function () use ($tx) {
            $booking = $tx->booking()->with('service.user')->firstOrFail();
            $provider = $booking->service->user;
            $escrow = $this->getEscrowUser();

            // Transfer from escrow to provider
            $escrow->transfer($provider, (float) $tx->held_amount, [
                'description' => 'Payout (provider net) for booking #' . $booking->id,
                'payment_transaction_id' => $tx->id,
            ]);

            $tx->update([
                'settlement_status' => 'released',
                'released_at' => now(),
            ]);
        });
    }

    public function refundToCustomer(PaymentTransaction $tx): void
    {
        DB::transaction(function () use ($tx) {
            $booking = $tx->booking()->with('user')->firstOrFail();
            $customer = $booking->user;
            $escrow = $this->getEscrowUser();

            // Transfer from escrow back to customer
            $escrow->transfer($customer, (float) $tx->held_amount, [
                'description' => 'Refund (provider net) for booking #' . $booking->id,
                'payment_transaction_id' => $tx->id,
            ]);

            $tx->update([
                'settlement_status' => 'refunded',
                'refunded_at' => now(),
            ]);
        });
    }

    public function partialSettle(PaymentTransaction $tx, float $providerAmount, float $customerAmount, ?string $remarks = null): void
    {
        DB::transaction(function () use ($tx, $providerAmount, $customerAmount, $remarks) {
            $booking = $tx->booking()->with('service.user', 'user')->firstOrFail();
            $escrow = $this->getEscrowUser();
            $provider = $booking->service->user;
            $customer = $booking->user;

            $held = (float) $tx->held_amount;
            if (round($providerAmount + $customerAmount, 2) !== round($held, 2)) {
                abort(422, 'Provider + customer amounts must equal held amount');
            }

            if ($providerAmount > 0) {
                $escrow->transfer($provider, $providerAmount, [
                    'description' => 'Partial payout for booking #' . $booking->id,
                    'payment_transaction_id' => $tx->id,
                ]);
            }
            if ($customerAmount > 0) {
                $escrow->transfer($customer, $customerAmount, [
                    'description' => 'Partial refund for booking #' . $booking->id,
                    'payment_transaction_id' => $tx->id,
                ]);
            }

            $tx->update([
                'settlement_status' => 'partially_released',
                'released_at' => now(),
                'admin_remarks' => $remarks,
            ]);
        });
    }
}

