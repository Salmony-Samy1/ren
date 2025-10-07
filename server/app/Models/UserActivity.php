<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'platform',
        'metadata',
        'status',
        'activity_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * تسجيل نشاط جديد للمستخدم
     */
    public static function log($userId, $action, $description = null, $metadata = [], $status = 'success')
    {
        $request = request();
        
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'platform' => self::detectPlatform($request?->userAgent()),
            'metadata' => $metadata,
            'status' => $status,
            'activity_at' => now(),
        ]);
    }

    /**
     * تحديد المنصة بناءً على User Agent
     */
    private static function detectPlatform($userAgent)
    {
        if (!$userAgent) return 'api';
        
        $userAgent = strtolower($userAgent);
        
        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android') || str_contains($userAgent, 'iphone')) {
            return 'mobile';
        } elseif (str_contains($userAgent, 'postman') || str_contains($userAgent, 'curl') || str_contains($userAgent, 'symfony')) {
            return 'api';
        } elseif (str_contains($userAgent, 'mozilla') || str_contains($userAgent, 'chrome') || str_contains($userAgent, 'safari')) {
            return 'web';
        } else {
            return 'unknown';
        }
    }
}
