<?php

namespace App\Services;

use App\Models\PointsLedger;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PointsLedgerService
{
    /**
     * Earn points with optional expiry.
     */
    public function earn(User $user, int $points, ?string $source = null, ?array $meta = null, ?int $validityDays = 365): PointsLedger
    {
        if ($points <= 0) {
            throw new \InvalidArgumentException('Points must be positive');
        }

        $expiresAt = $validityDays ? CarbonImmutable::now()->addDays($validityDays) : null;

        return PointsLedger::create([
            'user_id' => $user->id,
            'type' => 'earn',
            'points' => $points,
            'source' => $source,
            'expires_at' => $expiresAt,
            'meta' => $meta,
        ]);
    }

    /**
     * Spend points FIFO against non-expired earnings. Returns how many points were actually spent.
     */
    public function spend(User $user, int $points, ?string $source = null, ?array $meta = null): int
    {
        if ($points <= 0) {
            throw new \InvalidArgumentException('Points must be positive');
        }

        return DB::transaction(function () use ($user, $points, $source, $meta) {
            $remaining = $points;

            // Lock relevant rows to avoid race conditions
            $earnings = PointsLedger::where('user_id', $user->id)
                ->where('type', 'earn')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->orderBy('expires_at', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $spent = 0;
            foreach ($earnings as $earn) {
                if ($remaining <= 0) break;

                $alreadySpent = PointsLedger::where('user_id', $user->id)
                    ->where('type', 'spend')
                    ->where('meta->earn_id', $earn->id)
                    ->sum('points');

                $available = $earn->points - $alreadySpent;
                if ($available <= 0) continue;

                $use = min($available, $remaining);
                PointsLedger::create([
                    'user_id' => $user->id,
                    'type' => 'spend',
                    'points' => $use,
                    'source' => $source,
                    'meta' => array_merge(['earn_id' => $earn->id], $meta ?? []),
                ]);

                $remaining -= $use;
                $spent += $use;
            }

            return $spent;
        });
    }

    /**
     * Expire any earnings that have passed their expiry date. Returns number of expired points.
     */
    public function expireDue(User $user): int
    {
        return DB::transaction(function () use ($user) {
            $due = PointsLedger::where('user_id', $user->id)
                ->where('type', 'earn')
                ->where('expires_at', '<=', now())
                ->lockForUpdate()
                ->get();

            $expired = 0;
            foreach ($due as $earn) {
                $alreadySpent = PointsLedger::where('user_id', $user->id)
                    ->where('type', 'spend')
                    ->where('meta->earn_id', $earn->id)
                    ->sum('points');

                $remain = max(0, $earn->points - $alreadySpent);
                if ($remain > 0) {
                    PointsLedger::create([
                        'user_id' => $user->id,
                        'type' => 'expire',
                        'points' => $remain,
                        'source' => 'expiry',
                        'meta' => ['earn_id' => $earn->id],
                    ]);
                    $expired += $remain;
                }
            }

            return $expired;
        });
    }

    /**
     * Compute current balance = sum(earn) - sum(spend) - sum(expire) for non-expired earnings.
     */
    public function balance(User $user): int
    {
        $earned = PointsLedger::where('user_id', $user->id)
            ->where('type', 'earn')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->sum('points');

        $spent = PointsLedger::where('user_id', $user->id)->where('type', 'spend')->sum('points');
        $expired = PointsLedger::where('user_id', $user->id)->where('type', 'expire')->sum('points');

        return max(0, $earned - $spent - $expired);
    }

    public function history(User $user, int $limit = 20)
    {
        return PointsLedger::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($limit);
    }
}

