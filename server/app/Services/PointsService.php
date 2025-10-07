<?php

namespace App\Services;

use App\Models\User;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

class PointsService
{
    /**
     * Award points for service booking via Points Ledger (customer side)
     */
    public function awardBookingPoints(Booking $booking): bool
    {
        try {
            DB::beginTransaction();

            $ledger = app(\App\Services\PointsLedgerService::class);
            $validityDays = (int) (get_setting('points_expiry_days') ?? 365);

            // Regular booking points
            $loyalty = (int) get_setting('loyalty_points', 10);
            if ($loyalty > 0) {
                $ledger->earn($booking->user, $loyalty, 'booking', ['booking_id' => $booking->id], $validityDays);
            }

            // First booking bonus if this is the first booking
            $userBookingsCount = (int) $booking->user->bookings()->count();
            if ($userBookingsCount === 1) {
                $first = (int) get_setting('first_booking_points', 50);
                if ($first > 0) {
                    $ledger->earn($booking->user, $first, 'first_booking', ['booking_id' => $booking->id], $validityDays);
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return false;
        }
    }

    /**
     * Award points for service booking to provider via Points Ledger
     */
    public function awardProviderBookingPoints(Booking $booking): bool
    {
        try {
            $ledger = app(\App\Services\PointsLedgerService::class);
            $validityDays = (int) (get_setting('points_expiry_days') ?? 365);
            $provider = $booking->service->user;
            $points = (int) get_setting('provider_loyalty_points', 10);
            if ($points > 0) {
                $ledger->earn($provider, $points, 'provider_booking', ['booking_id' => $booking->id], $validityDays);
            }
            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Award points for service creation to provider via Points Ledger
     */
    public function awardServiceCreationPoints(\App\Models\Service $service): bool
    {
        try {
            $ledger = app(\App\Services\PointsLedgerService::class);
            $validityDays = (int) (get_setting('points_expiry_days') ?? 365);
            $points = (int) get_setting('service_creation_points', 15);
            if ($points > 0) {
                $ledger->earn($service->user, $points, 'service_creation', ['service_id' => $service->id], $validityDays);
            }
            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Award points for service review via Points Ledger
     */
    public function awardReviewPoints(Review $review): bool
    {
        try {
            $ledger = app(\App\Services\PointsLedgerService::class);
            $validityDays = (int) (get_setting('points_expiry_days') ?? 365);
            $points = (int) get_setting('review_points', 5);
            if ($points > 0) {
                $ledger->earn($review->user, $points, 'review', ['review_id' => $review->id], $validityDays);
            }
            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Award referral bonus points via Points Ledger (to referrer)
     */
    public function awardReferralPoints(User $referrer, User $referred): bool
    {
        try {
            $ledger = app(\App\Services\PointsLedgerService::class);
            $validityDays = (int) (get_setting('points_expiry_days') ?? 365);
            $points = (int) get_setting('referral_points', 100);
            if ($points > 0) {
                $ledger->earn($referrer, $points, 'referral_bonus', ['referred_user_id' => $referred->id], $validityDays);
            }
            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Get user's total points (via ledger balance)
     */
    public function getUserPoints(User $user): int
    {
        return app(\App\Services\PointsLedgerService::class)->balance($user);
    }

    /**
     * Get user's points history (via ledger)
     */
    public function getUserPointsHistory(User $user, int $limit = 20)
    {
        return app(\App\Services\PointsLedgerService::class)->history($user, $limit);
    }

    /**
     * Convert points to wallet balance using ledger spend
     */
    public function convertPointsToWallet(User $user, int $points): bool
    {
        try {
            DB::beginTransaction();

            $conversionRate = (float) get_setting('points_to_wallet_rate', 0.01); // default: 100 points = 1 SAR
            $walletAmount = $points * $conversionRate;

            // Check balance via ledger
            $ledger = app(\App\Services\PointsLedgerService::class);
            $balance = $ledger->balance($user);
            if ($balance < $points) {
                throw new \Exception('Insufficient points');
            }

            // Spend points in ledger
            $spent = $ledger->spend($user, $points, 'convert_to_wallet');
            if ($spent !== $points) {
                throw new \Exception('Failed to spend the requested amount');
            }

            // Add to wallet
            $user->deposit($walletAmount, [
                'description' => "Converted {$points} points to wallet",
                'conversion_rate' => $conversionRate
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return false;
        }
    }

    /**
     * Get points settings
     */
    public function getPointsSettings(): array
    {
        return [
            'loyalty_points' => (int)get_setting('loyalty_points', 10),
            'first_booking_points' => (int)get_setting('first_booking_points', 50),
            'review_points' => (int)get_setting('review_points', 5),
            'referral_points' => (int)get_setting('referral_points', 100),
            'provider_loyalty_points' => (int)get_setting('provider_loyalty_points', 10),
            'service_creation_points' => (int)get_setting('service_creation_points', 15),
            'points_to_wallet_rate' => (float)get_setting('points_to_wallet_rate', 0.1),
            'min_points_for_conversion' => (int)get_setting('min_points_for_conversion', 100),
        ];
    }

    /**
     * Update points settings
     */
    public function updatePointsSettings(array $settings): bool
    {
        try {
            foreach ($settings as $key => $value) {
                set_setting($key, $value);
            }
            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Get user's points statistics
     */
    public function getUserPointsStats(User $user): array
    {
        $ledger = app(\App\Services\PointsLedgerService::class);
        $totalPoints = $ledger->balance($user);
        $conversionRate = (float) get_setting('points_to_wallet_rate', 0.1);
        $potentialWalletAmount = $totalPoints * $conversionRate;
        $minPointsForConversion = (int) get_setting('min_points_for_conversion', 100);
        $historyPage = $ledger->history($user, 1); // paginator
        $historyCount = method_exists($historyPage, 'total') ? (int) $historyPage->total() : 0;

        return [
            'total_points' => $totalPoints,
            'potential_wallet_amount' => $potentialWalletAmount,
            'can_convert' => $totalPoints >= $minPointsForConversion,
            'min_points_required' => $minPointsForConversion,
            'conversion_rate' => $conversionRate,
            'points_history_count' => $historyCount,
        ];
    }
}
