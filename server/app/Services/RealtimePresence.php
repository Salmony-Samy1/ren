<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class RealtimePresence
{
    private const ZSET_USERS = 'presence:users';
    private const ZSET_PROVIDERS = 'presence:providers';
    private const ZSET_ADMINS = 'presence:admins';
    private const CACHE_COUNTS = 'presence:counts:last';

    /** Record a heartbeat for the current user and return the latest counts */
    public function heartbeat(User $user, int $ttlSeconds = 45): array
    {
        $now = time();
        $expireAt = $now + max(15, $ttlSeconds);
        $zset = match ($user->type) {
            'admin' => self::ZSET_ADMINS,
            'provider' => self::ZSET_PROVIDERS,
            default => self::ZSET_USERS,
        };

        // Add/update member expiry score
        Redis::zadd($zset, [$user->id => $expireAt]);

        // Only cleanup expired members occasionally (every 10th heartbeat) to reduce Redis load
        static $cleanupCounter = 0;
        $cleanupCounter++;
        if ($cleanupCounter % 10 === 0) {
            $this->cleanupExpired($now);
        }
        
        $counts = $this->counts($now);

        // Detect changes since last snapshot
        $last = Cache::get(self::CACHE_COUNTS, ['users' => 0, 'providers' => 0, 'admins' => 0]);
        $changed = ($counts['users'] !== $last['users'])
            || ($counts['providers'] !== $last['providers'])
            || ($counts['admins'] !== $last['admins']);

        if ($changed) {
            Cache::put(self::CACHE_COUNTS, $counts, 120);
        }

        return [$counts, $changed];
    }

    /** Return current counts. $now can be provided to avoid re-calling time() */
    public function counts(?int $now = null): array
    {
        $now = $now ?? time();
        // Ensure expired members are removed before counting
        $this->cleanupExpired($now);
        return [
            'users' => (int) Redis::zcard(self::ZSET_USERS),
            'providers' => (int) Redis::zcard(self::ZSET_PROVIDERS),
            'admins' => (int) Redis::zcard(self::ZSET_ADMINS),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    private function cleanupExpired(int $now): void
    {
        Redis::zremrangebyscore(self::ZSET_USERS, '-inf', $now);
        Redis::zremrangebyscore(self::ZSET_PROVIDERS, '-inf', $now);
        Redis::zremrangebyscore(self::ZSET_ADMINS, '-inf', $now);
    }
}

